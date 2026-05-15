# wp-claude-template — internal

> This file is internal docs for what's inside this template repo. After you copy the appropriate folders into your theme and rename to `.claude/`, you can ignore this file. Daily use is via the workspace-root `README.md` that `/setup-claude` writes for you.

## What you do once per project

1. Copy the runtime parts of this template (`CLAUDE.md`, `skills/`, `agents/`, `commands/`, `snippets/`, `settings.local.json`, `cheatsheet/`) into your theme directory as `.claude/`. Don't copy the template-internal docs (`RETRO.md`, `RETRO-WORKFLOW.md`, `CHANGELOG.md`, `completed-projects/`) — those belong only to the template repo.
2. Open the project in Claude (Cowork desktop or Claude Code CLI)
3. Type: `/setup-claude`
4. Answer the questions (project name, production URL, brand colors, font, container, phone country)
5. Done — Claude writes helpers, two page templates, ACF JSON, and a personalized `README.md` at your workspace root, then deletes its own setup files

## What's inside this template repo

```
wp-claude-template/                    ← this folder (the template repo)
│
├── ─── Runtime (copy into project as .claude/) ───
├── CLAUDE.md                          Master rules — Claude reads on every session
├── README.md                          (this file — internal docs, not copied)
├── settings.local.json                Bash + read permissions
├── skills/                            Workflow recipes (auto-trigger by phrasing)
├── agents/                            Sub-agents (frontend-builder, acf-architect, etc.)
├── commands/                          Slash commands (/implement, /make-dynamic, etc.)
├── cheatsheet/                        Single-page HTML reference (open in browser)
├── snippets/                          Files copied into the theme by /setup-claude
│   ├── helpers.php                    aiims_img() + aiims_svg_kses()
│   ├── acf-setup.php                  Local JSON + phone validation + admin tweaks
│   ├── custom-functions.php           Single-require pattern hub
│   ├── template-homepage.php          Front-page rigid template
│   ├── template-default.php           Universal flexible-content template
│   ├── section-_example.php           Reference partial (all patterns demonstrated)
│   ├── group_default_template_sections.json   Seed ACF Flexible Content group
│   └── README.template.md             Becomes the workspace-root README after setup
│
└── ─── Template-internal (DO NOT copy into projects) ───
    ├── RETRO.md                       Master retro doc (one section per finished project)
    ├── RETRO-WORKFLOW.md              The per-project continuous-improvement loop
    ├── CHANGELOG.md                   Versioned template releases
    └── completed-projects/            Frozen snapshots of every shipped project's .claude/
```

## What survives in the user's project after /setup-claude

- `.claude/CLAUDE.md` (master rules — Claude reads automatically)
- `.claude/skills/*` (workflow recipes, minus `setup-claude/`)
- `.claude/agents/*` (specialists)
- `.claude/commands/*` (daily slash commands, minus `setup-claude.md`)
- `.claude/cheatsheet/` (user's daily HTML reference)
- `.claude/settings.local.json`

## What gets deleted after /setup-claude

- `.claude/snippets/` (contents copied to theme already)
- `.claude/skills/setup-claude/`
- `.claude/commands/setup-claude.md`
- This `README.md` you're reading (only if it ended up in the user's `.claude/`)

You're left with a clean `.claude/` plus a personalized workspace-root `README.md` you actually look at.
