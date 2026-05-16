<?php
/**
 * Custom theme functions for this project.
 *
 * All project-specific PHP additions go through this file.
 * Add new requires below as the codebase grows.
 *
 * functions.php should NOT be edited beyond the single require to this file.
 * Keeps the underscoretw upstream clean and our customizations isolated here.
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/acf-setup.php';

/**
 * Auto-load any active one-shot loaders.
 *
 * Two pattern families share the same auto-load:
 * - inc/seed-<slug>.php   → populates ACF fields via update_field()
 * - inc/upload-<slug>.php → uploads static images to WP media library
 *
 * Both hook template_redirect, check ?aiims_<action>=<slug>, are admin-gated,
 * and unlink() themselves after success. After a file self-deletes, this glob
 * no longer picks it up — zero leftover endpoints in production.
 *
 * See snippets/seeder-template.php and snippets/uploader-template.php for
 * boilerplates; skills/seed-data/ and skills/upload-images/ for the workflows
 * Claude uses to write them.
 */
foreach (glob(__DIR__ . '/seed-*.php') as $loader) {
    require_once $loader;
}
foreach (glob(__DIR__ . '/upload-*.php') as $loader) {
    require_once $loader;
}

// Future additions go here, e.g.:
// require_once __DIR__ . '/cpt-projects.php';
// require_once __DIR__ . '/cpt-team.php';
// require_once __DIR__ . '/shortcodes.php';
// require_once __DIR__ . '/admin-tweaks.php';
