---
name: setup-claude
description: One-time project setup. Auto-detects theme slug, project name, local URL, author from the directory + style.css. Asks only 2-3 quick optional questions (Figma URL, anything weird). Copies helpers + templates + ACF JSON snippets into the theme, updates Tailwind brand tokens, creates briefs/, generates a personalized workspace-root README.md, then deletes its own setup files. Run this FIRST on every new project. Triggers on "/setup-claude", "set up the project", "bootstrap claude", "first-time setup".
---

# /setup-claude — first-time project setup

## When to run

- First time on a new project, after you've already:
  - Generated the underscoretw theme
  - Activated it
  - Run `npm install`
  - Copied `claude-setup/` into the theme and renamed to `.claude/`
- Once per project — this skill self-deletes after success

## What gets auto-detected (no questions)

Run these reads silently before asking the user anything:

```bash
# Working directory = theme root
THEME_SLUG=$(basename $(pwd))                          # e.g., "aiims"

# Workspace root path components
PROJECT_DIR=$(basename $(dirname $(dirname $(dirname $(pwd)))))   # e.g., "jgvertical"
                                                                  # path is .../<project>/wp-content/themes/<theme>/

# Local URL (Laragon convention)
LOCAL_URL="http://${PROJECT_DIR}.test"

# Display name from project slug (smart split + title case)
# jgvertical -> "JG Vertical"  (try ALLCAPS prefix detection)
# acme-roofing -> "Acme Roofing"
# rope-access-sydney -> "Rope Access Sydney"
DISPLAY_NAME=$(echo "$PROJECT_DIR" | sed 's/-/ /g' | awk '{for(i=1;i<=NF;i++) $i=toupper(substr($i,1,1)) substr($i,2)}1')
# (special-case: if first word is 2-3 lowercase letters, uppercase the whole word — likely an acronym like "jg")

# Theme details from style.css header (theme/style.css for underscoretw)
THEME_NAME=$(grep -i "^Theme Name:" theme/style.css | sed 's/.*: *//')
AUTHOR=$(grep -i "^Author:" theme/style.css | sed 's/.*: *//')
AUTHOR_URI=$(grep -i "^Author URI:" theme/style.css | sed 's/.*: *//')
THEME_VERSION=$(grep -i "^Version:" theme/style.css | sed 's/.*: *//')
```

**Note on helper namespace:** the template ships with `aiims_*` helpers (`aiims_img`, `aiims_svg_kses`, `aiims_image_dimensions`) as a fixed AIIMS Group convention. `setup-claude` does NOT rewrite these to the theme slug — the helpers stay `aiims_*` regardless of theme prefix. This is documented in CLAUDE.md section 9.

Example — for a project at `<workspace>/jgvertical/wp-content/themes/aiims/` you'd auto-detect:
- Theme slug: `aiims`
- Project slug: `jgvertical`
- Display name: `JG Vertical` (after smart-split)
- Local URL: `http://jgvertical.test` (Laragon convention; for non-Laragon dev environments, ask the user)
- Author: `AIIMS Group`

If the smart-split looks wrong (e.g., it produces `Jgvertical` instead of `JG Vertical`), Claude can ask the user to correct just the display name.

## The questions (only 2-3, all optional)

```
I auto-detected:
- Project name: JG Vertical
- Theme slug:   aiims
- Local URL:    http://jgvertical.test
- Author:       AIIMS Group

A few quick optional questions:

1. Project name correct? (yes / type the right name)
2. Production URL? (e.g., https://jgvertical.com.au — or "TBC" if not yet known)
3. Figma file URL? (paste it and I'll auto-detect brand colors, font, container width)
   (or skip — I'll use placeholder colors and you can update later)
4. Anything special? (e.g., "phone is US, not AU", "container is 1920 fluid",
   "needs RTL", "Swiper enabled by default"). Or "no" / "default".

Defaults applied unless you say otherwise:
- Mobile→desktop break: lg: (1024px)
- Phone format: AU (Australian)
- Swiper: off
- Container: 1320px (overridden by Figma master frame width if you give one)
```

If user says "yes / no / yes" or just pastes a Figma URL → done. Move to setup.

## Workflow

### 1. Detect theme structure

Works in both **Cowork** (bash sandbox, paths under `/sessions/.../mnt/<workspace>/`) and **Claude Code** (host shell, paths relative to project root). The user should have already selected their theme directory as the workspace before running this skill.

**Preferred (cross-platform):** use the Glob/Read tools — no bash needed, identical behavior on both platforms.

