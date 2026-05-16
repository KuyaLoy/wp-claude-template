# Per-Project Retro Workflow — the continuous-improvement loop

> **The single most important thing about this template:** it's not static. Every project you finish makes the next project's template better.

---

## The loop in one diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                                                                         │
│   1. NEW PROJECT          2. BUILD                  3. SHIP             │
│   ┌─────────────┐         ┌─────────────┐          ┌─────────────┐      │
│   │  Clone the  │         │ Use Claude  │          │   Hand off  │      │
│   │ wp-claude-  │  ───→   │  to build   │   ───→   │ to client / │      │
│   │  template   │         │ section by  │          │   staging   │      │
│   │ to project  │         │   section   │          │             │      │
│   └─────────────┘         └─────────────┘          └─────────────┘      │
│                                                          │              │
│                                                          ▼              │
│                                                  ┌─────────────┐        │
│                                                  │  4. RETRO   │        │
│                                                  │   (1 hour)  │        │
│                                                  └──────┬──────┘        │
│                                                         │               │
│                                                         ▼               │
│   8. NEXT PROJECT         7. IMPROVE              5. PASTE              │
│   ┌─────────────┐         ┌─────────────┐         ┌─────────────┐       │
│   │ Start with  │         │ Edit CLAUDE │         │   Paste     │       │
│   │  improved   │  ←───   │   .md, add  │   ←───  │ project's   │       │
│   │  template   │         │  commands,  │         │  template   │       │
│   │             │         │ etc.        │         │ back into   │       │
│   └─────────────┘         └─────────────┘         │ wp-claude-  │       │
│         ▲                        ▲                │  template   │       │
│         │                        │                └──────┬──────┘       │
│         │                        │                       │              │
│         │                  ┌─────┴──────┐                ▼              │
│         │                  │  6. WRITE  │         (without touching     │
│         │                  │  RETRO.md  │          the finished         │
│         │                  └────────────┘          project)             │
│         │                                                               │
│         └─────────────── repeat forever ───────────────────────────────┘│
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

The current project becomes a **frozen learning artifact**. You never go back and edit it. You take its lessons and update the template for the next project.

---

## Step-by-step: how to retro a finished project

After you ship a project, before you start the next one, do this. **It takes about an hour and pays back tenfold.**

### Step 1 — Paste the project's `.claude/` folder back into `wp-claude-template/`

```bash
# Copy the live project's .claude folder into your template repo
# so you can diff against the original template

cd D:\path\to\wp-claude-template
cp -r D:\path\to\completed-project\wp-content\themes\theme-name\.claude  .\completed-projects\<project-name>-snapshot
```

You now have a frozen snapshot of how this project's setup ended up — with any project-specific tweaks, additions, hand-edits, etc.

### Step 2 — Open Claude on the template repo

Open Claude (Cowork or Claude Code) in the `wp-claude-template` folder.

### Step 3 — Run the retro prompt

```
I just finished a project at <project-name-snapshot>. I want to retro it
against the current template so I can improve v<next-version>.

Please:
1. Read RETRO.md to understand the existing retro format
2. Compare completed-projects/<project-name>-snapshot/ vs claude-setup/
3. Identify what worked, what was painful, what got hand-edited or added
4. Write a new section in RETRO.md titled "## Retro — <project-name>
   (<date>)" with the findings
5. Propose specific v<next> updates as concrete file changes
6. Wait for me to approve each one before making changes
```

Claude will produce a diff-driven retro showing exactly what changed from template-as-shipped to template-as-actually-used. That's gold for finding template gaps.

### Step 4 — Approve the improvements one by one

Don't auto-apply. Read each proposed change, approve or push back, let Claude make the changes to:
- `CLAUDE.md` (new rules)
- `commands/` (new slash commands)
- `skills/` (new workflows)
- `snippets/` (new reusable files)
- `cheatsheet/` (new entries)

### Step 5 — Bump the version in `CHANGELOG.md`

```
v<n>.0.0 — Improvements derived from <project-name> retro

ADDED
  - ...

CHANGED
  - ...

FIXED
  - ...
```

### Step 6 — Commit AND push to GitHub

```bash
git add .
git commit -m "v<n>.0.0 — Retro from <project-name>"
git tag v<n>.0.0
git push
git push --tags
```

**Pushing is non-optional.** Other devs on the team (or future-you starting from a new machine) need this version. The template is only useful if everyone's on the latest improvements. Push every time.

Now the **next** project starts from this improved version.

