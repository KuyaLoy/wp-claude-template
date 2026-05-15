# Project rules — WordPress + ACF + Tailwind

Read this file in full before doing any work. It's the master ruleset for this project. Per-project values (brand colors, container width, breakpoints, font, phone format) live in the workspace-root `README.md` — read that file too at the start of every session via the `read-project-conventions` skill.

---

## 1. Stack

- WordPress 6.x
- Theme based on Underscores + Tailwind (`underscoretw.com` generator)
- Tailwind 4 with `@theme` brand mapping in the source CSS
- ACF Pro (Local JSON enabled in `acf-json/` folder inside the theme)
- PHP 8.1+
- No SCSS, no Bootstrap, no custom build tools beyond Tailwind CLI

## 2. The two-template pattern (NON-NEGOTIABLE)

Every project ships with exactly two custom page templates:

| Template | Purpose | Sections come from |
|---|---|---|
| `templates/template-homepage.php` | Front page only. Rigid order. | Homepage-specific ACF field groups (one per section) attached to the front page only |
| `templates/template-default.php` | Every other page. Universal. | Single ACF Flexible Content field `flexible_sections`, with one layout per section type |

The default template's job is just to loop over `flexible_sections` and `include` the matching `templates/parts/section-{layout-with-dashes}.php`. **Do not add page-specific templates** unless the user explicitly asks. Inner pages are all powered by `template-default.php` + flexible content.

## 3. Section-by-section build workflow (NON-NEGOTIABLE)

We do NOT build full pages at once. We build **one section at a time**.

The trigger is: **`@briefs/<section>.md <figma-url>`**

The user creates a `briefs/<section>.md` file with notes (use `briefs/_template.md` as the starting point). When they paste that reference plus a Figma URL, the `implement-figma-section` skill loads.

### Two phases per section: static → dynamic

**Phase 1 — Static (default):**
- Build `templates/parts/section-{name}.php` with hard-coded markup that pixel-matches Figma
- All copy, image paths, link URLs are inline strings
- No `get_field()`, no `have_rows()`, no ACF anywhere
- Image assets saved into `assets/images/{section-name}/<filename>.png|jpg|svg`
- This phase is for verifying pixel-perfect markup vs. Figma without ACF complications

**Phase 2 — Dynamic (only after user approves the static):**
- The user says "make {section} dynamic"
- The `make-section-dynamic` skill runs:
  1. Designs the ACF field group (Flexible Content layout for default template, or standalone group for homepage template)
  2. Writes the JSON to `acf-json/group_{slug}.json`
  3. Replaces inline strings with `get_sub_field()` / `get_field()` calls
  4. Wraps every output with conditional guards: `if ($field) : ... endif;`
  5. Reminds the user to go to **WP Admin → ACF → Field Groups → Sync changes**

NEVER skip phase 1. NEVER do both phases in one go unless the user explicitly says "build static + dynamic together".

## 4. Pixel-perfect rules (STRICT)

- 100% pixel-perfect target. Not "close enough".
- Container width follows Figma — never silently round up/down. Check `README.md` for project default.
- Spacing: within 2px of a Tailwind token → use the token. 3-5px → ask. >5px → arbitrary `[73px]` + flag.
- Colors: 2+ uses → propose new brand token. One-off → arbitrary `[#xxx]` + flag.
- Typography: match family/size/weight/line-height/letter-spacing. Tailwind utilities first; arbitrary only when the scale doesn't have it.
- Every section build lists deviations under "Deviations from Figma".
- **If both desktop AND mobile Figma frames exist, BOTH must be cross-checked against their respective renders.** Mobile is not a "scaled-down desktop".

## 5. Responsive rules

- Always responsive: **desktop → laptop → tablet → mobile**, mobile-first defaults.
- Two Figma URLs (desktop + mobile) → `match-mobile-desktop` skill.
- Desktop-only Figma → build mobile-first defaults; surface every guess for confirmation.
- Mobile-only Figma → ask if a desktop variant exists before guessing.
- Touch targets ≥ 44×44px.
- Never `display: none` meaningful content on mobile — restructure or accordion instead.

## 6. Tailwind-first rule

- Use Tailwind utility classes for everything.
- Custom CSS only when Tailwind can't express it (animations, complex selectors, third-party overrides). Put it in `assets/css/source/custom.css` under a clearly named class.
- Do not use `@apply` unless the same combination of utilities repeats 3+ times. Prefer composing utilities inline.
- Brand colors come from `@theme` (Tailwind 4) → use `bg-primary`, `text-secondary`, `font-brand`. Never hex literals in markup unless flagged as deviations.
- **If a Tailwind class "doesn't compile" on the live page, ASK the user before inlining CSS as a workaround.** The Tailwind watcher (`npm run watch`) sometimes pauses silently when the dev's machine stalls. The first response is *not* to write custom CSS — it's to ask: "I notice `<class>` isn't compiling. Is `npm run watch` still running? It may have paused — please restart it and confirm whether the class now applies." Only inline CSS if the user confirms the watcher is healthy and the class still doesn't resolve.

