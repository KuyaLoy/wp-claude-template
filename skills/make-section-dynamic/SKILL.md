---
name: make-section-dynamic
description: Convert a static section template part to ACF Flexible Content (for default template) or ACF field group (for homepage template). Triggers on "make <section> dynamic", "convert <section> to ACF", "wire up ACF for <section>", "make this dynamic". Writes the ACF Local JSON file, replaces inline markup with get_sub_field/get_field calls, adds conditional guards, and prompts the user to sync via WP Admin → ACF → Sync changes. Only run AFTER the static section is pixel-approved.
---

# Make Section Dynamic (Phase 2)

This is phase 2 of the section workflow. It converts an approved static section into an ACF-driven section. The user explicitly triggers this — never auto-run it after `implement-figma-section`.

## Pre-conditions

Before running, verify:
1. The static section file exists: `templates/parts/section-<name>.php`
2. The user has approved the static pixel match (don't ACF-ify a section that still needs visual fixes)
3. `acf-json/` folder exists in the theme and is writable
4. ACF Pro is active

If any of these fail, stop and tell the user.

## Decide which template owns this section

Two paths:

### Path A — Default template (Flexible Content layout)
The section will be one of many layouts in the `flexible_sections` field on `template-default.php`. Most inner-page sections fall here.

### Path B — Homepage template (standalone field group)
The section is part of the rigid homepage. Each homepage section gets its own ACF field group, attached to the front page only.

Ask the user if it's not specified in the brief: "Is this for the homepage (rigid) or a flexible layout for inner pages?"

## Workflow — Path A (default template flexible layout)

### A1. Read the existing flexible_sections group

```bash
cat acf-json/group_default_template_sections.json 2>/dev/null
```

If it doesn't exist, create the parent group first (this is rare — should have been done in `setup-claude`).

### A2. Design the layout fields

Inspect the static markup. Identify every piece of content + asset:

| Static content | ACF field type |
|---|---|
| Hard-coded heading | `text` |
| Hard-coded subheading | `text` or `textarea` |
| Body copy paragraph | `wysiwyg` (if rich text needed) or `textarea` |
| Image references | `image` (return: array) |
| SVG file references | `file` (allowed types: svg) OR `image` if WP allows SVG uploads |
| CTA buttons | `link` |
| Lists/cards/repeating items | `repeater` with sub-fields |
| Background images | `image` (with mobile_image companion if responsive) |
| Color picker (rare) | `color_picker` only if user toggles color per page |

### A3. Generate the ACF JSON

The Flexible Content layout JSON sits inside the parent group's `layouts` array. Each layout has:
- `key`: `layout_<random>` (preserve existing if editing)
- `name`: snake_case version of section name (e.g., `home_hero`)
- `label`: Human readable (e.g., `Home Hero`)
- `display`: `block`
- `sub_fields`: array of fields
- `min`: empty
- `max`: empty

Append the new layout to the existing `group_default_template_sections.json` `fields[0].layouts` array. Do NOT regenerate any existing `key` values — preserve them.

Use unique stable keys: `field_<groupname>_<layoutname>_<fieldname>`. Example: `field_flex_home_hero_heading`.

### A4. Bump the modified timestamp

Set `"modified": <unix-timestamp>` on the parent group. ACF Pro detects this and shows the Sync notice.

```php
"modified": 1730000000
```

### A5. Replace static markup in the section part

Edit `templates/parts/section-<name>.php`:

```php
<?php
/**
 * Section: <Name>
 * ACF layout: <name_snake>
 */

$heading = get_sub_field('heading');
$body    = get_sub_field('body');
$image   = get_sub_field('image');
$cta     = get_sub_field('cta');
?>
<?php if ($heading || $body || $image || $cta) : ?>
<section id="<slug>" class="bg-white py-12 sm:py-16 md:py-20 lg:py-24 xl:py-[120px]">
    <div class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12">

        <?php if ($heading) : ?>
            <h2 class="font-brand text-3xl md:text-4xl lg:text-5xl text-secondary" data-reveal>
                <?= esc_html($heading) ?>
            </h2>
        <?php endif; ?>

        <?php if ($body) : ?>
            <div class="mt-4 text-text" data-reveal>
                <?= wp_kses_post($body) ?>
            </div>
        <?php endif; ?>

        <?php if ($image) : ?>
            <div class="mt-12">
                <img
                    src="<?= esc_url($image['url']) ?>"
                    alt="<?= esc_attr($image['alt'] ?: $heading ?: '') ?>"
                    width="<?= (int) $image['width'] ?>"
                    height="<?= (int) $image['height'] ?>"
                    class="w-full h-auto"
                    loading="lazy"
                    decoding="async"
                >
            </div>
        <?php endif; ?>

        <?php if ($cta) : ?>
            <a
                href="<?= esc_url($cta['url']) ?>"
                target="<?= esc_attr($cta['target'] ?: '_self') ?>"
                class="mt-8 inline-flex items-center bg-primary text-white px-6 py-3 hover:bg-primary/90"
                data-reveal
            >
                <?= esc_html($cta['title']) ?>
            </a>
        <?php endif; ?>

    </div>
</section>
<?php endif; ?>
```

Rules:
- Every output wrapped in conditional guards
- Every dynamic value escaped (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` for rich text)
- ACF image arrays already include `width` and `height` keys — use them directly in the `<img>` tag. WebP conversion is handled by the image plugin.
- For ACF + mobile companion image: use `<picture>` with a `<source media="(max-width: 767px)">` element
- For ACF SVG (textarea field): `wp_kses($svg, aiims_svg_kses())` — allows the SVG to use `fill="currentColor"` and inherit color from a parent `text-*` class
- For ACF SVG file (image field): render as `<img>` like any other image
- Outer `if` guard prevents rendering empty sections
- Tailwind classes unchanged from static phase — only content sources change

### A6. Confirm the layout name matches the file name

ACF layout name: `home_hero` (underscores)
File name: `templates/parts/section-home-hero.php` (dashes)
Mapping: `template-default.php` does `str_replace('_', '-', get_row_layout())` to bridge.

If they don't match, the layout won't render. Verify before completing.

### A7. Tell the user to sync

```
## Section dynamic: home-hero

### ACF JSON updated
acf-json/group_default_template_sections.json
- Added layout: home_hero (Home Hero)
- Modified timestamp bumped → 1730000000

### Section file updated
templates/parts/section-home-hero.php
- Replaced inline content with get_sub_field() calls
- Added conditional guards on every field

### YOU MUST SYNC IN WP ADMIN

1. Go to: WP Admin → Custom Fields → Field Groups
2. You'll see a notice: "1 field group has changes available"
3. Click the "Sync changes" link/button
4. Confirm — the new layout becomes available

### After sync

- Edit any non-homepage page using "Universal Page" template
- Add row → "Home Hero" layout will appear in the picker
- Fill in heading, body, image, CTA — save → frontend renders the section
```

## Workflow — Path B (homepage rigid section)

### B1. Each homepage section is its own field group

Naming: `group_home_<sectionname>.json`

```json
{
  "key": "group_home_hero",
  "title": "Home — Hero",
  "fields": [...],
  "location": [
    [{"param": "page_type", "operator": "==", "value": "front_page"}]
  ],
  "menu_order": 0,
  "position": "normal",
  "style": "default",
  "label_placement": "top",
  "instruction_placement": "label",
  "hide_on_screen": "",
  "active": true,
  "description": "",
  "modified": <timestamp>
}
```

The `menu_order` controls section order in the admin UI — match the visual order on the page.

### B2. Use `get_field()` not `get_sub_field()`

Because the homepage groups are not inside a Flexible Content, fields are direct:

```php
$heading = get_field('home_hero_heading');
$image   = get_field('home_hero_image');
```

Prefix every field name with `<section>_` to avoid clashes (since they all attach to one page).

### B3. Wire into `template-homepage.php`

The homepage template has explicit includes in fixed order:

```php
<?php
/* Template Name: Homepage */
get_header();
?>
<main>
    <?php include get_template_directory() . '/templates/parts/section-home-hero.php'; ?>
    <?php include get_template_directory() . '/templates/parts/section-home-services.php'; ?>
    <?php include get_template_directory() . '/templates/parts/section-home-projects.php'; ?>
    <!-- ...etc... -->
</main>
<?php get_footer(); ?>
```

If the include isn't there yet, add it.

### B4. Sync prompt — same as A7

Path B sync notice will mention each homepage group separately.

## Self-check before completing

1. ☐ Static markup is fully replaced — no leftover hard-coded copy
2. ☐ Every output is escaped
3. ☐ Every output is guarded with `if`
4. ☐ Image/SVG calls use ACF arrays, not static paths
5. ☐ ACF JSON file is valid (lint mentally — closing braces/brackets match)
6. ☐ Layout name matches file name (with `_` ↔ `-` bridge for default template)
7. ☐ `modified` timestamp is current
8. ☐ Existing field keys preserved if editing existing layout

## Common mistakes to avoid

- Forgetting `wp_kses_post()` on `wysiwyg` field outputs (renders HTML safely)
- Using `esc_html()` on rich text (strips formatting)
- Regenerating `key` values when editing a layout (breaks existing data)
- Adding the layout but forgetting to bump `modified`
- Not telling the user where to click in WP Admin
- Wiring the section into `template-default.php` directly (it loops automatically — just register the layout)
