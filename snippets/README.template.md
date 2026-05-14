# {{PROJECT_NAME}}

WordPress + ACF + Tailwind 4. Underscoretw stack. AIIMS Group build.

- **Local:** {{LOCAL_URL}}
- **Production:** {{PROD_URL}}
- **Theme folder:** `wp-content/themes/{{THEME_SLUG}}/` (workspace root) → `theme/` is the actual WP theme

> 📖 **Visual cheatsheet:** open `.claude/cheatsheet/index.html` in your browser for a clickable reference of all patterns, commands, and code snippets.

---

## Daily commands

| What you type | What happens |
|---|---|
| `@briefs/<section>.md <figma-url>` | Build the section from Figma — static phase only (no ACF yet) |
| `@briefs/<section>.md <figma-desktop> <figma-mobile>` | Build with both desktop + mobile frames |
| `Make <section> dynamic` | Convert approved static section to ACF (writes JSON, edits the section file) |
| `Add a section: <name>` | Scaffold a flexible-content layout shell (no markup yet) |
| `/pixel-check <section>` | Compare live render to Figma, report deviations |
| `/ship-check` | Pre-deploy: a11y + perf + QA + ACF sync state |

### How ACF stays in sync (the timestamp mechanism)

Every time Claude writes to `theme/acf-json/<group>.json`, it bumps the `"modified"` field to the current unix timestamp. ACF Pro detects this and shows a **"Sync changes"** notice in WP Admin → Custom Fields → Field Groups. Click it to apply schema changes to the database.

This means:
- All schema edits live in code (git-trackable)
- The user controls the moment of sync
- Re-deploys to staging/prod just need the JSON files + a sync click

If you ever want to force a sync notice (e.g. after manually editing JSON), just type "bump acf timestamps" and Claude updates all `modified` fields to current time.

---

## How a section gets built

1. **Write a section brief** in `briefs/<section>.md` (use `briefs/_template.md` as a starting point — 5-15 lines)
2. **Trigger the build:** `@briefs/<section>.md <figma-url>`
3. Claude builds `theme/templates/parts/section-<name>.php` with hard-coded markup pixel-matched to Figma
4. Hard-refresh, eyeball it, run `/pixel-check <section>` for a structured review if you want
5. Iterate until you approve the static
6. **Make it dynamic:** type "Make <section> dynamic"
7. Claude writes the ACF Local JSON + edits the section to use `get_sub_field()`
8. **WP Admin → ACF → Sync changes** → fill in fields → done

Static-first → dynamic-second is non-negotiable. It's faster than going straight to ACF.

---

## Two-template pattern

| Template | Purpose | Sections |
|---|---|---|
| `theme/templates/template-homepage.php` | Front page only. Rigid order. | One ACF group per section, attached to front page |
| `theme/templates/template-default.php` | Every other page. Universal. | Single ACF Flexible Content (`flexible_sections`) — one layout per section type |

The default template auto-includes `theme/templates/parts/section-{layout-name-with-dashes}.php` based on which layouts the editor adds on a page.

---

## Project conventions (filled in by /setup-claude)

### Brand tokens

```
--color-primary:   {{COLOR_PRIMARY}}     /* main CTA */
--color-secondary: {{COLOR_SECONDARY}}   /* heading text */
--color-accent:    {{COLOR_ACCENT}}      /* secondary CTA / link */
--color-text:      {{COLOR_TEXT}}        /* body copy */
--font-brand:      '{{FONT}}', system-ui, sans-serif
```

Edit at `tailwind/tailwind-theme.css` `@theme { ... }` block. Use as `bg-primary`, `text-secondary`, `font-brand`, etc.

To add a new color: type "Add a brand color: name #hex" and Claude will run `tailwind-theme-sync`.

### Container

- Max width: **{{CONTAINER}}px**
- Pattern: `max-w-[{{CONTAINER}}px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12`

### Breakpoints

- Mobile → desktop break: **{{BREAKPOINT}}** ({{BREAKPOINT_PX}})
- Standard Tailwind: `sm:640 md:768 lg:1024 xl:1280 2xl:1536`

