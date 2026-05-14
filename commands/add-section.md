---
description: Scaffold a new ACF Flexible Content layout shell (no markup yet)
argument-hint: <section-name>
---

Run the `add-flexible-layout` skill for section `$1`.

This is for **scaffolding** a layout entry without building markup yet. Use when:
- Planning the site IA before designs are final
- Pre-registering layouts so the editor team knows what's coming
- Reserving the slug

Workflow:
1. Confirm display label and snake_case slug with the user (default: `$1` for both)
2. Add layout entry to `acf-json/group_default_template_sections.json` `fields[0].layouts`
3. Bump parent `modified` timestamp
4. Create stub file `templates/parts/section-$1.php` with a yellow "scaffold only" placeholder
5. Output sync instructions

To actually build the section later:
- `@briefs/$1.md <figma-url>` (build static)
- `/make-dynamic $1` (replace stub with real fields + markup)
