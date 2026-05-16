# Retrospective — v1 → v2

> Lessons learned from the first real project built on this template (**JG Vertical**, WordPress + ACF Pro + Tailwind 4, ~3 weeks).
> This doc is the **evidence base** for the v2 changes captured in `CHANGELOG.md`.

---

## ⭐ What worked — keep these in v2

### 1. The 5-step workflow per section: **static → cross-check → dynamic → sync → seed**

This was the **single biggest win**. Every section we built (18+ of them) followed the same arc:

1. **Static** — hard-code from Figma. Pixel-match. No ACF.
2. **Cross-check** — compare live render against Figma. Surface deviations.
3. **Dynamic** — swap inline strings for `get_sub_field()`. Add guards.
4. **Sync** — write ACF JSON, sync via WP admin.
5. **Seed** — one-shot self-deleting PHP seeder to populate data.

**Why it won:** the cross-check step caught typography, spacing, color, and layout issues *before* ACF complexity made them harder to fix. The seeders meant data populated once and stayed populated, with zero manual entry.

**Keep in v2:** core workflow stays. Add a `/qa <section>` slash command that runs cross-check programmatically (screenshot + Figma reference compare).

---

### 2. Self-deleting one-shot seeders

The pattern `inc/seed-<feature>.php` triggered by `/?aiims_seed=<slug>` and self-`unlink()`-ing after success was **brilliant**:

- 25+ seeders ran cleanly across the project
- Migrations are versioned via git (the PHP files)
- Re-runnable by re-creating the file
- Self-cleanup means no leftover endpoints in production
- All admin-gated via `current_user_can( 'manage_options' )`

**Keep in v2:** pattern stays. Expand the **library of reusable seeders** in `snippets/seeders/` (cta-audit, phone-consistency, slug-rename-with-redirect, etc.).

---

### 3. ACF Local JSON sync

Field groups stored as JSON in `acf-json/`, synced via WP admin. Code-reviewable, mergeable, versioned with the project.

**Keep in v2:** stays as the canonical pattern. Document better that **DB and JSON `modified` timestamps must match** or the sync prompt keeps appearing. Add a `/sync` slash command that does the verify step automatically.

---

### 4. The two-template pattern

`template-homepage.php` (rigid sections) + `template-default.php` (flex content). Plus dedicated templates for CPT singles (`single-project.php`) and archives (`archive-project.php`).

**Keep in v2:** stays. Add example for **service pages with the same pattern** (each service page is rigid, not flex).

---

### 5. The brief-driven section build trigger

`@briefs/<section>.md <figma-url>` → triggers the `implement-figma-section` skill.

**Keep in v2:** keep this trigger. Improve `briefs/_template.md` to be more structured (mandatory fields: Figma URLs for desktop + mobile, ACF field list, behaviors, deviations).

---

### 6. Static theme assets via `aiims_img()` + 3-pattern SVG approach

Pattern A (textarea + `currentColor`), Pattern B (image upload SVG), Pattern C (inline in template) — gave editors flexibility without losing brand control.

**Keep in v2:** stays. Add a **decision tree** in CLAUDE.md ("when to use which pattern").

---

## 🔴 What was painful — friction points to fix in v2

### 1. Tailwind JIT not picking up new arbitrary classes

**Friction:** when I added a new section partial with classes like `sm:max-w-[600px]` or `[&_.swiper-wrapper]:!contents`, the Tailwind watcher sometimes didn't recompile, so the classes wouldn't apply. I'd waste time inlining CSS as a workaround, when really the watcher had just paused on the dev's slow machine.

**Why:** the watcher pauses silently if the dev's PC stalls. Claude assumed JIT was broken and started inlining CSS.

**Fix in v2:** before inlining ANY CSS as a workaround, **Claude must ask the dev**:

> "I notice `[class-name]` isn't compiling on the live page. Is `npm run watch` still running? It may have paused if your machine was slow — please restart and let me know if the class compiles now."

Only after the dev confirms the watcher is running should Claude inline CSS.

**Bake into:** `CLAUDE.md` rule + a check in `implement-figma-section` skill.

---

### 2. Multi-seeder chains for what should be one command

**Friction:** building the quote modal took **4 separate seeders**:
1. Create the modal partial + JS + CSS
2. Create the Thank You page + template
3. Add CF7 per-form redirect script
4. Update header CTA URL to `#quote`

