---
description: Upload a section's static images at theme/assets/images/<section>/ to the WP media library. Returns attachment IDs and deletes the static files.
argument-hint: <section-name>
---

Run the `upload-images` skill for section `$1`.

This is the step between **sync** and **seed** in the 5-step workflow (when the section has images). It:

1. Scans `theme/assets/images/$1/` for static image files
2. Writes a one-shot loader at `theme/inc/upload-$1.php`
3. Tells you to hit `http://<project>.test/?aiims_upload=$1` as admin
4. The loader uploads each image via `wp_handle_sideload` + `wp_insert_attachment`
5. Returns the attachment IDs
6. Deletes the static files from `theme/assets/images/$1/`
7. Self-deletes the loader file

After this, use the attachment IDs in `/seed`:

```
/seed home-hero heading=Welcome, image=142, mobile_image=143
```

## Pre-checks (silent before writing)

- ☐ `theme/assets/images/$1/` exists and is non-empty
- ☐ Section is dynamic (uses `get_sub_field()` / `get_field()`)
- ☐ Section's ACF field group is synced

If any pre-check fails, stop and tell the user.

## Examples

```
/upload-images home-hero
```

```
/upload-images services-grid
```

## Skip this step when

- Section has no images (text-only)
- Section uses only inline-SVG pattern (Pattern C, no ACF media field)
- Section uses ACF textarea for SVG (Pattern A — the SVG is pasted directly, no media-library upload needed)