> **Convention:** the GitHub repo's `main` branch always reflects the latest retro'd version. Pinned releases (git tags) let any project re-pin to a specific older version if needed.

### Step 7 — Move the snapshot to `completed-projects/`

Keep snapshots forever — they're searchable evidence of every decision. Useful when a new project hits a problem and you remember "we solved this on Project X."

---

## What to look for during a retro

Use this checklist. For each, write down what happened on the project:

### 🟢 What worked well — keep + document

- [ ] Which skills triggered correctly without you forcing them?
- [ ] Which slash commands did you use most?
- [ ] Which snippets did you copy-paste 3+ times?
- [ ] Did the static → dynamic → seed flow stay intact for every section?
- [ ] Were ACF JSON syncs clean?
- [ ] Were seeders self-deleting properly?

### 🔴 What was painful — fix the template

- [ ] Where did you have to write a custom seeder for something that should be reusable?
- [ ] What did you hand-edit in `CLAUDE.md` because the rules didn't cover the case?
- [ ] What did Claude assume that it should have asked you about?
- [ ] What did you have to rebuild from scratch that should be a snippet?
- [ ] Where did the same code appear 3+ times across sections (extract to component)?
- [ ] What client request couldn't be solved by an existing pattern?

### 🟡 Patterns that emerged — promote to template

- [ ] Did you build a custom partial (modal, sticky bar, popup, etc.) that other projects would also want?
- [ ] Did you write a custom helper function that's project-agnostic?
- [ ] Did you create a useful debug/diagnostic page or seeder?
- [ ] Did you discover a clever use of an existing tool?

### 🆘 Workarounds and hacks — eliminate

- [ ] Did you inline CSS because Tailwind wasn't compiling? (Add a "check the watcher" rule)
- [ ] Did you bypass the workflow? (Why? Strengthen the workflow.)
- [ ] Did you `force push` because of a workflow conflict? (Document the right path.)

---

## Anti-pattern: editing the completed project

**Don't.**

The completed project is a frozen artifact. If you find a bug after handoff, fix it in the project's own repo — not by going back and editing the template snapshot. The snapshot is **evidence of what the template-as-it-was produced**, and that evidence is more valuable than fixing one bug in one file.

The right cycle:
1. Bug found → fix in the client project's own repo (live, not snapshot)
2. Identify that the bug was caused by a template gap
3. Fix the template (`wp-claude-template`)
4. Bump the template version
5. Note in RETRO.md: "Bug X was found in Project Y because template was missing Z; fixed in v<n>"

This keeps the retro data clean and lets you spot patterns across projects.

---

## Example retro entries

Imagine your second project on the template adds these to RETRO.md:

```markdown
## Retro — ABC Painting Co (2026-06)

### What worked
- /audit caught 14 broken links in one pass — huge time saver
- /scaffold-quote-modal worked first try on this project

### What was painful
- Client wanted a "Before / After" image slider component — not in template
  → New snippet: `snippets/before-after-slider.php`
  → New brief example: `briefs/before-after-section.md`
- ACF "Before / After" needed image-pair grouping — pattern wasn't documented
  → Added to CLAUDE.md: "Use ACF group field for paired images"

### Hand edits that became permanent
- I hand-edited `hero-2-service.php` to add a `data-aos` attribute (for AOS lib)
  → Better solution: should be configurable via $args['animation_in']
  → Added to v3: standardise animation_in across all hero variants

### Template changes for v3
1. Add `snippets/before-after-slider.php` partial + JS
2. Add `commands/scaffold-before-after.md`
3. Add `briefs/before-after-section.md` example
4. Standardise `animation_in` arg across all hero variants
5. Update CLAUDE.md image rules — mention group-for-pairs pattern
```

Three projects in, you'll have a battle-tested template that wins time on every new build.

---

## Quick reference card

| When | What you do | Time |
|---|---|---|
| **Project starts** | `git clone wp-claude-template` + customise for client | 30 min |
| **During project** | Use the workflow. Don't touch the template. | (build time) |
| **Project ships** | Hand off to client | (deploy) |
| **After ship** | Run retro on the project | ~1 hr |
| **After retro** | Bump template version, commit | ~30 min |
| **Next project** | Start with improved template | 30 min |

**Total retro overhead per project: ~90 minutes. Value across all future projects: hours saved per build.**

---

## See also

- `RETRO.md` — the master retro doc (one section per project)
- `CHANGELOG.md` — versioned list of template changes
- `cheatsheet/index.html` — daily reference (also continuously improves)

