# Changelog

All notable changes to `wp-claude-template`. Each version is the result of a retro on a finished project (see `RETRO-WORKFLOW.md`).

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) · [Semantic versioning](https://semver.org/spec/v2.0.0.html)

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

#### Planned for next release (v4)

- `/audit` slash command — single-pass link + phone + page validation
- `/scaffold-quote-modal <form-id>` — bundled modal + JS + CSS + CF7 redirect + thank-you template
- `/scaffold-cpt <name>` — CPT skeleton + single + archive + listing
- `/scaffold-contact-form` — CF7 + branded HTML email + redirect
- `/redirect-rename <old-slug> <new-slug>` — slug change with auto-301
- `/email-templates` — install branded CF7 email body templates
- `/migrate-assets` — copy live image assets to staging
- `brand.config.json` — single source of truth for brand name / colors / contact / fonts
- `snippets/image-with-skeleton.php` — standard lazy-image with pulse animation
- `snippets/cf7-emails/` — drop-in branded HTML email templates
- `snippets/footer-brand-block.php` — standardised footer with brand placeholder
- `snippets/sticky-mobile-cta.php` — optional mobile-bottom CTA bar
- Cheatsheet restructured by audience: Editors / Designers / Developers / Troubleshooting
- Cheatsheet adds Claude Code (VSCode) usage section alongside Claude Cowork (Desktop)
- 30+ copy-paste sample prompts in cheatsheet

### Changed

- `CLAUDE.md` rule: must ASK dev before inlining CSS as a Tailwind workaround (was: assume JIT broken, inline)
- `CLAUDE.md` rule: 100% pixel-match required for both desktop + mobile if both Figma frames exist
- `CLAUDE.md` rule: SVG icon decision tree (textarea + currentColor for themed icons, image upload for fixed-color logos, inline for theme-wide statics)
- Hero variants: standardised `$args` contract across all 4 variants
- `briefs/_template.md`: now requires desktop + mobile Figma URLs separately
- Skill renamed: `merge-mobile-desktop` → `match-mobile-desktop` for clarity

### Fixed

- Long PHP-string CF7 emails extracted to template files with variable substitution
- Multi-seeder chains for common features bundled into single `/scaffold-*` commands

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