```
Glob: theme/style.css        → if found → IS_UNDERSCORETW=true
Glob: style.css              → if found at root → IS_UNDERSCORETW=false
```

**Bash fallback** (still works in both, just uses shell):

```bash
test -f theme/style.css && IS_UNDERSCORETW=true
test -f style.css && test -f functions.php && IS_UNDERSCORETW=false
```

For underscoretw: WP files at `theme/`, Tailwind tokens at `tailwind/tailwind-theme.css`.
For standard: WP files at workspace root, Tailwind tokens at `assets/css/source/style.css`.

> **Cowork note:** the sandbox's bash sessions are independent (no cwd carry-over). When chaining steps that depend on each other (e.g. detect → cp), put them in a single bash call or rely on Read/Write tools which are stateless and identical across platforms.

### 2. If Figma URL provided — auto-detect brand tokens

Call Figma MCP (probe for availability first — see INSTALL-MCPS.md):

```
mcp__Figma__get_metadata
mcp__Figma__get_variable_defs
mcp__Figma__get_design_context
mcp__Figma__get_screenshot
```

If `mcp__Figma__*` tools aren't available, do NOT proceed to auto-detect brand tokens from a screenshot — that would seed wrong values into Tailwind's `@theme` block and propagate inconsistency to every section build. Instead, fall through to step 3 (placeholder colors) and tell the user:

> "The Figma MCP isn't connected, so I can't auto-detect your brand colors and container width right now. I'm using placeholders so you can finish setup, but as soon as you connect Figma (Cowork: Settings → Connectors → Figma. Claude Code: `claude mcp add figma`), run `tailwind-theme-sync from <figma-url>` and I'll pull the real tokens."

Setup continues with placeholders. Real values get filled in later, once Figma is available. This is consistent with CLAUDE.md §4 (Figma as source of truth — NON-NEGOTIABLE).

Extract:
- **Primary** — most-used CTA / brand color (variable names with "primary", "brand", "main")
- **Secondary** — heading text (often dark slate / near-black)
- **Accent** — secondary CTA / link
- **Text** — body copy (#4-5xxxxx range)
- **Font** — primary font family
- **Container width** — master frame width → derive container ~75-90% of frame width
  - Master 1920 → container 1320 or 1440
  - Master 1440 → container 1280 or 1320
  - Master 1280 → container 1200

Confirm with user inline:

```
From your Figma:
- primary:   #2563EB
- secondary: #181B22
- accent:    #00AEEF
- text:      #4D4B50
- font:      Manrope
- master frame: 1920px → suggesting container 1320px

Look right? (yes / change anything)
```

### 3. If no Figma — use placeholders

```
--color-primary:   #2563EB    /* placeholder */
--color-secondary: #1E293B
--color-accent:    #0EA5E9
--color-text:      #475569
--font-brand: 'Inter', system-ui, sans-serif
```

Container: 1320px. Font: Inter. Tell the user: "Brand tokens are placeholders. Once Figma is shared, type 'tailwind-theme-sync from <figma-url>' and I'll pull real values."

### 4. Pre-flight checks

```bash
test -f .claude/snippets/helpers.php
test -f .claude/snippets/acf-setup.php
test -f .claude/snippets/template-homepage.php
test -f .claude/snippets/template-default.php
test -f .claude/snippets/section-_example.php
test -f .claude/snippets/group_default_template_sections.json
test -f .claude/snippets/README.template.md
test -f .claude/snippets/_brief-template.md     # if you ship one separately

! test -f <theme-base>/inc/helpers.php   # would mean re-run
```

If any check fails, STOP and ask the user.

### 5. Copy snippets

For underscoretw:

```bash
THEME=theme

mkdir -p $THEME/inc $THEME/templates/parts $THEME/acf-json $THEME/assets/images $THEME/assets/icons

cp .claude/snippets/helpers.php           $THEME/inc/helpers.php
cp .claude/snippets/acf-setup.php         $THEME/inc/acf-setup.php
cp .claude/snippets/custom-functions.php  $THEME/inc/custom-functions.php
cp .claude/snippets/template-homepage.php $THEME/templates/template-homepage.php
cp .claude/snippets/template-default.php  $THEME/templates/template-default.php
cp .claude/snippets/section-_example.php  $THEME/templates/parts/section-_example.php
cp .claude/snippets/group_default_template_sections.json $THEME/acf-json/group_default_template_sections.json
```

**Important:** the seed JSON's `modified` timestamp is set to the current unix time so ACF Pro shows "Sync available" notice immediately on first WP Admin load.

```bash
# Bump timestamp on the seed
TS=$(date +%s)
sed -i "s/\"modified\": [0-9]*/\"modified\": $TS/" $THEME/acf-json/group_default_template_sections.json
```

### 5b. Apply phone regex for project country

`snippets/acf-setup.php` ships with an AU regex inside markers `PHONE_REGEX_START` / `PHONE_REGEX_END`. If the user's "anything special" answer mentioned a non-AU country, swap the block between those markers using this lookup table.

| Country | `$pattern` | `$message` |
|---|---|---|
| AU (default) | `/^(0[2-9]\d{8}|1[38]00\d{6})$/` | `Please enter a valid Australian phone number.` |
| US / CA | `/^(\+?1)?[2-9]\d{9}$/` | `Please enter a valid US/Canadian phone number.` |
| UK | `/^(0|\+44)(7\d{9}|[12]\d{8,9})$/` | `Please enter a valid UK phone number.` |
| PH | `/^(0|\+63)(9\d{9}|2\d{7,8}|[3-8]\d{6,8})$/` | `Please enter a valid Philippine phone number.` |

Use the Edit tool to replace the AU block between the markers. Keep the markers in place — they're how future re-runs find the block.

If the user names a country not in the table, ask them for the regex + error message, then apply the same edit.

### 6. Wire functions.php — single-require pattern

**Don't append multiple requires to `functions.php`.** Keep the underscoretw upstream clean. Instead:

**a) Create `<theme-base>/inc/custom-functions.php`** — this becomes the central hub for all project-specific PHP. Helpers, ACF setup, future CPTs, shortcuts, admin tweaks — everything goes through here.

