---
name: pixel-perfect-verify
description: Compare the live browser render to the Figma frame and report deviations. Triggers on "/pixel-check", "compare to figma", "is this pixel perfect", "verify section <name>". Auto-detects the environment — uses browser automation (Cowork's Claude in Chrome OR Playwright/Browser MCP) when available; falls back to a strict manual checklist when not.
---

# Pixel-Perfect Verify

Side-by-side compare live render vs. Figma. The skill auto-detects what tools are available and uses the most accurate method.

## Environment detection (first thing this skill does)

Probe the available tools silently before doing anything:

| Available tool | Environment | Method |
|---|---|---|
| `mcp__Claude_in_Chrome__*` or `mcp__Control_Chrome__*` | **Cowork mode** (primary supported) | Auto: take screenshot of live, compare to Figma frame, report diffs |
| `mcp__playwright__*` (or other browser MCP user installed) | **Claude Code with browser MCP** | Same as above; manual install required (see `INSTALL-MCPS.md`) |
| No browser MCP available | **Claude Code without browser tools** | Manual checklist with strict rigor |

```
# Probe (silent — don't ask the user)
- Look for any mcp tool whose name contains: Chrome, browser, playwright, screenshot, navigate
- If found: AUTO mode
- If not found: MANUAL mode
```

The supported AUTO path is Cowork's built-in Chrome MCP. Claude Code users can also AUTO-screenshot via Playwright but have to install it themselves; see `INSTALL-MCPS.md` for the install command. If neither is present, MANUAL mode runs the user through a strict checklist instead of guessing.

## AUTO mode (browser MCP available)

### 1. Identify the section + URL

If the user gave a section name → focus on `theme/templates/parts/section-<name>.php`. Else iterate over all section parts.

For each section, find the Figma URL:
- The section file's PHP doc comment (`Source: <url>`)
- `briefs/<section>.md` if it exists
- Ask the user if not found

Determine the live URL where the section renders:
- Homepage section → `<local-url>/`
- Flexible section → page using template-default with the layout in flexible_sections (might need to ask which page or auto-detect)

### 2. Take screenshots

Browser MCP:
- Navigate to the live URL
- Wait 1-2 seconds for animations + image loads
- Take desktop screenshot at the project's master frame width (commonly 1920px) — set viewport
- Take mobile screenshot at 390×844 (iPhone 13)

Figma MCP:
- `get_screenshot` of the same frame (caps at 1024px max edge — for accurate pixel diff cross-reference `get_design_context` for actual frame dimensions and styling)
- For mobile, fetch the mobile-frame screenshot if there's a separate mobile design

### 3. Compare

For each element in the section:
- **Spacing** — does padding/margin match within 2px?
- **Color** — do hex/RGB values match (allow 5% delta on subtle gradients/shadows)?
- **Typography** — font family, size, weight, line-height, letter-spacing (see "Typography drift" below for the special case)
- **Position** — element placement within parent
- **Asset quality** — is image at intended resolution? Is SVG crisp?
- **Hover/focus** — if Figma shows a hover variant, trigger via JS execute and compare

### 3a. Typography drift (Figma vs browser rendering)

**This is the most common "looks slightly off" cause.** Figma uses its own text renderer; browsers use the system/font-engine renderer. Even when font-family, size, and weight all match exactly, the rendered text can differ visually because:

- **Figma letter-spacing** uses percent units relative to font-size. Browser CSS `letter-spacing` uses em/px directly. Figma 0% ≈ browser default, but Figma's default often LOOKS tighter than the browser's because of the way Figma anti-aliases edges.
- **Figma line-height** can be "Auto" (resolves to ~1.2× font-size depending on font metrics). Browser CSS default depends on font + browser. A heading rendered at "Auto" in Figma will often look tighter than the same heading at browser default `line-height: normal`.
- **Figma font-rendering** uses `optimizeLegibility` equivalent. Browser default varies.

**When AUTO mode detects typography drift, suggest specific Tailwind adjustments:**

| Symptom | Likely cause | Suggested fix (Tailwind) |
|---|---|---|
| Heading text looks "looser" / "wider" than Figma | Browser letter-spacing > Figma | Add `tracking-tight` (-0.025em) or arbitrary `tracking-[-0.02em]` |
| Heading lines look "taller" than Figma | Browser line-height > Figma's Auto | Add `leading-tight` (1.25) or arbitrary `leading-[1.1]` or `leading-none` (1.0) |
| Body text feels "airy" compared to Figma | Browser line-height too loose | Add `leading-relaxed` (1.625) vs `leading-snug` (1.375) — pick to match Figma's computed line-height |
| Display text (large headings) looks "thin" in browser | Browser smoothing differs | Add `[text-rendering:optimizeLegibility]` or `subpixel-antialiased` arbitrary, OR use `font-feature-settings` if specific OpenType features are needed |
| Letters look slightly clipped on left/right edges | `font-feature-settings` mismatch | Try `font-feature-settings: 'kern' 1` via arbitrary class, or `font-stretch` adjustments |
| Numbers look misaligned in cards/tables | Default numeric variant differs | `tabular-nums` (proportional → tabular) or `oldstyle-nums` (lining → oldstyle) |

**Default rule of thumb:** if a heading in Figma looks tight + bold, try `tracking-tight leading-tight` as the first adjustment. Most "off but I can't say why" typography issues resolve there.

**In the report, flag every typography difference under "Typography drift" with a specific suggestion the user can copy-paste:**

```
### Typography drift
1. h1 heading-text: Figma renders ~3px tighter than browser.
   Suggested: add `tracking-tight leading-none` (or arbitrary `tracking-[-0.02em] leading-[1.05]`)

2. Body paragraph: line-height 1.6 in browser, ~1.4 in Figma.
   Suggested: change `leading-relaxed` → `leading-snug`

3. CTA button text: appears slightly thicker in browser.
   Suggested: try `font-medium` instead of `font-semibold`, OR add `antialiased`
```

### 4. Categorize findings

| Severity | Examples |
|---|---|
| Critical | Wrong color, illegible text, broken layout, missing element |
| Important | Spacing >5px off, wrong font weight, animation missing, asset blurry |
| Minor | Sub-pixel spacing, anti-alias differences, hover state slightly off |

### 5. Output structured diff report

```
## Pixel-check (AUTO): section-home-hero

### Live URL
http://jgvertical.test/

### Figma frame
https://www.figma.com/design/abcd/?node-id=1234-5678

### Critical (0)
None

### Important (2)
1. Heading font-weight is 600 in Figma, rendered as 500.
   Fix: `font-medium` → `font-semibold` on the h1

2. CTA padding-x is 32px in Figma, rendered as 24px.
   Fix: `px-6` → `px-8`

### Minor (1)
1. Image has 1px gap on right edge — sub-pixel rounding. Safe to ignore.

### Mobile (390px)
- ✓ Stack order matches
- ⚠ Heading wraps to 3 lines on mobile (Figma shows 2). Likely due to longer placeholder copy.

### Recommend
Apply the 2 Important fixes, hard-refresh, re-run /pixel-check.
```

## MANUAL mode (no browser MCP — Claude Code without browser tools)

The skill cannot screenshot the live render itself. Walk the user through a strict manual diff. **Don't be lax** — the user explicitly chose this skill because they want certainty.

### Output a manual checklist with rigor reminder

```
## Pixel-check (MANUAL): section-home-hero

> Browser MCP is not available in this session. Here's a manual side-by-side diff.
> **Be strict.** Pixel-perfect means matching within 1-2px. Don't eyeball it; use the inspector.

### Open both side by side
- Browser at: http://<project>.test/<page-with-section>
- Figma at:   <figma-frame-url>

### Use a measurement tool for every check
- **Browser:** Chrome DevTools → Inspect → hover element → see padding/margin overlay
- **Figma:** click element → right panel shows W/H/padding values
- **Overlay diff:** install "PerfectPixel" Chrome extension OR drag a Figma screenshot over the live page at 50% opacity

### Strict checklist (verify EACH item, don't skim)

#### Container
- [ ] max-width matches
- [ ] Horizontal padding matches at every breakpoint
- [ ] Container is centered

#### Section padding (top/bottom)
- [ ] Desktop py value matches
- [ ] Tablet py value matches
- [ ] Mobile py value matches

#### Heading
- [ ] Font family matches
- [ ] Font size matches at every breakpoint
- [ ] Font weight matches
- [ ] Line-height matches
- [ ] Letter-spacing matches
- [ ] Color matches (use color picker, not your eyes)
- [ ] Margin from neighbors matches

#### Body text
- [ ] All of the above for body

#### Image
- [ ] Position within container matches
- [ ] Size at each breakpoint matches
- [ ] Aspect ratio matches
- [ ] Border radius matches
- [ ] Object-fit setting matches

#### CTA / Buttons
- [ ] Background color matches
- [ ] Text color matches
- [ ] Padding (X and Y) matches
- [ ] Border radius matches
- [ ] Hover state matches
- [ ] Focus state visible
- [ ] Touch target ≥ 44×44px on mobile

#### Animations
- [ ] data-reveal direction + delay matches Figma annotations (if any)
- [ ] No janky reflow on load

### Reporting back
After you check, paste back:
- Anything off → I'll fix it
- All matches → "looks good" → we move to dynamic phase

### Don't skip the inspector
The most common pixel-perfect failure is "looks close" eyeballing. Open DevTools, hover, read the actual padding/margin/font values, compare to Figma's value panel. If different → not pixel-perfect.
```

## Things you must never do

- Skip the rigor reminder in MANUAL mode — the whole point of this skill is precision
- Approve "close enough" without confirming — pixel-perfect means pixel-perfect
- Use only `get_screenshot` from Figma without cross-checking actual design tokens (Figma screenshot caps at 1024px and downscales; always reference `get_design_context` for accurate values)
- Compare on the wrong viewport (desktop screenshot vs mobile design — easy mistake)
