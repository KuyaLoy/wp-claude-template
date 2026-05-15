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
 * Auto-load any active one-shot data seeders at inc/seed-*.php
 *
 * The seeder pattern: each seeder is a self-contained file that hooks
 * template_redirect, checks ?aiims_seed=<slug>, populates data, then
 * unlink()s itself. After it self-deletes, this glob no longer picks it up.
 *
 * See snippets/seeder-template.php for the boilerplate and skills/seed-data/
 * for the workflow Claude uses to write them.
 */
foreach (glob(__DIR__ . '/seed-*.php') as $seeder) {
    require_once $seeder;
}

// Future additions go here, e.g.:
// require_once __DIR__ . '/cpt-projects.php';
// require_once __DIR__ . '/cpt-team.php';
// require_once __DIR__ . '/shortcodes.php';
// require_once __DIR__ . '/admin-tweaks.php';