```php
<?php
/**
 * Custom theme functions for this project.
 *
 * All project-specific PHP additions go through this file.
 * Add new requires below as the codebase grows.
 *
 * functions.php should NOT be edited beyond the single require to this file.
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/acf-setup.php';

// Future additions go here, e.g.:
// require_once __DIR__ . '/cpt-projects.php';
// require_once __DIR__ . '/shortcodes.php';
// require_once __DIR__ . '/admin-tweaks.php';
```

**b) Append exactly ONE line to `<theme-base>/functions.php`** (after the last existing require):

```php

/**
 * Project-specific custom functions.
 * All additions go in inc/custom-functions.php (don't edit this file further).
 */
require get_stylesheet_directory() . '/inc/custom-functions.php';
```

`get_stylesheet_directory()` is used here instead of `get_template_directory()` so child themes can later override `inc/custom-functions.php` if needed. In a parent-only setup both functions return the same path — there's no downside to the more permissive choice.

When future skills generate new function files (e.g. `/create-template` registering a CPT), they go in `theme/inc/<name>.php` and get added as a `require_once` line inside `custom-functions.php` — never directly into `functions.php`.

### 7. Update Tailwind brand tokens

Open `tailwind/tailwind-theme.css` (underscoretw) or `assets/css/source/style.css` (standard). Replace the existing `--color-*` lines inside `@theme {` with the detected/placeholder values:

```css
@theme {
    --color-primary:   <COLOR>;
    --color-secondary: <COLOR>;
    --color-accent:    <COLOR>;
    --color-text:      <COLOR>;

    --font-brand: '<FONT>', system-ui, sans-serif;

    /* preserve existing Tailwind defaults */
}
```

If `tailwind-theme.css` uses `var(--wp--preset--color--primary)` etc. (underscoretw default), replace with hex values.

### 8. Create briefs/ and brief template

```bash
mkdir -p briefs
```

Write `briefs/_template.md`:

```markdown
# Section brief — <Section Display Name>

## Slug
- File: `<section-slug>` → produces `theme/templates/parts/section-<section-slug>.php`
- ACF layout: `<section_slug>` (snake_case)

## Template owner
- [ ] Homepage (rigid section)
- [ ] Default flexible (one of many layouts)

## Figma frames
- Desktop: <url>
- Mobile (if separate): <url>

## Background
- bg-white / bg-[var(--hero-bg)] / image with overlay

## Content blocks
- Heading
- Subheading / kicker
- Body
- Image(s)
- CTA(s)
- Cards / repeater

## Behavior
- Sticky? Modal? Animation?

## Special notes
- Anything unusual
```

### 9. Generate workspace-root README.md

Read `.claude/snippets/README.template.md`. Replace placeholders:

