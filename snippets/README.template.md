# {{PROJECT_NAME}}

WordPress + ACF Pro + Tailwind 4. AIIMS Group build.

- **Local:** {{LOCAL_URL}}
- **Production:** {{PROD_URL}}
- **Theme folder:** `wp-content/themes/{{THEME_SLUG}}/` (workspace root) → `theme/` is the actual WP theme
- **Figma:** {{FIGMA_URL}}

> 📖 **Visual cheatsheet:** open `.claude/cheatsheet/index.html` in your browser. One page with **Cowork** and **Claude Code** tabs — flip between them depending on which tool you're using.

---

## Daily commands

| What you type | What happens |
|---|---|
| `@<section> <figma-url>` | Build the section from Figma. Auto-creates `briefs/<section>.md`. One URL = desktop + auto-responsive. |
| `@<section> <figma-desktop> <figma-mobile>` | Build with both desktop + mobile frames (`match-mobile-desktop`, pixel-perfect both) |
| `@<section> <urls> <inline deviations>` | Add tweaks after URLs — merged with the brief |
| `/build <section> <figma-url>` | Chained: implement → pause for approval → make-dynamic → optional seed |
| `Make <section> dynamic` | Convert approved static section to ACF (writes JSON + edits the section file) |
| `/upload-images <section>` | Move static images to WP media library, return attachment IDs, delete static folder |
| `/seed <section> heading=..., body=..., image=<id>` | One-shot self-deleting populator for ACF fields |
| `/cleanup-section <section>` | Strip dev comments, validate ACF refs, flag dead code (production sweep) |
| `/pixel-check <section>` | Compare live render to Figma — includes typography drift detection |
| `/ship-check` | Pre-deploy: a11y + perf + QA + ACF sync state |

### How ACF stays in sync (the timestamp mechanism)

Every time Claude writes to `theme/acf-json/<group>.json`, it bumps the `"modified"` field to the current unix timestamp. ACF Pro detects this and shows a **"Sync changes"** notice in WP Admin → Custom Fields → Field Groups. Click it to apply schema changes to the database.

This means:
- All schema edits live in code (git-trackable)
- The user controls the moment of sync
- Re-deploys to staging/prod just need the JSON files + a sync click

If you ever want to force a sync notice (e.g. after manually editing JSON), just type "bump acf timestamps" and Claude updates all `modified` fields to current time.

---

## How a section gets built (6-step workflow)

**1. Static** — paste the Figma URL(s) in chat:
```
@home-hero https://www.figma.com/design/XXXX/?node-id=1-2 https://www.figma.com/design/XXXX/?node-id=1-3
```
Claude reads Figma, writes `theme/templates/parts/section-home-hero.php` with hard-coded markup. Auto-creates `briefs/home-hero.md` for the paper trail. No file to write yourself.

