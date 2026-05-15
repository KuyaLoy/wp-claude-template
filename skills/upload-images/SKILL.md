---
name: upload-images
description: Upload static images at `theme/assets/images/<section>/` into the WP media library and return attachment IDs. Triggers on "/upload-images <section>", "upload <section> images to WP", or after `/make-dynamic` when the user wants to finish moving static assets into the CMS. Writes a one-shot loader at `theme/inc/upload-<slug>.php` that uses `media_sideload_*` helpers, captures IDs, then deletes the source files + self-unlinks. Output IDs feed straight into `/seed`.
---

# Upload Images (static → media library)

Step 5.5 of the section workflow — between sync (Step 4) and seed (Step 5). Takes the static image files you saved during the Static phase, uploads them to the WP media library as proper attachments, returns the attachment IDs the seeder can use, then deletes the static files so the theme stays clean.

Use after the section is dynamic + synced. Skip if the section has no images (or only inline SVG icons, which don't need media-library entries).

## Pre-conditions

1. ☐ Section is dynamic (`templates/parts/section-<slug>.php` uses `get_sub_field()` / `get_field()`)
2. ☐ Section's ACF field group is synced in WP Admin
3. ☐ `theme/assets/images/<slug>/` exists and has at least one image file
4. ☐ User is logged in as administrator (the loader will gate)

If `assets/images/<slug>/` is empty or doesn't exist, stop and tell the user there's nothing to upload.

## Trigger phrases

- `/upload-images home-hero`
- "Upload the home hero images to WP"
- "Move static images for testimonials to the media library"
- Auto-suggested by `/seed` when it detects unuploaded images in `assets/images/<slug>/`

## Workflow

### 1. Scan the static images folder

```
ls theme/assets/images/<slug>/
```

List every file. Common extensions: `.jpg`, `.jpeg`, `.png`, `.webp`, `.svg`. Skip nothing — let the WP filter decide what's allowed. Note: SVG uploads are enabled in `inc/helpers.php`.

### 2. Decide a folder strategy in the media library

WP media library is flat by default but supports a `wp_handle_upload_prefilter` hook to namespace uploads. For simplicity, the uploader uses WP's default year/month folders and tags each uploaded attachment with the section slug via `update_post_meta('_aiims_source_section', '<slug>')`. That way you can query "all uploads from home-hero" later if needed.

### 3. Write the one-shot uploader

Copy `snippets/uploader-template.php` to `theme/inc/upload-<slug>.php`. Replace placeholders. The uploader:

1. Hooks `template_redirect`
2. Checks `$_GET['aiims_upload'] === '<slug>'`
3. Admin-gates via `current_user_can('manage_options')`
4. Loops every file in `theme/assets/images/<slug>/`
5. For each file:
   - Reads the file bytes
   - Builds a WP file array (uses `wp_check_filetype()` to detect MIME)
   - Inserts via `wp_handle_sideload()` → gets an upload result
   - Creates the attachment via `wp_insert_attachment()`
   - Generates metadata via `wp_generate_attachment_metadata()`
   - Tags it with `_aiims_source_section` meta
   - Captures the attachment ID in `$results[<filename>]`
6. After all files succeed: deletes the source files at `theme/assets/images/<slug>/` (so the theme stays clean)
7. Self-unlinks the uploader file
8. Outputs a JSON-friendly result block the user can paste into `/seed`:

   ```
   ✓ Uploaded 4 images from home-hero.
   - banner.jpg     → attachment ID 142
   - bg-mobile.jpg  → attachment ID 143
   - decorative.svg → attachment ID 144
   - logo.svg       → attachment ID 145

   Static files at theme/assets/images/home-hero/ deleted.
   Uploader file deleted.

   Next: seed the section using these IDs, e.g.
     Seed home-hero with: image=142, mobile_image=143, logo=145
   ```

### 4. Tell the user where to click

```
Uploader written: theme/inc/upload-home-hero.php

To run it:
1. Make sure you're logged in to WP Admin as administrator
2. Visit: http://<project>.test/?aiims_upload=home-hero
3. You'll see the list of attachment IDs + confirmation
4. Use those IDs in the next /seed call

The static image files at theme/assets/images/home-hero/ will be deleted after success.
If you want to keep them as backup, copy the folder elsewhere first.
```

### 5. Self-verify the uploader before writing

- ☐ Slug in `$_GET` matches filename
- ☐ Admin gate present
- ☐ `wp_handle_sideload()` is the upload function (not `media_sideload_image()` — that's for URLs)
- ☐ `wp_generate_attachment_metadata()` called (so WP generates the resized sizes)
- ☐ Each filename appears in the output ID map
- ☐ Source-file deletion happens AFTER all uploads succeed (not per-file)
- ☐ Uploader self-unlinks at end

## Special cases

### Re-running (if some images fail)

The uploader doesn't keep state across runs. If you re-run, you'd get duplicate attachments in the media library. Safer: if a partial run fails, manually clean up the partial attachments in WP Admin → Media, then re-run from a fresh git checkout of the uploader.

### Replacing existing attachments

This skill creates NEW attachments. If you want to UPDATE an existing one (replace the file but keep the attachment ID + URL), use WP Admin → Media → Edit Image → Replace. Out of scope for this skill.

### SVGs in `assets/icons/` (theme-wide)

This skill is for per-section images in `assets/images/<slug>/`. Theme-wide SVGs in `assets/icons/` are intentionally static — they're loaded via `aiims_img()` and don't go through the media library. Don't upload those.

### When the section uses an inline SVG paste (Pattern A from CLAUDE.md §8)

Inline SVGs pasted into an ACF textarea field don't need media-library uploads. They're stored in the postmeta directly. Skip the upload step for those fields.

## Reply format

```
## Images uploaded: <Section Name>

### Source folder
theme/assets/images/<slug>/ — 4 files

### Uploader written
theme/inc/upload-<slug>.php

### Run it (admin login required)
http://<project>.test/?aiims_upload=<slug>

### What will happen
- Each file → wp_handle_sideload → wp_insert_attachment
- Attachment IDs returned in the browser
- Source files deleted from theme/assets/images/<slug>/
- Uploader file self-deletes

### Then
Use the attachment IDs in a /seed call:
  Seed <slug> with: image=<id>, ...
```

## Things you must never do

- Skip the admin gate
- Delete source files BEFORE confirming all uploads succeeded
- Use `media_sideload_image()` for local files (it's for remote URLs)
- Forget `wp_generate_attachment_metadata()` (WP won't have the resized sizes for srcset)
- Upload SVGs without the SVG upload safety filter (already present in `helpers.php`)
- Upload to a custom folder outside WP's default — breaks WP's attachment URL logic
