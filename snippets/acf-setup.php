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

/* -----------------------------------------------------------
 *  Phone validation
 *
 *  Triggers only on ACF text fields named "phone", "mobile", or "telephone".
 *  The regex + error message below are filled in by /setup-claude based on
 *  the project's country (default AU). To switch country later, edit the
 *  lines between PHONE_REGEX_START and PHONE_REGEX_END.
 * -------------------------------------------------------- */
add_filter('acf/validate_value/type=text', function ($valid, $value, $field) {
    if ($valid !== true) return $valid;

    if (!preg_match('/^(phone|mobile|telephone)$/i', $field['name'])) return $valid;

    $clean = preg_replace('/[\s\-\(\)]/', '', $value);

    /* PHONE_REGEX_START — replaced by /setup-claude based on country choice */
    // AU: 04xx mobile, 02/03/07/08 landline, 1300/1800 specials
    $pattern  = '/^(0[2-9]\d{8}|1[38]00\d{6})$/';
    $message  = 'Please enter a valid Australian phone number.';
    /* PHONE_REGEX_END */

    if ($value !== '' && !preg_match($pattern, $clean)) {
        return $message;
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
