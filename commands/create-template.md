---
description: Create a new WordPress template (page / archive / single / search / 404). Asks normal vs flexible.
---

Run the `create-template` skill.

Walks the user through:
1. Kind: page / archive / single / search / 404
2. Style: normal (rigid) or flexible (ACF Flexible Content)
3. Scope: post type for archive/single, name for page templates
4. Generates the PHP file at the right path with proper WP template hierarchy + (if flexible) the ACF JSON field group

After creation, reminds the user:
- For page templates: select the new template from the page's "Template" dropdown
- For archive/single: works automatically based on file naming
- For flexible: WP Admin → Custom Fields → Field Groups → Sync changes
