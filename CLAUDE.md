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

The trigger is any of: **`@<name> <figma-url>`** · `@<name>.md <urls>` · `<name> <urls>` · plain English ("Build the home hero from this Figma: <url>"). Brief files at `briefs/<name>.md` are OPTIONAL — `implement-figma-section` auto-creates one from chat context if missing.

### Five steps per section: static → cross-check → dynamic → sync → seed

**Step 1 — Static:**
- Build `templates/parts/section-{name}.php` with hard-coded markup that pixel-matches Figma
- All copy, image paths, link URLs are inline strings
- No `get_field()`, no `have_rows()`, no ACF anywhere
- Image assets saved into `assets/images/{section-name}/<filename>.png|jpg|svg`
- This phase is for verifying pixel-perfect markup vs. Figma without ACF complications

**Step 2 — Cross-check:**
- Run `/pixel-check <name>` (or compare manually) to diff live render against the Figma frame
- Surface deviations: spacing, color, typography, asset quality, mobile breakpoints
- Iterate Step 1 until pixel-perfect

**Step 3 — Dynamic (only after user approves the static):**
- The user says "make {section} dynamic" (or runs `/build` which chains automatically)
- The `make-section-dynamic` skill runs:
  1. Designs the ACF field group (Flexible Content layout for default template, or standalone group for homepage template)
  2. Writes the JSON to `acf-json/group_{slug}.json`
  3. Replaces inline strings with `get_sub_field()` / `get_field()` calls
  4. Wraps every output with conditional guards: `if ($field) : ... endif;`

**Step 4 — Sync:**
- The user goes to **WP Admin → Custom Fields → Field Groups → click "Sync changes"**
- ACF Pro reads the JSON `modified` timestamp and applies the schema to the DB
- After this, the editable fields appear in the page editor

**Step 5a — Upload static images to media library (if any):**
- If the section has ACF image fields AND static images at `theme/assets/images/<slug>/`, the user runs `/upload-images <slug>` (or asks Claude to "upload section images")
- The `upload-images` skill writes `theme/inc/upload-<slug>.php` from `snippets/uploader-template.php`
- The user hits `http://<project>.test/?aiims_upload=<slug>` while logged in as admin → each file goes through `wp_handle_sideload` + `wp_insert_attachment` → returns attachment IDs → deletes the static folder → self-deletes
- The returned attachment IDs feed directly into Step 5b's seed call

**Step 5b — Seed:**
- The user says "Seed the {section} with: heading=..., body=..., image=<id>..." (or runs `/seed <section>`)
- The `seed-data` skill writes `theme/inc/seed-{slug}.php` from `snippets/seeder-template.php` — a one-shot self-deleting PHP file that hooks `template_redirect`, checks `?aiims_seed=<slug>`, populates ACF fields via `update_field()`, then `unlink()`s itself
- The user hits `http://<project>.test/?aiims_seed=<slug>` while logged in as admin → seeder runs once → file deletes itself
- Versioned in git, re-runnable by `git checkout`, admin-gated, no leftover endpoints in production

**Step 6 — Cleanup (optional, production sweep):**
- The user runs `/cleanup-section <slug>` (or asks Claude to "clean up <section>")
- The `cleanup-section` skill strips dev-only doc comments (`Phase: static (pre-ACF)`, `Source: <figma-url>`), removes yellow scaffold-stub markup if present, validates every `get_sub_field()` / `get_field()` call points at a real ACF field, flags orphaned static assets, and reports any dead code. Section becomes production-deployable.

NEVER skip Step 1 (static). NEVER do Steps 1+3 in one go unless the user explicitly says "build static + dynamic together". Steps 5a, 5b, and 6 are OPTIONAL but encouraged — they eliminate manual content entry and keep the theme clean.

## 4. Figma as source of truth (NON-NEGOTIABLE)

**Figma MCP is the design data pipeline.** Every section build requires real values from Figma — not from a screenshot, not from a description, not from memory. Screenshots are downscaled (Figma MCP caps `get_screenshot` at 1024px), so spacing, color hex codes, font sizes, and design-token names get distorted. Building from a screenshot produces visually-plausible but inconsistent code that drifts across sections.

