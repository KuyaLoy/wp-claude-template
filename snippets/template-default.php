<?php
/**
 * Template Name: Universal Page
 *
 * Drives every page that uses ACF Flexible Content sections.
 * Each layout in `flexible_sections` resolves to:
 *     templates/parts/section-{layout-name-with-dashes}.php
 *
 * Underscores in the ACF layout name are converted to dashes for the file.
 */

get_header();

if (have_posts()) :
    while (have_posts()) : the_post();

        if (have_rows('flexible_sections')) :
            while (have_rows('flexible_sections')) : the_row();

                $layout = str_replace('_', '-', get_row_layout());
                $part   = locate_template('templates/parts/section-' . $layout . '.php', false, false);

                if ($part) {
                    include $part;
                } else {
                    if (current_user_can('manage_options')) {
                        echo '<!-- Missing template: templates/parts/section-' . esc_html($layout) . '.php -->';
                    }
                }

            endwhile;
        endif;

    endwhile;
endif;

get_footer();