**2. Cross-check** — hard-refresh the live page (`http://yourproject.test/`) and compare to Figma. Or run `/pixel-check home-hero` for a structured diff (it'll call out typography drift, spacing, colors, missing assets). Iterate the static until pixel-perfect.

**3. Dynamic** — once approved:
```
Make home-hero dynamic
```
Claude writes ACF Local JSON, replaces inline strings with `get_sub_field()` / `get_field()`, wraps every output with conditional guards.

**4. Sync** — go to **WP Admin → Custom Fields → Field Groups → click "Sync changes"**. The new editable fields appear in the page editor.

**5a. Upload images (if any)** — `/upload-images home-hero` writes a one-shot file that uploads every image from `theme/assets/images/home-hero/` to the WP media library, returns attachment IDs, deletes the static folder.

**5b. Seed** — populate real content:
```
Seed home-hero with: heading=Welcome to {{PROJECT_NAME}}, body=..., image=<id>, cta=Get a quote/#quote
```
Claude writes `theme/inc/seed-home-hero.php`. Hit `http://yourproject.test/?aiims_seed=home-hero` as admin → seeder runs → data populates → file self-deletes.

**6. Cleanup (optional)** — `/cleanup-section home-hero` strips dev-phase doc comments, validates ACF field references, flags orphaned assets. Section becomes production-deployable.

**Or in one shot:** `/build home-hero <figma-url> <figma-mobile-url>` chains 1 → 3 with a pause for your approval, optionally chains seed too.

Static-first is non-negotiable. It's faster than going straight to ACF.

---

## Two-template pattern

| Template | Purpose | Sections |
|---|---|---|
| `theme/templates/template-homepage.php` | Front page only. Rigid order. | One ACF group per section, attached to front page |
| `theme/templates/template-default.php` | Every other page. Universal. | Single ACF Flexible Content (`flexible_sections`) — one layout per section type |

The default template auto-includes `theme/templates/parts/section-{layout-name-with-dashes}.php` based on which layouts the editor adds on a page. The homepage template iterates a `$home_sections` slug array and silently skips any that don't exist yet.

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
- Validation: in `theme/inc/acf-setup.php` between `PHONE_REGEX_START` / `PHONE_REGEX_END` markers
- Display format: **{{PHONE_DISPLAY}}**

---

## `brand.config.json` — single source of truth

Located at the workspace root (same level as this README). One JSON file holds the brand identity:

- `brand.name`, `brand.tagline`
- `contact.phone_display`, `contact.phone_tel`, `contact.email`, `contact.address_*`
- `urls.local`, `urls.production`, `urls.figma`
- `colors.primary/secondary/accent/text`
- `fonts.brand/body`
- `social.facebook/instagram/linkedin/twitter/youtube`

**Use in PHP via `aiims_brand()`** with dot-notation:

```php
echo aiims_brand('contact.phone_display');   // → "02 8107 3910"
echo aiims_brand('contact.email');           // → "hello@..."
echo aiims_brand('brand.name');              // → project name
$config = aiims_brand();                     // → full array
```

Change phone number / email / address ONCE in `brand.config.json` — it propagates to every template, footer, header, CF7 email body. No find-replace across 31 files.

---

## Image and SVG cheatsheet

WebP conversion is handled by an image plugin (ShortPixel / EWWW / Imagify). Don't try to generate WebP in PHP. Every `<img>` MUST have `width` and `height` attributes (CLS).

**Asset fidelity rules (NON-NEGOTIABLE):**
- SVGs stay SVGs — never rasterize unless explicitly approved
- Preserve transparency on PNG/WebP exports
- Composite Figma frames stay composite — don't break grouped layouts into separate HTML pieces

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

### SVG — three patterns (preference order)

| Use case | Pattern | Why |
|---|---|---|
| Editor needs to change the icon per page, themed via Tailwind text color | **Pattern A** — ACF textarea, paste raw `<svg>` with `fill="currentColor"`. Render with `wp_kses($svg, aiims_svg_kses())`. | **DEFAULT** for per-page SVGs. Preserves theming. |
| Brand logo with fixed colors, editor-changeable | **Pattern B** — ACF image (file) | Last-resort fallback when theming isn't needed. |
| Theme-wide static icon, never changes | **Pattern C** — inline `<svg>` markup in template, or `assets/icons/` via `aiims_img()` | For icons used in multiple sections. |

**Never** rasterize an SVG asset to PNG/JPG.

---

## File layout

```
wp-content/themes/{{THEME_SLUG}}/         ← workspace root
├── README.md                             ← this file
├── brand.config.json                     ← single source of truth for brand
├── briefs/                               ← section briefs (auto-created by Claude)
│   └── _template.md
├── .claude/                              ← Claude config (don't touch)
│   ├── CLAUDE.md                         ← master rules
│   ├── cheatsheet/index.html             ← Cowork + Code tabbed reference
│   └── ... (skills, agents, commands)
├── tailwind/
│   └── tailwind-theme.css                ← brand tokens here
├── javascript/
│   └── script.js                         ← scroll animations here
└── theme/                                ← actual WordPress theme
    ├── inc/
    │   ├── helpers.php                   ← aiims_img() + aiims_svg_kses() + aiims_brand()
    │   ├── acf-setup.php                 ← Local JSON + phone validation
    │   ├── custom-functions.php          ← single hub: helpers + acf + seed/upload loaders
    │   ├── seed-<slug>.php               ← one-shot ACF populators (auto-load, self-delete)
    │   └── upload-<slug>.php             ← one-shot media uploaders (auto-load, self-delete)
    ├── templates/
    │   ├── template-homepage.php
    │   ├── template-default.php
    │   └── parts/
    │       ├── section-_example.php      ← reference (don't include)
    │       └── section-<your-section>.php
    ├── acf-json/                         ← ACF Local JSON
    └── assets/
        ├── images/<section>/             ← static-phase images (cleared by /upload-images)
        └── icons/                        ← theme-wide static SVGs
```

---

## Sections (status tracker)

| Section | Layout name | Template | Status |
|---|---|---|---|
| Header / Nav | (header.php) | global | ☐ |
| Footer | (footer.php) | global | ☐ |
| Home Hero | home_hero | homepage | ☐ static / ☐ dynamic / ☐ seeded |
| Home Services | home_services | homepage | ☐ static / ☐ dynamic / ☐ seeded |
| Home Projects | home_projects | homepage | ☐ static / ☐ dynamic / ☐ seeded |
| Home About | home_about | homepage | ☐ static / ☐ dynamic / ☐ seeded |
| Home Testimonials | home_testimonials | homepage | ☐ static / ☐ dynamic / ☐ seeded |
| Home Contact | home_contact | homepage | ☐ static / ☐ dynamic / ☐ seeded |
| Page Hero | page_hero | flexible | ☐ |
| Content Card | content_card | flexible | ☐ |
| Testimonials | testimonials | flexible | ☐ |
| FAQ | faq | flexible | ☐ |
| Contact | contact | flexible | ☐ |

(Update the status checkboxes as you complete each section. Note: ACF layout names use underscores; section file names use dashes — the template-default loop translates between them.)

---

## Pixel-perfect rules

- **Figma is source of truth (NON-NEGOTIABLE).** Claude refuses to build from screenshots if the Figma MCP is disconnected — it stops and asks you to reconnect. Don't override casually.
- Match Figma container width, padding, color, type exactly
- Spacing within 2px of a Tailwind token → use the token. 3-5px → ask. >5px → arbitrary `[Xpx]` and surface
- Colors not in brand tokens, used 2+ times → propose adding via `tailwind-theme-sync`. One-off → arbitrary `[#xxx]` and surface
- **If desktop + mobile Figma both exist, BOTH must be cross-checked** — mobile is a distinct design, not a scaled-down desktop
- Touch targets ≥ 44×44px on mobile
- Body text ≥ 16px on mobile
- Never use `display: none` on mobile to remove content — restructure or accordion instead
- Typography drift (Figma renders fonts differently than browsers): `/pixel-check` flags this and suggests specific Tailwind tweaks (`tracking-tight`, `leading-none`, etc.)

---

## Before you ship

```
/cleanup-section <each-section>   ← per-section production sweep
/ship-check                       ← whole-theme a11y + perf + QA + ACF sync state
npm run bundle                    ← production zip at wp-content/themes/{{THEME_SLUG}}.zip
```

Then upload the zip to live host, activate, sanity-check.

---

## Common scenarios — copy-paste prompts

### Build the homepage hero from desktop + mobile Figma

```
@home-hero https://www.figma.com/design/XXXX/?node-id=1-2 https://www.figma.com/design/XXXX/?node-id=1-3
```

(Two URLs → triggers `match-mobile-desktop` skill — pixel-matches both frames.)

### Build a section with one URL only (auto-responsive)

```
@home-hero https://www.figma.com/design/XXXX/?node-id=1-2
```

(One URL → Claude builds desktop AND auto-makes it responsive. Mobile guesses surfaced in the reply for you to confirm.)

### Build with inline deviations

```
@services-grid https://www.figma.com/.../1-2 https://www.figma.com/.../1-3 use one bg image not per-card, h3 for card titles
```

(Everything after the URLs becomes deviations, merged with the auto-generated brief.)

### Build static + dynamic in one shot

```
/build home-hero https://www.figma.com/.../1-2 https://www.figma.com/.../1-3
```

(Claude builds static → pauses for "looks good" → makes dynamic → optionally chains seed.)

### Quick-check the live render against Figma

```
/pixel-check home-hero
```

(Reports critical / important / minor deviations. Now includes typography drift detection.)

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

### Upload static images to WP media library

```
/upload-images home-hero
```

(Writes a one-shot loader. Hit the URL Claude gives you. Static files at `assets/images/home-hero/` get deleted after upload.)

### Seed real content

```
Seed home-hero with: heading=Welcome, body=<p>Long intro paragraph</p>, image=142, cta=Get a quote/#quote
```

(Writes a one-shot seeder. Hit the URL as admin. Data populates. File self-deletes.)

### Production sweep

```
/cleanup-section home-hero
```

(Strips dev comments, validates ACF refs, flags issues.)

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

### Fix a broken/oversized SVG export from Figma

```
The arrow SVG from Figma is broken / huge / has hard-coded colors — fix it
```

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

- **Claude stops mid-build: "Figma is disconnected"** → reconnect Figma (Cowork: Settings → Connectors. Claude Code: `claude mcp add figma`), then say `continue`. This is intentional behavior — Claude refuses to build from screenshots because they're downscaled and produce inconsistent code.
- **`bg-X` Tailwind class doesn't work** → token's not in `@theme`. Type "add brand color X #hex". Or `npm run watch` paused — restart it.
- **Section won't render** → check `theme/templates/parts/section-<dashes-name>.php` exists and the layout name in JSON matches with underscores.
- **ACF fields don't show in editor** → sync hasn't been run. WP Admin → Custom Fields → Field Groups → Sync changes.
- **WebP not being served** → image optimization plugin not active or not configured.
- **Skills not loading in Claude** → `.claude/` folder must exist at workspace root with leading dot.
- **A seeder won't run** → not logged in as admin, OR the seeder file already self-deleted (re-create from git), OR `inc/custom-functions.php` doesn't have the glob loader.
