---
name: implement-figma-section
description: Build a single static section from Figma frame URL(s). **Brief file is OPTIONAL** — if `briefs/<name>.md` doesn't exist, this skill auto-creates one from chat context + Figma data following `briefs/_template.md`, then builds. Triggers on `@<name>.md <urls>`, `@<name> <urls>` (no extension), bare `<name> <urls>`, "implement / build / code / recreate this section: <url>", or plain English. ONE Figma URL = builds desktop + auto-responsive (tablet/mobile inferred, guesses surfaced). TWO URLs = `match-mobile-desktop` pixel-matches both. Trailing freeform text after URLs = inline deviations, merged additively with the brief. Always builds STATIC first — no ACF — for pixel-perfect verification before going dynamic.
---

# Implement Figma Section (static phase)

This is the workhorse skill for the section-by-section workflow. It builds ONE section at a time, statically, into `templates/parts/section-{name}.php`. ACF conversion is a separate skill (`make-section-dynamic`).

## Trigger phrases (all token-efficient)

The skill auto-resolves the brief path, auto-creates the brief if missing, and parses URLs + trailing text. All of these work:

| You type | Resolves to |
|---|---|
| `@home-hero <url>` | brief at `briefs/home-hero.md` (auto-created if missing), single desktop URL, auto-responsive |
| `@home-hero.md <url>` | same (`.md` extension optional) |
| `@briefs/home-hero.md <url>` | same (explicit path also works) |
| `home-hero <url>` | same (no `@` prefix needed) |
| `@home-hero <desktop-url> <mobile-url>` | both URLs → routes to `match-mobile-desktop`, pixel-matches both |
| `@home-hero <url> <url> use one bg image not per-card` | both URLs + everything after = inline deviations |
| "Build the home hero from this Figma: `<url>`" | Plain English. Same workflow. Auto-extracts name from "home hero" → `home-hero`. |
| "Implement / code / recreate this section: `<url>`" | Asks for section name once if it can't be inferred. |

**Brief resolution rule:** strip `@`, strip `.md`, strip `briefs/` prefix → resolve to `briefs/<remaining>.md`. So `@home-hero`, `@home-hero.md`, `@briefs/home-hero.md`, and `home-hero` all resolve to the same file.

**If no brief file exists yet:** the skill AUTO-CREATES `briefs/<name>.md` from chat context + Figma data, following `briefs/_template.md`. The user does NOT have to manually create a file before triggering a build. This is essential for non-technical users.

**One URL vs two:**
- **One URL** = desktop frame. Claude builds desktop, then auto-makes it responsive (web → tablet → mobile) using sensible breakpoints from the project README. Every mobile assumption is surfaced in the final reply ("Stacked 3-card grid into single column. Reduced heading text-5xl → text-3xl on mobile."). User confirms by previewing, or asks to revise.
- **Two URLs** = desktop + mobile Figma. Claude invokes `match-mobile-desktop` and pixel-matches both frames. No mobile guessing.

**Inline deviations:** any text after the URL(s) that isn't itself a URL is treated as deviations. Merged additively with whatever the brief says — both apply.

## Mental model

> Section-by-section, static-first. No ACF in this phase.

We build the static section so we can verify pixel-perfect markup vs. Figma without ACF complications. Once approved, `make-section-dynamic` converts it.

## Workflow

### 1. Resolve / auto-create the section brief

**Brief files are OPTIONAL but always present after a build.** The skill ensures `briefs/<name>.md` exists by end-of-step-1 — either it was there already, or you just wrote it from chat context. This makes the workflow zero-friction for non-technical users while preserving the paper trail for retros.

**Brief resolution:**

```bash
# Try in this order, use the first that exists:
cat briefs/<name>.md            # canonical
cat briefs/<name>               # without extension
```

The user might type any of these — all resolve to `briefs/<name>.md`:

- `@home-hero.md <urls>`
- `@home-hero <urls>` (no `.md`)
- `home-hero <urls>` (no `@` either)
- "Build the home hero from this Figma: `<urls>`" (plain English)

**Brief exists** → read it. Treat its contents as the source of intent. Continue.

**Brief does NOT exist** → auto-create it from chat context + Figma data. Steps:

