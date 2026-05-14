---
description: Pre-deploy verification — runs accessibility, performance, QA, and ACF sync state checks in parallel
---

Run these agents/skills in parallel on the current state of the theme:

1. `accessibility-auditor` — WCAG 2.1 AA review of all section parts (color contrast, semantic HTML, alt text, keyboard, touch targets, focus states)
2. `performance-auditor` — Image weight, render-blocking, LCP candidate sizing, unused fonts, large vendor scripts
3. `qa-reviewer` — Typos, broken links, escape coverage, conditional guards on ACF fields, dead static content left over
4. `acf-json-sync` skill verification — Are all `acf-json/*.json` files synced (mtime ≤ DB)? Any orphaned section parts (file exists but no layout)? Any orphaned layouts (registered but no file)?

For each agent, summarize findings. Categorize as:
- **Critical** — blocks deploy
- **Important** — should fix before deploy
- **Minor** — can fix later

Final report:

```
## Ship-check report

### Critical (blocks deploy)
- [issues]

### Important (fix before deploy)
- [issues]

### Minor (defer if needed)
- [issues]

### Pre-deploy todo
1. Sync ACF JSON in WP Admin (X groups have changes)
2. Run `npm run build` (production build, minified)
3. Verify `wp-config.php` has WP_DEBUG = false
4. Backup the database
5. Push theme to staging or live
```
