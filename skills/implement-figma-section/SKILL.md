---
name: implement-figma-section
description: Build a single static section from a section brief + Figma frame URL(s). Triggers on `@<name>.md <figma-url>` (with or without `briefs/` prefix), one or two Figma URLs, and any trailing freeform text as inline deviations. Also triggers on "implement / build / code / recreate this section from <figma-url>". Default entry point for the section-by-section workflow. Always builds STATIC first — no ACF — for pixel-perfect verification before going dynamic.
---

# Implement Figma Section (static phase)

This is the workhorse skill for the section-by-section workflow. It builds ONE section at a time, statically, into `templates/parts/section-{name}.php`. ACF conversion is a separate skill (`make-section-dynamic`).

## Trigger phrases (all token-efficient)

The skill auto-resolves the brief path and parses URLs + trailing text. All of these work:

| You type | Resolves to |
|---|---|
| `@home-hero.md <figma-url>` | brief at `briefs/home-hero.md`, single desktop URL |
| `@briefs/home-hero.md <figma-url>` | same (explicit path also works) |
| `home-hero.md <figma-url>` | same (no `@` prefix needed) |
| `@home-hero.md <desktop-url> <mobile-url>` | both URLs → routes to `match-mobile-desktop` |
| `@home-hero.md <url> <url> use one bg image not per-card` | both URLs + everything after = inline deviations |
| "Implement / build / code the hero: `<figma-url>`" | Same workflow; asks for section name if not obvious |
| "Recreate this Figma frame as section-<name>: <url>" | Same |

**Brief resolution rule:** if the path doesn't start with `/` or `briefs/`, auto-prepend `briefs/`. So `@home-hero.md` and `@briefs/home-hero.md` are equivalent.

**If no brief file exists yet:** ask the user to confirm section name + owner template, then proceed without a brief file (using inline deviations only).

**Inline deviations:** any text after the URL(s) that isn't itself a URL is treated as deviations. It's MERGED additively with the brief's existing "Deviations" or "Special notes" section — both apply. See workflow step 2.5.

## Mental model

> Section-by-section, static-first. No ACF in this phase.

We build the static section so we can verify pixel-perfect markup vs. Figma without ACF complications. Once approved, `make-section-dynamic` converts it.

## Workflow

### 1. Read the section brief (if provided)

```bash
cat briefs/<section>.md
```

The brief tells you:
- Section name (becomes file slug, e.g., `home-hero` → `section-home-hero.php`)
- Which template owns it (homepage-template or default-template flexible layout)
- Container width override (if different from project default)
- Special notes (sticky behavior, modals, scroll behavior)
- Mobile-only / desktop-only flags
- Asset notes ("logo here is theme-wide, others are section-specific")

If no brief exists: ask the user to confirm:
1. Section name
2. Which template (homepage or default-flexible)
3. Anything special about behavior

### 1b. Parse inline deviations from the user's message

After resolving the brief path, scan the rest of the user's message for:

