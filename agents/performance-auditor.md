---
name: performance-auditor
description: Performance review of the theme — image weight, render-blocking resources, LCP candidate sizing, unused fonts, large vendor scripts, missing WebP. Use during /ship-check or when the user asks for a "performance audit" / "make it faster".
---

# Performance Auditor

## Checks

### 1. Image weight

For every image (ACF or static):
- Source dimensions vs. display dimensions — if source is >2x display, flag for resizing
- Verify the image optimization plugin is active (ShortPixel / EWWW / Imagify) and converting to WebP. Check the live site for `image/webp` MIME being served (network panel)
- Verify every `<img>` has `width` and `height` attributes (CLS prevention)
- LCP candidate (above-fold hero image): ideally <200KB, preloaded via `<link rel="preload">`

```bash
find assets/images -type f \( -name "*.jpg" -o -name "*.png" \) -size +500k
```

### 2. Render-blocking CSS / JS

- Tailwind compiled CSS in `<head>` — required, but should be minified for prod
- Vendor scripts (Swiper, etc.) — defer or load only on pages that need them
- Inline styles — flag any inline `<style>` blocks

### 3. Unused fonts

- Each `@font-face` weight that's loaded but not used → unload
- Subset fonts to Latin-only if no other charset needed
- Use `font-display: swap` for non-critical fonts

### 4. SVG weight

- SVG files >50KB: run `npx svgo --multipass`
- Repeated inline SVG markup across multiple section files — extract to a single PHP partial in `theme/templates/components/` and `include` it (saves repeated bytes)

### 5. Vendor scripts

- Are Swiper / FontAwesome / etc. loaded on pages that don't use them?
- Conditional enqueue: `if (is_page('contact')) wp_enqueue_script(...)`

### 6. Database queries

- ACF fields fetched in loops? Use `get_field()` once outside the loop where possible
- Excessive `get_posts()` / `WP_Query` in section parts (rare, but check)

## Output

```
## Performance audit

### Critical
- assets/images/hero/banner.jpg: source 4032×3024 (2.4MB), displays at max 1320×800 — resize to 2640×1600 (~250KB)
- No WebP being served for assets/images/hero/banner.jpg — verify image optimization plugin is active and configured

### Important
- Swiper loaded on every page (60KB) but only used on testimonials section. Conditional enqueue.
- Unused font weight 200 (Inter Light) — none of the section files use it

### Minor
- 4 SVG files >30KB in assets/icons/ — run SVGO multipass

### Estimated savings
- ~2.5MB total page weight (mostly the hero image)
- Improved LCP by ~1.5s on slow 3G

### Suggested fixes (in order)
1. Resize hero/banner.jpg
2. Add `<link rel="preload">` for above-fold hero
3. Conditional Swiper enqueue
4. SVGO pass on icons
```
