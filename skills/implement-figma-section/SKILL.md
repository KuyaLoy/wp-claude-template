---
name: implement-figma-section
description: Build a single static section from a section brief + Figma frame URL. Triggers when the user pastes a Figma URL alongside a section brief reference (e.g., "@briefs/home-hero.md https://figma.com/..."), or asks to "implement", "build", "code", "recreate" a section from a Figma link. This is the default entry point for the section-by-section workflow. Always builds STATIC first — no ACF — for pixel-perfect verification before going dynamic.
---

# Implement Figma Section (static phase)

This is the workhorse skill for the section-by-section workflow. It builds ONE section at a time, statically, into `templates/parts/section-{name}.php`. ACF conversion is a separate skill (`make-section-dynamic`).

## Trigger phrases

- `@briefs/<section>.md <figma-url>` (the canonical pattern)
- "Implement this hero: <figma-url>"
- "Build this section: <figma-url>"
- "Code this design: <figma-url>"
- "Recreate this Figma frame as section-<name>: <figma-url>"

If the user pastes only a Figma URL with no brief reference, ask if they have a brief or want one auto-generated from the Figma frame name + a few questions.

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

### 2. Run `read-project-conventions` skill

Always first. You need brand tokens, container, breakpoints, padding cadence, font, helpers before writing any markup.

### 3. Fetch Figma via MCP

Call these in parallel for the same node URL:

```
mcp__1c83dedb...__get_metadata     → file metadata, design system
mcp__1c83dedb...__get_design_context → frame structure, layout, content, text values
mcp__1c83dedb...__get_screenshot   → visual reference (note: 1024px max edge)
mcp__1c83dedb...__get_variable_defs → design tokens
```

If Figma MCP is not available, ask the user to paste a screenshot of the frame inline + the layout intent in plain language.

### 4. Map Figma colors/fonts to project brand tokens

| Figma role | Project token | Tailwind class |
|---|---|---|
| Primary brand | `--color-primary` | `bg-primary`, `text-primary` |
| Secondary | `--color-secondary` | `bg-secondary`, `text-secondary` |
| Accent / CTA | `--color-accent` | `bg-accent`, `text-accent` |
| Body text | `--color-text` | `text-text` |
| Heading font | `--font-brand` | `font-brand` |

If Figma uses a color that doesn't match any token, follow the decision rule:
- **2+ uses across the design** → propose adding it to `@theme inline` and run `tailwind-theme-sync`
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
- **Two URLs given (desktop + mobile)** → invoke `merge-mobile-desktop` skill
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

If it's for the **homepage**: add `<?php include get_template_directory() . '/templates/parts/section-<name>.php'; ?>` to `templates/template-homepage.php` at the correct position.

If it's for the **default flexible content template**: that template already loops over `flexible_sections` and includes the right part by layout name. The section is wired automatically once `make-section-dynamic` registers the layout. **In static phase, do NOT add it to `template-default.php` directly** — the user can't preview it on a normal page until it's dynamic. For static preview, temporarily include it in `template-homepage.php` or in a test page template.

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
- Mobile pass needed → user says "make it responsive" or skill auto-runs → `responsive-build` or `merge-mobile-desktop`
- Figma SVG broken → `handle-messy-figma-svg`
- New brand color needed → `tailwind-theme-sync`
- Browser-vs-Figma diff → `pixel-perfect-verify`
