---
description: Production sweep for a finished section — strip dev comments, validate ACF refs, flag orphaned assets.
argument-hint: <section-name>
---

Run the `cleanup-section` skill for section `$1`.

This is the optional final step in the section workflow, run after seed has populated real content. The skill:

1. Strips dev-only doc comments (`Phase: static (pre-ACF)`, `Source: <figma-url>`) from the section file
2. Removes any leftover yellow scaffold-stub markup
3. Validates every `get_sub_field()` / `get_field()` call references a field that exists in the ACF JSON — flags orphans
4. Reports orphaned static assets at `assets/images/$1/` if the section is now fully dynamic
5. Flags `// TODO`, `// FIXME`, `var_dump()`, `print_r()`, and commented-out code blocks
6. Returns a clean report: what was auto-applied, what needs your decision, what's a hard issue

Section becomes production-deployable after this passes.

## Pre-checks (silent)

- ☐ `theme/templates/parts/section-$1.php` exists
- ☐ Section is dynamic (has `get_sub_field()` / `get_field()`)
- ☐ Section's ACF group exists in `theme/acf-json/`

If section is still static, refuses: "Run `/make-dynamic $1` first."

## What auto-applies (safe)

- Stripping `Phase: static` and `Source: <url>` doc comments
- Removing yellow-stub markup left over from `/add-section`

## What requires your decision

- Deleting `assets/images/$1/` if all images are now ACF (might want to keep as backup)
- Removing unused ACF fields (schema change, may break existing data)
- Removing commented-out PHP blocks (you may want them as reference)

## Example output

```
## Cleanup: home-hero

### ✓ Applied
- Stripped dev doc comments

### ⚠ Needs your decision
- theme/assets/images/home-hero/ still has 4 files. Last static reference removed.
  → Run /upload-images home-hero first if you haven't already, then re-run cleanup.

### 🔴 Issues found
- Line 22: get_sub_field('heading_2') but ACF JSON only has 'heading'. Typo?

### Status
Has 1 critical issue.
```

## Suggested final pre-deploy flow

```
/cleanup-section home-hero       (per section, after seed)
/cleanup-section services-grid   (...)
/cleanup-section about-us        (...)
/ship-check                       (whole-theme audit)
```
