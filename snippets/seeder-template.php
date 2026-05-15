<?php
/**
 * Seeder template — copy to theme/inc/seed-<slug>.php and customize.
 *
 * One-shot self-deleting data seeder. The pattern:
 *
 *   1. Drop this file into theme/inc/ named seed-<slug>.php
 *   2. It auto-loads via custom-functions.php glob
 *   3. Hit /?aiims_seed=<slug> while logged in as admin
 *   4. The seeder runs, populates data, self-unlinks
 *   5. File is gone from disk; re-creatable from git if needed
 *
 * Why this pattern:
 * - Versioned (git tracks each seeder)
 * - Re-runnable (re-create the file, hit the URL again)
 * - Self-cleaning (no leftover endpoints in production)
 * - Admin-gated (manage_options required)
 * - Safe to leave in production until next deploy — they don't run for non-admins
 *
 * For a section: read the section's ACF field group to know which fields to populate,
 * then update_field() each one. Find the target post via get_page_by_path() or
 * a WP_Query.
 *
 * Replace the placeholders below:
 * - <slug>           → kebab-case slug (matches filename: seed-<slug>.php → ?aiims_seed=<slug>)
 * - <Section Name>   → human label for comments
 * - $page_slug       → the target page's slug (e.g. 'home', 'about')
 * - field_name + val → the ACF fields to populate
 */

add_action('template_redirect', function () {
    // Guard: must hit the exact URL parameter
    if (!isset($_GET['aiims_seed']) || $_GET['aiims_seed'] !== '<slug>') {
        return;
    }

    // Guard: admin-only
    if (!current_user_can('manage_options')) {
        wp_die('Forbidden — admin only.', '403', ['response' => 403]);
    }

    // ─── SEEDER WORK START ─────────────────────────────────────
    // Customize below for the section you're seeding.

    $page_slug = 'home';
    $page      = get_page_by_path($page_slug);
    if (!$page) {
        wp_die('Target page not found: ' . esc_html($page_slug));
    }
    $page_id = $page->ID;

    // Example: populate a homepage section's ACF fields.
    // For homepage section groups (Path B in make-section-dynamic),
    // field names are usually prefixed: home_hero_heading, home_hero_body, etc.
    update_field('home_hero_heading',    'Welcome to <Brand>', $page_id);
    update_field('home_hero_subheading', 'Short tagline below the heading.', $page_id);
    update_field('home_hero_body',       '<p>Body paragraph (wysiwyg field). Use HTML.</p>', $page_id);

    // Example: populate a link field (array of url/title/target).
    update_field('home_hero_cta', [
        'url'    => '#quote',
        'title'  => 'Get a Quote',
        'target' => '',
    ], $page_id);

    // Example: attach an existing media-library image to an image field.
    // The image must already be uploaded — use the attachment ID.
    // $attachment_id = 42;
    // update_field('home_hero_image', $attachment_id, $page_id);

    // Example: populate a repeater (array of rows, each row is an associative array).
    // update_field('home_services_cards', [
    //     ['title' => 'Service A', 'description' => '...', 'link' => ['url' => '/services/a', 'title' => 'Learn more', 'target' => '']],
    //     ['title' => 'Service B', 'description' => '...', 'link' => ['url' => '/services/b', 'title' => 'Learn more', 'target' => '']],
    // ], $page_id);

    // For Flexible Content (default-template sections), populate flexible_sections
    // with layout rows. Each row needs 'acf_fc_layout' to match the layout name:
    // update_field('flexible_sections', [
    //     ['acf_fc_layout' => 'hero', 'heading' => '...', 'body' => '...'],
    //     ['acf_fc_layout' => 'cards', 'cards' => [['title' => '...'], ['title' => '...']]],
    // ], $page_id);

    // ─── SEEDER WORK END ───────────────────────────────────────

    // Self-delete after success
    @unlink(__FILE__);

    // Show confirmation + redirect to the seeded page
    wp_die(
        '✓ Seeded <Section Name>. File self-deleted. <a href="' . esc_url(get_permalink($page_id)) . '">View page</a>',
        'Seeded ✓',
        ['response' => 200]
    );
});
