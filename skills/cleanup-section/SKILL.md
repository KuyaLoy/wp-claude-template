---
name: cleanup-section
description: Production sweep for a finished section. Strips dev-only doc comments (`Phase: static (pre-ACF)`, `Source: <figma-url>`), deletes orphaned static assets at `assets/images/<slug>/` if no static references remain, validates every `get_sub_field()` / `get_field()` call points at a real ACF field, and reports any dead code. Triggers on "/cleanup-section <name>", "clean up <section>", "make <section> production-ready". Run AFTER seed has populated real content; section becomes deployable.
---

# Cleanup Section (production sweep)

The final pass on a section. After static → cross-check → dynamic → sync → seed (and optionally upload-images), this skill makes the section production-ready by removing dev artifacts and validating that nothing's referencing non-existent fields.

## When to run

- After `/seed` has populated real content
- After `/upload-images` (if the section used static images)
- Before `/ship-check` — clean each section first, then run the full pre-deploy audit
- When you re-open an old section and aren't sure what's still dev-only

## What it does (checklist)

### 1. Strip dev-phase doc comments

The static phase added a header like:

```php
<?php
/**
 * Section: Home Hero
 * Phase: static (pre-ACF)
 * Source: https://www.figma.com/design/abc?node-id=1-2
 */
?>
```

After dynamic, that comment is stale. Replace with a production-clean header:

```php
<?php
/**
 * Section: Home Hero
 * Layout: home_hero (ACF Flexible Content) OR Homepage field group
 */
?>
```

Keep section name + layout/group reference. Drop "Phase: static", drop "Source: <figma-url>" (the Figma URL belongs in the brief, not in production code).

### 2. Strip yellow scaffold-stub markers (if present)

If `add-flexible-layout` was used and the section was scaffolded before being implemented, there might be leftover yellow placeholder markup like:

```html
<section class="bg-yellow-50 ...">
    <p>[Section is scaffolded but not implemented. See section file for next steps.]</p>
</section>
```

Remove if found. If still present after a real implementation, something went wrong — surface to the user before deleting.

### 3. Validate ACF field references

For every `get_sub_field('foo')` and `get_field('foo')` in the section file:

1. Look up the section's ACF group (`acf-json/group_default_template_sections.json` for flexible layouts, `acf-json/group_<slug>.json` for homepage sections)
2. Find the layout / group fields list
3. Verify every field name referenced in PHP exists in the JSON
4. Flag any orphans:
   - **PHP references field that doesn't exist in JSON** → typo or schema drift. Report. Don't auto-fix; user decides.
   - **JSON has field but PHP doesn't render it** → not necessarily a bug (could be intentional), but worth reporting as "unused field".

### 4. Orphaned static assets

If the section is fully dynamic (no `aiims_img()` calls referencing `assets/images/<slug>/`, no inline image paths), and `theme/assets/images/<slug>/` still exists, it's dead code.

Two paths:
- If `/upload-images` already ran successfully, the folder should be gone. If it's still there, something went wrong with the uploader.
- If `/upload-images` never ran, the static files are still serving — DON'T delete without asking the user. They may have intentionally kept the section static.

Default behavior: report the existence of `assets/images/<slug>/` + ask the user before deleting.

### 5. Check inline SVGs vs ACF textarea SVGs

If the section has hard-coded `<svg>...</svg>` markup directly inline (Pattern C from CLAUDE.md §8), that's fine — theme-wide static.

If the section has hard-coded `<svg>` AND an ACF textarea SVG field exists with no `get_sub_field('icon_svg')` call rendering it, something's inconsistent. Report.

### 6. Dead code patterns

Look for:
- `// TODO`, `// FIXME`, `// XXX` comments — flag for user review
- `var_dump()`, `print_r()`, `error_log()` left over from debugging — flag
- Commented-out blocks of PHP from dev iteration — surface but don't auto-delete

## Workflow

### 1. Pre-checks (silent)

- ☐ `theme/templates/parts/section-<slug>.php` exists
- ☐ Section is dynamic (has at least one `get_sub_field()` / `get_field()`)
- ☐ ACF JSON for the section's group exists

If section is still static, refuse: "Section is still in static phase. Run `/make-dynamic <slug>` first, then come back."

### 2. Run the checklist

Apply each check from above. Build a report.

### 3. Apply safe edits

Auto-apply low-risk edits:
- Strip "Phase: static" + "Source: <url>" doc comments → replace with production header
- Remove yellow scaffold-stub markup if present

DON'T auto-apply:
- Deleting `assets/images/<slug>/` (ask user)
- Removing fields from ACF JSON (schema change — user decides)
- Deleting commented-out code (user may want it)

### 4. Reply with report

```
## Cleanup: <Section Name>

### ✓ Applied
- Stripped dev doc comments (Phase: static / Source: <url>)
- [Anything else auto-applied]

### ⚠ Needs your decision
- theme/assets/images/<slug>/ still has 4 files. Last static reference removed.
  → Delete? Run `/upload-images <slug>` first if you haven't already.
- Field `subheading_secondary` in ACF JSON not referenced in PHP — unused. Remove from JSON?

### 🔴 Issues found
- PHP calls get_sub_field('heading_2') but ACF JSON only has 'heading'. Typo?

### Section status
[Production-ready / Has 1 critical issue / Has 2 minor cleanup decisions pending]
```

### 5. Self-verify

- ☐ No `Source: <figma-url>` remains in the section header
- ☐ No `Phase: static` remains in the section header
- ☐ Every PHP field reference has a matching ACF JSON field (or is flagged)
- ☐ Yellow-stub markup is gone (or flagged for user to remove)
- ☐ Reply lists every change applied + every decision still pending

## Reply format

See above. Three sections: ✓ Applied · ⚠ Needs decision · 🔴 Issues. End with overall section status.

## Things you must never do

- Auto-delete files at `assets/images/<slug>/` without explicit user approval
- Remove fields from ACF JSON without user approval (changes schema, may break existing data)
- Strip comments the user wrote (only strip the ones the template adds: `Phase: static`, `Source:`, yellow-stub markup)
- Run cleanup before the section is dynamic (refuse and ask user to `/make-dynamic` first)
- Delete the brief at `briefs/<slug>.md` — that's a paper trail for retros, leave it