Plus follow-up audits.

**Fix in v2:** **bundled "scaffold" commands** that emit multiple files at once:

- `/scaffold-quote-modal <form-id>` → modal partial + CSS + JS + CF7 redirect seeder + thank-you template + header CTA seeder, all in one go
- `/scaffold-cpt <name>` → CPT JSON + single + archive + listing section + ACF skeleton
- `/scaffold-contact-form <fields>` → CF7 form definition + branded HTML email + redirect + thank-you handler

**Bake into:** new `commands/scaffold-*.md` slash commands.

---

### 3. CTA link audits ran 3 times

**Friction:** I wrote 3 separate audit seeders — broken-link audit, "promote quote-flavoured", and "fix home-specific". Should have been one well-designed tool.

**Fix in v2:** single `/audit` slash command that:
- Scans every ACF link field
- Flags broken/empty
- Suggests `#quote` for quote-flavoured titles
- Suggests `tel:` for phone-format titles
- Suggests `mailto:` for email-format titles
- Lets the dev approve before applying

**Bake into:** new `skills/run-audit/SKILL.md` + `commands/audit.md`.

---

### 4. CF7 HTML emails as 200-line PHP strings

**Friction:** writing the JG-branded HTML email body inside a PHP `update_post_meta()` call was painful — inline CSS for table-based email markup is verbose.

**Fix in v2:** extract email templates to **PHP files** with variable substitution:
- `snippets/cf7-emails/admin-notification.tpl.php`
- `snippets/cf7-emails/customer-autoresponder.tpl.php`
- Color tokens from `brand.config.json` so the same templates work across projects.

**Bake into:** new `snippets/cf7-emails/` + a `/email-templates` command that installs them.

---

### 5. Slug renames need auto-301

**Friction:** renaming "Service" → "Painting" required keeping the `/service/` slug to avoid breaking links. There was no clean way to change the slug + auto-create a redirect.