The rule:

1. **Before fetching design data, probe for `mcp__Figma__*` tools.** Look for `mcp__Figma__get_design_context`, `mcp__Figma__get_variable_defs`, `mcp__Figma__get_metadata`, `mcp__Figma__get_screenshot`.

2. **If `mcp__Figma__*` is available** → use it. Always call `get_design_context` and `get_variable_defs` for true values; `get_screenshot` is for visual cross-reference only, never the primary source.

3. **If `mcp__Figma__*` is NOT available** → **STOP. Do not build.** Reply with:

   > "I can't access Figma right now. The Figma MCP is disconnected, and I won't build from a screenshot — it produces inconsistent design tokens across sections. Please reconnect Figma:
   > - **Cowork:** Settings → Connectors → Figma → reconnect
   > - **Claude Code:** restart your session, run `claude mcp list` to verify, or `claude mcp add figma`
   >
   > Then say 'continue' and I'll resume."

   Wait for the user to confirm reconnection. Do NOT proceed with a screenshot. Do NOT proceed with a verbal description. Do NOT proceed with assumed values.

4. **No exceptions** unless the user explicitly types something like "I have no Figma access, build from this screenshot only, I accept reduced accuracy." Even then, surface the risk in the reply: every value taken from the screenshot is flagged as "screenshot-derived, verify when Figma is reconnected."

5. **If Figma disconnects mid-build** (the MCP returns errors after working earlier) → same rule. Stop, ask to reconnect, don't fall back to your earlier in-context Figma data unless the user explicitly says "use what you remember and flag everything." Memory drifts across multi-turn sessions; it's not a substitute for live MCP data.

This is why we ship `INSTALL-MCPS.md` and why the `pixel-perfect-verify` skill probes for MCP availability before doing any work — the entire workflow assumes Figma is the source of truth.

## 5. Pixel-perfect rules (STRICT — depends on §4)

- 100% pixel-perfect target. Not "close enough".
- Container width follows Figma — never silently round up/down. Check `README.md` for project default.
- Spacing: within 2px of a Tailwind token → use the token. 3-5px → ask. >5px → arbitrary `[73px]` + flag.
- Colors: 2+ uses → propose new brand token. One-off → arbitrary `[#xxx]` + flag.
- Typography: match family/size/weight/line-height/letter-spacing. Tailwind utilities first; arbitrary only when the scale doesn't have it.
- Every section build lists deviations under "Deviations from Figma".
- **If both desktop AND mobile Figma frames exist, BOTH must be cross-checked against their respective renders.** Mobile is not a "scaled-down desktop".

## 6. Responsive rules

- Every section must work at desktop → laptop → tablet → mobile, mobile-first defaults. There is no "desktop-only" section.
- **One Figma URL given** → Claude builds desktop AND auto-makes it responsive (web → tablet → mobile) in the same build pass. Every breakpoint assumption Claude makes is surfaced in the final reply under "Mobile guesses" — the user confirms by previewing or asks to revise. Don't pause mid-build to ask; build through.
- **Two Figma URLs given** (desktop + mobile) → invoke `match-mobile-desktop`. Both frames are pixel-matched; no guessing.
- **Mobile-only Figma** → ask if a desktop variant exists before guessing.
- Touch targets ≥ 44×44px on mobile.
- Body text ≥ 16px on mobile.
- Never `display: none` meaningful content on mobile — restructure or accordion instead. The exception is decorative-only elements (purely visual, no info value).

## 7. Tailwind-first rule

