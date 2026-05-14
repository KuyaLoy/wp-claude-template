---
name: responsive-build
description: Add mobile-first responsive behavior to a section that was built from desktop-only Figma. Triggers on "make this responsive", "add mobile pass", or auto-runs after implement-figma-section when only one Figma URL was given. Reads project breakpoints, plans mobile layout, surfaces guesses for confirmation.
---

# Responsive Build (desktop-only → mobile-first)

When the user gives only a desktop Figma frame, we still need a working mobile experience. This skill plans and applies a mobile-first responsive pass with documented guesses.

## Workflow

### 1. Read project breakpoints

From the workspace README: primary mobile↔desktop break (commonly `lg:` 1024px) and any custom breakpoints.

### 2. Inspect the desktop markup

Read the section's desktop markup. For each block, decide:

| Desktop pattern | Mobile default |
|---|---|
| 2-3 column grid | Stack vertically |
| Image left, text right | Stack: image first, then text |
| Hero with overlay text | Reduce padding, smaller heading scale |
| Card row with horizontal padding | Card stack with vertical gaps |
| Sticky desktop nav | Hamburger trigger (already in header) |
| Container `max-w-[1320px] px-12` | Same container, mobile padding `px-4 sm:px-6` |
| Heading `text-5xl` | Mobile `text-3xl md:text-4xl lg:text-5xl` |
| Section padding `xl:py-[120px]` | `py-12 sm:py-16 md:py-20 lg:py-24 xl:py-[120px]` |

### 3. Apply mobile-first classes

Rewrite the markup with mobile defaults, then layer up:

```html
<!-- Before (desktop-only) -->
<div class="grid grid-cols-3 gap-12">

<!-- After (mobile-first) -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8 lg:gap-12">
```

### 4. Surface every guess

```
## Mobile guesses (no mobile Figma provided)

- Stacked the 3-card grid on mobile (single column, gap-6).
- Reduced heading from text-5xl to text-3xl md:text-4xl lg:text-5xl on mobile.
- Image stacks above text on mobile (was right side on desktop).
- Reduced section padding from py-[120px] to py-12 on mobile.

Confirm before approving.
```

### 5. Touch targets + readability

- Buttons / links / interactive: minimum 44×44px tap target → `px-4 py-3` or `min-h-[44px]`
- Body text on mobile: at least `text-base` (16px) — no smaller
- Line length: cap reading width with `max-w-prose` or `max-w-[65ch]` if a paragraph is wide

### 6. Don't hide content

Avoid `hidden md:block` to remove things on mobile. If a piece doesn't fit mobile, restructure (different order, smaller version, accordion). The exception: secondary decoration that's purely visual.

## Reply format

```
## Responsive pass: <section name>

### Changes
- Grid 3-col → 1-col on mobile (`grid-cols-1 md:grid-cols-2 lg:grid-cols-3`)
- Heading scale `text-3xl md:text-4xl lg:text-5xl`
- Stacked image/text on mobile

### Mobile guesses (need confirmation)
- [list]

### Verified
- Touch targets ≥ 44px
- No content hidden via `display:none`
- Body text ≥ 16px on mobile
```
