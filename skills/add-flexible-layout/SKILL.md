---
name: add-flexible-layout
description: Register a new ACF Flexible Content layout shell for the default template, without building markup yet. Triggers on "add a new section: <name>", "register a flexible layout for <name>", "scaffold a new section type". Use this when the user wants to plan section types ahead of building them, or when a section will be implemented later. Creates the layout entry in acf-json with placeholder fields and a stub templates/parts/section-<name>.php that says "TODO".
---

# Add Flexible Layout (scaffold only)

This is a lightweight skill for when the user wants to **register** a new section type without building it yet. Useful for:

- Planning the site IA before any section markup exists
- Onboarding the marketing team to the available section types
- Reserving the layout name + slug before the design is final

For full build, use `implement-figma-section` then `make-section-dynamic`.

## Workflow

### 1. Confirm name + slug

Ask if not provided:
- Section display label (e.g., "Testimonials")
- Layout name slug (snake_case, used as ACF layout name → `testimonials`)
- File slug (dashes, used as `templates/parts/section-{slug}.php` → `testimonials`)

Default mapping: `Testimonials` → `testimonials` (both name and file).

### 2. Add layout to flexible_sections group

Read `acf-json/group_default_template_sections.json`. Add to `fields[0].layouts`:

```json
{
  "key": "layout_testimonials",
  "name": "testimonials",
  "label": "Testimonials",
  "display": "block",
  "sub_fields": [
    {
      "key": "field_flex_testimonials_placeholder",
      "label": "(scaffold) Will be filled in when section is built",
      "name": "_placeholder",
      "type": "message",
      "message": "Section is scaffolded only. Run `implement-figma-section` and then `make-section-dynamic` to build it.",
      "esc_html": 0
    }
  ],
  "min": "",
  "max": ""
}
```

Bump the parent group's `modified` timestamp.

### 3. Create stub section file

`templates/parts/section-testimonials.php`:

```php
<?php
/**
 * Section: Testimonials
 * Status: SCAFFOLD — not implemented yet
 *
 * To implement:
 *   1. Create briefs/testimonials.md with notes from Figma
 *   2. In Claude: "@briefs/testimonials.md <figma-url>"
 *   3. Then: "Make testimonials dynamic"
 */
?>
<section id="testimonials" class="bg-yellow-50 py-12">
    <div class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <p class="font-mono text-sm text-yellow-900">
            [Testimonials section is scaffolded but not implemented. See section file for next steps.]
        </p>
    </div>
</section>
```

The yellow stub is intentionally ugly so it's obvious in preview that it's unfinished.

### 4. Sync prompt

```
## Layout scaffolded: testimonials

### ACF JSON updated
acf-json/group_default_template_sections.json
- New layout: testimonials (Testimonials)
- Modified timestamp bumped

### Stub file created
templates/parts/section-testimonials.php — yellow placeholder

### YOU MUST SYNC

WP Admin → Custom Fields → Field Groups → Sync changes

### Next steps when ready to build

1. Write briefs/testimonials.md
2. "@briefs/testimonials.md <figma-url>"
3. "Make testimonials dynamic"
```