**Fix in v2:** `/redirect-rename <slug>` command that:
- Updates the post slug
- Creates a 301 redirect entry (via Redirection plugin's table OR a custom rewrite rule)
- Reports the change

**Bake into:** new `commands/redirect-rename.md` + skill.

---

### 6. Phone number / contact info scattered across fields

**Friction:** phone `02 8107 3910` was hardcoded into ACF fields and template strings in 31 places. Required an audit seeder to verify consistency.

**Fix in v2:** **single brand config file** `brand.config.json`:

```json
{
  "name": "JG Vertical",
  "tagline": "Rope Access | Rigging | Rescue",
  "phone_display": "02 8107 3910",
  "phone_tel": "0281073910",
  "email": "admin@jgvertical.com.au",
  "address": "Unit 1/12 Homepride Avenue, Warwick Farm NSW 2170",
  "colors": {
    "primary": "#00baa6",
    "secondary": "#1e4c77",
    "dark": "#26252f"
  },
  "fonts": {
    "brand": "Poppins",
    "body": "Montserrat"
  }
}
```

Every component / helper reads from this. Single source of truth. Zero audits needed.

**Bake into:** new `snippets/brand.config.json` + helpers that read it.

---

### 7. Skeleton loading was bolted on

**Friction:** image lazy-load with `bg-[dark] animate-pulse` + opacity fade-in was added per-page as I built listing sections. Should have been a standard image partial.

**Fix in v2:** ship a reusable image partial:
- `snippets/image-with-skeleton.php` — handles `loading="lazy"`, dark bg placeholder, pulse animation, fade-in on load
- All listing/card images use it
- Single change point if pattern needs updating

---

### 8. Hero variants drift apart

**Friction:** 4 hero variants (`hero-1-feature`, `hero-2-service`, `hero-3-project`, `hero-4-banner`) each had slightly different field contracts. Easy to forget which arg goes where.

**Fix in v2:** **standardise the args contract** — all heroes accept the same `$args` shape (bg_desktop, bg_mobile, kicker, heading, body, cta_primary, cta_secondary, overlay_opacity). Each variant just renders differently.

---

### 9. Missing pages — image asset migration

**Friction:** when SEO audit revealed the new site had 16 pages vs live's 74, there was no tool to bulk-pull live image assets into staging.

**Fix in v2:** `/migrate-assets <source-url>` command that:
- Scans a live URL for all referenced images
- Downloads them to `wp-content/uploads/`
- Reports what was copied

---

### 10. "100% Figma pixel-match for both desktop AND mobile" not enforced

**Friction:** Figma usually has both desktop and mobile frames. v1 rules said "pixel-perfect" but didn't explicitly require checking mobile against its Figma frame, leading to mobile being treated as a "responsive adjustment" rather than a separate design to match.

**Fix in v2:** **strict rule** — if Figma has both desktop and mobile frames, BOTH must be cross-checked against their respective live renders. Mobile is not a "scaled-down version" — it's a distinct design.

**Bake into:** `CLAUDE.md` (responsive rules section, strengthened) + the `merge-mobile-desktop` skill (renamed `match-mobile-desktop` for clarity).

---

## 🟡 Workflow gaps (smaller items)

| Gap | Fix |
|---|---|
| Slow Tailwind rebuilds on big projects | Document `--watch` + `--minify` for prod, `--watch` only for dev |
| Search functionality in cheatsheet but no Claude Code coverage | Add Claude Code (VSCode) section to cheatsheet |
| No copy-paste samples for common prompts | Add 30+ sample prompts to cheatsheet, by category |
| Footer / common partials varied per project | Ship a standardised footer with brand block placeholder |
| No mobile-friendly sticky CTA bar partial | Add as optional snippet |
| Permissions list in `settings.local.json` is fixed | Extend with project-specific patterns (custom `Bash` allowlist) |
| No `.editorconfig` / `.gitignore` / `.prettierrc` shipped | Add to template |
| Brand colors hardcoded in scattered places | All from `brand.config.json` (see #6) |

---

## v2 changelog summary (one-liners)

These will land in `CHANGELOG.md`:

```
v2.0.0 — Improvements derived from JG Vertical retro

ADDED
- /audit  — single-pass link + phone + page validation
- /scaffold-quote-modal <form-id>  — bundled modal + JS + CSS + CF7 + thank-you
- /scaffold-cpt <name>  — CPT skeleton + single + archive + listing
- /scaffold-contact-form  — CF7 + branded HTML email + redirect
- /redirect-rename <old-slug> <new-slug>  — slug change + auto-301
- /email-templates  — install branded CF7 email body templates
- /migrate-assets  — copy live image assets to staging
- brand.config.json — single source of truth for brand name / colors / contact / fonts
- snippets/image-with-skeleton.php — standard lazy-image with pulse
- snippets/cf7-emails/ — drop-in branded HTML email templates
- snippets/footer-brand-block.php — standardised footer with brand placeholder
- snippets/sticky-mobile-cta.php — optional mobile-bottom CTA bar

CHANGED
- CLAUDE.md rule: must ASK dev before inlining CSS as Tailwind workaround
- CLAUDE.md rule: 100% pixel-match required for both desktop + mobile if both Figma frames exist
- CLAUDE.md rule: SVG icon decision tree (textarea vs upload vs inline)
- Hero variants: standardised $args contract across all 4 variants
- briefs/_template.md: now requires desktop + mobile Figma URLs separately
- Cheatsheet: restructured by audience (Editors, Designers, Developers, Troubleshooting) with copy-paste samples
- Cheatsheet: added Claude Code (VSCode) usage section alongside Claude Cowork (Desktop)
- merge-mobile-desktop skill renamed to match-mobile-desktop for clarity

FIXED
- Long PHP-string emails extracted to template files with variable substitution
- Multi-seeder chains for common features bundled into scaffold commands
```

---

## Open questions for v2 design

1. **Should `brand.config.json` live at theme root or in `.claude/`?** Theme root is more discoverable; `.claude/` keeps all template-related stuff together.
2. **Should `/audit` apply changes automatically, or always show a preview + ask?** Preview-then-ask is safer.
3. **Where does the section component catalog live?** Static HTML page like the cheatsheet, or a "kitchen-sink" page in WP itself?
4. **Should `/scaffold-cpt` ask about taxonomies, archive slug, supports?** Probably yes — opinionated defaults + ask for overrides.
5. **For Claude Code (VSCode) users — are slash commands invoked the same way?** Need to test and document.

---

*This retro is the source-of-truth doc for v2 changes. Each numbered item maps to a concrete file or rule update in the v2 release.*
