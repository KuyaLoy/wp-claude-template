---
name: acf-json-sync
description: Manage the acf-json/ folder — bump modified timestamps, validate JSON, check sync state vs DB, prompt the user to run "Sync changes" in WP Admin. Triggers on "sync acf", "check acf state", "is acf in sync", "bump acf timestamps".
---

# ACF JSON Sync

ACF Pro auto-syncs from `acf-json/<group>.json` whenever the file's `modified` timestamp is newer than the DB version. The user controls the moment of sync via WP Admin → Custom Fields → Field Groups → Sync changes.

## What this skill does

1. Lists every `acf-json/*.json` file
2. Validates JSON syntax
3. Checks the `modified` field exists and is a unix timestamp
4. (Optional) bumps timestamps to current time so all groups show as needing sync
5. Reminds the user where to click

## When to run

- After `make-section-dynamic` or `add-flexible-layout` writes a JSON file
- During `/ship-check` to confirm nothing is out of sync
- When the user says "sync ACF" or "is everything synced"

## Bump a single group's timestamp

```bash
# In bash equivalent — for actual edit, use the Edit/Write tool
# Find the line "modified": <number> in the JSON file
# Replace with current time()
```

Use the Read tool to load the JSON, parse, set `modified` to current unix timestamp via `date +%s`, and Write back.

## Bump every group

```bash
for f in acf-json/*.json; do
    # update "modified": <ts> in each
done
```

(Do this carefully — only useful when forcing a re-sync after manual edits.)

## Detect orphans

Two kinds of orphans:

1. **Layout file with no JSON layout entry**
   - File exists: `templates/parts/section-foo.php`
   - But no `foo` layout in `acf-json/group_default_template_sections.json` `layouts[]`
   - Verdict: file is dead code OR layout was deleted accidentally

2. **JSON layout entry with no file**
   - Layout `bar` registered in flexible_sections
   - But no `templates/parts/section-bar.php`
   - Verdict: editor will see "missing template" comment in the rendered HTML

Report both kinds.

## Reply format

```
## ACF sync state

### Files
- acf-json/group_default_template_sections.json  ✓ valid  modified: 2026-05-06 10:14:00
- acf-json/group_home_hero.json                  ✓ valid  modified: 2026-05-05 16:30:11
- acf-json/group_home_services.json              ⚠ INVALID JSON

### Layouts in flexible_sections
- hero                ✓ section-hero.php exists
- testimonials        ✗ section-testimonials.php MISSING

### Orphan section files
- section-old-hero.php (no layout registered — safe to delete or re-register)

### To sync changes in WP Admin

1. WP Admin → Custom Fields → Field Groups
2. Look for "Sync available" notice
3. Click "Sync changes"
```
