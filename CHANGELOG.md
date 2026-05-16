# Changelog

All notable changes to `wp-claude-template`. Each version is the result of a retro on a finished project (see `RETRO-WORKFLOW.md`).

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) · [Semantic versioning](https://semver.org/spec/v2.0.0.html)

---

## [Unreleased]

Aspirational features identified during retros that haven't landed yet. Each will be picked up on a future retro when a real project surfaces the need.

### Planned commands

- `/audit` — single-pass link + phone + page validation
- `/scaffold-quote-modal <form-id>` — bundled modal + JS + CSS + CF7 redirect + thank-you template
- `/scaffold-cpt <name>` — CPT skeleton + single + archive + listing
- `/scaffold-contact-form` — CF7 + branded HTML email + redirect
- `/redirect-rename <old-slug> <new-slug>` — slug change with auto-301
- `/email-templates` — install branded CF7 email body templates
- `/migrate-assets` — copy live image assets to staging

### Planned snippets

- `snippets/image-with-skeleton.php` — standard lazy-image with pulse animation
- `snippets/cf7-emails/` — drop-in branded HTML email templates
- `snippets/footer-brand-block.php` — standardised footer with brand placeholder
- `snippets/sticky-mobile-cta.php` — optional mobile-bottom CTA bar

### Recently shipped (formerly listed here, now done)

- ✓ `brand.config.json` single source of truth → shipped in v3.7.0
- ✓ Cheatsheet split into Cowork + Code paths → shipped in v3.4.0, then **consolidated back into a single tabbed page** in v3.8.0 because the maintenance overhead of two separate files outweighed the audience-segmentation benefit

---

## [3.7.0] — 2026-05-15

**Workflow polish: upload + cleanup + typography tune + brand config.** Four retro-driven additions that close the remaining manual-step gaps in the section workflow. Eliminates 20-40 manual WP-Admin clicks per project (image uploads + section cleanup) and gives the user one source of truth for brand identity.

### Added

- **`skills/upload-images/SKILL.md` + `commands/upload-images.md` + `snippets/uploader-template.php`** — new step 5a in the workflow. Scans `theme/assets/images/<slug>/`, writes a one-shot loader at `theme/inc/upload-<slug>.php` that uploads each file to the WP media library via `wp_handle_sideload` + `wp_insert_attachment`, generates resized variants via `wp_generate_attachment_metadata`, tags each attachment with `_aiims_source_section` postmeta for future querying, returns the attachment IDs as HTML output, deletes the source folder, self-unlinks. Tied into the seed-data skill's pre-checks so users get pointed to it automatically.
- **`skills/cleanup-section/SKILL.md` + `commands/cleanup-section.md`** — new step 6 (optional production sweep). Strips dev-only doc headers (`Phase: static (pre-ACF)`, `Source: <figma-url>`), removes yellow scaffold-stub markup, validates every `get_sub_field()` / `get_field()` reference against the ACF JSON, flags orphaned static assets, surfaces `TODO`/`FIXME`/`var_dump`/commented-out code. Three-tier report: ✓ Applied · ⚠ Needs decision · 🔴 Issues. Auto-applies only the safe edits.
- **`snippets/brand.config.json` + `aiims_brand()` helper in `helpers.php`** — single source of truth for brand identity. JSON at workspace root with brand.name/tagline, contact.phone/email/address, urls.local/production/figma, colors, fonts, social. Read via dot-notation: `aiims_brand('contact.phone_display')`. Result cached per-request. setup-claude now populates the JSON during initial project setup.
- **Typography drift detection in `pixel-perfect-verify`** — new "Typography drift (Figma vs browser rendering)" sub-section in AUTO mode comparison. Documents the most common Figma-to-browser font rendering issues (letter-spacing differences, line-height "auto" vs CSS normal, font-rendering hints) with a lookup table mapping symptoms to specific Tailwind tweaks (`tracking-tight`, `leading-none`, `tabular-nums`, etc.). Report now flags every typography difference with a copy-paste fix.

### Changed

- **`snippets/custom-functions.php` glob loader extended** to load both `inc/seed-*.php` AND `inc/upload-*.php` — same auto-load pattern, two file families.
- **`skills/seed-data/SKILL.md` pre-checks updated:** now explicitly checks for unuploaded images in `theme/assets/images/<slug>/` and points the user at `/upload-images <slug>` first when found. Stops the previous failure mode where users would write seeders against attachment IDs they didn't have yet.
- **CLAUDE.md §3 workflow expanded from 5 steps to 6** (with Step 5 split into 5a Upload + 5b Seed): static → cross-check → dynamic → sync → upload-images → seed → cleanup. Steps 5a + 5b + 6 marked OPTIONAL but encouraged.
- **CLAUDE.md §13 slash commands list** updated with `/upload-images`, `/seed`, `/cleanup-section`. `/build` description notes optional seed chain. `/pixel-check` description notes typography drift detection.
- **`skills/setup-claude/SKILL.md`** gets a new step 7b: generate `brand.config.json` from the user's answers, populated with project name, phone (display + tel formats), country, URLs (local + prod + figma), colors, font.

---

## [3.6.0] — 2026-05-15

**Seed as a first-class workflow step.** Retro-driven: the seeder pattern was used 25+ times on JG Vertical but never codified into the template. Now `static → cross-check → dynamic → sync → seed` is the full 5-step section workflow, with skills/commands/snippets supporting the seed step end-to-end.

### Added

- **`snippets/seeder-template.php`** — boilerplate for a one-shot self-deleting data seeder. Hooks `template_redirect`, checks `?aiims_seed=<slug>`, admin-gated, populates ACF fields via `update_field()`, `unlink()`s itself after success. Includes commented examples for plain fields, link fields, image fields, repeaters, and flexible content rows.
- **`snippets/custom-functions.php` glob loader** — auto-requires every `inc/seed-*.php` in the theme. Drop a seeder file in `inc/` and it's live. After the seeder unlinks itself, the glob no longer picks it up. Zero registration ceremony.
- **`skills/seed-data/SKILL.md`** — the workflow skill. Triggers on plain-English ("Seed the home hero with: heading=..., body=...") or `/seed <section> <data>`. Reads the section's ACF field group to enumerate fields, maps user-provided data to field names, writes `theme/inc/seed-<slug>.php` from the boilerplate, tells the user the URL to hit. Pre-checks for sync state, ACF Pro active, target post existence.
- **`commands/seed.md`** — `/seed <section> [data]` slash command wrapper. Plain English ("Seed the home hero with: ...") also works without the slash.
- **CLAUDE.md §3 expanded from 2 phases to 5 steps:** static → cross-check → dynamic → sync → seed. Each step has explicit responsibilities. Seed is OPTIONAL but encouraged.
- **`/build` now optionally chains through seed.** After the sync prompt, asks "want me to seed real data too?" — if user provides content, invokes `seed-data` skill. If user says skip, proceeds to final reply.
- **`cheatsheet/cowork.html` walkthrough now 8 steps** (added Step 7: "Seed the data — optional but recommended") with plain-English example. New seed prompt card in the "What to type" section.
- **`cheatsheet/code.html` seeders section updated:** v3.6 badge, points users at the new `/seed` command and `seed-data` skill as the canonical path. Hand-written boilerplate is now the "if you ever need to" fallback.
- **`cheatsheet/code.html` commands table cleaned up:** added `/build` (was missing) and `/seed` (new). Removed four unreleased aspirational entries (`/audit`, `/scaffold-quote-modal`, `/scaffold-cpt`, `/redirect-rename`) that were misleadingly badged as "v3 shipped" — they remain in CHANGELOG `## [Unreleased]` where they belong.

### Changed

- The 4-step workflow (`static → ACF → sync → seed`) the user asked about now matches reality. Previously seed was only mentioned in `RETRO.md` and `code.html` as a pattern, not as a workflow step.

---

## [3.5.1] — 2026-05-15

**Design fidelity + zero-friction build trigger.** Three retro-driven rules layered on top of v3.5.0: SVG preference order, asset transparency / composite-frame fidelity, and a fully brief-optional build flow with explicit responsive defaults.

### Added

- **CLAUDE.md §8 "Asset fidelity from Figma (NON-NEGOTIABLE)"** — new sub-section in Image and SVG rules. Four rules: (1) SVGs stay SVGs (never rasterize), (2) preserve transparency on PNG/WebP exports, (3) composite frames stay composite (don't break grouped designer layouts into separate elements), (4) export at 2× where possible for retina sharpness.
- **CLAUDE.md §8 SVG preference order made explicit:** Pattern A (textarea, currentColor theming) is now the DEFAULT for editor-changeable SVGs; Pattern B (image upload) is documented as last-resort fallback. Pattern C (inline) for theme-wide static. New "Never rasterize" closing line.
- **Brief files now auto-created.** If the user triggers a build and `briefs/<name>.md` doesn't exist, `implement-figma-section` populates one from chat context + Figma `get_design_context` data following `briefs/_template.md` structure. Non-technical users no longer have to create a file before triggering a build; the paper trail still gets created for retros.
- **More token-efficient trigger forms:** `@home-hero <url>` (no `.md` needed), bare `home-hero <url>` (no `@`), and plain-English "Build the home hero from this Figma: <url>" all work and resolve to the same place.
- New "Brief files (optional — auto-created)" callout in `cheatsheet/cowork.html` explaining the auto-creation, and the walkthrough no longer has "Write a brief file" as a required step. Step count reduced from 8 to 7.

### Changed

- **CLAUDE.md §6 Responsive rules made explicit:** one Figma URL = Claude builds desktop AND auto-makes it responsive in the same pass, surfaces every mobile assumption in the reply for the user to confirm by previewing. No mid-build pause. Two URLs = `match-mobile-desktop` pixel-matches both.
- **`implement-figma-section/SKILL.md` step 1 rewritten** to "Resolve / auto-create the section brief" — codifies the auto-create flow including the lookup of `briefs/_template.md` for structure, the one-question fallback when template ownership can't be inferred, and the user-facing "Wrote briefs/<name>.md with..." confirmation line.
- **`agents/frontend-builder.md` rule 5 expanded** to inline the SVG preference order and asset-fidelity rules (defers to CLAUDE.md §8 for the source of truth).
- **`skills/handle-messy-figma-svg/SKILL.md` preamble** reinforces that the skill's goal is to keep SVG as SVG; rasterization requires explicit user consent per the asset-fidelity rule.
- **`cheatsheet/cowork.html` walkthrough updated:** 7 steps instead of 8 (brief step removed), responsive-link logic explained inline at step 1, prompts table updated to drop `.md` from shorthand examples.

---

## [3.5.0] — 2026-05-15

**Figma-as-source-of-truth rule (NON-NEGOTIABLE).** Post-Phase-5 retro change: real production-level rule promoted from the user's own friction. Claude will no longer fall back to building from screenshots when the Figma MCP disconnects — it hard-stops and asks for reconnection. Same workflow, more consistency, no more drift across sections.

### Added

- **CLAUDE.md section 4: "Figma as source of truth (NON-NEGOTIABLE)"** — new third NON-NEGOTIABLE section alongside the two-template pattern (§2) and the section-by-section workflow (§3). Codifies: Figma MCP is the design data pipeline; screenshots are visual reference only, never the primary source; if MCP is unavailable, Claude STOPS and asks for reconnection rather than guessing from a screenshot. Includes the exact reply Claude should give the user on disconnect.
- New Figma-disconnect troubleshooting cards in both cheatsheets (`cowork.html` and `code.html`) explaining the hard-stop as a feature, not a bug, with platform-specific reconnect steps.
- New "Claude stops mid-build" entry at the top of `INSTALL-MCPS.md` Troubleshooting section, with the override syntax for the rare case where someone genuinely has no Figma access.

### Changed

- **`implement-figma-section/SKILL.md` step 3 rewritten.** The previous "If Figma MCP isn't available, ask the user to paste a screenshot inline" — exactly the inconsistent behavior the rule is preventing — is gone. Replaced with the hard-stop reply + reconnect instructions for both platforms. Override clause preserved for explicit user opt-out, with mandatory deviation-flagging.
- **`agents/frontend-builder.md` step 2 rewritten.** Same change. Defers explicitly to CLAUDE.md §4 so future skills don't have to repeat the rule.
- **`skills/setup-claude/SKILL.md` Figma probe behavior changed.** During initial setup, if Figma MCP is unavailable, Claude no longer attempts screenshot-driven brand-token detection (which would have seeded wrong values into Tailwind's `@theme` block). Instead it falls through to placeholders and tells the user to run `tailwind-theme-sync from <figma-url>` once Figma is connected.
- CLAUDE.md section 5 (Pixel-perfect rules) now reads "STRICT — depends on §4" so the dependency on Figma-as-source-of-truth is explicit.
- CLAUDE.md renumbered: previous §4-§14 are now §5-§15. Section content unchanged.

---

## [3.4.0] — 2026-05-15

**Phase 5 cheatsheet split — major release.** The single `cheatsheet/index.html` is now three files, audience-segmented. Non-technical users get a hand-holding mode; developers keep the technical reference. End of the polish roadmap.

### Added

- **`cheatsheet/cowork.html`** — brand-new hand-holding cheatsheet for editors, designers, project managers, anyone using Cowork (the desktop app) without coding background. Plain-English glossary (5 key terms), step-by-step first-time setup, a full walkthrough of building one section start to finish, common prompts to copy-paste, friendly troubleshooting, clear "when to call your dev" boundaries, examples of good vs bad prompts. ~600 lines.
- **`cheatsheet/code.html`** — Claude-Code-specific (CLI/VSCode) reference. Adapted from the previous `index.html`: same comprehensive technical content (ACF patterns, seeders, git workflow, anti-patterns) plus a new MCP Install section at the top with the `claude mcp add figma` / `claude mcp add playwright` commands. Purple branding to differentiate from Cowork.
- **`cheatsheet/index.html`** — replaced. Now a small picker page with two big cards (Cowork vs Code) and a "not sure?" hint. ~85 lines.
- `snippets/README.template.md` now links both cheatsheets in the "Visual cheatsheets" callout near the top.
- `setup-claude/SKILL.md` success summary now points the user at the cheatsheet picker (`cheatsheet/index.html`) with a one-line description of each version.

### Changed

- Cheatsheet branding split: **cyan** for Cowork (non-technical, friendly), **purple** for Claude Code (technical, dev-focused). Cross-links at top header and footer of each page so users can swap if they landed on the wrong one.
- The "v1 archive" link in the previous cheatsheet sidebar is now removed (the v1 file was deleted in v3.1.0).

---

## [3.3.0] — 2026-05-15

**Phase 4 token-efficiency pass** — fewer keystrokes to trigger work, less context burn per session. The biggest day-to-day-feel change in the polish roadmap.

### Added

- **`/build` chained slash command** at `commands/build.md`. One shot: `implement-figma-section` → pause for user approval → `make-section-dynamic` → sync prompt. Existing `/implement` and `/make-dynamic` stay independent for cases where you want to iterate the static for a while.
- Token-efficient trigger phrases for `implement-figma-section`. Now all of these work:
  - `@home-hero.md <figma-url>` (auto-prepends `briefs/`)
  - `@home-hero.md <desktop-url> <mobile-url>` (routes to `match-mobile-desktop`)
  - `@home-hero.md <url> <url> use one bg image not per-card` (trailing text = inline deviations)
  - Bare `home-hero.md <url>` (no `@` prefix needed)
- **Inline-deviations merge.** Trailing freeform text after URLs is treated as deviations and appended additively to whatever the brief's "Deviations" / "Special notes" section already says — both apply, never replace. The final build reply surfaces both.

### Changed

- **`read-project-conventions` is now lazy-trigger.** No longer fires on every session start. Only runs before the FIRST build action in a session; subsequent calls within the same session short-circuit to the cached summary in context. Saves ~2k tokens per session for non-build conversations. The skill's `description:` and "When to run" section both updated; new "Cache rule" section documents when to defensively re-read.
- **CLAUDE.md trimmed (conservative).** Removed section 11 (skills list) and section 12 (agents list) — those are auto-discoverable from `skills/*/SKILL.md` and `agents/*.md` frontmatter, so duplicating them in CLAUDE.md just rots. Section 11 is now a 6-line "common day-to-day patterns" pointer instead. Sections 4 (pixel-perfect), 5 (responsive), and 8 (ACF JSON sync) compressed to tighter bullets without losing rules. Section 9 (code style with `aiims_*` rule) kept intact. Section 7 (image/SVG decision tree) kept fully per user preference — it's the highest-traffic rule. Renumbered subsequent sections: 13→12, 14→13, 15→14. Net char savings ~12-15%.

---

## [3.2.0] — 2026-05-15

**Phase 3 cross-platform pass** — make every skill, agent, and slash command work identically in Claude Cowork (desktop) and Claude Code (CLI). MCP names standardized, install paths documented, PHP path helpers unified, environment-detect preambles added.

### Added

- `INSTALL-MCPS.md` at workspace root — single-page reference for installing Figma MCP and browser MCP on both Cowork and Claude Code. Covers the auto-detect contract, the without-MCP fallback path for every skill, a verify-session block, and a troubleshooting section.
- Environment-detect preamble in `setup-claude/SKILL.md` step 1 — explicit Cowork bash-sandbox caveat (independent calls, no cwd carry-over) and a Glob/Read tool alternative for theme-structure detection.
- Inline notes in `frontend-builder.md`, `implement-figma-section/SKILL.md`, `setup-claude/SKILL.md` reminding what to do if the Figma MCP isn't installed (degrade to placeholder colors / ask user for a screenshot).
- `pixel-perfect-verify/SKILL.md` now lists both `mcp__Claude_in_Chrome__*` and `mcp__Control_Chrome__*` as supported Cowork tools, with a clear "Cowork is primary, Claude Code via Playwright is optional, MANUAL is the universal fallback" hierarchy.

### Changed

- Figma MCP tool names: all placeholder `mcp__1c83dedb...__*` references replaced with real `mcp__Figma__*` names that resolve in both platforms. Touched: `frontend-builder.md`, `setup-claude/SKILL.md`, `implement-figma-section/SKILL.md`.
- PHP path helpers standardized to **`locate_template($file, false, false)` for includes** (child-theme safe, silent-fail) and **`get_stylesheet_directory()` for everything else** (correct in both parent and child themes). Updated examples in `make-section-dynamic/SKILL.md` (Path B homepage template), `implement-figma-section/SKILL.md` (wiring instructions), and `setup-claude/SKILL.md` (generated functions.php require line). The `template-homepage.php` snippet already used this pattern after Phase 1.

---

## [3.1.0] — 2026-05-15

**Phase 2 doc-truth pass** — based on full structural audit. Aligns docs with reality. No behavior changes.

### Added

- CLAUDE.md section 6: explicit rule — must ASK the user before inlining CSS as a Tailwind workaround. Watcher may have paused; checking that comes before writing custom CSS.
- CLAUDE.md section 13: `/setup-claude` now listed alongside the other slash commands (with self-delete note).
- CHANGELOG: `## [Unreleased]` section at the top of the file — aspirational v4 items live here, not inside a released version.

### Changed

- Skill **renamed**: `skills/merge-mobile-desktop/` → `skills/match-mobile-desktop/`. Frontmatter `name:` updated, six cross-references fixed (CLAUDE.md, RETRO.md sees historical mentions kept, README.template.md, implement-figma-section, responsive-engineer agent, the SKILL.md itself).
- Standardized `@theme` block reference across the template. `@theme inline` (incorrect for source-of-truth tokens in Tailwind 4) replaced with plain `@theme` in CLAUDE.md, `tailwind-theme-sync/SKILL.md`, `read-project-conventions/SKILL.md`, `implement-figma-section/SKILL.md`, and the cheatsheet.

### Removed

- `cheatsheet/index-v1.html` — orphan v1 cheatsheet (the current `index.html` is v3; v1 was kept only for diff reference). Phase 5 will fully restructure the cheatsheet.

### Fixed

- `claude-setup/README.md` "What's inside" now reflects the actual folder structure (added `RETRO.md`, `RETRO-WORKFLOW.md`, `CHANGELOG.md`, `completed-projects/`, `cheatsheet/`, `agents/` to the listing).
- `completed-projects/README.md`: the `jg-vertical/` and `abc-painting/` examples are now clearly marked as illustrative, since the folders don't actually exist yet.

---

## [3.0.1] — 2026-05-15

**Phase 1 bug-fix pass** — based on full structural audit. Fixes the bugs that would bite a fresh user project. No new features.

### Fixed

- `template-homepage.php` no longer throws PHP warnings on fresh projects. Replaced six hard-coded `include`s with a `locate_template($file, false, false)` loop over a `$home_sections` slug array. Missing files are silently skipped; admins see HTML comments where sections are pending.
- Phone regex in `acf-setup.php` is now substitutable. Wrapped the AU regex + error message in `PHONE_REGEX_START` / `PHONE_REGEX_END` markers so `/setup-claude` (or a future retro) can swap it cleanly.
- `{{PROD_URL}}` placeholder in `README.template.md` is now in the `setup-claude` substitution map. Previously the literal `{{PROD_URL}}` stayed in the generated README.
- `settings.local.json` allow-list now includes the git commands the retro workflow needs (`git status`, `diff`, `log`, `branch`, `add`, `commit`, `push`, `tag`) plus `npm run bundle`.

### Changed

- `aiims_*` helper namespace is now documented as a **fixed AIIMS Group convention** in CLAUDE.md section 9 — not derived from theme slug. Removed the misleading `FN_PREFIX` auto-detection step from `setup-claude/SKILL.md` so the skill no longer pretends to substitute the prefix.
- `setup-claude` now asks for a Production URL during initial questions (defaults to `TBC` if skipped) and ships a phone-regex lookup table (AU/US-CA/UK/PH) for the country choice.

---

## [3.0.0] — 2026-05-14

**Based on retro: JG Vertical (2026-05)** — see `RETRO.md`

> Note: jumped from v1 → v3 (skipping v2) because the cheatsheet went through two design iterations during this retro. v3 is the consolidated release.

### Added

- `RETRO.md` — master retro doc, one section per finished project
- `RETRO-WORKFLOW.md` — the per-project continuous-improvement loop documented
- `CHANGELOG.md` — this file, versioned releases of the template
- `completed-projects/` directory — frozen snapshots of each shipped project, source of retro evidence

### Changed

- `CLAUDE.md` rule: 100% pixel-match required for both desktop + mobile if both Figma frames exist
- `CLAUDE.md` rule: SVG icon decision tree (textarea + currentColor for themed icons, image upload for fixed-color logos, inline for theme-wide statics)
- Hero variants: standardised `$args` contract across all 4 variants
- `briefs/_template.md`: now requires desktop + mobile Figma URLs separately

### Fixed

- Long PHP-string CF7 emails extracted to template files with variable substitution
- Multi-seeder chains for common features bundled into single `/scaffold-*` commands

> Note: items originally listed here as "Planned for next release (v4)" — `/audit`, `/scaffold-*`, `brand.config.json`, `snippets/cf7-emails/`, etc. — have been moved to `## [Unreleased]` at the top of this file. The `merge-mobile-desktop` → `match-mobile-desktop` rename and the "ask before inlining CSS" rule were also originally listed here but only landed in v3.1.0.

---

## [1.0.0] — 2026-05-06

**Initial release.** Used to build the JG Vertical site.

### Added

- `CLAUDE.md` master rules
- 12 skills: `setup-claude`, `implement-figma-section`, `make-section-dynamic`, `add-flexible-layout`, `create-template`, `acf-json-sync`, `merge-mobile-desktop`, `pixel-perfect-verify`, `responsive-build`, `handle-messy-figma-svg`, `tailwind-theme-sync`, `read-project-conventions`
- 6 agents: `frontend-builder`, `responsive-engineer`, `acf-architect`, `qa-reviewer`, `accessibility-auditor`, `performance-auditor`
- 7 slash commands: `/setup-claude`, `/implement`, `/make-dynamic`, `/add-section`, `/create-template`, `/pixel-check`, `/ship-check`
- 7 snippets: `helpers.php`, `acf-setup.php`, `custom-functions.php`, `template-homepage.php`, `template-default.php`, `section-_example.php`, `group_default_template_sections.json`
- Single-page HTML cheatsheet (12 topic sections: setup, workflow, commands, templates, filetree, acf, images, tailwind, anti-patterns, git, scenarios, troubleshooting)
- `settings.local.json` with bash permissions allowlist
