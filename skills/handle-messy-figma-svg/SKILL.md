---
name: handle-messy-figma-svg
description: Repair or rework SVG exports from Figma that are truncated, oversized, broken paths, or use hard-coded fills when currentColor is needed. Triggers on "the SVG is broken", "Figma SVG is huge", "fix the SVG export", or auto-runs when implement-figma-section detects SVG issues.
---

# Handle Messy Figma SVG

Figma SVG exports are unreliable. Common issues + fixes.

**Per CLAUDE.md §8 (asset fidelity — NON-NEGOTIABLE):** the goal is always to keep the asset as SVG. Rasterize to PNG only as a last resort when the SVG is genuinely larger than a compressed JPEG (rare — hand-drawn illustration territory), and even then surface the trade-off to the user before doing it.

## Issue: Truncated path

Symptom: SVG copy-pasted from Figma cuts off mid-path, missing closing `</svg>`.

Fix:
1. Drop the unfinished `<path d="...">` element
2. Append `</svg>` if missing
3. Tell the user what's lost ("the bottom curve of icon X is missing — re-export from Figma")

## Issue: Oversized PNG export from Figma

Symptom: Figma exports a 3402×1063 PNG for a logo that displays at 56px tall.

Fix:
1. Resize source to ~2x display size (so retina is fine)
2. Save resized PNG to `assets/images/<section>/<file>.png` (or `assets/icons/<file>.svg` if it's actually an SVG)
3. Render via `aiims_img()` (static) or as `<img>` with width/height (ACF). The image optimization plugin handles WebP.
4. Document in the section reply: "resized 3402×1063 → 400×125, savings ~XX KB"

If `npx svgo --multipass` is available and the asset is actually an SVG, run it through SVGO:

```bash
npx svgo assets/images/<section>/<file>.svg --multipass
```

## Issue: Hard-coded fills when text-color theming needed

Symptom: SVG has `fill="#181B22"` but should follow the parent text color (theme via Tailwind classes).

Fix:
1. Open the SVG file
2. Replace `fill="#xxxxxx"` with `fill="currentColor"` on the path(s) that should follow text color
3. Save
4. In the section markup:
   - For ACF textarea SVG (paste markup): wrap in a parent with `text-*` class, use `wp_kses($svg, aiims_svg_kses())`
   - For static SVG (theme-wide): paste the `<svg>` markup directly inline in the template, parent gets `text-*` class

If only some paths should follow text color (e.g., logo with two colors), only swap those specific paths.

## Issue: Massive raw SVG (200KB+)

Symptom: Hand-drawn SVG with thousands of points, file is 200KB+.

Fix:
1. Run SVGO multipass:
   ```bash
   npx svgo <file>.svg --multipass
   ```
2. Typical savings 65-75%.
3. If still too large after SVGO, consider rasterizing at the display size — sometimes a compressed JPEG/WebP is smaller than vector for complex illustrations.

## Issue: SVG uses inline `<style>` blocks

Symptom: `<style>...</style>` inside the SVG with class selectors.

Fix:
- For inline SVG use (markup directly in template, or via ACF textarea), the styles will work but may collide with other inline SVGs on the page.
- Convert critical fills/strokes to inline attributes (`fill="..."`, `stroke="..."`) and remove the `<style>` block.
- Run SVGO with `--multipass` which can do some of this conversion automatically.

## Reply format

```
## SVG fixed: <icon name>

### Issues found
- Truncated path (last 18 lines were missing)
- Oversized: 3402×1063 → 400×125
- Hard-coded fill="#181B22" → swapped to currentColor on logo body

### File
assets/icons/<name>.svg (now 4.2 KB, was 218 KB)

### Use it like
Theme-wide static (in `header.php` / `footer.php` / shared component):
```html
<div class="w-8 h-8 text-secondary">
    <svg viewBox="0 0 ..." xmlns="http://www.w3.org/2000/svg">
        <path d="..." fill="currentColor"/>
    </svg>
</div>
```

ACF textarea (editor pasted the cleaned-up SVG):
```php
<div class="w-8 h-8 text-secondary">
    <?= wp_kses(get_sub_field('icon_svg'), aiims_svg_kses()) ?>
</div>
```
```
