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

- `brand.config.json` — single source of truth for brand name / colors / contact / fonts
- `snippets/image-with-skeleton.php` — standard lazy-image with pulse animation
- `snippets/cf7-emails/` — drop-in branded HTML email templates
- `snippets/footer-brand-block.php` — standardised footer with brand placeholder
- `snippets/sticky-mobile-cta.php` — optional mobile-bottom CTA bar

### Planned cheatsheet work

- Split `cheatsheet/index.html` into `cowork.html` + `code.html` with a small `index.html` picker (Phase 5 of the polish roadmap)

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
