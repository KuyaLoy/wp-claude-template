# claude-setup/ — internal

> This file is internal docs for what's inside this folder. After you copy this folder into your theme and rename to `.claude/`, you can ignore this file. Daily use is via the workspace-root `README.md` that `/setup-claude` writes for you.

## What you do once per project

1. Copy this entire `claude-setup/` folder into your theme directory
2. Rename it to `.claude/`
3. Open the project in Claude
4. Type: `/setup-claude`
5. Answer the questions (brand colors, font, container, phone format)
6. Done — Claude writes helpers, two page templates, ACF JSON, and a personalized `README.md` at your workspace root, then deletes its own setup files

## What's inside

```
claude-setup/                          ← this folder
├── CLAUDE.md                          Master rules — Claude reads on every session
├── README.md                          (this file)
├── settings.local.json                Bash + read permissions
├── skills/                            Workflow recipes (auto-trigger by phrasing)
├── agents/                            Sub-agents (frontend-builder, acf-architect, etc.)
├── commands/                          Slash commands (/implement, /make-dynamic, etc.)
└── snippets/                          Files copied into the theme by /setup-claude
    ├── helpers.php
    ├── acf-setup.php
    ├── template-homepage.php
    ├── template-default.php
    ├── section-_example.php
    ├── group_default_template_sections.json
    └── README.template.md             ← becomes the workspace-root README
```

## What survives after /setup-claude

- `.claude/CLAUDE.md` (master rules — Claude reads automatically)
- `.claude/skills/*` (workflow recipes, minus setup-claude/)
- `.claude/agents/*` (specialists)
- `.claude/commands/*` (daily slash commands, minus setup-claude.md)
- `.claude/settings.local.json`

## What gets deleted after /setup-claude

- `.claude/snippets/` (contents copied to theme already)
- `.claude/skills/setup-claude/`
- `.claude/commands/setup-claude.md`
- This `README.md` you're reading

You're left with a clean `.claude/` plus a personalized workspace-root `README.md` you actually look at.
