<?php
/**
 * ACF setup
 * - Local JSON save/load points to <theme>/acf-json/
 * - Phone validation hook
 * - Front-page edit shortcut in admin
 */

/* Local JSON: save destination */
add_filter('acf/settings/save_json', function ($path) {
    return get_stylesheet_directory() . '/acf-json';
});

/* Local JSON: load source (theme acf-json + plugin defaults if any) */
add_filter('acf/settings/load_json', function ($paths) {
    $paths[] = get_stylesheet_directory() . '/acf-json';
    return $paths;
});

/* Phone validation — adjust regex per project (see workspace README.md). */
add_filter('acf/validate_value/type=text', function ($valid, $value, $field) {
    if ($valid !== true) return $valid;

    // Trigger only on fields named "phone" or "mobile"
    if (!preg_match('/^(phone|mobile|telephone)$/i', $field['name'])) return $valid;

    // Australian default: 04xx xxx xxx, 02/03/07/08 landlines, 1300/1800 specials
    $clean = preg_replace('/[\s\-\(\)]/', '', $value);
    $au    = '/^(0[2-9]\d{8}|1[38]00\d{6})$/';

    if ($value !== '' && !preg_match($au, $clean)) {
        return 'Please enter a valid Australian phone number.';
    }

    return $valid;
}, 10, 3);

/* Add a "Front Page" shortcut to the admin Pages menu */
add_action('admin_menu', function () {
    global $submenu;
    $front_id = (int) get_option('page_on_front');
    if (!$front_id) return;

    $submenu['edit.php?post_type=page'][501] = [
        'Front Page',
        'manage_options',
        get_edit_post_link($front_id),
    ];
});
