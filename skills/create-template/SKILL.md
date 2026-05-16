---
name: create-template
description: Create a new WordPress template (page / archive / single / search / 404). Asks if it should be normal (rigid) or flexible (ACF Flexible Content). Generates the PHP file at the right path following WP template hierarchy. If flexible, also writes the ACF Local JSON field group with a flexible_sections field that drives section parts. Triggers on "/create-template", "@create-template", "make a new template", "I need a custom archive for projects", "make a single template for case-studies", "create a flexible page template called Landing".
---

# /create-template

Generic skill for spinning up new WordPress templates. Handles all flavors:

- **Page templates** → `theme/templates/template-{slug}.php` with `Template Name:` header
- **Archive templates** → `theme/archive-{cpt}.php`
- **Single templates** → `theme/single-{cpt}.php`
- **Search results** → `theme/search.php` (overrides default)
- **404** → `theme/404.php` (overrides default)
- **Taxonomy** → `theme/taxonomy-{tax}.php` or `theme/category.php` / `theme/tag.php`

Each can be **normal** (rigid layout) or **flexible** (ACF Flexible Content driver).

## The questions

Ask in a single message:

```
What kind of template?

1. Page template (custom layout for individual pages)
2. Archive template (post type listing page)
3. Single template (individual post detail page)
4. Search results template
5. 404 page template
6. Taxonomy template (category / tag / custom taxonomy)
```

Wait for answer. Then ask the follow-ups based on what was chosen:

### If page template:
```
1. Display name? (shows in Page Attributes dropdown — e.g., "Landing Page", "Contact Page")
2. Slug? (default: derived from display name → "landing-page")
3. Normal or flexible?
   - Normal = rigid sections, you add ACF groups to specific pages later
   - Flexible = its own ACF Flexible Content field, separate from the default
4. (If flexible) Re-use the existing flexible_sections layouts? Or create a separate field group with its own layouts?
```

### If archive template:
```
1. Which post type? (e.g., "post", "project", "case-study", "team")
2. Normal or flexible?
   - Normal = standard post grid + pagination
   - Flexible = ACF sections above/below the post list (hero, intro, CTA strip)
3. (If flexible) Where do sections appear?
   - Above the post grid only (most common)
   - Below the post grid only
   - Both above and below (separate fields)
```

### If single template:
```
1. Which post type? (e.g., "post", "project", "case-study", "team")
2. Normal or flexible?
   - Normal = title + featured image + content (standard post detail)
   - Flexible = ACF sections, with the post content as one of the layouts
3. (If flexible) Should the post's main content be:
   - Always rendered first (above sections)
   - One of the available flexible layouts
   - Hidden — sections drive everything
```

### If search / 404:
```
Normal or flexible?
- Normal = simple, hard-coded layout
- Flexible = editable via ACF (good for marketing-controlled 404s and search results)
```

### If taxonomy template:
```
1. Which taxonomy? (e.g., "category", "post_tag", "project_category")
2. Normal or flexible?
3. (If flexible) Same questions as archive
```

## Workflow per kind

### Page template (normal)

Create `theme/templates/template-{slug}.php`:

```php
<?php
/**
 * Template Name: {display name}
 *
 * Rigid page template. Attach ACF field groups to specific pages
 * using this template via Location rules in Field Groups.
 */
get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
        // Add your section includes here, e.g.:
        // include get_stylesheet_directory() . '/templates/parts/section-{slug}-hero.php';
        // include get_stylesheet_directory() . '/templates/parts/section-{slug}-content.php';
    endwhile;
endif;

get_footer();
```

### Page template (flexible — own field group)

1. Create `theme/templates/template-{slug}.php`:

```php
<?php
/**
 * Template Name: {display name}
 *
 * Flexible content template. Editable sections via ACF.
 */
get_header();

if (have_posts()) :
    while (have_posts()) : the_post();

        if (have_rows('{slug}_sections')) :
            while (have_rows('{slug}_sections')) : the_row();
                $layout = str_replace('_', '-', get_row_layout());
                $part   = locate_template('templates/parts/section-' . $layout . '.php', false, false);
                if ($part) include $part;
            endwhile;
        endif;

    endwhile;
endif;

get_footer();
```

2. Create `theme/acf-json/group_{slug}_template_sections.json`:

