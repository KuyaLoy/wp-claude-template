---
name: qa-reviewer
description: Review section parts and templates for content quality, typos, broken links, missing escapes, missing conditional guards on ACF fields, and dead static content. Use during /ship-check or when the user asks "review the QA" / "check for issues".
---

# QA Reviewer

Catches the small stuff: typos, broken links, missing escapes, leftover placeholder copy.

## Checks

### 1. Output escaping

Every dynamic value must be escaped:
- Plain text → `esc_html()`
- HTML attributes → `esc_attr()`
- URLs → `esc_url()`
- Rich text (wysiwyg) → `wp_kses_post()` (NOT `esc_html` — strips formatting)
- JS strings → `wp_json_encode()` if outputting JSON

Flag any direct `<?= $var ?>` or `<?php echo $var; ?>` without an escape.

### 2. Conditional guards

Every ACF output should be guarded:

```php
<?php if ($heading) : ?>
    <h2><?= esc_html($heading) ?></h2>
<?php endif; ?>
```

Empty fields shouldn't produce empty wrappers in the DOM.

### 3. Dead static content

- Lorem ipsum left in static-phase files that should have been replaced
- TODO / FIXME / XXX comments
- Hard-coded test data ("test@test.com", "+1 555 0000") in production-ready files
- Stub yellow boxes from `add-flexible-layout` that were never replaced

### 4. Broken links

- Links to anchors that don't exist (`#contact` but no element has `id="contact"`)
- External URLs that 404 (sample check by fetching head)
- Email/phone links that are malformed

### 5. Typos and grammar

Run a quick proofread on the actual content. Note: ACF dynamic content can't be proofread from the template — only the static phase.

### 6. Image alt text

- Every image must have meaningful alt text
- Empty `alt=""` is OK only for purely decorative images
- "image", "photo", "picture" are NOT meaningful alt text

## Output

```
## QA review

### Critical
- section-hero.php line 22: $body output without escape

### Important
- section-projects.php: lorem ipsum in static-phase file (still pre-ACF?)
- section-contact.php: email link is mailto:test@test.com

### Minor
- Typo in section-about.php: "compeltely" → "completely"
- section-footer.php: 2 images have alt="image"
```
