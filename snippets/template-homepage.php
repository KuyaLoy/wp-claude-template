<?php
/**
 * Template Name: Homepage
 *
 * Front page only. Rigid section order.
 * Each section is a separate ACF field group attached to the front page,
 * with fields prefixed by section name (e.g., home_hero_heading).
 *
 * Sections render in the order listed in $home_sections below.
 * Missing files are silently skipped — no PHP warnings on a fresh project.
 * Add new homepage sections by:
 *   1. Building static section: "@briefs/home-<section>.md <figma-url>"
 *   2. Adding its slug to $home_sections in the right position
 *   3. Running "/make-dynamic home-<section>" to wire ACF
 */

get_header();

$home_sections = [
    'home-hero',
    'home-services',
    'home-projects',
    'home-about',
    'home-testimonials',
    'home-contact',
];
?>

<main id="primary" class="site-main">

    <?php
    foreach ($home_sections as $slug) {
        $part = locate_template('templates/parts/section-' . $slug . '.php', false, false);
        if ($part) {
            include $part;
        } elseif (current_user_can('manage_options')) {
            echo '<!-- Homepage section pending: templates/parts/section-' . esc_html($slug) . '.php -->';
        }
    }
    ?>

</main>

<?php
get_footer();
