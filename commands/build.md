---
description: Chained section build — static implement → pause for approval → make dynamic. The one-shot section workflow.
argument-hint: <section-name> <figma-url> [figma-mobile-url] [inline deviations]
---

# /build — one-shot section workflow

Chains `implement-figma-section` → user approval → `make-section-dynamic` into a single command. Use this when you know the section is approved-by-design and you want to land it end-to-end without typing two prompts.

## Arguments

- `$1` — section name (e.g. `home-hero`). Resolves to `briefs/$1.md` if a brief exists; otherwise the skill asks for owner template + intent.
- `$2` — Figma URL (desktop). Required.
- `$3` — Figma URL (mobile), optional. If present, routes to `match-mobile-desktop`.
- `$4+` — any trailing text is treated as inline deviations and merged additively with the brief's existing notes.

## Workflow

### Step 1 — Run `implement-figma-section`

Pass `$1`, all URLs, and trailing text as inline deviations. The skill:
1. Resolves the brief (auto-prepends `briefs/` if `$1` doesn't already have a path)
2. Runs `read-project-conventions` if not already cached in this session
3. Fetches Figma data via `mcp__Figma__*` tools
4. Routes single-URL → desktop-only build, two-URLs → `match-mobile-desktop`
5. Builds `theme/templates/parts/section-$1.php` with static markup
6. Saves assets to `theme/assets/images/$1/`
7. Wires into `template-homepage.php` `$home_sections` array if it's a homepage section
8. Replies with the standard `implement-figma-section` format including "Deviations from Figma"

### Step 2 — PAUSE for user approval

**Do not auto-continue to dynamic.** After the static build reply, output:

```
Static build complete. Hard-refresh http://<project>.test/ and check:
- Pixel match vs. Figma (run /pixel-check $1 if you want a structured diff)
- Mobile breakpoints behave as expected
- Copy / images / CTAs look right

Reply with one of:
- "looks good" / "approved" / "go dynamic" → I'll wire ACF
- A specific fix → I'll iterate the static
- "stop here" → leave it static, you handle the rest
```

Wait for the user's response. Do not proceed without an explicit go-ahead.

### Step 3 — Run `make-section-dynamic` (on approval)

When the user approves, invoke `make-section-dynamic` on `$1`. The skill:
1. Determines path A (Flexible Content layout) vs path B (homepage rigid group) from the brief or section file owner
2. Hands off to `acf-architect` to design fields + write JSON
3. Replaces inline markup with `get_sub_field()` / `get_field()` + escapes + conditional guards
4. Bumps `modified` timestamp on the parent group
5. Replies with sync instructions

### Step 4 — Offer to seed (optional)

After the sync prompt, ask the user once:

```
Want me to seed real data too? Reply with the content like:

  heading=Welcome, subheading=..., body=..., cta=Get a quote/#quote

Or "skip" to fill in WP Admin manually.
```

If the user provides data, invoke the `seed-data` skill — it writes `theme/inc/seed-$1.php` from `snippets/seeder-template.php` and tells the user to hit `/?aiims_seed=$1` while logged in as admin.

If the user says "skip" / "later" / "manually" → proceed to Step 5 (final reply).

### Step 5 — Final reply

```
## /build complete: $1

### Static
templates/parts/section-$1.php ✓

### Dynamic (ACF)
acf-json/<group-file>.json ✓ — layout `<name>` added/updated, modified bumped

### YOU MUST SYNC
WP Admin → Custom Fields → Field Groups → click "Sync changes"

### Seeded (if Step 4 ran)
theme/inc/seed-$1.php ✓ — hit http://<project>.test/?aiims_seed=$1 as admin to populate.
Or: skipped, fill in WP Admin manually.

### Then
Edit the page → fill in ACF fields → save → frontend renders.
```

## Pre-checks (run silently before Step 1)

- ☐ `briefs/$1.md` exists OR the user provided enough inline context
- ☐ `theme/templates/parts/` is writable
- ☐ `theme/acf-json/` exists and is writable
- ☐ ACF Pro appears active (presence of `acf-json/` is a reasonable proxy)
- ☐ `npm run watch` is mentioned in the reply if not obviously running

If any pre-check fails, stop before Step 1 and tell the user what's missing.

## When to use /build vs separate commands

| Situation | Use |
|---|---|
| Approved design, low-risk section, you trust pixel match will be close | `/build` |
| Complex section, lots of edge cases, want to iterate static for a while | `/implement` then `/make-dynamic` separately |
| Design still moving, ACF schema not yet decided | `/implement` only — defer dynamic indefinitely |
| Re-wiring an existing section after design changes | `/implement` (static refresh) then `/make-dynamic` (skill detects existing layout) |

## Examples

```
/build home-hero https://figma.com/design/abc?node-id=1-2
```

Single desktop URL, no inline deviations. Uses brief at `briefs/home-hero.md` if it exists.

```
/build services-grid https://figma.com/.../1-2 https://figma.com/.../1-3
```

Desktop + mobile URLs → routes through `match-mobile-desktop`.

```
/build services-grid https://figma.com/.../1-2 https://figma.com/.../1-3 use one bg image not per-card, h3 for card titles
```

Desktop + mobile + inline deviations. Merged with `briefs/services-grid.md` Deviations section.
