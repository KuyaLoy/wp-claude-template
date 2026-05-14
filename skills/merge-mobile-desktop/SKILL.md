---
name: merge-mobile-desktop
description: Build a single responsive section from TWO Figma frames (desktop + mobile). Triggers when the user pastes two Figma URLs together. Maps both frames to one mobile-first markup using project breakpoints, surfaces any irreconcilable differences.
---

# Merge Mobile + Desktop Figma

Use when the user provides both desktop and mobile Figma frames. Output is a single section file with mobile-first responsive utilities.

## Workflow

### 1. Fetch both frames

Run get_design_context, get_screenshot, get_variable_defs on both URLs.

### 2. Compare side-by-side

Build a difference table:

| Element | Desktop | Mobile | Strategy |
|---|---|---|---|
| Heading size | 56px | 32px | `text-3xl lg:text-[56px]` |
| Layout | 2-col grid | Stack | `grid grid-cols-1 lg:grid-cols-2` |
| Image | Right side | Above text | `order-1 lg:order-2` |
| Padding | 120px Y | 48px Y | `py-12 lg:py-[120px]` |
| Hidden element | Decorative shape visible | Removed on mobile | `hidden lg:block` (this exception is OK if the element is decorative) |

### 3. Build mobile-first

Start from the mobile design, layer up to desktop with `lg:` (or whatever the project breakpoint is).

### 4. Flag irreconcilable differences

Sometimes mobile and desktop have different content — e.g., desktop shows 6 cards, mobile shows 3 + "see more". Flag these to the user:

```
## Irreconcilable differences

- Desktop shows 6 cards, mobile shows 3 + "Load more" CTA.
  → Recommendation: ACF repeater of 6 cards, hide cards 4-6 on mobile via CSS, show "Load more" trigger on mobile only.
- Desktop CTA is text + icon, mobile is icon-only.
  → Use `<span class="hidden lg:inline">CTA Text</span>` next to the icon.
```

## Reply format

Same as `implement-figma-section` reply, plus:

```
### Mobile/desktop merge notes
- [diff table]
- [irreconcilable items + decisions]
```