```json
{
    "key": "group_{slug}_template_sections",
    "title": "{Display Name} — Sections",
    "fields": [
        {
            "key": "field_flex_{slug}_sections",
            "label": "Sections",
            "name": "{slug}_sections",
            "type": "flexible_content",
            "layouts": [],
            "button_label": "Add section"
        }
    ],
    "location": [
        [{"param": "page_template", "operator": "==", "value": "templates/template-{slug}.php"}]
    ],
    "menu_order": 0,
    "position": "normal",
    "style": "default",
    "label_placement": "top",
    "instruction_placement": "label",
    "active": true,
    "modified": <unix-timestamp>
}
```

### Page template (flexible — share existing flexible_sections)

Same as default-template — locations rule points to BOTH templates:

In `acf-json/group_default_template_sections.json` `location`, ADD:

```json
[
    {"param": "page_template", "operator": "==", "value": "templates/template-default.php"}
],
[
    {"param": "page_template", "operator": "==", "value": "templates/template-{slug}.php"}
]
```

The page template uses `flexible_sections` (same field name) and includes section parts the same way.

### Archive template (normal)

Create `theme/archive-{cpt}.php`:

```php
<?php
get_header();
?>
<main id="primary" class="site-main py-12 sm:py-16 md:py-20 lg:py-24">
    <div class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12">

        <header class="mb-12">
            <h1 class="font-brand text-3xl md:text-4xl lg:text-5xl text-secondary">
                <?php post_type_archive_title(); ?>
            </h1>
            <?php $desc = get_the_archive_description(); if ($desc) : ?>
                <div class="mt-4 prose max-w-none"><?= wp_kses_post($desc) ?></div>
            <?php endif; ?>
        </header>

        <?php if (have_posts()) : ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                <?php while (have_posts()) : the_post(); ?>
                    <article class="bg-white border border-text/10 rounded-lg overflow-hidden">
                        <?php if (has_post_thumbnail()) : ?>
                            <a href="<?php the_permalink(); ?>" class="block aspect-[4/3] overflow-hidden">
                                <?php
                                $thumb_id = get_post_thumbnail_id();
                                $thumb = wp_get_attachment_image_src($thumb_id, 'large');
                                ?>
                                <img
                                    src="<?= esc_url($thumb[0]) ?>"
                                    alt="<?= esc_attr(get_post_meta($thumb_id, '_wp_attachment_image_alt', true) ?: get_the_title()) ?>"
                                    width="<?= (int) $thumb[1] ?>"
                                    height="<?= (int) $thumb[2] ?>"
                                    class="w-full h-full object-cover"
                                    loading="lazy"
                                >
                            </a>
                        <?php endif; ?>
                        <div class="p-6">
                            <h2 class="font-brand text-xl font-semibold text-secondary">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                            <div class="mt-2 text-text"><?php the_excerpt(); ?></div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <nav class="mt-12 flex justify-center">
                <?php the_posts_pagination([
                    'prev_text' => '&larr; Previous',
                    'next_text' => 'Next &rarr;',
                ]); ?>
            </nav>
        <?php endif; ?>

    </div>
</main>
<?php
get_footer();
```

### Archive template (flexible)

Create `theme/archive-{cpt}.php` that mixes the post grid with ACF flexible sections:

```php
<?php
get_header();

// Sections above the grid (from an Options page or this archive's ACF group)
if (have_rows('archive_{cpt}_top_sections', 'option')) :
    while (have_rows('archive_{cpt}_top_sections', 'option')) : the_row();
        $layout = str_replace('_', '-', get_row_layout());
        $part = locate_template('templates/parts/section-' . $layout . '.php', false, false);
        if ($part) include $part;
    endwhile;
endif;
?>

<section class="py-12 sm:py-16 md:py-20 lg:py-24">
    <div class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12">
        <?php if (have_posts()) : ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                <?php while (have_posts()) : the_post(); ?>
                    <!-- post card markup (same as normal archive) -->
                <?php endwhile; ?>
            </div>
            <?php the_posts_pagination(); ?>
        <?php endif; ?>
    </div>
</section>

<?php
// Sections below the grid (if applicable)
if (have_rows('archive_{cpt}_bottom_sections', 'option')) :
    while (have_rows('archive_{cpt}_bottom_sections', 'option')) : the_row();
        $layout = str_replace('_', '-', get_row_layout());
        $part = locate_template('templates/parts/section-' . $layout . '.php', false, false);
        if ($part) include $part;
    endwhile;
endif;

get_footer();
```

