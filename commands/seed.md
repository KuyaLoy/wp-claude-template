---
description: Write a one-shot self-deleting PHP seeder to populate a section's ACF fields. Plain-English data accepted.
argument-hint: <section-name> [data in plain English]
---

Run the `seed-data` skill for section `$1` with the provided data.

This is step 5 of the section workflow (static → ACF → sync → seed). Use after the section is dynamic and synced in WP Admin.

## Examples

```
/seed home-hero heading=Welcome to AIIMS, subheading=Real estate marketing, cta=Get a quote/#quote
```

```
/seed services-grid

Card 1: title=Painting, body=Residential & commercial, link=/services/painting
Card 2: title=Repairs, body=Same-day response, link=/services/repairs
Card 3: title=Consulting, body=Free estimates, link=/services/consulting
```

```
/seed about-us heading="Our Story", body="<p>Founded in 2015...</p>"
```

## Plain English also works (no slash needed)

```
Seed the home hero with: heading=Welcome to AIIMS, body=...
```

```
Populate the services grid with three cards: Painting, Repairs, Consulting (use the URLs and descriptions from the brief).
```

Both routes through the same `seed-data` skill.

## What happens

1. Claude reads `theme/templates/parts/section-$1.php` and the section's ACF JSON to enumerate fields
2. Maps your provided data to the field names
3. Writes `theme/inc/seed-$1.php` based on `snippets/seeder-template.php`
4. Tells you the URL to hit while logged in as admin: `http://<project>.test/?aiims_seed=$1`
5. After you hit the URL, the seeder runs, populates the data, and `unlink()`s itself

## Pre-checks (run silently before writing)

- ☐ `theme/templates/parts/section-$1.php` exists (section was built)
- ☐ Section's ACF JSON exists (section was made dynamic)
- ☐ User has synced ACF in WP Admin (otherwise `update_field()` fails)
- ☐ `inc/custom-functions.php` has the seeder glob loader

If any pre-check fails, stop and tell the user what's missing.
