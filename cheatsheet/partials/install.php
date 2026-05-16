<section id="install" data-searchable data-search="install installation setup boilerplate underscoretw sage laragon prerequisites">
    <div class="section-header">
        <span class="section-icon">📦</span>
        <div>
            <h2>Install — first time on a new project</h2>
            <p>Works with any WordPress theme that uses ACF Pro + Tailwind 4. Auto-detects underscoretw, plain Underscores, GeneratePress dev variants, custom starters.</p>
        </div>
    </div>

    <h3>Prerequisites</h3>
    <div class="grid grid-2">
        <div class="card">
            <h4>🖥 Local WordPress</h4>
            <p style="font-size: 0.8125rem;">Laragon (Windows) or Local by Flywheel (Mac). Sets up Apache + MySQL + PHP + the <code>.test</code> hostname automatically.</p>
        </div>
        <div class="card">
            <h4>🔌 ACF Pro plugin</h4>
            <p style="font-size: 0.8125rem;">Required (the template's whole "dynamic phase" relies on ACF). Install and activate before running setup. Free ACF doesn't have Flexible Content; needs Pro.</p>
        </div>
        <div class="card">
            <h4>🎨 Tailwind 4 in your theme</h4>
            <p style="font-size: 0.8125rem;">A working <code>npm run watch</code> that produces compiled CSS. Recommended: <a href="https://underscoretw.com" target="_blank" rel="noopener">underscoretw.com</a> theme generator (Underscores + Tailwind 4 ready to go).</p>
        </div>
        <div class="card">
            <h4>🌐 Figma access</h4>
            <p style="font-size: 0.8125rem;">A Figma account with view access to the project designs. The Figma MCP needs to be connected (see <a href="../INSTALL-MCPS.md">INSTALL-MCPS.md</a>).</p>
        </div>
    </div>

    <h3 style="margin-top: var(--space-6);">Install the template into your theme</h3>

    <div class="step-stack">
        <div class="step-card reveal">
            <div class="step-num">1</div>
            <div class="step-body">
                <h3>Clone the template</h3>
                <p>From your terminal — anywhere on disk:</p>
                <div class="codeblock"><pre>git clone https://github.com/&lt;your-org&gt;/wp-claude-template.git</pre></div>
                <p style="margin-top: 6px; font-size: 0.8125rem;">If you don't have the GitHub URL yet, copy the template folder from your existing source.</p>
            </div>
        </div>

        <div class="step-card reveal">
            <div class="step-num">2</div>
            <div class="step-body">
                <h3>Copy into your theme as <code>.claude/</code></h3>
                <p>Inside your theme directory:</p>
                <div class="codeblock"><pre># Windows
xcopy /E /I "&lt;path-to&gt;\wp-claude-template" "&lt;your-theme&gt;\.claude"

# Mac / Linux
cp -r &lt;path-to&gt;/wp-claude-template &lt;your-theme&gt;/.claude</pre></div>
                <div class="callout callout-warn" style="margin-top: 12px; margin-bottom: 0;">
                    <span class="callout-icon">⚠</span>
                    <div class="callout-body">The folder must start with a dot: <code>.claude/</code>. Windows hides dotfiles by default — enable "Hidden items" in Explorer. Mac: <kbd>⌘+Shift+.</kbd></div>
                </div>
            </div>
        </div>

        <div class="step-card reveal">
            <div class="step-num">3</div>
            <div class="step-body">
                <h3>Connect Figma</h3>
                <p data-only="cowork">In Cowork: Settings → Connectors → Figma → Connect. Login with the account that has design access. One-time per machine.</p>
                <p data-only="code">In your terminal:</p>
                <div class="codeblock" data-only="code"><pre>claude mcp add figma</pre></div>
                <p style="font-size: 0.8125rem;">Full reference: <a href="../INSTALL-MCPS.md">INSTALL-MCPS.md</a></p>
            </div>
        </div>

        <div class="step-card reveal">
            <div class="step-num">4</div>
            <div class="step-body">
                <h3>Open the project + run setup</h3>
                <p data-only="cowork">In Cowork: Select folder → navigate to your theme directory → Select. Then in the chat box:</p>
                <p data-only="code">In your terminal:</p>
                <div class="codeblock" data-only="code"><pre>cd &lt;your-theme&gt;
claude</pre></div>
                <p>Then ask Claude to run setup:</p>
                <div class="codeblock"><pre>/setup-claude</pre></div>
                <p style="margin-top: 8px; font-size: 0.8125rem;">Claude detects the theme structure, asks 2-3 questions (project name, production URL, optional Figma URL for auto brand-token detection), copies the snippets in, writes a personalized <code>README.md</code> at workspace root, and creates <code>brand.config.json</code>. ~2 minutes.</p>
            </div>
        </div>

        <div class="step-card reveal">
            <div class="step-num" style="background: var(--color-success);">5</div>
            <div class="step-body">
                <h3>Finish in WordPress admin</h3>
                <p style="font-size: 0.875rem;">Open <code>http://&lt;your-project&gt;.test/wp-admin/</code> and:</p>
                <ol style="font-size: 0.8125rem;">
                    <li>Settings → Permalinks → "Post name" → Save</li>
                    <li>Pages → Add New → title "Home" → Template = "Homepage" → Publish</li>
                    <li>Settings → Reading → Homepage = "Home" → Save</li>
                    <li>Custom Fields → Field Groups → click "Sync changes" (blue notice)</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="callout callout-success" style="margin-top: var(--space-6);">
        <span class="callout-icon">🎉</span>
        <div class="callout-body">
            <strong>Setup is done.</strong> You now have a personalized <code>README.md</code> at workspace root. Open it anytime as a quick reference. Now jump to <a href="#workflow">the 6-step workflow</a> to build your first section.
        </div>
    </div>
</section>
