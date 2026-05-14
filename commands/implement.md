---
description: Build a static section from a section brief + Figma URL
argument-hint: <brief-file.md> <figma-url>
---

Run the `implement-figma-section` skill with these inputs:

- Section brief: `$1` (a path like `briefs/home-hero.md` — read it for context)
- Figma URL: `$2`

Workflow:
1. Run `read-project-conventions` skill first
2. Read the brief file at `$1`
3. Fetch Figma data via MCP from `$2`
4. Map Figma colors/typography to project brand tokens
5. Hand off to the `frontend-builder` agent to write `templates/parts/section-<name>.php`
6. Asset handling: save images to `assets/images/<section>/`, SVGs theme-wide to `assets/icons/` or section-specific to `assets/images/<section>/`
7. Wire into homepage template if specified, or leave for `make-section-dynamic` if it's a flexible layout
8. If only desktop Figma was given, run `responsive-build` skill to add mobile-first responsive
9. Reply with the standard `implement-figma-section` reply format

This is STATIC PHASE only. Do not add ACF. Do not call `get_field()` or `get_sub_field()`. The user will explicitly trigger phase 2 via "Make <section> dynamic" later.
