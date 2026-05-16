---
description: Compare the live render to the Figma frame and report deviations
argument-hint: [section-name]
---

Run the `pixel-perfect-verify` skill against section `$1` (or all sections if no arg).

Workflow:
1. If `$1` is provided, focus on that section. Else iterate over all section parts.
2. For each section:
   - Read `templates/parts/section-<name>.php`
   - Find the Figma URL in the file header comment or section brief
   - Take a screenshot of the live URL where the section renders
   - Side-by-side compare with the Figma frame
   - Report deviations: spacing, color, typography, positioning, asset quality
3. Categorize:
   - **Critical** — visible breaks, wrong colors, illegible text
   - **Important** — spacing >5px off, wrong font weight, missing animation
   - **Minor** — sub-pixel spacing, anti-aliasing differences

Use the browser MCP for screenshots. If the browser tools are unavailable, fall back to a manual checklist the user can run.

Report back with prioritized fix list.
