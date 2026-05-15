<?php
/**
 * Uploader template — copy to theme/inc/upload-<slug>.php and customize.
 *
 * One-shot self-deleting media-library uploader. The pattern:
 *
 *   1. Drop this file into theme/inc/ named upload-<slug>.php
 *   2. It auto-loads via custom-functions.php glob (same as seeders)
 *   3. Hit /?aiims_upload=<slug> while logged in as admin
 *   4. Every file in theme/assets/images/<slug>/ is uploaded to WP media library
 *   5. Attachment IDs are reported back; static files + this loader self-delete
 *
 * Replace the placeholders:
 * - <slug>   → kebab-case slug (matches filename + URL param)
 * - <Section Name> → human label for the confirmation message
 *
 * After success, use the reported attachment IDs in a /seed call. The static
 * folder at theme/assets/images/<slug>/ is removed because every image is
 * now in the WP media library.
 */

add_action('template_redirect', function () {
    // Guard: must hit exact URL parameter
    if (!isset($_GET['aiims_upload']) || $_GET['aiims_upload'] !== '<slug>') {
        return;
    }

    // Guard: admin-only
    if (!current_user_can('manage_options')) {
        wp_die('Forbidden — admin only.', '403', ['response' => 403]);
    }

    // Require core file APIs (only loaded in admin by default)
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $slug          = '<slug>';
    $source_folder = get_stylesheet_directory() . '/assets/images/' . $slug;

    if (!is_dir($source_folder)) {
        wp_die('No source folder at: ' . esc_html($source_folder));
    }

    $files = array_values(array_diff(scandir($source_folder), ['.', '..']));
    if (empty($files)) {
        wp_die('No files to upload in: ' . esc_html($source_folder));
    }

    $results = [];
    $errors  = [];

    foreach ($files as $filename) {
        $src = $source_folder . '/' . $filename;
        if (!is_file($src)) continue;

        // Build a fake $_FILES entry for wp_handle_sideload
        $tmp     = wp_tempnam($filename);
        @copy($src, $tmp);
        $type    = wp_check_filetype($filename);
        $sideload = [
            'name'     => $filename,
            'type'     => $type['type'] ?: 'application/octet-stream',
            'tmp_name' => $tmp,
            'error'    => 0,
            'size'     => filesize($src),
        ];

        $overrides = ['test_form' => false];
        $upload    = wp_handle_sideload($sideload, $overrides);

        if (isset($upload['error'])) {
            $errors[$filename] = $upload['error'];
            @unlink($tmp);
            continue;
        }

        // Build the attachment record
        $attachment_id = wp_insert_attachment([
            'guid'           => $upload['url'],
            'post_mime_type' => $upload['type'],
            'post_title'     => sanitize_title(pathinfo($filename, PATHINFO_FILENAME)),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ], $upload['file']);

        if (is_wp_error($attachment_id) || !$attachment_id) {
            $errors[$filename] = is_wp_error($attachment_id) ? $attachment_id->get_error_message() : 'wp_insert_attachment returned 0';
            continue;
        }

        // Generate metadata (resized sizes, image dimensions, etc.)
        $meta = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $meta);

        // Tag with source section for future querying
        update_post_meta($attachment_id, '_aiims_source_section', $slug);

        $results[$filename] = $attachment_id;
    }

    // If any uploads failed, stop here — keep source files for retry
    if (!empty($errors)) {
        $error_html = '<h2>⚠ Upload errors</h2><ul>';
        foreach ($errors as $name => $msg) {
            $error_html .= '<li><code>' . esc_html($name) . '</code>: ' . esc_html($msg) . '</li>';
        }
        $error_html .= '</ul><p>Source files NOT deleted. Loader file NOT deleted. Fix the errors and re-run.</p>';

        if (!empty($results)) {
            $error_html .= '<h3>Partial success — already uploaded:</h3><ul>';
            foreach ($results as $name => $id) {
                $error_html .= '<li><code>' . esc_html($name) . '</code> → attachment ID ' . (int) $id . '</li>';
            }
            $error_html .= '</ul><p>You may want to delete those partial uploads in WP Admin → Media before retrying.</p>';
        }

        wp_die($error_html, 'Upload errors', ['response' => 500]);
    }

    // All uploads succeeded — delete source files
    foreach ($files as $filename) {
        @unlink($source_folder . '/' . $filename);
    }
    @rmdir($source_folder);

    // Self-delete the loader
    @unlink(__FILE__);

    // Build success output
    $html = '<h2>✓ Uploaded ' . count($results) . ' images from <Section Name></h2>';
    $html .= '<ul>';
    foreach ($results as $name => $id) {
        $html .= '<li><code>' . esc_html($name) . '</code> → attachment ID <strong>' . (int) $id . '</strong></li>';
    }
    $html .= '</ul>';
    $html .= '<p>Static folder <code>theme/assets/images/' . esc_html($slug) . '/</code> deleted.</p>';
    $html .= '<p>Uploader file deleted.</p>';
    $html .= '<p><strong>Next:</strong> seed the section using these IDs, e.g.<br>';
    $html .= '<code>Seed ' . esc_html($slug) . ' with: image=' . esc_html(reset($results)) . ', ...</code></p>';

    wp_die($html, 'Uploaded ✓', ['response' => 200]);
});