## 7. Image and SVG rules

**Native HTML `<img>` and `<picture>`.** WebP conversion is handled by an image optimization plugin (ShortPixel / EWWW / Imagify / equivalent) — never generated in PHP.

The ONLY helpers that exist:
- `aiims_img($path, $alt, $class)` — for STATIC theme images (logo, decorative shapes). Outputs an `<img>` tag with auto width/height read from the file. Use only for assets that ship with the theme.
- `aiims_svg_kses()` — returns the allowed-tag list for `wp_kses()` when rendering ACF textarea-pasted SVG markup.

### Image rules

**Every `<img>` MUST have `width` and `height` attributes** (best practice for CLS — Cumulative Layout Shift). The browser uses them to compute aspect ratio before the image loads.

**Static phase images** (hard-coded in template, ship with theme):
```php
<?php aiims_img('hero/banner.jpg', 'Alt text', 'w-full h-auto rounded-lg'); ?>
```
The helper auto-reads dimensions from the file on disk.

**ACF image** (Phase 2 — dynamic):
```php
<?php $img = get_sub_field('image'); if ($img) : ?>
<img
    src="<?= esc_url($img['url']) ?>"
    alt="<?= esc_attr($img['alt']) ?>"
    width="<?= (int) $img['width'] ?>"
    height="<?= (int) $img['height'] ?>"
    class="w-full h-auto"
    loading="lazy"
    decoding="async"
>
<?php endif; ?>
```
ACF image arrays already include `width` and `height` — no helper needed.

**ACF image + mobile companion** (different image on mobile):
```php
<?php
$img = get_sub_field('image');
$mobile = get_sub_field('mobile_image');
if ($img) :
?>
<picture>
    <?php if ($mobile) : ?>
        <source
            srcset="<?= esc_url($mobile['url']) ?>"
            media="(max-width: 767px)"
            width="<?= (int) $mobile['width'] ?>"
            height="<?= (int) $mobile['height'] ?>"
        >
    <?php endif; ?>
    <img
        src="<?= esc_url($img['url']) ?>"
        alt="<?= esc_attr($img['alt']) ?>"
        width="<?= (int) $img['width'] ?>"
        height="<?= (int) $img['height'] ?>"
        class="w-full h-auto"
        loading="lazy"
        decoding="async"
    >
</picture>
<?php endif; ?>
```

### SVG rules — three patterns

**Pattern A — ACF textarea (preferred for icons that need theming).**
Editor pastes raw `<svg>...</svg>` markup into a textarea field. Render with `wp_kses($svg, aiims_svg_kses())`. The SVG can use `fill="currentColor"` so the parent `text-*` class themes it.

```php
<?php $icon = get_sub_field('icon_svg'); if ($icon) : ?>
    <div class="w-12 h-12 text-primary">
        <?= wp_kses($icon, aiims_svg_kses()) ?>
    </div>
<?php endif; ?>
```

**Pattern B — ACF image field (SVG file uploaded via media library).**
Use when the SVG doesn't need theming (e.g. brand logo with fixed colors). Output as `<img>`:

```php
<?php $logo = get_sub_field('logo'); if ($logo) : ?>
<img
    src="<?= esc_url($logo['url']) ?>"
    alt="<?= esc_attr($logo['alt']) ?>"
    width="<?= (int) ($logo['width']  ?: 200) ?>"
    height="<?= (int) ($logo['height'] ?: 60) ?>"
    class="h-12 w-auto"
    loading="lazy"
>
<?php endif; ?>
```

