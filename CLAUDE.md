# Project rules — WordPress + ACF + Tailwind

Read this file in full before doing any work. It's the master ruleset for this project. Per-project values (brand colors, container width, breakpoints, font, phone format) live in the workspace-root `README.md` — read that file too at the start of every session via the `read-project-conventions` skill.

---

## 1. Stack

- WordPress 6.x
- Theme based on Underscores + Tailwind (`underscoretw.com` generator)
- Tailwind 4 with `@theme inline` brand mapping in the source CSS
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

- **100% pixel-perfect target.** Not "close enough".
- **Container widths follow Figma.** If Figma's section is 1320px wide, the project container is 1320px (or matches the project's documented container — check `README.md`). Never silently round up/down.
- **Spacing scale:** Use Tailwind tokens when within 2px of a token. 3-5px off — ask the user. >5px or hard requirement — use arbitrary `[73px]` values and surface the deviation in your reply.
- **Colors:** Map Figma colors to brand tokens (`bg-primary`, `text-secondary`, etc.). If the Figma color doesn't match a brand token: 2+ uses → propose adding the token. One-off → arbitrary `[#xxx]` and flag.
- **Typography:** Match font weight, size, line-height, letter-spacing. Use Tailwind classes; arbitrary only when scale doesn't have it.
- After every section build, list deviations in your reply under "Deviations from Figma".

## 5. Responsive rules

- Always responsive: **desktop → laptop → tablet → mobile**.
- If the Figma file has both desktop AND mobile frames → user pastes both URLs → `merge-mobile-desktop` skill handles it.
- If the Figma has only desktop → build mobile-first defaults using sensible breakpoints from `README.md`. Surface guesses in your reply ("I assumed mobile stacks vertically with 24px gap — confirm before approving").
- If the Figma has only mobile → ask the user if a desktop variant exists before guessing.
- Touch targets minimum 44×44px on mobile.
- Never use `display: none` to hide on mobile if the content is meaningful — restructure instead.

## 6. Tailwind-first rule

- Use Tailwind utility classes for everything.
- Custom CSS only when Tailwind can't express it (animations, complex selectors, third-party overrides). Put it in `assets/css/source/custom.css` under a clearly named class.
- Do not use `@apply` unless the same combination of utilities repeats 3+ times. Prefer composing utilities inline.
- Brand colors come from `@theme inline` (Tailwind 4) → use `bg-primary`, `text-secondary`, `font-brand`. Never hex literals in markup unless flagged as deviations.

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

ACF Pro auto-syncs from `acf-json/<group-id>.json` whenever the file's mtime is newer than the database version. The user controls when to apply changes.

When the `make-section-dynamic` skill writes a new field group:
1. Save to `wp-content/themes/<theme>/acf-json/group_{layout-slug}.json`
2. Use the format ACF expects (`key`, `title`, `fields`, `location`, `menu_order`, `position`, `style`, `label_placement`, `instruction_placement`, `hide_on_screen`, `active`, `description`, `modified` (unix timestamp))
3. Bump the `modified` timestamp to current `time()`
4. Tell the user the exact step: **"Go to WP Admin → Custom Fields → Field Groups. You'll see a notice 'X field group has changes available'. Click 'Sync changes'."**

If a group already exists:
- Read the existing JSON
- Merge new fields in (preserve existing `key` IDs — never regenerate them)
- Bump `modified`
- Same sync prompt

For Flexible Content layouts on the default template: the `flexible_sections` field group is shared. New section types ADD a new layout to that group — they don't create new groups. Edit `acf-json/group_default_template_sections.json`.

## 9. Code style

- Minimal comments. The user wants to read and edit this themselves. Comments only when the intent isn't obvious from the code.
- PHP follows WordPress coding standards (4-space indent, snake_case functions, prefix project functions with the theme slug — e.g., `aiims_setup()`).
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

## 11. Skills you have available

- `read-project-conventions` — read the workspace `README.md`, Tailwind theme tokens, container, breakpoints, helpers
- `setup-claude` — one-time setup, runs only on first project init (self-deletes after success — only present in fresh projects)
- `implement-figma-section` — build a static section from `@briefs/<name>.md` + Figma URL
- `make-section-dynamic` — convert an approved static section to ACF Flexible Content layout + JSON
- `add-flexible-layout` — add a new layout to the default template's Flexible Content field
- `create-template` — make a new page / archive / single / search / 404 / taxonomy template (normal or flexible)
- `acf-json-sync` — manage `acf-json/` folder, bump timestamps, prompt user to sync
- `merge-mobile-desktop` — given desktop + mobile Figma URLs, build a single responsive section
- `pixel-perfect-verify` — screenshot the live page, compare to Figma, report diffs
- `responsive-build` — given a desktop-only design, plan and apply mobile-first responsive
- `handle-messy-figma-svg` — when Figma SVG export is truncated/oversized
- `tailwind-theme-sync` — when adding a new brand token to `@theme inline`

## 12. Agents you have available

- `frontend-builder` — Figma → Tailwind PHP markup (reads project conventions first)
- `responsive-engineer` — desktop → mobile-first pass (reads project breakpoints)
- `acf-architect` — designs ACF field groups, writes Local JSON
- `qa-reviewer` — typos, links, content, escaping
- `accessibility-auditor` — WCAG 2.1 AA review
- `performance-auditor` — image weight, render-blocking, LCP

## 13. Slash commands

- `/implement <section-brief.md> <figma-url>` — build a static section
- `/make-dynamic <section-name>` — convert static to ACF
- `/add-section <section-name>` — register a new flexible layout
- `/create-template` — make a new page / archive / single / search / 404 / taxonomy template
- `/pixel-check [section-name]` — compare live render to Figma
- `/ship-check` — pre-deploy: a11y + perf + QA + ACF JSON sync state

## 14. What NOT to do

- Don't build a full page in one shot.
- Don't go straight to ACF — static phase first, always.
- Don't write SCSS or any non-Tailwind CSS unless Tailwind can't do it.
- Don't add comments explaining what obvious code does.
- Don't use `display: none` on mobile to hide design pieces — restructure.
- Don't silently round container widths or spacings — surface deviations.
- Don't use static image paths in dynamic ACF sections — every section image is ACF (use `aiims_img()` only for theme-wide static assets like logos).
- Don't generate a new ACF `key` when editing an existing field — preserve the original.
- Don't skip escaping. Every dynamic output must be escaped.
- Don't skip `width` and `height` on `<img>` tags — required for CLS. ACF arrays always include them; static images use `aiims_img()` which reads them automatically.
- For ACF images, write `<img>` / `<picture>` directly (using `$img['width']`, `$img['height']` from the array). Use `aiims_img()` ONLY for static theme assets.
- Don't add features the user didn't ask for (e.g., custom post types unless they say so).

## 15. When in doubt

Ask the user. Specifically:
- "Spacing in Figma is 73px — Tailwind has `py-[72px]` (4.5rem) or `py-20` (80px). Which?"
- "Figma uses #2A4D8B for this CTA — none of the brand tokens match. Add as `--brand-cta` or arbitrary `[#2A4D8B]`?"
- "No mobile design — should I build mobile defaults from these guesses, or wait?"

The user prefers honesty over guessing.