### Section padding cadence

```
py-12 sm:py-16 md:py-20 lg:py-24 xl:py-[120px] 2xl:py-[150px]
```

### Phone format

- Country: **{{PHONE_COUNTRY}}**
- Validation: in `theme/inc/acf-setup.php`
- Display format: **{{PHONE_DISPLAY}}**

---

## Image and SVG cheatsheet

WebP conversion is handled by an image plugin (ShortPixel / EWWW / Imagify). Don't try to generate WebP in PHP. Every `<img>` MUST have `width` and `height` attributes (CLS).

### ACF image (single)
```php
<?php $img = get_sub_field('image'); if ($img) : ?>
<img
    src="<?= esc_url($img['url']) ?>"
    alt="<?= esc_attr($img['alt']) ?>"
    width="<?= (int) $img['width'] ?>"
    height="<?= (int) $img['height'] ?>"
    loading="lazy"
>
<?php endif; ?>
```

### ACF image + mobile companion → `<picture>`
```php
<picture>
    <source
        srcset="<?= esc_url($mobile['url']) ?>"
        media="(max-width: 767px)"
        width="<?= (int) $mobile['width'] ?>"
        height="<?= (int) $mobile['height'] ?>"
    >
    <img src="..." width="..." height="..." loading="lazy">
</picture>
```

### Static theme image
```php
<?php aiims_img('hero/banner.jpg', 'Alt text', 'w-full h-auto'); ?>
```
The helper reads width/height from the file. Use only for static theme assets (logo, decorative shapes).

### SVG — three patterns

| Use case | Pattern |
|---|---|
| Editor needs to change the icon per page, themed via Tailwind text color | ACF **textarea** field, paste raw `<svg>...</svg>` markup with `fill="currentColor"`. Render with `wp_kses($svg, aiims_svg_kses())` inside a parent with `text-*` class. |
| Editor needs to change the icon per page, fixed colors (logo, brand mark) | ACF **image** field (allowed types include svg). Render as `<img>`. |
| Theme-wide static icon, never changes | Paste `<svg>` markup directly inline in template. Or save to `assets/icons/` and use `aiims_img()`. |

---

## File layout

```
wp-content/themes/{{THEME_SLUG}}/         ← workspace root
├── README.md                             ← this file
├── briefs/                               ← write section briefs here
│   └── _template.md
├── .claude/                              ← Claude config (don't touch)
├── tailwind/
│   └── tailwind-theme.css                ← brand tokens here
├── javascript/
│   └── script.js                         ← scroll animations here
└── theme/                                ← actual WordPress theme
    ├── inc/
    │   ├── helpers.php                   ← aiims_img() + aiims_svg_kses()
    │   ├── acf-setup.php                 ← Local JSON + phone validation
    │   ├── template-functions.php
    │   └── template-tags.php
    ├── templates/
    │   ├── template-homepage.php
    │   ├── template-default.php
    │   └── parts/
    │       ├── section-_example.php      ← reference (don't include)
    │       └── section-<your-section>.php
    ├── acf-json/                         ← ACF Local JSON
    └── assets/
        ├── images/<section>/             ← static-phase images
        └── icons/                        ← theme-wide static SVGs
```

---

## Sections (status tracker)

| Section | Layout name | Template | Status |
|---|---|---|---|
| Header / Nav | (header.php) | global | ☐ |
| Footer | (footer.php) | global | ☐ |
| Home Hero | home_hero | homepage | ☐ static / ☐ dynamic |
| Home Services | home_services | homepage | ☐ static / ☐ dynamic |
| Home Projects | home_projects | homepage | ☐ static / ☐ dynamic |
| Home About | home_about | homepage | ☐ static / ☐ dynamic |
| Home Contact | home_contact | homepage | ☐ static / ☐ dynamic |
| Page Hero | page_hero | flexible | ☐ |
| Content Card | content_card | flexible | ☐ |
| Testimonials | testimonials | flexible | ☐ |
| FAQ | faq | flexible | ☐ |
| Contact | contact | flexible | ☐ |