(Fallback width/height are sensible defaults if ACF can't read SVG dimensions — happens occasionally.)

**Pattern C — Inline SVG markup directly in template (theme-wide static icons).**
For icons used everywhere, never editable, no need for ACF. Just paste the SVG directly:

```php
<svg class="w-4 h-4 text-current" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
    <path d="M..." fill="currentColor"/>
</svg>
```

### Decision tree for SVGs

- Editor needs to change it per page → **Pattern A** (textarea, theming via currentColor)
- Editor needs to change it per page but no theming → **Pattern B** (image upload)
- Theme-wide, never changes → **Pattern C** (inline directly in template)
- Logo / brand mark used across site → **Pattern C** in header.php, footer.php (or static file via `aiims_img()`)

## 8. ACF JSON sync (manual workflow)

ACF Pro auto-syncs from `acf-json/<group-id>.json` when the file's `modified` timestamp is newer than the DB. The user controls the moment of sync via WP Admin → Custom Fields → Field Groups → "Sync changes".

When writing a new field group:
1. Save to `acf-json/group_{layout-slug}.json` with the full ACF format (`key`, `title`, `fields`, `location`, etc.).
2. Bump `modified` to current unix time.
3. Tell the user where to click in WP Admin.

When editing an existing group: read it, merge new fields (**preserve existing `key` IDs** — never regenerate), bump `modified`, same sync prompt.

For Flexible Content layouts on the default template: append the new layout to `acf-json/group_default_template_sections.json` `fields[0].layouts`. One shared group; new section types ADD layouts, don't create new groups.

## 9. Code style

- Minimal comments. The user wants to read and edit this themselves. Comments only when the intent isn't obvious from the code.
- PHP follows WordPress coding standards (4-space indent, snake_case functions).
- **Helper namespace is always `aiims_*`** — fixed template convention (AIIMS Group). NOT derived from the theme slug. Examples: `aiims_img()`, `aiims_svg_kses()`, `aiims_image_dimensions()`. Any project-specific helpers, CPT registrations, or shortcodes follow the same `aiims_*` prefix unless the user explicitly overrides.
- Always escape: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()` for rich text.
- Always guard ACF output: `if ($field) :` / `if (have_rows('x')) :`.
- Prefer `<?= ?>` short echo tags inside markup, full `<?php ?>` for logic blocks.

## 10. File structure inside the theme

```
wp-content/themes/<theme>/
├── .claude/                      ← this folder (Claude reads here)
├── README.md              ← per-project conventions
├── briefs/                       ← section briefs the user writes (@briefs/x.md)
├── acf-json/                     ← ACF Local JSON (auto-sync source)
├── assets/
│   ├── css/source/               ← Tailwind input
│   ├── images/{section}/         ← static-phase images
│   └── icons/                    ← theme-wide static SVGs
├── templates/
│   ├── template-homepage.php
│   ├── template-default.php
│   ├── parts/
│   │   └── section-{layout}.php  ← one per Flexible Content layout
│   └── components/               ← reusable bits used by multiple sections
├── inc/
│   ├── helpers.php          ← aiims_img() + aiims_svg_kses() + SVG upload safety
│   ├── theme-setup.php
│   ├── enqueue.php
│   └── acf-setup.php        ← Local JSON + phone validation + admin tweaks
├── functions.php                 ← thin: requires inc/* files
├── header.php
├── footer.php
├── page.php                      ← inherits default behavior
├── index.php
└── style.css
```

## 11. Skills, agents, commands

These are auto-discovered from `skills/*/SKILL.md`, `agents/*.md`, and `commands/*.md` frontmatter. Open those folders for the current set. Day-to-day, you mostly use:

- `@<name>.md <figma-url>` → `implement-figma-section` (static build)
- "Make <name> dynamic" → `make-section-dynamic` (ACF wire-up)
- `/build <name> <figma-url>` → chained: static + dynamic in one flow
- `/pixel-check [name]` → live-vs-Figma diff
- `/ship-check` → a11y + perf + QA + ACF sync state before deploy

## 12. Slash commands

- `/setup-claude` — first-time bootstrap (self-deletes after success)
- `/build <name> <figma-url>` — chained: implement → pause for approval → make-dynamic
- `/implement <section-brief.md> <figma-url>` — static section build only
- `/make-dynamic <section-name>` — convert static to ACF
- `/add-section <section-name>` — register a new flexible layout (no markup)
- `/create-template` — new page / archive / single / search / 404 / taxonomy template
- `/pixel-check [section-name]` — compare live render to Figma
- `/ship-check` — pre-deploy: a11y + perf + QA + ACF JSON sync state

## 13. What NOT to do

- Don't build a full page in one shot.
- Don't go straight to ACF — static phase first, always.
- Don't write SCSS or any non-Tailwind CSS unless Tailwind can't do it.
- Don't `display: none` mobile content — restructure or accordion instead.
- Don't silently round container widths or spacings — surface deviations.
- Don't use static image paths in dynamic ACF sections — `aiims_img()` is only for theme-wide static assets like logos.
- Don't regenerate ACF `key` values when editing an existing field — preserve them.
- Don't skip escaping or `width`/`height` on `<img>` (CLS).
- Don't add features the user didn't ask for (custom post types, etc.).

## 14. When in doubt

Ask. The user prefers honesty over guessing. Examples:

- "Spacing in Figma is 73px — `py-[72px]` (4.5rem) or `py-20` (80px)?"
- "Figma uses #2A4D8B for this CTA, no matching brand token. Add as `--color-cta` or use arbitrary `[#2A4D8B]`?"
- "No mobile Figma — build mobile defaults from these guesses, or wait?"