- **URLs** (`figma.com/*` patterns). One URL = desktop only. Two URLs = desktop + mobile (route to `match-mobile-desktop`).
- **Trailing freeform text** (anything that isn't a URL or the brief path): treat as inline deviations.

**Merge rule (additive):** the inline deviations get APPENDED to whatever the brief's "Deviations" or "Special notes" section already says. Both apply. Don't replace.

Example:

```
User: @home-hero.md https://figma.com/.../1-2 https://figma.com/.../1-3 use one bg image not per-card, cards stack on mobile, h3 not h2 for card titles
```

Parsed as:
- Brief: `briefs/home-hero.md` (read existing notes)
- URLs: 2 (routes to `match-mobile-desktop`)
- Inline deviations:
  - use one bg image not per-card
  - cards stack on mobile
  - h3 not h2 for card titles

When building the section, surface BOTH the brief's notes AND the inline deviations in the final reply under "Deviations from Figma" so the user sees what was applied.

### 2. Run `read-project-conventions` skill (lazy)

Run this only if you haven't already pulled project conventions earlier in the session. The skill itself short-circuits if it sees a recent in-context summary — it doesn't re-read style.css and README.md on every call. You need brand tokens, container, breakpoints, padding cadence, font, helpers before writing any markup, but you only need them once per session.

### 3. Fetch Figma via MCP

Call these in parallel for the same node URL:

```
mcp__Figma__get_metadata     → file metadata, design system
mcp__Figma__get_design_context → frame structure, layout, content, text values
mcp__Figma__get_screenshot   → visual reference (note: 1024px max edge)
mcp__Figma__get_variable_defs → design tokens
```

These names work in both Cowork and Claude Code. If `mcp__Figma__*` tools aren't available, the Figma MCP isn't installed — point the user at `INSTALL-MCPS.md`, then ask them to paste a screenshot of the frame inline + the layout intent in plain language so you can still build.

### 4. Map Figma colors/fonts to project brand tokens

| Figma role | Project token | Tailwind class |
|---|---|---|
| Primary brand | `--color-primary` | `bg-primary`, `text-primary` |
| Secondary | `--color-secondary` | `bg-secondary`, `text-secondary` |
| Accent / CTA | `--color-accent` | `bg-accent`, `text-accent` |
| Body text | `--color-text` | `text-text` |
| Heading font | `--font-brand` | `font-brand` |

If Figma uses a color that doesn't match any token, follow the decision rule:
- **2+ uses across the design** → propose adding it to `@theme` and run `tailwind-theme-sync`
- **One-off** → use arbitrary `bg-[#xxx]` and surface in deviations
- **Close to existing** (≤5% RGB delta) → use existing token, surface as judgment call

### 5. Asset handling (static phase)

For images:
- Download via Figma MCP at 2x where possible (note `get_screenshot` caps at 1024px — for hero / above-fold images, ask the user for higher-res exports from Figma's export panel)
- Save to `assets/images/<section-name>/<filename>.png|jpg`
- WebP conversion is handled by the image plugin (ShortPixel / EWWW / Imagify) — don't try to generate WebP in PHP
- Use `aiims_img('section/filename.jpg', 'Alt text', 'tailwind classes')` to render — it reads width/height from disk automatically

For SVGs in static phase:
- If the SVG should follow text color (icons, decorative marks) → paste markup inline directly in the section template (with `fill="currentColor"` swapped on relevant paths) and apply `text-*` Tailwind class on parent
- If the SVG is a brand logo or fixed-color illustration → save to `assets/images/<section-name>/<file>.svg` and render with `aiims_img(...)` (or for theme-wide assets, `assets/icons/<file>.svg`)
- If truncated/malformed from Figma export → invoke `handle-messy-figma-svg` skill

### 6. Decide responsive strategy

- **One Figma URL given (desktop)** → build desktop, then make responsive mobile-first via `responsive-build` skill (or inline if simple)
- **Two URLs given (desktop + mobile)** → invoke `match-mobile-desktop` skill
- **Mobile-only Figma** → build mobile-first, ask user about desktop variant

### 7. Build markup in `templates/parts/section-<name>.php`

Static rules:
- Plain HTML inside the PHP file — no `get_field()`, no `have_rows()`, no ACF
- Hard-coded copy from Figma (the actual text — easier to verify pixel-perfect)
- Image references (static phase): `<?php aiims_img('<section>/<file>.png', 'Alt text', 'tailwind classes'); ?>`
- SVG references (static phase): paste `<svg>...</svg>` markup directly inline with `fill="currentColor"` so parent `text-*` Tailwind class themes it. For fixed-color SVGs, save to `assets/images/<section>/` and use `aiims_img(...)`
- Container, padding, breakpoints from the workspace README
- Tailwind utilities only; arbitrary values flagged in deviations
- Use `data-reveal` (or project's animation system) on key elements
- Each section starts with `<section class="..." id="<section-name>">` for navigation jumps

Template structure:

```php
<?php
/**
 * Section: <Section Name>
 * Phase: static (pre-ACF)
 * Source: <figma-url>
 */
?>
<section id="<section-slug>" class="bg-white py-12 sm:py-16 md:py-20 lg:py-24 xl:py-[120px]">
    <div class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12">
        <h2 class="font-brand text-3xl md:text-4xl lg:text-5xl text-secondary" data-reveal>
            Hard-coded heading from Figma
        </h2>
        <p class="mt-4 text-text" data-reveal>
            Hard-coded body copy from Figma.
        </p>
        <div class="mt-12">
            <?php aiims_img('home-hero/banner.jpg', 'Rope access specialists at work', 'w-full h-auto'); ?>
        </div>
    </div>
</section>
```

### 8. Wire the section into a template

If it's for the **homepage**: add the new slug to the `$home_sections` array in `templates/template-homepage.php` at the correct visual position. The template uses `locate_template($file, false, false)` to resolve each entry, so missing files are silently skipped — no manual `include` line needed.

If it's for the **default flexible content template**: that template already loops over `flexible_sections` and uses `locate_template` to include the right part by layout name. The section is wired automatically once `make-section-dynamic` registers the layout. **In static phase, do NOT add it to `template-default.php` directly** — the user can't preview it on a normal page until it's dynamic. For static preview, temporarily add the slug to `$home_sections` in `template-homepage.php` or to a test page template.

### 9. Self-verify

Before declaring done, re-read your markup against the Figma screenshot:
- Spacing matches (within 2px tolerance or flagged)
- Colors match brand tokens
- Typography (size, weight, line-height) matches
- Image positions match
- Mobile breakpoints make sense

### 10. Tailwind compile reminder

If `npm run watch` is running, Tailwind will pick up the new classes automatically. If not, tell the user to run it.

## Reply format

```
## Implemented (static): <Section name>

### File created
templates/parts/section-<name>.php

### Wired into
templates/template-homepage.php (line X) — homepage section
   OR
(Will be auto-wired once `make-section-dynamic` runs — default-template)

### Assets added
- assets/images/<section>/<file>.jpg (display 800x600, source 1600x1200)
- assets/icons/<icon>.svg (theme-wide)

### Tailwind utilities used
[brief summary or skip if standard]

### Deviations from Figma (judgment calls)
- Figma uses 73px padding-y; used `xl:py-[72px]` (matches token + arbitrary blend)
- Color #2A4D8B used for CTA hover — added as `--color-cta-hover` (appears 4 times)
- Mobile guess: assumed stack vertically with 24px gap (no mobile Figma provided)

### Open questions
- [if any]

### Next steps
1. Run `npm run watch` (if not running) to compile new Tailwind classes
2. Hard-refresh browser at http://<project>.test
3. Pixel-check vs. Figma — say "looks good" or describe what's off
4. When approved: "Make <section-name> dynamic"
```

## Handoff to other skills

- Static is approved → user says "make X dynamic" → `make-section-dynamic`
- Mobile pass needed → user says "make it responsive" or skill auto-runs → `responsive-build` or `match-mobile-desktop`
- Figma SVG broken → `handle-messy-figma-svg`
- New brand color needed → `tailwind-theme-sync`
- Browser-vs-Figma diff → `pixel-perfect-verify`
