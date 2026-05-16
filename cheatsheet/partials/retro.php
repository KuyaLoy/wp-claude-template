<section id="retro" data-searchable data-search="retro retrospective continuous improvement workflow loop">
    <div class="section-header">
        <span class="section-icon">🔁</span>
        <div>
            <h2>Retro — continuous improvement loop</h2>
            <p>The template's not static. Every shipped project makes the next one's template better. Here's how.</p>
        </div>
    </div>

    <div class="card">
        <h3>The loop</h3>
        <ol style="font-size: 0.9375rem; line-height: 1.7;">
            <li><strong>Ship a project</strong> using the current template version.</li>
            <li><strong>Copy the project's <code>.claude/</code> into <code>wp-claude-template/completed-projects/&lt;project&gt;-snapshot/</code></strong> as a frozen reference.</li>
            <li><strong>Run the retro</strong> — open Claude in the template repo, ask it to compare the snapshot against the current template, identify what got hand-edited or added, propose v(n+1) changes.</li>
            <li><strong>Approve each proposed change individually.</strong> Don't auto-apply.</li>
            <li><strong>Bump the template version</strong>, commit, tag, push.</li>
            <li><strong>Next project starts from the improved template.</strong></li>
        </ol>
    </div>

    <h3 style="margin-top: var(--space-5);">What to capture during a retro</h3>
    <div class="grid grid-3">
        <div class="card">
            <h4 style="color: var(--color-success);">🟢 What worked</h4>
            <ul style="font-size: 0.8125rem;">
                <li>Which skills/commands triggered without you forcing them?</li>
                <li>Which snippets did you copy-paste 3+ times?</li>
                <li>Did the workflow stay intact for every section?</li>
            </ul>
        </div>

        <div class="card">
            <h4 style="color: var(--color-danger);">🔴 What was painful</h4>
            <ul style="font-size: 0.8125rem;">
                <li>Custom seeders / helpers you wrote that should be reusable?</li>
                <li>Rules you hand-edited in CLAUDE.md?</li>
                <li>Code that appeared 3+ times across sections?</li>
            </ul>
        </div>

        <div class="card">
            <h4 style="color: var(--color-warning);">🟡 Patterns emerging</h4>
            <ul style="font-size: 0.8125rem;">
                <li>Custom partials (modal, sticky bar) other projects would want?</li>
                <li>Helper functions that are project-agnostic?</li>
                <li>Useful debug / diagnostic seeders?</li>
            </ul>
        </div>
    </div>

    <p style="margin-top: var(--space-5); font-size: 0.875rem;">
        Full reference: <a href="../RETRO-WORKFLOW.md">RETRO-WORKFLOW.md</a> · Master retro log: <a href="../RETRO.md">RETRO.md</a> · Version history: <a href="../CHANGELOG.md">CHANGELOG.md</a>
    </p>
</section>
