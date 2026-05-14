---
name: accessibility-auditor
description: WCAG 2.1 AA review of section parts and templates. Checks color contrast, semantic HTML, alt text, keyboard navigation, focus states, ARIA labels, touch targets. Use during /ship-check or when the user asks for an "accessibility audit" / "a11y review".
---

# Accessibility Auditor (WCAG 2.1 AA)

## Checks

### 1. Color contrast

- Body text vs background: ≥ 4.5:1
- Large text (18px+/24px bold) vs background: ≥ 3:1
- UI elements (button bg vs text): ≥ 4.5:1
- Focus indicators: ≥ 3:1 against background

For each brand token combination, compute contrast ratio (estimate from hex values). Flag fails.

### 2. Semantic HTML

- One `<h1>` per page — usually in the hero
- Heading hierarchy unbroken (no `<h4>` directly after `<h2>`)
- `<nav>`, `<main>`, `<section>`, `<article>`, `<aside>`, `<footer>` used correctly
- `<button>` for buttons, `<a>` for links — never the other way

### 3. Image alt text

- Decorative → `alt=""`
- Functional (icon button) → describes the action ("Open menu")
- Content → describes what it shows
- Empty alt on a content image → flag

### 4. Form controls

- Every `<input>` has a `<label>` (visible or `sr-only`)
- Required fields have `required` attribute + visual indicator
- Error messages linked via `aria-describedby`
- Submit buttons have descriptive text (not just "Submit")

### 5. Keyboard navigation

- All interactive elements reachable via Tab
- Focus order matches visual order
- Skip link to main content
- No keyboard traps (modal can be closed via Esc, focus returns to trigger)

### 6. Focus states

- Visible focus indicator on every interactive element
- Don't kill default outlines without replacing them
- Tailwind: `focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2`

### 7. Touch targets (mobile)

- Minimum 44×44px tappable area for any interactive element
- Adequate spacing between adjacent tap targets (≥8px)

### 8. ARIA where needed

- `aria-label` on icon-only buttons
- `aria-expanded` on accordion/disclosure triggers
- `aria-current="page"` on active nav links
- `role="dialog"` + focus management on modals

### 9. Reduced motion

- Animations respect `prefers-reduced-motion`
- The `data-reveal` system already does this — check it's not bypassed by inline animations

## Output

```
## Accessibility audit (WCAG 2.1 AA)

### Critical
- section-hero.php: heading is `<div>` styled like h1 — not screen-reader accessible
- section-cta.php: button background bg-accent (#FFD700) on white text fails contrast 1.6:1 (need 4.5:1)

### Important
- section-services.php: card images have alt="image"
- section-contact.php: phone input has no <label>

### Minor
- section-projects.php: focus ring is 1px wide, recommend 2px

### Pass
- Heading hierarchy across all sections
- Touch targets on CTAs (≥44px)
- Reduced motion respected
```