- Use Tailwind utility classes for everything.
- Custom CSS only when Tailwind can't express it (animations, complex selectors, third-party overrides). Put it in `assets/css/source/custom.css` under a clearly named class.
- Do not use `@apply` unless the same combination of utilities repeats 3+ times. Prefer composing utilities inline.
- Brand colors come from `@theme` (Tailwind 4) → use `bg-primary`, `text-secondary`, `font-brand`. Never hex literals in markup unless flagged as deviations.
- **If a Tailwind class "doesn't compile" on the live page, ASK the user before inlining CSS as a workaround.** The Tailwind watcher (`npm run watch`) sometimes pauses silently when the dev's machine stalls. The first response is *not* to write custom CSS — it's to ask: "I notice `<class>` isn't compiling. Is `npm run watch` still running? It may have paused — please restart it and confirm whether the class now applies." Only inline CSS if the user confirms the watcher is healthy and the class still doesn't resolve.

## 8. Image and SVG rules

**Native HTML `<img>` and `<picture>`.** WebP conversion is handled by an image optimization plugin (ShortPixel / EWWW / Imagify / equivalent) — never generated in PHP.

### Asset fidelity from Figma (NON-NEGOTIABLE)

These rules apply when fetching assets via the Figma MCP:

1. **If Figma says it's an SVG, it stays an SVG.** Never rasterize a vector asset to PNG/JPG. Vectors stay crisp at any size; raster conversion loses that and bloats the file. The only exception: hand-drawn illustrations Figma exports as 200KB+ SVGs that are smaller as a compressed JPEG — flag the trade-off and ask the user.

2. **Preserve transparency.** If the source frame has a transparent background, export PNG (or WebP) with alpha. Never bake in a white/solid background "just to be safe." If Figma shows transparency, the live site shows transparency. Composite layouts where the asset overlays a section background depend on this.

3. **Composite frames stay composite.** When a designer groups multiple elements into one frame (e.g. a hero illustration with a person + decorative shapes + text overlay rendered together), treat it as a single asset. Don't try to extract child layers as separate HTML elements unless the brief explicitly says so — sub-pixel drift from the designer's intent is the result.

4. **Export at 2× when possible** for retina sharpness, then let the image-optimization plugin downscale/WebP. Note `mcp__Figma__get_screenshot` caps at 1024px max edge — that's for visual cross-reference only. For real assets, use Figma's PNG/SVG export at 2× via the MCP or ask the user for the export.

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

**Preference order:** Pattern A (textarea) → Pattern C (inline) → Pattern B (image upload). Image upload is the last resort, not the default. Always keep SVG as SVG; never rasterize unless the file is genuinely smaller as raster (rare, hand-drawn illustration territory — surface the trade-off and ask the user).

**Pattern A — ACF textarea (DEFAULT for editor-changeable SVGs).**
Editor pastes raw `<svg>...</svg>` markup into a textarea field. Render with `wp_kses($svg, aiims_svg_kses())`. The SVG can use `fill="currentColor"` so the parent `text-*` class themes it. This is the default for any per-page SVG because it preserves theming control and keeps the asset vector-crisp.

```php
<?php $icon = get_sub_field('icon_svg'); if ($icon) : ?>
    <div class="w-12 h-12 text-primary">
        <?= wp_kses($icon, aiims_svg_kses()) ?>
    </div>
<?php endif; ?>
```

**Pattern B — ACF image field (SVG file uploaded via media library).** Last-resort fallback.
Use ONLY when Pattern A is impractical: brand logo with fixed colors that doesn't need theming, very large complex SVG the editor can't reasonably paste, or the editor explicitly prefers file uploads. Output as `<img>`:

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

- Editor needs to change it per page → **Pattern A** (textarea, theming via currentColor) — DEFAULT
- Editor needs to change it per page, complex SVG paste impractical → **Pattern B** (image upload) — last resort
- Brand logo with fixed colors, editor-changeable → **Pattern B** (image upload, theming not needed)
- Theme-wide, never changes → **Pattern C** (inline directly in template)
- Logo / brand mark used across site, not editable → **Pattern C** in header.php / footer.php (or static file via `aiims_img()`)

**Never:** convert an SVG asset to a raster image (PNG/JPG) for ease. Vector stays vector. Exception requires user confirmation per the asset-fidelity rule above.

## 9. ACF JSON sync (manual workflow)