(Update the status checkboxes as you complete each section.)

---

## Pixel-perfect rules

- Match Figma container width, padding, color, type exactly
- Spacing within 2px of a Tailwind token → use the token. 3-5px → ask. >5px → arbitrary `[Xpx]` and surface
- Colors not in brand tokens, used 2+ times → propose adding via `tailwind-theme-sync`. One-off → arbitrary `[#xxx]` and surface
- Touch targets ≥ 44×44px on mobile
- Body text ≥ 16px on mobile
- Never use `display: none` on mobile to remove content — restructure or accordion

---

## Before you ship

```
/ship-check                    ← runs a11y + perf + QA + ACF sync state in parallel
npm run bundle                 ← production zip at wp-content/themes/{{THEME_SLUG}}.zip
```

Then upload the zip to live host, activate, sanity-check.

---

## Common scenarios — copy-paste prompts

### Build the homepage hero from desktop Figma

1. Create `briefs/home-hero.md` with notes (slug, owner template, Figma URL — see `briefs/_template.md`)
2. In Claude:
```
@briefs/home-hero.md https://www.figma.com/design/XXXX/JG-Vertical?node-id=1-2
```

### Build a section with both desktop AND mobile Figma frames

```
@briefs/home-hero.md https://www.figma.com/design/XXXX/JG-Vertical?node-id=1-2 https://www.figma.com/design/XXXX/JG-Vertical?node-id=1-3
```

(Two URLs → triggers `merge-mobile-desktop` skill.)

### Build a section with no brief written yet

```
Build the home hero from this Figma: https://www.figma.com/design/XXXX/JG-Vertical?node-id=1-2
```

(Claude will ask for the slug + owner template before building.)

### Quick-check the live render against Figma

```
/pixel-check home-hero
```

### Tweak something specific that's off

```
The hero heading is too small on mobile — Figma shows 32px not 24px. Fix it.
```

```
Replace the <img> in home-hero with a <picture> that has a mobile companion (ACF mobile_image field).
```

```
The CTA button color is slightly off from Figma — Figma is #2563EB but I see it rendering darker.
```

### Convert an approved static section to ACF

```
Make home-hero dynamic
```

(Claude writes ACF JSON + replaces inline content with `get_field()`/`get_sub_field()` + tells you to sync in WP Admin.)

### Scaffold a flexible-content layout shell (no markup yet)

```
Add a section: testimonials
```

(Use this when you want to reserve a layout slug for the editor team without building it yet.)

### Add a new brand color when you find one in a Figma frame

```
Add a brand color: cta-hover #2A4D8B
```

```
Tailwind theme sync: pull all colors and font from this Figma file: https://www.figma.com/design/XXXX
```

### Auto-pull updated brand tokens after the designer updates Figma

```
Brand tokens have changed in Figma — re-read and update tailwind-theme.css from https://www.figma.com/design/XXXX
```

### Fix a broken/oversized SVG export from Figma

```
The arrow SVG from Figma is broken / huge / has hard-coded colors — fix it
```

### Pre-deploy

```
/ship-check
```

(Runs accessibility + performance + QA + ACF sync state in parallel.)

### Build production zip

In your terminal (not Claude):
```
npm run bundle
```

Produces `wp-content/themes/{{THEME_SLUG}}.zip`. Upload to live host via WP Admin → Appearance → Themes → Add New → Upload Theme.

### Other handy prompts

```
What sections exist so far?
```
```
Is the ACF JSON in sync?
```
```
Audit the asset pipeline — find any raw <img> without width/height
```
```
Show me what /pixel-check would compare
```

---

## When something's odd

- **`bg-X` Tailwind class doesn't work** → token's not in `@theme`. Type "add brand color X #hex".
- **Section won't render** → check `theme/templates/parts/section-<dashes-name>.php` exists and the layout name in JSON matches with underscores.
- **ACF fields don't show in editor** → sync hasn't been run, or location rule is wrong.
- **WebP not being served** → image optimization plugin not active or not configured.
- **Skills not loading in Claude** → `.claude/` folder must exist at workspace root with leading dot.
