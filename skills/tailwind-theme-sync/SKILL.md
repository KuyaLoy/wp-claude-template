---
name: tailwind-theme-sync
description: Add or update brand tokens in Tailwind 4 @theme inline so utilities like bg-success or text-cta-hover work. Triggers on "add a new brand color", "the bg-X utility doesn't work", "register a new token". Updates assets/css/source/style.css and README.md, prompts user to npm run watch to pick up changes.
---

# Tailwind Theme Sync

Tailwind 4 reads brand tokens from `@theme inline` blocks. To add a new utility like `bg-success`, the corresponding `--color-success` must be defined in that block.

## When to run

- A new brand color is referenced 2+ times across designs
- A new font variant is needed (e.g., `--font-display` for headings only)
- A custom breakpoint is needed (e.g., `--breakpoint-1560: 1560px`)
- The user reports that `bg-X` / `text-X` / `font-X` "doesn't work"

## Workflow

### 1. Locate the source CSS

```bash
ls assets/css/source/style.css src/style.css 2>/dev/null
```

### 2. Read the existing @theme inline block

```bash
sed -n '/@theme inline/,/^}/p' <path>
```

### 3. Add the new token

Append inside the `@theme inline { ... }`:

```css
@theme inline {
  /* existing tokens */
  --color-primary: #...;
  --color-secondary: #...;

  /* new tokens */
  --color-success: #10B981;
  --color-cta-hover: #2A4D8B;
  --font-display: 'Anton', sans-serif;
  --breakpoint-1560: 1560px;
}
```

### 4. Update README.md

Add the new token to the brand tokens section so future sessions know about it.

### 5. Verify the utility resolves

After Tailwind rebuilds (`npm run watch`), the utilities should resolve:
- `--color-success` → `bg-success`, `text-success`, `border-success`
- `--font-display` → `font-display`
- `--breakpoint-1560` → `1560:` prefix (`1560:flex 1560:gap-12`)

### 6. Tell the user to rebuild

```
## Token added: --color-success: #10B981

Updated:
- assets/css/source/style.css (added to @theme inline)
- README.md (Brand tokens section)

Now run:
   npm run watch (if not already running)

Use as:
   bg-success, text-success, border-success
```

## Common mistakes

- Adding tokens outside `@theme inline` block (won't generate utilities)
- Using `--brand-success` instead of `--color-success` (Tailwind needs the `--color-*` prefix to make utilities)
- Forgetting to rebuild after adding (utilities won't show up)
- Adding the same token twice (Tailwind takes the last definition silently)