ACF Pro auto-syncs from `acf-json/<group-id>.json` when the file's `modified` timestamp is newer than the DB. The user controls the moment of sync via WP Admin → Custom Fields → Field Groups → "Sync changes".

When writing a new field group:
1. Save to `acf-json/group_{layout-slug}.json` with the full ACF format (`key`, `title`, `fields`, `location`, etc.).
2. Bump `modified` to current unix time.
3. Tell the user where to click in WP Admin.

When editing an existing group: read it, merge new fields (**preserve existing `key` IDs** — never regenerate), bump `modified`, same sync prompt.

For Flexible Content layouts on the default template: append the new layout to `acf-json/group_default_template_sections.json` `fields[0].layouts`. One shared group; new section types ADD layouts, don't create new groups.

## 10. Code style

- Minimal comments. The user wants to read and edit this themselves. Comments only when the intent isn't obvious from the code.
- PHP follows WordPress coding standards (4-space indent, snake_case functions).
- **Helper namespace is always `aiims_*`** — fixed template convention (AIIMS Group). NOT derived from the theme slug. Examples: `aiims_img()`, `aiims_svg_kses()`, `aiims_image_dimensions()`. Any project-specific helpers, CPT registrations, or shortcodes follow the same `aiims_*` prefix unless the user explicitly overrides.
- Always escape: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()` for rich text.
- Always guard ACF output: `if ($field) :` / `if (have_rows('x')) :`.
- Prefer `<?= ?>` short echo tags inside markup, full `<?php ?>` for logic blocks.

## 11. File structure inside the theme

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

## 12. Skills, agents, commands

These are auto-discovered from `skills/*/SKILL.md`, `agents/*.md`, and `commands/*.md` frontmatter. Open those folders for the current set. Day-to-day, you mostly use:

- `@<name>.md <figma-url>` → `implement-figma-section` (static build)
- "Make <name> dynamic" → `make-section-dynamic` (ACF wire-up)
- `/build <name> <figma-url>` → chained: static + dynamic in one flow
- `/pixel-check [name]` → live-vs-Figma diff
- `/ship-check` → a11y + perf + QA + ACF sync state before deploy

## 13. Slash commands

- `/setup-claude` — first-time bootstrap (self-deletes after success)
- `/build <name> <figma-url>` — chained: implement → pause for approval → make-dynamic → optional seed
- `/implement <section-brief.md> <figma-url>` — static section build only
- `/make-dynamic <section-name>` — convert static to ACF
- `/upload-images <section-name>` — upload static images to WP media library, return IDs, delete static folder
- `/seed <section-name> <data>` — one-shot self-deleting populator for ACF fields
- `/cleanup-section <section-name>` — production sweep (strip dev comments, validate ACF refs)
- `/add-section <section-name>` — register a new flexible layout (no markup)
- `/create-template` — new page / archive / single / search / 404 / taxonomy template
- `/pixel-check [section-name]` — compare live render to Figma (includes typography drift detection)
- `/ship-check` — pre-deploy: a11y + perf + QA + ACF JSON sync state

## 14. What NOT to do

- Don't build a full page in one shot.
- Don't go straight to ACF — static phase first, always.
- Don't write SCSS or any non-Tailwind CSS unless Tailwind can't do it.
- Don't `display: none` mobile content — restructure or accordion instead.
- Don't silently round container widths or spacings — surface deviations.
- Don't use static image paths in dynamic ACF sections — `aiims_img()` is only for theme-wide static assets like logos.
- Don't regenerate ACF `key` values when editing an existing field — preserve them.
- Don't skip escaping or `width`/`height` on `<img>` (CLS).
- Don't add features the user didn't ask for (custom post types, etc.).

## 15. When in doubt

Ask. The user prefers honesty over guessing. Examples:

- "Spacing in Figma is 73px — `py-[72px]` (4.5rem) or `py-20` (80px)?"
- "Figma uses #2A4D8B for this CTA, no matching brand token. Add as `--color-cta` or use arbitrary `[#2A4D8B]`?"
- "No mobile Figma — build mobile defaults from these guesses, or wait?"