| Placeholder | Replace with |
|---|---|
| `{{PROJECT_NAME}}` | Display name (auto-detected, user-confirmed) |
| `{{LOCAL_URL}}` | Auto-detected local URL |
| `{{PROD_URL}}` | Production URL from question 2, or `TBC` if user said skip |
| `{{THEME_SLUG}}` | Theme folder slug |
| `{{COLOR_PRIMARY/SECONDARY/ACCENT/TEXT}}` | From Figma or placeholders |
| `{{FONT}}` | From Figma or "Inter" |
| `{{CONTAINER}}` | From Figma master frame ratio or 1320 |
| `{{BREAKPOINT}}` | `lg:` |
| `{{BREAKPOINT_PX}}` | `1024px` |
| `{{PHONE_COUNTRY}}` | `AU` (or override from "anything special") |
| `{{PHONE_DISPLAY}}` | `1300 000 000` |
| `{{FIGMA_URL}}` | The Figma URL (or "TBC") |

After substitution, grep the generated `README.md` for any remaining `{{` to catch placeholders that weren't mapped. None should remain — if any do, replace them with `TBC` and surface to the user.

If a `README.md` already exists (underscoretw default), confirm before overwriting.

### 10. Self-delete setup files

```bash
rm -rf .claude/snippets/
rm -rf .claude/skills/setup-claude/
rm -f .claude/commands/setup-claude.md
rm -f .claude/README.md
```

**Important:** keep `.claude/cheatsheet/` — that's the user's daily reference, not a setup file.

(Request `mcp__cowork__allow_cowork_file_delete` permission if needed.)

### 11. Print success summary

```
## ✓ Setup complete for <Project Name>

### Auto-detected
- Project: <name>
- Theme: <slug>
- Local URL: <url>
- Author: <from style.css>

### Brand tokens applied
- primary:   #...
- secondary: #...
- accent:    #...
- text:      #...
- font:      ...
- container: ...px

### Files created
- theme/inc/helpers.php
- theme/inc/acf-setup.php
- theme/templates/template-homepage.php
- theme/templates/template-default.php
- theme/templates/parts/section-_example.php   (reference)
- theme/acf-json/group_default_template_sections.json   (synced now)
- briefs/_template.md
- README.md (workspace root)

### Updated
- theme/functions.php   (helpers + acf-setup loaded)
- tailwind/tailwind-theme.css

### WordPress admin steps (manual — do these now)
1. Settings → Permalinks → Post name → Save
2. Pages → Add New → "Home" → Page Attributes → Template "Homepage" → Publish
3. Settings → Reading → Static page = "Home"
4. Custom Fields → Field Groups → "Sync changes" (one notice expected)

### Daily workflow
Open README.md anytime. First section:
1. Make sure `npm run watch` is running
2. Write briefs/home-hero.md (copy from briefs/_template.md)
3. @briefs/home-hero.md <figma-url>
4. /pixel-check home-hero
5. Make home-hero dynamic
6. WP Admin → ACF → Sync changes
7. Edit home page → fill ACF fields

### Reference
- 📖 **Visual cheatsheets:** open `.claude/cheatsheet/index.html` in a browser → pick between:
  - `cowork.html` — hand-holding mode for non-developers
  - `code.html` — full reference for developers
- Project rules: `.claude/CLAUDE.md`
- MCP install (if a tool stops working): `INSTALL-MCPS.md`
```

## ACF JSON sync — the timestamp mechanism (CRITICAL)

Every time the `make-section-dynamic`, `add-flexible-layout`, or `acf-architect` writes to `acf-json/<group>.json`:

1. Update the field changes
2. Set `"modified": <current-unix-timestamp>` on the parent group
3. Save

ACF Pro reads JSON file mtime + the `modified` field. If either is newer than the database version of the group, "Sync changes" appears in WP Admin → Custom Fields → Field Groups.

The `modified` field is THE mechanism. Without bumping it, the user won't see the sync notice.

The seed JSON file (`group_default_template_sections.json`) gets its `modified` set to current time during setup so the user sees a sync notice on first WP Admin load.

## Idempotency

If `/setup-claude` runs after success → skill files don't exist (self-deleted), command isn't available.

If user manually re-installs `claude-setup/` and runs again → pre-flight checks catch existing files and stop.

## Things you must never do

- Overwrite existing `theme/functions.php` content (only append)
- Overwrite an existing workspace-root `README.md` without asking
- Skip the pre-flight checks
- Forget to bump the ACF JSON `modified` timestamp on the seed group
- Self-delete BEFORE confirming all writes succeeded — if step 5-9 fail, leave setup-claude in place