For the ACF group: register it on an Options page (since archives don't have a single post to attach fields to). Or use a single dedicated page with custom logic to read its ACF fields here.

The Options page approach is cleanest:

```json
"location": [
    [{"param": "options_page", "operator": "==", "value": "archive-{cpt}-settings"}]
]
```

The skill should auto-create the Options page registration in `theme/inc/acf-setup.php`.

### Single template (normal)

Create `theme/single-{cpt}.php`:

```php
<?php
get_header();

if (have_posts()) :
    while (have_posts()) : the_post(); ?>

        <article class="py-12 sm:py-16 md:py-20 lg:py-24">
            <header class="max-w-[768px] mx-auto px-4 sm:px-6 lg:px-8 mb-8">
                <h1 class="font-brand text-3xl md:text-4xl lg:text-5xl text-secondary">
                    <?php the_title(); ?>
                </h1>
                <div class="mt-4 text-sm text-text/70">
                    <?php echo get_the_date(); ?>
                </div>
            </header>

            <?php if (has_post_thumbnail()) : ?>
                <div class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12 mb-12">
                    <?php
                    $thumb_id = get_post_thumbnail_id();
                    $thumb = wp_get_attachment_image_src($thumb_id, 'large');
                    ?>
                    <img
                        src="<?= esc_url($thumb[0]) ?>"
                        alt="<?= esc_attr(get_post_meta($thumb_id, '_wp_attachment_image_alt', true) ?: get_the_title()) ?>"
                        width="<?= (int) $thumb[1] ?>"
                        height="<?= (int) $thumb[2] ?>"
                        class="w-full h-auto rounded-lg"
                    >
                </div>
            <?php endif; ?>

            <div class="max-w-[768px] mx-auto px-4 sm:px-6 lg:px-8 prose prose-lg">
                <?php the_content(); ?>
            </div>

        </article>

    <?php endwhile;
endif;

get_footer();
```

### Single template (flexible)

```php
<?php
get_header();

if (have_posts()) :
    while (have_posts()) : the_post();

        // Optional fixed header (title + featured image)
        // Or hide entirely if sections drive everything
        ?>
        <header class="py-12 max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12">
            <h1 class="font-brand text-3xl md:text-4xl lg:text-5xl text-secondary">
                <?php the_title(); ?>
            </h1>
        </header>
        <?php

        if (have_rows('single_{cpt}_sections')) :
            while (have_rows('single_{cpt}_sections')) : the_row();
                $layout = str_replace('_', '-', get_row_layout());

                // Special "the_content" layout — renders this post's main content
                if ($layout === 'post-content') {
                    echo '<section class="py-12"><div class="max-w-[768px] mx-auto px-4 sm:px-6 lg:px-8 prose prose-lg">';
                    the_content();
                    echo '</div></section>';
                    continue;
                }

                $part = locate_template('templates/parts/section-' . $layout . '.php', false, false);
                if ($part) include $part;
            endwhile;
        endif;

    endwhile;
endif;

get_footer();
```

ACF group `group_single_{cpt}_sections.json`:

```json
"location": [
    [{"param": "post_type", "operator": "==", "value": "{cpt}"}]
]
```

Add a layout named `post_content` so editors can place the post body wherever they want.

### Search / 404

For these, the file is just `theme/search.php` or `theme/404.php`. Same pattern: normal = direct markup; flexible = ACF Flexible Content (use Options page or single-instance page registration).

## Self-check before completing

1. ☐ File path follows WP template hierarchy correctly
2. ☐ Template Name: header on page templates
3. ☐ For flexible: ACF JSON written + `modified` timestamp = current time
4. ☐ Container width matches project conventions
5. ☐ Section padding cadence consistent
6. ☐ All escapes in place
7. ☐ All images use native `<img>` / `<picture>` (or `aiims_img()` for static)
8. ☐ Image attributes (width/height/loading/decoding) on all `<img>`

## Reply format

```
## Template created: <kind> — <name>

### File created
<path>

### ACF JSON written (if flexible)
<path> — modified <timestamp>

### How WordPress finds it
- Page template: appears in Pages → Edit → Page Attributes → Template dropdown
- Archive: WP automatically uses for /<cpt>/ archive pages
- Single: WP automatically uses for /<cpt>/<slug>/ detail pages

### Next steps
1. (If flexible) WP Admin → Custom Fields → Field Groups → Sync changes
2. Build sections to be added to this template via @briefs/<name>.md <figma-url>
```

## Things you must never do

- Mix template hierarchy patterns (e.g. `single-cpt.php` should never have a `Template Name:` header — that's only for page templates)
- Forget the `modified` timestamp on new ACF JSON
- Hardcode container width — read from project conventions
- Skip the Options page approach for archives/search/404 flexible content (no single post to attach to)
