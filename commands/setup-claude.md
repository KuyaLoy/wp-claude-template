---
description: One-time project setup — bootstraps helpers, two templates, ACF JSON, and writes a personalized workspace-root README.md. Self-deletes after success.
---

Run the `setup-claude` skill.

This is the FIRST thing to run on a new project. Do not run any other commands or skills before this.

The skill will:
1. Detect theme structure (underscoretw vs. standard)
2. Ask for project info (name, brand colors, font, container width, phone country)
3. Copy snippets into the right locations:
   - `helpers.php` → `theme/inc/helpers.php` (or `inc/helpers.php` for standard themes)
   - `acf-setup.php` → `theme/inc/acf-setup.php`
   - `template-homepage.php` + `template-default.php` → `theme/templates/`
   - `section-_example.php` → `theme/templates/parts/`
   - `group_default_template_sections.json` → `theme/acf-json/`
4. Update `theme/functions.php` to require the new helpers
5. Update Tailwind theme tokens with brand colors (in `tailwind/tailwind-theme.css` for underscoretw)
6. Create `briefs/` folder with `_template.md`
7. Generate the workspace-root `README.md` from `snippets/README.template.md`, filling in the user's project info
8. Self-delete:
   - `.claude/snippets/` folder (all contents copied where they need to be)
   - `.claude/skills/setup-claude/` folder
   - `.claude/commands/setup-claude.md` (this file)
   - `.claude/README.md` (the internal "what's inside" doc)

After success, the user is left with `.claude/` (clean) and a personalized workspace-root `README.md`.

If the skill encounters anything unexpected (theme structure not recognized, snippets missing, helpers already exist with different content), STOP and ask the user how to proceed. Do not silently overwrite.
