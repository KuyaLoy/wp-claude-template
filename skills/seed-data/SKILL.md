---
name: seed-data
description: Write a one-shot self-deleting PHP seeder to populate a section's ACF fields with real content. Triggers on "seed the <section> with...", "populate <section>", "fill in <section> data", "/seed <section>", or after `/build` when the user says "seed too". Plain-English data accepted (`heading=Welcome, body=...`). Writes `theme/inc/seed-<slug>.php` from `snippets/seeder-template.php`, tells the user to hit `/?aiims_seed=<slug>` as admin. File self-unlinks on success.
---

# Seed Data (one-shot self-deleting seeders)

Step 5 of the section workflow (after static → ACF → sync). Populates the synced ACF fields with real content via a one-shot PHP file at `theme/inc/seed-<slug>.php`. The file auto-loads via the glob in `inc/custom-functions.php`, hooks `template_redirect`, runs when the user hits `/?aiims_seed=<slug>` as admin, and `unlink()`s itself after success.

## Pre-conditions

Before writing a seeder, verify:

1. ☐ The section's ACF JSON exists (`acf-json/group_<group>.json` or layout in `group_default_template_sections.json`)
2. ☐ The user has synced ACF in WP Admin (otherwise the field group exists in code but not in the DB — `update_field()` will fail)
3. ☐ The target post exists (e.g. "Home" page for homepage sections, or any page using `template-default.php` for flexible layouts)
4. ☐ `inc/custom-functions.php` has the seeder glob (added in v3.6.0 — verify line `foreach (glob(__DIR__ . '/seed-*.php')` is present)

If any pre-check fails, stop and tell the user what's missing.

## Trigger phrases

All of these route here:

- "Seed the home hero with: heading=Welcome, body=..., cta=Get a quote/#quote"
- "Populate services-grid with these three cards: ..."
- "Fill in the about-us section: heading=Our Story, body=..."
- `/seed home-hero <data>`
- After `/build` when the user says "seed too" / "go ahead and seed"

Plain English is preferred. The user describes the content in whatever shape feels natural; this skill figures out which ACF field each value maps to by reading the section's field group.

## Workflow

### 1. Identify the target section and its ACF field group

From the user's reference (`home-hero`, `services-grid`, `about-us`):

- Find `theme/templates/parts/section-<slug>.php` — confirms the section exists
- Find the ACF group: look in `theme/acf-json/group_<slug>.json` first (homepage section group), then `theme/acf-json/group_default_template_sections.json` (look for the layout named `<slug-with-underscores>`)
- Read the field definitions to enumerate which fields exist + their types

### 2. Map user-provided data to ACF fields

The user might say:

```
Seed the home hero with:
- heading = "Welcome to AIIMS Group"
- subheading = "Real estate marketing for the modern agent"
- body = "<p>Long-form intro paragraph here...</p>"
- cta = "Get a Quote" → /#quote
- image = home-hero/banner.jpg (already in media library)
```

Map each to the ACF field name. Verify the field exists in the JSON — if a field name doesn't match, ask the user before guessing.

For image fields: the user usually provides a path or an attachment ID. If the image is in `theme/assets/images/<section>/<file>` (static phase), it needs to be uploaded to the media library first OR the seeder uses the file URL directly. The simplest path: ask the user to upload it via WP Admin → Media → Library, then provide the attachment ID. The seeder uses `update_field(<field>, <attachment_id>, $page_id)`.

### 3. Identify the target post

For homepage sections (Path B):
- Target is the front page: `$page = get_page_by_path('home')` (or whatever the homepage slug is)
- Field names are prefixed: `home_hero_heading`, etc.

For flexible-content sections (Path A):
- Target is whichever page uses `template-default.php` with this layout in `flexible_sections`
- Ask the user which page if not obvious from context
- Use `update_field('flexible_sections', [...rows...], $page_id)` where each row has `'acf_fc_layout' => '<layout_name>'`

### 4. Write the seeder

Copy `snippets/seeder-template.php` to `theme/inc/seed-<slug>.php`. Replace:

- `<slug>` → the section's slug (e.g. `home-hero`)
- `<Section Name>` → human label for the success message
- `$page_slug` → the target page's slug
- The example `update_field()` calls → real ones for this section's fields

Make sure the slug in `$_GET['aiims_seed']` matches the file name. Convention:

```
inc/seed-home-hero.php   ↔   /?aiims_seed=home-hero
```

### 5. Tell the user the URL to hit

```
Seeder written: theme/inc/seed-home-hero.php

To populate the data:
1. Make sure you're logged in to WP Admin as an administrator
2. Visit: http://<project>.test/?aiims_seed=home-hero
3. You'll see "✓ Seeded Home Hero. File self-deleted." and a link to the page
4. The seeder file is gone from disk — re-create it from git if you need to re-run

If you see "Forbidden", you're not logged in as admin.
If you see a blank page, the field group hasn't been synced yet — sync first.
```

### 6. Self-verify the JSON validity (before writing)

Re-read your generated seeder mentally:

- ☐ `$_GET['aiims_seed'] !== '<slug>'` check matches the filename
- ☐ `current_user_can('manage_options')` admin gate present
- ☐ Every `update_field()` uses a field name that exists in the section's ACF JSON
- ☐ Image/repeater data structure matches ACF's expected format
- ☐ `@unlink(__FILE__)` is present at the end
- ☐ Success message identifies which section was seeded

## Special cases

### Re-running a seeder

The seeder self-deletes after success. To re-run:

1. Restore the file from git: `git checkout theme/inc/seed-<slug>.php`
2. Hit `/?aiims_seed=<slug>` again

The seeder is idempotent if it only uses `update_field()` (which overwrites). It is NOT idempotent if it uses `wp_insert_post()` or creates terms/users — in those cases, add a "skip if exists" check.

### Multiple seeders in one shot

If the user says "seed the whole homepage" (multiple sections), write one seeder per section, OR write a single combined seeder that populates all sections. Combined is faster for the user but harder to re-run partially. Default to per-section unless the user asks for combined.

### Removing a seeder without running it

```bash
rm theme/inc/seed-<slug>.php
```

The auto-load glob in `custom-functions.php` will no longer pick it up.

### Seeders in production

Seeders are admin-gated, so they're safe to leave in production until next deploy. But best practice is to run them once locally, commit the file (so it's in git history), and let the file self-delete. Production then never sees the file.

## Reply format

```
## Seeded: <Section Name>

### File written
theme/inc/seed-<slug>.php

### Run it (admin login required)
http://<project>.test/?aiims_seed=<slug>

### What gets populated
- <field_name> = <value summary>
- <field_name> = <value summary>
- ...

### After it runs
- File self-deletes from disk
- Page renders with the seeded content
- Re-runnable: `git checkout theme/inc/seed-<slug>.php` then hit the URL again
```

## Things you must never do

- Run a seeder without admin gating
- Skip the `unlink(__FILE__)` self-delete (leaves orphan endpoints in production)
- Use `update_field()` on a field that isn't in the section's JSON (silently no-ops, looks like a bug)
- Hardcode attachment IDs without confirming they exist
- Write a seeder before the user has synced the field group (the fields won't exist in DB)
- Mix seeder code with section template code — seeders are always in `inc/seed-*.php`, never inside `templates/parts/`
