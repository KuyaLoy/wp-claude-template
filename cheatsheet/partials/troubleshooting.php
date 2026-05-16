<section id="troubleshooting" data-searchable data-search="troubleshooting weird looks wrong tailwind acf images figma errors">
    <div class="section-header">
        <span class="section-icon">🆘</span>
        <div>
            <h2>Troubleshooting</h2>
            <p>The 8 most common "wait what" moments. Try these before calling your dev.</p>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <h4>🔁 Page looks unchanged after Claude said it built something</h4>
            <p style="font-size: 0.875rem;">Hard refresh the browser. <kbd>Ctrl+Shift+R</kbd> (Windows) or <kbd>⌘+Shift+R</kbd> (Mac). Browsers cache CSS aggressively.</p>
        </div>

        <div class="card">
            <h4>🎨 Tailwind colors / spacing not applying (page looks unstyled)</h4>
            <p style="font-size: 0.875rem;">The Tailwind watcher stopped. In a terminal at your theme folder, run <code>npm run watch</code>. Leave it running while you work.</p>
        </div>

        <div class="card">
            <h4>📋 ACF fields don't appear in the page editor</h4>
            <p style="font-size: 0.875rem;">You forgot to sync. WP Admin → Custom Fields → Field Groups → click the blue "Sync changes" notice at the top.</p>
        </div>

        <div class="card">
            <h4>🖼 Image isn't showing on the live page</h4>
            <p style="font-size: 0.875rem;">Two possibilities: (1) ACF image field is empty — fill it in the page editor. (2) Wrong attachment ID — re-run <code>/upload-images &lt;section&gt;</code> to get correct IDs, then re-seed.</p>
        </div>

        <div class="card border-purple-300">
            <h4>🎨 Claude stops: "Figma is disconnected"</h4>
            <p style="font-size: 0.875rem;"><strong>By design.</strong> The template refuses to build from screenshots. Reconnect:</p>
            <ul style="font-size: 0.8125rem; margin-top: 4px;">
                <li data-only="cowork">Settings → Connectors → Figma → Reconnect</li>
                <li data-only="code"><code>claude mcp add figma</code></li>
            </ul>
            <p style="font-size: 0.875rem; margin-top: 6px;">Then type <code>continue</code> in chat.</p>
        </div>

        <div class="card">
            <h4>📐 Section is there but spacing / colors / fonts feel off</h4>
            <p style="font-size: 0.875rem;">Run <code>/pixel-check &lt;section&gt;</code> — it'll list every deviation with a specific Tailwind fix. Or tell Claude in plain English: "the h2 on mobile is too big, Figma says 28px".</p>
        </div>

        <div class="card">
            <h4>🪦 White screen of death on the local site</h4>
            <p style="font-size: 0.875rem;"><strong>Stop and call your dev.</strong> This is a PHP fatal error and the message is hidden by default. Your dev can turn on <code>WP_DEBUG</code> in <code>wp-config.php</code> and see what broke.</p>
        </div>

        <div class="card">
            <h4>🌱 Seeder won't run</h4>
            <p style="font-size: 0.875rem;">Check (in order): are you logged into WP Admin as administrator? Has the seeder file already self-deleted (re-create from git via <code>git checkout</code>)? Has the section's ACF group been synced?</p>
        </div>

        <div class="card">
            <h4>⚠ Prompt Builder says "No API key configured"</h4>
            <p style="font-size: 0.875rem;">Open <code>cheatsheet/.env</code>, paste your Gemini API key after <code>GEMINI_API_KEY=</code>, save, refresh this page. Get a free key at <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener">aistudio.google.com/app/apikey</a>.</p>
        </div>

        <div class="card">
            <h4>🤖 Cheatsheet page is blank / styles missing</h4>
            <p style="font-size: 0.875rem;">You're probably opening <code>index.php</code> via <code>file://</code> instead of <code>http://</code>. PHP only runs through a web server. Open the cheatsheet via your Laragon URL: <code>http://&lt;project&gt;.test/wp-content/themes/&lt;theme&gt;/.claude/cheatsheet/</code></p>
        </div>
    </div>

    <div class="callout callout-info" style="margin-top: var(--space-6);">
        <span class="callout-icon">📨</span>
        <div class="callout-body">
            <strong>How to ask your dev for help if none of these work.</strong> Give them four things so they don't have to interrogate you:
            <ol style="margin: 8px 0 0; padding-left: 20px;">
                <li><strong>What you were trying to do</strong> ("build the home-hero section")</li>
                <li><strong>What you expected</strong> ("should match Figma exactly")</li>
                <li><strong>What actually happened</strong> (paste the exact error message or Claude's reply)</li>
                <li><strong>The section name or URL involved</strong></li>
            </ol>
        </div>
    </div>
</section>
