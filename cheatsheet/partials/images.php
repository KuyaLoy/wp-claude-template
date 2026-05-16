<section id="images" data-searchable data-search="images svg picture mobile companion transparency decision tree pattern a b c">
    <div class="section-header">
        <span class="section-icon">🖼</span>
        <div>
            <h2>Images &amp; SVG</h2>
            <p>The three SVG patterns + asset fidelity rules (preserve transparency, never rasterize SVGs).</p>
        </div>
    </div>

    <h3>SVG preference order (NON-NEGOTIABLE)</h3>
    <div class="grid grid-3">
        <div class="card card-accent">
            <span class="pill pill-new">DEFAULT</span>
            <h3 style="margin-top: 8px;">Pattern A — Textarea</h3>
            <p>Editor pastes raw <code>&lt;svg&gt;</code> markup into a textarea ACF field. Use <code>fill="currentColor"</code> so parent <code>text-*</code> class themes it.</p>
            <p style="font-size: 0.8125rem; color: var(--color-text-dim);"><strong>Use for:</strong> icons, decorative marks, anything editor-changeable that needs Tailwind theming.</p>
        </div>

        <div class="card">
            <span class="pill">Theme-wide</span>
            <h3 style="margin-top: 8px;">Pattern C — Inline</h3>
            <p>Paste <code>&lt;svg&gt;</code> markup directly in the template file. Or save to <code>assets/icons/</code> and use <code>aiims_img()</code>.</p>
            <p style="font-size: 0.8125rem; color: var(--color-text-dim);"><strong>Use for:</strong> theme-wide icons that never change (logo in header/footer).</p>
        </div>

        <div class="card">
            <span class="pill pill-optional">Last resort</span>
            <h3 style="margin-top: 8px;">Pattern B — Image upload</h3>
            <p>ACF image field, editor uploads SVG file. Rendered as <code>&lt;img&gt;</code>.</p>
            <p style="font-size: 0.8125rem; color: var(--color-text-dim);"><strong>Use only when:</strong> Pattern A is impractical — brand logo with fixed colors, huge SVG hard to paste.</p>
        </div>
    </div>

    <h3 style="margin-top: var(--space-6);">ACF image rendering (single)</h3>
    <div class="codeblock"><pre>&lt;?php $img = get_sub_field('image');
if ($img) : ?&gt;
&lt;img
    src="&lt;?= esc_url($img['url']) ?&gt;"
    alt="&lt;?= esc_attr($img['alt']) ?&gt;"
    width="&lt;?= (int) $img['width'] ?&gt;"
    height="&lt;?= (int) $img['height'] ?&gt;"
    loading="lazy"
    decoding="async"
&gt;
&lt;?php endif; ?&gt;</pre></div>

    <h3 style="margin-top: var(--space-5);">ACF image + mobile companion → <code>&lt;picture&gt;</code></h3>
    <div class="codeblock"><pre>&lt;?php
$img = get_sub_field('image');
$mobile = get_sub_field('mobile_image');
if ($img) :
?&gt;
&lt;picture&gt;
    &lt;?php if ($mobile) : ?&gt;
        &lt;source srcset="&lt;?= esc_url($mobile['url']) ?&gt;"
                media="(max-width: 767px)"
                width="&lt;?= (int) $mobile['width'] ?&gt;"
                height="&lt;?= (int) $mobile['height'] ?&gt;"&gt;
    &lt;?php endif; ?&gt;
    &lt;img src="&lt;?= esc_url($img['url']) ?&gt;"
         alt="&lt;?= esc_attr($img['alt']) ?&gt;"
         width="&lt;?= (int) $img['width'] ?&gt;"
         height="&lt;?= (int) $img['height'] ?&gt;"
         loading="lazy" decoding="async"&gt;
&lt;/picture&gt;
&lt;?php endif; ?&gt;</pre></div>

    <h3 style="margin-top: var(--space-5);">SVG via textarea (Pattern A, recommended)</h3>
    <div class="codeblock"><pre>&lt;?php $svg = get_sub_field('icon_svg');
if ($svg) : ?&gt;
&lt;div class="w-12 h-12 text-primary"&gt;
    &lt;?= wp_kses($svg, aiims_svg_kses()) ?&gt;
&lt;/div&gt;
&lt;?php endif; ?&gt;</pre></div>

    <div class="callout callout-danger" style="margin-top: var(--space-5);">
        <span class="callout-icon">🚫</span>
        <div class="callout-body">
            <strong>Asset fidelity rules (NON-NEGOTIABLE):</strong>
            <ol style="margin: 8px 0 0; padding-left: 20px;">
                <li><strong>If Figma says it's an SVG, it stays an SVG.</strong> Never rasterize to PNG/JPG.</li>
                <li><strong>Preserve transparency.</strong> If the source frame is transparent, export PNG/WebP with alpha. Never bake in a white background.</li>
                <li><strong>Composite frames stay composite.</strong> Don't break grouped designer layouts into separate HTML pieces.</li>
                <li><strong>Every <code>&lt;img&gt;</code> MUST have <code>width</code> and <code>height</code></strong> attributes — required for CLS (Cumulative Layout Shift).</li>
            </ol>
        </div>
    </div>
</section>