1. Read `briefs/_template.md` to see the structure the user has standardized on
2. Populate every field you can infer:
   - **Section name** — from the user's reference (`home-hero`)
   - **Template owner** — ask ONE question if you can't tell from name/context: "Homepage section or flexible layout for inner pages?" (homepage = rigid front-page; flexible = any other page)
   - **Figma URLs** — from the user's message
   - **Content blocks** — from `mcp__Figma__get_design_context` if available (heading, body, image, CTA, cards, etc.)
   - **Behavior** — from the inline text the user provided after the URLs, if any
   - **Deviations** — from inline text marked as deviations, if any
3. Write `briefs/<name>.md` using the populated template
4. Show the user the brief in the reply, very briefly: *"Wrote briefs/home-hero.md with: heading + body + bg image + CTA, default-flexible layout, no special behavior. Building now."*
5. Continue to the build steps below

**If `briefs/_template.md` doesn't exist** (rare — pre-setup project), use a minimal default structure and tell the user to run `/setup-claude` first if appropriate.

**Why auto-create:** non-technical users hate "go create a file first." This skill removes that barrier while still producing a brief file at `briefs/<name>.md` that future sessions / retros / handovers can read. The brief follows the project's own `briefs/_template.md` so it stays consistent across sections.

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
mcp__Figma__get_screenshot   → visual cross-reference only (1024px max edge — never the primary source)
mcp__Figma__get_variable_defs → design tokens
```

**Per CLAUDE.md §4 (NON-NEGOTIABLE):** Figma MCP is the source of truth. `get_design_context` and `get_variable_defs` provide the real values; `get_screenshot` is for visual cross-checking only — never the data source.

**If `mcp__Figma__*` tools aren't available** → STOP. Do not build from a screenshot. Reply with:

```
I can't access Figma right now — the Figma MCP is disconnected.

I won't build from a screenshot because Figma screenshots are downscaled
to 1024px max, which distorts spacing, colors, and font sizes. Sections
built that way drift inconsistently across the project.

Please reconnect Figma:
- Cowork: Settings → Connectors → Figma → reconnect
- Claude Code: run `claude mcp list` to check, or `claude mcp add figma`

Once you've reconnected, say "continue" and I'll resume this build.
```

Wait for the user to confirm reconnection. Do NOT proceed with screenshot, verbal description, or remembered Figma data unless the user explicitly types something like "build from this screenshot only, I accept reduced accuracy." In that override case, flag EVERY screenshot-derived value in the deviations block as "verify when Figma is reconnected".

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

**Per CLAUDE.md §8 asset-fidelity rules:** SVGs stay SVGs, transparency stays transparent, composite frames stay composite.

For images:
- Download via Figma MCP at 2× where possible (note `get_screenshot` caps at 1024px — for hero / above-fold images, ask the user for higher-res exports from Figma's export panel)
- **Preserve transparency.** If the source frame is transparent, export PNG/WebP with alpha. Never bake in a white background — it will visibly break overlay sections. The image-optimization plugin handles WebP conversion; don't try to flatten transparency in PHP.
- **Composite frames stay composite.** If the designer grouped multiple elements into one frame (person + decorative shapes + overlay text as one visual unit), export the whole frame as a single asset. Do not try to extract child layers as separate HTML elements unless the brief explicitly says to.
- Save to `assets/images/<section-name>/<filename>.png|jpg` (use `.png` for transparent assets)
- WebP conversion is handled by the image plugin (ShortPixel / EWWW / Imagify) — don't try to generate WebP in PHP
- Use `aiims_img('section/filename.png', 'Alt text', 'tailwind classes')` to render — it reads width/height from disk automatically

For SVGs in static phase — **keep SVG as SVG**:
- **Never rasterize.** If Figma says it's an SVG, the asset stays an SVG. The only exception is a 200KB+ hand-drawn illustration where a compressed JPEG is genuinely smaller — flag the trade-off and ask the user.
- **Inline first (preferred):** if the SVG should follow text color (icons, decorative marks, simple shapes) → paste the markup directly inline in the section template, swap relevant `fill="#..."` to `fill="currentColor"`, parent gets the `text-*` Tailwind class. This is Pattern C from CLAUDE.md §8.
- **File second (logos, fixed-color illustrations):** save to `assets/images/<section-name>/<file>.svg` and render with `aiims_img(...)`. Use only when theming-by-currentColor isn't needed.
- **Theme-wide static SVGs** (icons used in multiple sections) → `assets/icons/<file>.svg`, rendered via `aiims_img()` or inlined in `header.php`/`footer.php`.
- If truncated or malformed from Figma export → invoke `handle-messy-figma-svg` skill.

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
