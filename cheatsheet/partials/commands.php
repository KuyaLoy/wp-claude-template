<section id="commands" data-searchable data-search="commands slash all reference build implement make dynamic seed upload cleanup pixel ship setup">
    <div class="section-header">
        <span class="section-icon">📋</span>
        <div>
            <h2>All commands</h2>
            <p>Every slash command. Type them anywhere in chat. Plain English usually works too — these are just shortcuts.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Command</th>
                    <th>What it does</th>
                    <th>Example</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>/setup-claude</code></td>
                    <td>One-time per-project setup. Auto-detects theme structure, asks 2-3 questions, configures everything. Self-deletes after success.</td>
                    <td style="font-size: 11px; color: var(--color-text-dim);">First run on a new project</td>
                </tr>
                <tr style="background: var(--color-accent-soft);">
                    <td><code>/build</code></td>
                    <td>Chained workflow: implement static → pause for approval → make-dynamic → optional seed. The "do it all" shortcut.</td>
                    <td><code style="font-size: 11px;">/build hero https://figma.com/.../1-2 https://figma.com/.../1-3</code></td>
                </tr>
                <tr>
                    <td><code>@&lt;section&gt;</code></td>
                    <td>Build static only. Trigger Claude with the section name + Figma URL(s). Auto-creates the brief.</td>
                    <td><code style="font-size: 11px;">@hero https://figma.com/.../1-2</code></td>
                </tr>
                <tr>
                    <td><code>/implement</code></td>
                    <td>Same as <code>@&lt;section&gt;</code> but explicit. Useful when you want to be sure which skill triggers.</td>
                    <td><code style="font-size: 11px;">/implement hero https://figma.com/.../1-2</code></td>
                </tr>
                <tr>
                    <td><code>Make &lt;name&gt; dynamic</code></td>
                    <td>Convert approved static section to ACF. Writes JSON, replaces inline strings with <code>get_sub_field()</code> calls.</td>
                    <td><code style="font-size: 11px;">Make hero dynamic</code></td>
                </tr>
                <tr>
                    <td><code>/make-dynamic</code></td>
                    <td>Same as above but explicit slash form.</td>
                    <td><code style="font-size: 11px;">/make-dynamic hero</code></td>
                </tr>
                <tr style="background: var(--color-accent-soft);">
                    <td><code>/upload-images</code></td>
                    <td>Upload static images at <code>assets/images/&lt;section&gt;/</code> to WP media library. Returns attachment IDs. Deletes static folder.</td>
                    <td><code style="font-size: 11px;">/upload-images hero</code></td>
                </tr>
                <tr style="background: var(--color-accent-soft);">
                    <td><code>/seed</code></td>
                    <td>One-shot self-deleting seeder. Populates ACF fields with real content. Hit the URL Claude returns as admin.</td>
                    <td><code style="font-size: 11px;">/seed hero heading=Welcome, body=..., image=142</code></td>
                </tr>
                <tr style="background: var(--color-accent-soft);">
                    <td><code>/cleanup-section</code></td>
                    <td>Production sweep. Strips dev comments, validates ACF references, flags orphaned assets and dead code.</td>
                    <td><code style="font-size: 11px;">/cleanup-section hero</code></td>
                </tr>
                <tr>
                    <td><code>/add-section</code></td>
                    <td>Register a new ACF Flexible Content layout shell (no markup). Useful for IA planning before designs are final.</td>
                    <td><code style="font-size: 11px;">/add-section testimonials</code></td>
                </tr>
                <tr>
                    <td><code>/create-template</code></td>
                    <td>New page / archive / single / search / 404 / taxonomy WordPress template. Asks rigid vs flexible.</td>
                    <td>—</td>
                </tr>
                <tr>
                    <td><code>/pixel-check</code></td>
                    <td>Compare live render to Figma. Reports critical / important / minor deviations. Includes typography drift detection.</td>
                    <td><code style="font-size: 11px;">/pixel-check hero</code></td>
                </tr>
                <tr>
                    <td><code>/ship-check</code></td>
                    <td>Pre-deploy audit. Accessibility (WCAG 2.1 AA) + performance + QA + ACF sync state, in parallel.</td>
                    <td>—</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p style="margin-top: var(--space-4); font-size: 0.8125rem; color: var(--color-text-dim);">
        Highlighted rows are recent additions (v3.5+) — the ones that close the manual-step gaps in the workflow.
    </p>
</section>
