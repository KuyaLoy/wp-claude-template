<section id="workflow" data-searchable data-search="workflow six step static dynamic sync seed upload cleanup">
    <div class="section-header">
        <span class="section-icon">🎯</span>
        <div>
            <h2>The 6-step workflow</h2>
            <p>One section at a time. Static-first is non-negotiable. Steps 5a, 5b, 6 are optional but recommended.</p>
        </div>
    </div>

    <div class="step-stack" style="display: flex; flex-direction: column; gap: var(--space-3);">
        <div class="step-card reveal">
            <div class="step-num">1</div>
            <div class="step-body">
                <h3>Static</h3>
                <p style="margin-bottom: 8px;">Paste the Figma URL(s) in chat. Claude reads Figma, writes the section with hard-coded markup (no ACF yet), auto-creates <code>briefs/&lt;name&gt;.md</code>.</p>
                <div class="codeblock"><pre>@home-hero https://figma.com/.../1-2 https://figma.com/.../1-3</pre></div>
                <p style="font-size: 0.8125rem; color: var(--color-text-dim); margin-top: 6px;">One URL = desktop + auto-responsive. Two URLs = desktop + mobile pixel-matched.</p>
            </div>
        </div>

        <div class="step-card reveal">
            <div class="step-num">2</div>
            <div class="step-body">
                <h3>Cross-check</h3>
                <p style="margin-bottom: 8px;">Compare live render to Figma. Hard-refresh the page, eyeball it, or run:</p>
                <div class="codeblock"><pre>/pixel-check home-hero</pre></div>
                <p style="font-size: 0.8125rem; color: var(--color-text-dim); margin-top: 6px;">Claude takes screenshots and reports deviations (spacing, color, typography, asset quality). Includes typography drift detection (Figma's letter-spacing vs browser default).</p>
            </div>
        </div>

        <div class="step-card reveal">
            <div class="step-num">3</div>
            <div class="step-body">
                <h3>Dynamic</h3>
                <p style="margin-bottom: 8px;">Once the static matches Figma, type:</p>
                <div class="codeblock"><pre>Make home-hero dynamic</pre></div>
                <p style="font-size: 0.8125rem; color: var(--color-text-dim); margin-top: 6px;">Claude writes ACF JSON, replaces inline content with <code>get_sub_field()</code> calls, adds conditional guards.</p>
            </div>
        </div>

        <div class="step-card reveal">
            <div class="step-num">4</div>
            <div class="step-body">
                <h3>Sync ACF</h3>
                <p>Go to WP Admin → Custom Fields → Field Groups → click "Sync changes". The editable fields appear in the page editor.</p>
            </div>
        </div>

        <div class="step-card reveal">
            <div class="step-num" style="background: var(--color-warning); color: black;">5a</div>
            <div class="step-body">
                <h3>Upload images <span class="pill pill-optional">optional</span></h3>
                <p style="margin-bottom: 8px;">If the section has static images at <code>theme/assets/images/&lt;slug&gt;/</code>:</p>
                <div class="codeblock"><pre>/upload-images home-hero</pre></div>
                <p style="font-size: 0.8125rem; color: var(--color-text-dim); margin-top: 6px;">Writes a one-shot loader. Hit the URL Claude gives you (as admin). Images upload to WP media library, return attachment IDs, static folder deletes itself.</p>
            </div>
        </div>

        <div class="step-card reveal">
            <div class="step-num" style="background: var(--color-warning); color: black;">5b</div>
            <div class="step-body">
                <h3>Seed real content <span class="pill pill-optional">optional</span></h3>
                <p style="margin-bottom: 8px;">Instead of typing every field in WP Admin:</p>
                <div class="codeblock"><pre>Seed home-hero with: heading=Welcome, body=..., image=142, cta=Get a quote/#quote</pre></div>
                <p style="font-size: 0.8125rem; color: var(--color-text-dim); margin-top: 6px;">Writes a one-shot seeder. Hit the URL as admin. Data populates. File self-deletes.</p>
            </div>
        </div>

        <div class="step-card reveal">
            <div class="step-num" style="background: var(--color-success);">6</div>
            <div class="step-body">
                <h3>Cleanup <span class="pill pill-optional">optional</span></h3>
                <p style="margin-bottom: 8px;">Production sweep:</p>
                <div class="codeblock"><pre>/cleanup-section home-hero</pre></div>
                <p style="font-size: 0.8125rem; color: var(--color-text-dim); margin-top: 6px;">Strips dev-only doc comments, validates ACF field references, flags orphaned static assets and TODO/FIXME comments. Section becomes production-deployable.</p>
            </div>
        </div>
    </div>

    <div class="callout callout-info" style="margin-top: var(--space-6);">
        <span class="callout-icon">⚡</span>
        <div class="callout-body">
            <strong>Want it all chained?</strong> <code>/build &lt;section&gt; &lt;figma-url&gt; &lt;figma-mobile&gt;</code> runs steps 1 → 3 with a pause for your "looks good", optionally chains seed too.
        </div>
    </div>
</section>
