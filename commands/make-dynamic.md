---
description: Convert an approved static section to ACF (Flexible Content layout or homepage field group)
argument-hint: <section-name>
---

Run the `make-section-dynamic` skill on section `$1`.

Workflow:
1. Verify `templates/parts/section-$1.php` exists and is in static state (no `get_field()` calls yet)
2. Decide path:
   - If section is a homepage section → Path B (standalone field group, attached to front_page)
   - If section is meant for flexible content on the default template → Path A (layout in `flexible_sections`)
   - Ask the user if not clear from the section brief
3. Hand off to `acf-architect` agent to design fields and write JSON
4. Replace inline markup with `get_sub_field()` / `get_field()` + escapes + conditional guards
5. Bump `modified` timestamp
6. Reply with sync instructions: "WP Admin → Custom Fields → Field Groups → Sync changes"

Pre-checks before proceeding:
- ☐ Static section pixel-approved by the user
- ☐ `acf-json/` folder exists and is writable
- ☐ ACF Pro is active
- ☐ For Path A: parent flexible_sections group JSON exists

If any pre-check fails, stop and report the issue.
