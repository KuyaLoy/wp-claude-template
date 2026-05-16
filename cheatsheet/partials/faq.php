<section id="faq" data-searchable data-search="faq questions answers help glossary explanation">
    <div class="section-header">
        <span class="section-icon">❓</span>
        <div>
            <h2>FAQ</h2>
            <p>Click any question to expand. Searches the answer text too — use the search bar to find anything.</p>
        </div>
    </div>

    <h3>Basics</h3>

    <div class="faq-item" data-open="false">
        <div class="faq-question">
            <span>What is this template?</span>
            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            <p>A starter kit for building WordPress + ACF Pro + Tailwind 4 sites by chatting with Claude. Designers / non-developers describe what they want; Claude builds the actual PHP/HTML/CSS section by section, pixel-matched to Figma. Editors then maintain content via WP Admin like any normal site.</p>
        </div>
    </div>

    <div class="faq-item" data-open="false">
        <div class="faq-question">
            <span>What's the difference between Cowork and Claude Code?</span>
            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            <p><strong>Cowork</strong> is the desktop chat app — friendly UI, file picker, drag-and-drop. Best for designers, project managers, content editors. Built-in Chrome + Figma connectors.</p>
            <p><strong>Claude Code</strong> is the CLI tool you run from a terminal — better for developers who already live in the terminal/VSCode. MCPs installed via <code>claude mcp add ...</code>.</p>
            <p>Both work identically with this template. Use whichever you're comfortable with — switch anytime, the template doesn't care.</p>
        </div>
    </div>

    <div class="faq-item" data-open="false">
        <div class="faq-question">
            <span>Do I need to know coding?</span>
            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            <p>No. You need to be able to <strong>look at a live preview and tell Claude when something doesn't match Figma</strong>. That's the only skill required. You don't write code, write CSS, or open a code editor.</p>
            <p>You do need a developer (yours or borrowed) for the initial setup — installing WordPress, ACF Pro, Tailwind 4. After that, day-to-day building is conversational.</p>
        </div>
    </div>

    <div class="faq-item" data-open="false">
        <div class="faq-question">
            <span>What's a "section"?</span>
            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            <p>One horizontal band on a page. The "hero section" is the big banner at the top. The "testimonials section" is the row of quotes. The "footer" is technically a section too. We build one section at a time, never the whole page in one shot — that's the entire workflow.</p>
        </div>
    </div>

    <div class="faq-item" data-open="false">
        <div class="faq-question">
            <span>Why static first, then dynamic?</span>
            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            <p><strong>Static phase</strong>: Claude writes the section with hard-coded text + image paths exactly matching Figma. You can verify pixel-perfectness without ACF complexity getting in the way.</p>
            <p><strong>Dynamic phase</strong>: once static is approved, Claude swaps the hard-coded values for ACF fields. Editors can then change content via WP Admin.</p>
            <p>If you skip static and go straight to dynamic, every visual problem becomes 3x harder to debug because you can't tell if the bug is in the design code or the ACF wiring. Static-first is non-negotiable.</p>
        </div>
    </div>

    <h3 style="margin-top: var(--space-6);">Working with Figma</h3>

    <div class="faq-item" data-open="false">
        <div class="faq-question">
            <span>What if Figma changes mid-build?</span>
            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            <p>Tell Claude: "The Figma for home-hero changed — heading is now 56px not 48px. Update it." Claude reads Figma again and adjusts. The brief at <code>briefs/home-hero.md</code> gets updated automatically as a paper trail.</p>
        </div>
    </div>

    <div class="faq-item" data-open="false">
        <div class="faq-question">
            <span>What if I only have a desktop Figma frame?</span>
            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            <p>Paste one URL. Claude builds desktop and auto-makes it responsive (web → tablet → mobile) using sensible defaults. Every mobile guess gets surfaced in the reply so you can confirm by previewing on phone-width.</p>
            <p>If you have BOTH desktop and mobile Figma frames, paste both URLs — Claude pixel-matches both, no guessing.</p>
        </div>
    </div>

    <div class="faq-item" data-open="false">
        <div class="faq-question">
            <span>Claude says "Figma is disconnected" — what now?</span>
            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            <p><strong>This is intentional, not a bug.</strong> The template refuses to build from screenshots because they're downscaled and produce inconsistent code across sections.</p>
            <ul>
                <li><strong>Cowork:</strong> Settings → Connectors → Figma → Reconnect</li>
                <li><strong>Claude Code:</strong> <code>claude mcp list</code> to check, <code>claude mcp add figma</code> to reinstall</li>
            </ul>
            <p>Then back in chat: <code>continue</code>. Claude resumes right where it stopped, no work lost.</p>
        </div>
    </div>

    <div class="faq-item" data-open="false">
        <div class="faq-question">
            <span>What about typography looking off in the browser?</span>
            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            <p>Common — Figma renders text differently than browsers. Letter-spacing and line-height especially. Run <code>/pixel-check &lt;section&gt;</code> and Claude flags typography drift with specific Tailwind tweaks like <code>tracking-tight leading-none</code>.</p>
        </div>
    </div>

    <h3 style="margin-top: var(--space-6);">Workflow</h3>

    <div class="faq-item" data-open="false">
        <div class="faq-question">
            <span>Do I have to write a brief file before building?</span>
            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            <p><strong>No.</strong> Brief files are optional. When you paste <code>@home-hero &lt;figma-url&gt;</code>, Claude auto-creates <code>briefs/home-hero.md</code> from the Figma data + your chat context. You can write one upfront for complex sections, but the workflow doesn't require it.</p>
        </div>
    </div>

    <div class="faq-item" data-open="false">
        <div class="faq-question">
            <span>What happens if I change my mind mid-build?</span>
            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            <p>Just tell Claude in plain English: "Make the heading smaller. Move the CTA above the image. Use a darker color for the background." Claude updates the section file accordingly. Iterate until it looks right, THEN say "make it dynamic".</p>
        </div>
    </div>

    <div class="faq-item" data-open="false">
        <div class="faq-question">
            <span>What if I want to undo something?</span>
            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            <p>Use git. From your terminal: <code>git status</code> shows what changed, <code>git diff</code> shows the changes, <code>git checkout &lt;file&gt;</code> reverts a specific file, <code>git reset --hard HEAD</code> reverts everything to the last commit. If you don't use git, ask your dev to set it up — this is non-negotiable on any serious project.</p>
        </div>
    </div>

    <div class="faq-item" data-open="false">
        <div class="faq-question">
            <span>Can multiple people work on the same project?</span>
            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            <p>Yes, via git. Each person clones the project, works on their local WordPress, commits + pushes. ACF schemas live in <code>acf-json/</code> as files — git-trackable. The only thing not in git is media-library content (images uploaded via WP Admin) and the database — those need separate sync.</p>
        </div>
    </div>

    <div class="faq-item" data-open="false">
        <div class="faq-question">
            <span>Can I use this template on an existing WordPress site?</span>
            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-with="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            <p>Yes, but the existing theme should already have Tailwind 4 + ACF Pro installed. If not, your dev needs to add those first. The <code>.claude/</code> folder just adds the build workflow on top — it doesn't conflict with existing theme code.</p>
        </div>
    </div>

    <h3 style="margin-top: var(--space-6);">Going live / shipping</h3>

    <div class="faq-item" data-open="false">
        <div class="faq-question">
            <span>How do I deploy to staging / production?</span>
            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            <p>Run <code>/ship-check</code> first — Claude audits accessibility, performance, QA, and ACF sync state. Fix any criticals. Then your dev handles the actual deploy (zipping the theme, uploading via WP Admin, syncing the database). The template doesn't do deployment itself — that's outside <code>.claude/</code> scope.</p>
        </div>
    </div>

    <div class="faq-item" data-open="false">
        <div class="faq-question">
            <span>What about SEO?</span>
            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            <p>The template produces semantic HTML (proper headings, alt text on images, fast load via lazy-loading + WebP). For metadata / sitemaps / structured data, install Yoast SEO or Rank Math plugin. Those are outside Claude's scope.</p>
        </div>
    </div>

    <div class="faq-item" data-open="false">
        <div class="faq-question">
            <span>What about accessibility?</span>
            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            <p>Built in. Every section uses semantic HTML, ARIA labels where needed, focus states on interactive elements, touch targets ≥ 44×44px on mobile, alt text on images, body text ≥ 16px on mobile. <code>/ship-check</code> runs a WCAG 2.1 AA audit before deploy.</p>
        </div>
    </div>

    <h3 style="margin-top: var(--space-6);">The AI Prompt Builder</h3>

    <div class="faq-item" data-open="false">
        <div class="faq-question">
            <span>Does my Gemini API key leave my computer?</span>
            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            <p><strong>No.</strong> The key lives in <code>cheatsheet/.env</code> (gitignored — never committed). When you click "Polish", your browser sends the rough text to <code>api/polish.php</code> on your local server. The PHP file reads the key, calls Gemini server-side, returns the polished result. The key never enters the browser, never goes to git, never appears in network requests visible to anyone but Google.</p>
        </div>
    </div>

    <div class="faq-item" data-open="false">
        <div class="faq-question">
            <span>How much does Gemini cost?</span>
            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            <p>Free tier: 15 requests/minute, ~1500 requests/day for <code>gemini-2.0-flash-exp</code> via Google AI Studio. More than enough for prompt-polishing throughout a build. No credit card needed for the free tier.</p>
        </div>
    </div>

    <div class="faq-item" data-open="false">
        <div class="faq-question">
            <span>Why not just type the prompt myself?</span>
            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="faq-answer">
            <p>You can — both work. The AI Prompt Builder is for when you don't remember the exact syntax, when your description is rough English, or when you want to make sure your prompt follows the template's current conventions (it reads <code>CLAUDE.md</code> for context). For repeat builds you'll know the shorthand by heart; the polisher is for the rough first drafts.</p>
        </div>
    </div>
</section>
