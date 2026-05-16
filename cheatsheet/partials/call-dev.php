<section id="call-dev" data-searchable data-search="when to call developer escalation handoff dev">
    <div class="section-header">
        <span class="section-icon">📞</span>
        <div>
            <h2>When to call your developer</h2>
            <p>Claude is great at building sections. Your developer is great at the stuff Claude shouldn't touch. Clear split below.</p>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card" style="border-color: rgba(34, 197, 94, 0.3); background: rgba(34, 197, 94, 0.04);">
            <h3 style="color: var(--color-success);">✅ Do these with Claude</h3>
            <ul>
                <li>Building any section from Figma</li>
                <li>Tweaking visuals (spacing, colors, fonts)</li>
                <li>Converting static to dynamic (ACF)</li>
                <li>Adding new sections or section types</li>
                <li>Creating new page templates</li>
                <li>Comparing live to Figma (<code>/pixel-check</code>)</li>
                <li>Pre-deploy checks (<code>/ship-check</code>)</li>
                <li>Uploading images to media library</li>
                <li>Seeding content for fresh sections</li>
                <li>Cleanup before deploy</li>
                <li>Editing text/images via WP Admin (no Claude needed)</li>
            </ul>
        </div>

        <div class="card" style="border-color: rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.04);">
            <h3 style="color: var(--color-danger);">⚠ Call your developer for these</h3>
            <ul>
                <li>Initial WordPress + theme + plugin setup</li>
                <li>Installing ACF Pro license</li>
                <li>White screen / PHP fatal errors</li>
                <li>Database errors / migration</li>
                <li>Deploying to staging or production (theme zip upload, DNS, SSL)</li>
                <li>Plugin licensing / SSL certificates</li>
                <li>Anything beyond <code>npm run watch</code> in the terminal</li>
                <li>Contact forms not sending email (SMTP config)</li>
                <li>Git conflicts / merge problems</li>
                <li>WordPress core updates</li>
                <li>"This is outside the .claude/ scope" (Claude will say so)</li>
            </ul>
        </div>
    </div>

    <div class="callout callout-warn" style="margin-top: var(--space-6);">
        <span class="callout-icon">🤝</span>
        <div class="callout-body">
            <strong>The handover from dev to you (and back).</strong> Your dev does the one-time setup (~30 min on a new project). After that, you build sections with Claude. If you hit something that needs PHP debugging, file permissions, server config, or git surgery, that's their territory. When in doubt, ask — there's no harm in a quick "is this me or you?".
        </div>
    </div>
</section>
