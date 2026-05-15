---
name: read-project-conventions
description: Reads project-specific conventions (brand tokens, fonts, breakpoints, container widths, section padding patterns) from README.md + Tailwind source CSS + functions.php. **Lazy trigger** — runs only before the FIRST build action in a session (frontend-builder, responsive-engineer, acf-architect), not on every session start. Also triggers on "what are the project conventions" / "read the project notes" / when something feels wrong (color drifted, spacing off, font mismatched).
---

# Read Project Conventions

This is the meta-skill every other build skill depends on. **No two WordPress projects share the same brand tokens, fonts, breakpoints, or container widths.** Before doing project-specific work, read what THIS project actually uses.

## When to run (lazy)

- **Before the first build action in a session** — i.e. the first time `frontend-builder`, `responsive-engineer`, or `acf-architect` would be invoked.
- When the user asks "what are the project conventions" / "what brand tokens do we have".
- When something feels wrong (color drifted, spacing off, font mismatched, container width seems wrong).

## What NOT to do

- **Don't auto-run on every session start.** That burns ~2k tokens for nothing if the user is just asking a question or making a tiny edit.
- **Don't re-read within the same session.** Once the conventions summary is in context, the calling skill/agent should reference it from chat history instead of re-invoking this skill. The summary is small and stable for the duration of the session.
- **Don't read project files mid-build** unless the user mentions something has changed (e.g. "I just updated the brand colors"). If they say that, re-run this skill explicitly.

## Cache rule

If your context already contains a recent "Project conventions for <project name>" summary from this session, short-circuit: skip the file reads, return the cached summary. Only re-read when:
- The user explicitly asks to refresh conventions
- The user mentions they updated brand tokens / README / Tailwind theme
- More than ~10 turns of conversation have passed and you're starting a new build action (defensive re-read for long sessions)

## Why this skill exists

Without it, the AI silently assumes:
- The font is whatever it last saw
- The container is 1280px (it might be 1320, 1420, 1920, or something custom)
- The mobile↔desktop break is `lg:` (it might be `xl:` or a custom `1560:`)
- The brand-primary is some default
- Section padding cadence is the last project's pattern

These wrong assumptions produce broken markup that *looks* right at first but drifts from the design system. Reading the project notes takes 5 seconds and prevents an hour of rework.

## Discovery sequence

### 1. Find the project README

```bash
ls README.md
```

The workspace-root `README.md` (written by `/setup-claude`) holds project conventions: brand colors, container, breakpoints, font, phone format, section status tracker.

If `README.md` doesn't exist OR doesn't contain the expected `## Project conventions` section, the project hasn't been bootstrapped. Tell the user to run `/setup-claude` first. **Do not proceed to build any section** without project conventions.

### 2. Read brand tokens from Tailwind source CSS

The theme uses Tailwind 4 with `@theme`. Look for the source CSS file (commonly `assets/css/source/style.css` or `src/style.css`):

```bash
grep -r "@theme" assets/ src/ 2>/dev/null | head -5
```

Then read that block:

```bash
sed -n '/@theme/,/^}/p' <path-to-source-css>
```

Look for:
- `--color-primary` / `--color-secondary` / `--color-accent` / `--color-text`
- `--font-brand` / `--font-body` / `--font-heading`
- `--breakpoint-*` definitions (custom breakpoints — critical)
- `--container-*` (if project defines container variants)
- `--spacing-*` overrides (rare)

### 3. Read the container convention

```bash
grep -E "max-w-\[" templates/ inc/ -r | head -10
grep "\.container" assets/css/ -r | head -5
```

Note from `README.md`:
- The container max-width (e.g., 1280, 1320, 1420, 1920px)
- Whether the project uses Tailwind's `.container` utility or inline `max-w-[Xpx]`
- Padding pattern (e.g., `px-4 sm:px-6 lg:px-8 xl:px-12`)

### 4. Read functions.php and inc/* for theme setup

```bash
ls inc/ 2>/dev/null
```

Look at:
- `inc/theme-setup.php` — theme supports, image sizes, menus
- `inc/enqueue.php` — what scripts/styles are enqueued, vendor libs (Swiper, AOS?)
- `inc/acf.php` — ACF setup, JSON folder location, validation rules
- `inc/helpers.php` — confirm `aiims_img()` and `aiims_svg_kses()` exist (the only helpers — most images use native `<img>`/`<picture>` directly, WebP comes from an image plugin)

### 5. Read README.md mandatory fields

Confirm the user filled in:
- Project / client name + domain
- Brand color hex codes
- Container width
- Mobile↔desktop breakpoint
- Section padding cadence
- Phone format (validation regex)
- Font family

### 6. Inventory existing sections + ACF JSON

```bash
ls templates/parts/section-*.php 2>/dev/null
ls acf-json/*.json 2>/dev/null
```

Note which section parts exist and which Flexible Content layouts are already registered.

### 7. Check homepage template state

```bash
ls templates/template-homepage.php templates/template-default.php 2>/dev/null
```

Confirm both templates exist. If `template-homepage.php` is missing, the project hasn't been bootstrapped fully.

## Output

Print a concise summary the calling agent can use directly:

```
## Project conventions for <project name>

### Brand tokens (from style.css @theme)
- --color-primary:   #F5412C → bg-primary, text-primary
- --color-secondary: #181B22 → bg-secondary, text-secondary
- --color-accent:    #00AEEF → bg-accent
- --color-text:      #4D4B50 → text-text
- --font-brand:      'Manrope', system-ui, sans-serif → font-brand

### Container
- Max-width: 1320px
- Pattern: inline `max-w-[1320px]` with `px-4 sm:px-6 lg:px-8 xl:px-12 mx-auto`

### Breakpoints
- Tailwind defaults plus: --breakpoint-3xl: 1920px, --breakpoint-1560: 1560px
- Mobile↔desktop primary break: lg: (1024px)

### Section padding cadence
pt-12 sm:pt-16 md:pt-20 lg:pt-24 xl:pt-[120px] 2xl:pt-[150px]
pb-12 sm:pb-16 md:pb-20 lg:pb-24 xl:pb-[120px] 2xl:pb-[150px]

### Phone format
- AU /^0[2-9]\d{8}$/
- Display: 1300 000 000

### Vendor / animation
- Swiper: yes (testimonials, gallery)
- AOS: no — using data-reveal IntersectionObserver
- data-reveal variants: default fade-up, "left", "right", "zoom"

### ACF state
- Local JSON: acf-json/ (writable: yes/no)
- Existing flexible layouts: hero, intro_one, content_card, contact, footer
- Existing homepage groups: home_hero, home_services, home_about

### Existing section parts
templates/parts/section-hero.php
templates/parts/section-intro-one.php
templates/parts/section-content-card.php
templates/parts/section-contact.php
templates/parts/section-footer.php
```

## When critical files are missing

| Missing | Action |
|---|---|
| `README.md` | Stop. Offer `setup-claude`. |
| `template-homepage.php` or `template-default.php` | Stop. Offer `setup-claude`. |
| `acf-json/` folder | Create it, ensure writable, document in the workspace README |
| `inc/helpers.php` | Stop. Offer `setup-claude` to install the helper. |
| `@theme` block | Stop. The Tailwind 4 brand mapping is required. |

## Reply format

Either return the summary (above) and yield to the calling skill/agent, OR flag the missing files and stop with a clear request to fix them.

Never proceed to build code with assumed conventions. Always read first.
