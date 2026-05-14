<?php
/**
 * Template Name: Homepage
 *
 * Front page only. Rigid section order.
 * Each section is a separate ACF field group attached to the front page,
 * with fields prefixed by section name (e.g., home_hero_heading).
 *
 * Add new homepage sections by:
 *   1. Building static section: "@briefs/home-<section>.md <figma-url>"
 *   2. Including it below in the right order
 *   3. Running "/make-dynamic home-<section>" to wire ACF
 */

get_header();
?>

<main id="primary" class="site-main">

    <?php include get_stylesheet_directory() . '/templates/parts/section-home-hero.php'; ?>
    <?php include get_stylesheet_directory() . '/templates/parts/section-home-services.php'; ?>
    <?php include get_stylesheet_directory() . '/templates/parts/section-home-projects.php'; ?>
    <?php include get_stylesheet_directory() . '/templates/parts/section-home-about.php'; ?>
    <?php include get_stylesheet_directory() . '/templates/parts/section-home-testimonials.php'; ?>
    <?php include get_stylesheet_directory() . '/templates/parts/section-home-contact.php'; ?>

</main>

<?php
get_footer();
