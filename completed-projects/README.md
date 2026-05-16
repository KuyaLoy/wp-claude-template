# Completed Projects — frozen snapshots

This folder holds **frozen snapshots** of every finished project's `.claude/` folder.

## Why this folder exists

After a project ships, you copy its `.claude/` here. This snapshot is your **evidence base** for retros:
- What did this project's template look like at handoff?
- What got hand-edited vs what came from the template as-is?
- Which custom seeders / snippets / partials did this project need?

When you retro the project (see `../RETRO-WORKFLOW.md`), Claude diffs the snapshot against the current template, finds the gaps, and proposes v(n+1) improvements.

## Folder structure

> **Note:** the folders shown below are **illustrative** of how this directory will look once you have completed projects to archive. As of v3.1.0 no snapshots have been added yet — the first one will be `jg-vertical/` after its retro.

```
completed-projects/
├── README.md          ← this file
├── jg-vertical/       ← (illustrative) v1.0.0 — the first real project
│   ├── .claude/       (frozen snapshot)
│   └── notes.md       (date shipped, key learnings, link to retro section)
├── abc-painting/      ← (illustrative) future project
│   ├── .claude/
│   └── notes.md
└── ...
```

## How to add a new completed project

```bash
# 1. Copy the project's .claude folder here (frozen, never edited)
cp -r <project>/wp-content/themes/<theme>/.claude  ./completed-projects/<project-name>/.claude

# 2. Write a quick notes.md (3 minutes)
echo "# <project-name>

- Date shipped: YYYY-MM-DD
- Template version used at start: v1.0.0
- Template version at ship: v1.0.0 (or v1.0.1 if you bumped during build)
- Key learnings: see RETRO.md ## Retro — <project-name> (<date>)
- Live URL: https://...
- Staging URL: https://...
- Github: https://github.com/..." > ./completed-projects/<project-name>/notes.md

# 3. Run retro
# See ../RETRO-WORKFLOW.md
```

## What NOT to put here

- ❌ The whole project (theme, plugins, uploads) — way too big. Just the `.claude/` folder + a notes.md
- ❌ Credentials or secrets — these snapshots may be public
- ❌ Live database dumps — privacy / data risk

## Privacy note

If the project's `.claude/` contains any sensitive paths or hostnames (e.g. internal staging URLs), strip them before committing. The `notes.md` is fine to keep references like the public live URL.
