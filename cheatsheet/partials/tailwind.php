<section id="tailwind" data-searchable data-search="tailwind theme tokens brand colors font container breakpoint">
    <div class="section-header">
        <span class="section-icon">🎨</span>
        <div>
            <h2>Tailwind tokens</h2>
            <p>Brand colors, fonts, container width — all defined once in <code>@theme</code> at <code>tailwind/tailwind-theme.css</code>. Use as utility classes.</p>
        </div>
    </div>

    <h3>The <code>@theme</code> block</h3>
    <div class="codeblock"><pre>@theme {
    --color-primary:   #2563EB;   /* main CTA */
    --color-secondary: #181B22;   /* heading text */
    --color-accent:    #00AEEF;   /* secondary CTA / link */
    --color-text:      #4D4B50;   /* body copy */

    --font-brand: 'Manrope', system-ui, sans-serif;

    /* Custom breakpoints, if needed */
    --breakpoint-3xl: 1920px;
}</pre></div>

    <h3 style="margin-top: var(--space-5);">Use as utilities</h3>
    <div class="grid grid-2">
        <div class="card">
            <h4>Colors</h4>
            <p style="font-family: var(--font-mono); font-size: 12px; color: var(--color-text-muted); line-height: 1.6;">
                bg-primary · text-primary · border-primary<br>
                bg-secondary · text-secondary<br>
                bg-accent · text-accent<br>
                text-text (body) · bg-white · bg-black
            </p>
            <p style="font-size: 0.8125rem; margin-top: 8px; color: var(--color-text-dim);">All standard Tailwind opacity modifiers work: <code>bg-primary/10</code>, <code>text-secondary/70</code>, etc.</p>
        </div>

        <div class="card">
            <h4>Fonts</h4>
            <p style="font-family: var(--font-mono); font-size: 12px; color: var(--color-text-muted); line-height: 1.6;">
                font-brand   (headings)<br>
                font-body    (body — system default if undefined)<br>
                font-mono    (code)
            </p>
            <p style="font-size: 0.8125rem; margin-top: 8px; color: var(--color-text-dim);">Apply on the element: <code>&lt;h2 class="font-brand"&gt;</code>.</p>
        </div>
    </div>

    <h3 style="margin-top: var(--space-5);">When to add a new color</h3>
    <div class="callout callout-info">
        <span class="callout-icon">💡</span>
        <div class="callout-body">
            <strong>2+ uses → propose adding as a brand token.</strong> Say to Claude:
            <div class="codeblock" style="margin-top: 8px;"><pre>Add a brand color: cta-hover #2A4D8B</pre></div>
            Claude runs the <code>tailwind-theme-sync</code> skill — updates the <code>@theme</code> block + updates README. After <code>npm run watch</code> picks it up, <code>bg-cta-hover</code> works.<br><br>
            <strong>1 use → arbitrary value.</strong> Use <code>bg-[#2A4D8B]</code> directly + flag the deviation in your build reply.
        </div>
    </div>

    <h3 style="margin-top: var(--space-5);">Spacing scale rules</h3>
    <ul>
        <li><strong>Within 2px of a Tailwind token</strong> → use the token (<code>py-20</code> = 80px is close enough to Figma 78px).</li>
        <li><strong>3-5px off</strong> → ask the user.</li>
        <li><strong>&gt;5px or specific design requirement</strong> → arbitrary value <code>py-[73px]</code> + flag in deviations.</li>
    </ul>

    <h3 style="margin-top: var(--space-5);">Typography drift (Figma vs browser)</h3>
    <p>Browsers render text slightly differently from Figma. The most common adjustments:</p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Symptom</th><th>Suggested Tailwind tweak</th></tr>
            </thead>
            <tbody>
                <tr><td>Heading looks "looser" than Figma</td><td><code>tracking-tight</code> or arbitrary <code>tracking-[-0.02em]</code></td></tr>
                <tr><td>Lines look "taller" than Figma</td><td><code>leading-tight</code>, <code>leading-none</code>, or <code>leading-[1.1]</code></td></tr>
                <tr><td>Body text feels "airy"</td><td><code>leading-snug</code> vs <code>leading-relaxed</code></td></tr>
                <tr><td>Numbers look misaligned</td><td><code>tabular-nums</code></td></tr>
            </tbody>
        </table>
    </div>
    <p style="font-size: 0.8125rem; margin-top: 8px; color: var(--color-text-dim);"><code>/pixel-check</code> automatically detects these and suggests the tweak.</p>
</section>
