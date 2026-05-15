# MCP install — Cowork vs Claude Code

The template works in both **Claude Cowork** (desktop app) and **Claude Code** (CLI / VSCode). It uses two external MCP servers:

- **Figma MCP** — for fetching design context, screenshots, variables (used by `implement-figma-section`, `setup-claude`, `frontend-builder`)
- **Browser MCP** — for live-render screenshots in `/pixel-check`

This document lists how to install each on each platform.

---

## Figma MCP

The template's skills probe for tools named `mcp__Figma__*` (e.g. `mcp__Figma__get_metadata`, `mcp__Figma__get_design_context`). Both platforms surface the same names when the official Figma MCP is installed.

### Cowork

Built-in once the Figma connector is enabled in your Cowork settings. No CLI install needed.

1. Open Cowork → Settings → Connectors
2. Find Figma → Connect → authorise in browser
3. Restart your session — `mcp__Figma__*` tools become available

### Claude Code

```bash
claude mcp add figma
```

Follow the prompts to authenticate. Then restart your `claude` session.

To verify it works:

```bash
claude mcp list
# Should show 'figma' as a connected server
```

If the install pattern has changed since this doc was written, see Figma's MCP docs at https://www.figma.com/developers — the tool names should still be `mcp__Figma__*` regardless of installer evolution.

### Without the Figma MCP

Every skill in this template degrades gracefully. If Figma MCP isn't available:

- `setup-claude` falls back to placeholder brand colors
- `implement-figma-section` asks the user to paste a screenshot inline + describe the layout
- `frontend-builder` asks the same

You can still build sections; you'll just type more.

---

## Browser MCP (for /pixel-check)

The template's `pixel-perfect-verify` skill probes for any tool whose name contains `Chrome`, `browser`, `playwright`, `screenshot`, or `navigate`. Cowork ships a Chrome MCP; Claude Code requires manual install if you want AUTO mode.

### Cowork (primary supported path)

Already built-in. The tools surface as:

- `mcp__Claude_in_Chrome__*` — full browser automation
- `mcp__Control_Chrome__*` — lightweight tab/page control

Nothing to install. `/pixel-check` runs in AUTO mode by default.

### Claude Code (optional)

Pick one:

**Playwright** (most common):

```bash
claude mcp add playwright
```

This installs Chromium under the hood. Tools surface as `mcp__playwright__*`.

**Puppeteer** (less common):

```bash
claude mcp add puppeteer
```

Either works — the probe in `pixel-perfect-verify/SKILL.md` matches by tool-name keywords, not exact server name.

### Without a browser MCP

`/pixel-check` falls back to MANUAL mode and walks the user through a strict side-by-side checklist using Chrome DevTools + a Figma window. Not as fast as AUTO, but no precision lost — the rigor reminder in the skill enforces actually using the inspector instead of eyeballing.

---

## Quick reference table

| Need | Cowork | Claude Code |
|---|---|---|
| Figma data | Settings → Connectors → enable Figma | `claude mcp add figma` |
| Browser screenshots | Built-in Chrome MCP | `claude mcp add playwright` (optional) |
| No MCP at all | Skills degrade to manual workflows | Skills degrade to manual workflows |

---

## Verifying a session has what it needs

If a skill is misbehaving, ask Claude:

```
Which MCPs are connected in this session? List the mcp__ tools available.
```

It will list the tool prefixes that are currently usable. Compare against:

- Expected: `mcp__Figma__*` and (Cowork) `mcp__Claude_in_Chrome__*` / `mcp__Control_Chrome__*` OR (Claude Code) `mcp__playwright__*`
- Missing means you haven't installed/enabled the corresponding MCP — come back to this doc.

---

## Troubleshooting

**`mcp__Figma__get_design_context` returns "node not found"**
The Figma URL needs to point at a specific node, not just a file. Click the frame in Figma, copy the link from the share menu — it should include `?node-id=...`. Without that, the MCP doesn't know which frame to fetch.

**`/pixel-check` says "browser MCP unavailable" in Cowork**
The Chrome MCP may have disconnected. Restart the Cowork session, or check Settings → Connectors → Chrome.

**`claude mcp add figma` fails with "command not found"**
You're on an older Claude Code build. Update with `claude update` (or follow the install instructions at https://docs.claude.com/claude-code), then retry.
