<section id="acf" data-searchable data-search="acf field group json sync local timestamp modified field types">
    <div class="section-header">
        <span class="section-icon">🗂</span>
        <div>
            <h2>ACF patterns</h2>
            <p>How Advanced Custom Fields works in this template. Local JSON, manual sync, field types decision tree.</p>
        </div>
    </div>

    <h3>The Local JSON sync mechanism</h3>
    <p>ACF Pro auto-syncs from <code>acf-json/&lt;group&gt;.json</code> when the file's <code>"modified"</code> timestamp is newer than the database version. The user controls the moment of sync via <strong>WP Admin → Custom Fields → Field Groups → Sync changes</strong>.</p>
    <p style="font-size: 0.875rem;">All schema edits live in code (git-trackable), the user controls the moment of sync, re-deploys to staging/prod just need the JSON files + a sync click.</p>

    <h3 style="margin-top: var(--space-6);">Field types decision tree</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Content type</th><th>ACF field type</th><th>Notes</th></tr>
            </thead>
            <tbody>
                <tr><td>Heading / short text</td><td><code>text</code></td><td>Render with <code>esc_html()</code></td></tr>
                <tr><td>Sentence / blurb (1 line)</td><td><code>text</code></td><td></td></tr>
                <tr><td>Plain multi-line</td><td><code>textarea</code></td><td><code>nl2br(esc_html(...))</code></td></tr>
                <tr><td>Body paragraph (rich text)</td><td><code>wysiwyg</code></td><td><code>wp_kses_post()</code></td></tr>
                <tr><td>Image</td><td><code>image</code> (Return: Array)</td><td>Use <code>$img['url']</code>, <code>$img['alt']</code>, <code>$img['width']</code>, <code>$img['height']</code></td></tr>
                <tr><td>SVG that themes via currentColor</td><td><code>textarea</code></td><td>Paste raw <code>&lt;svg&gt;</code>, render with <code>wp_kses($svg, aiims_svg_kses())</code></td></tr>
                <tr><td>SVG with fixed colors (logo)</td><td><code>image</code></td><td>Render as <code>&lt;img&gt;</code></td></tr>
                <tr><td>Single CTA</td><td><code>link</code></td><td>Returns <code>{url, title, target}</code></td></tr>
                <tr><td>List / cards / repeating items</td><td><code>repeater</code></td><td><code>have_rows()</code> + <code>the_row()</code> + <code>get_sub_field()</code></td></tr>
                <tr><td>Boolean toggle</td><td><code>true_false</code></td><td>Use for variants</td></tr>
                <tr><td>Color override</td><td><code>color_picker</code></td><td>Rare — only when per-page</td></tr>
                <tr><td>Choose-from-list</td><td><code>select</code> / <code>radio</code></td><td>Variant pickers</td></tr>
            </tbody>
        </table>
    </div>

    <h3 style="margin-top: var(--space-6);">Code patterns</h3>
    <div class="grid grid-2">
        <div class="card">
            <h4>Basic field output (escaped + guarded)</h4>
            <div class="codeblock"><pre>&lt;?php $heading = get_sub_field('heading');
if ($heading) : ?&gt;
    &lt;h2&gt;&lt;?= esc_html($heading) ?&gt;&lt;/h2&gt;
&lt;?php endif; ?&gt;</pre></div>
        </div>

        <div class="card">
            <h4>WYSIWYG (rich text)</h4>
            <div class="codeblock"><pre>&lt;?php $body = get_sub_field('body');
if ($body) : ?&gt;
    &lt;div class="prose"&gt;
        &lt;?= wp_kses_post($body) ?&gt;
    &lt;/div&gt;
&lt;?php endif; ?&gt;</pre></div>
        </div>

        <div class="card">
            <h4>Link / CTA</h4>
            <div class="codeblock"><pre>&lt;?php $cta = get_sub_field('cta');
if ($cta &amp;&amp; $cta['url']) : ?&gt;
    &lt;a href="&lt;?= esc_url($cta['url']) ?&gt;"
       target="&lt;?= esc_attr($cta['target'] ?: '_self') ?&gt;"&gt;
        &lt;?= esc_html($cta['title']) ?&gt;
    &lt;/a&gt;
&lt;?php endif; ?&gt;</pre></div>
        </div>

        <div class="card">
            <h4>Repeater (cards)</h4>
            <div class="codeblock"><pre>&lt;?php if (have_rows('cards')) : ?&gt;
&lt;div class="grid grid-cols-3 gap-6"&gt;
    &lt;?php while (have_rows('cards')) : the_row(); ?&gt;
        &lt;article&gt;
            &lt;h3&gt;&lt;?= esc_html(get_sub_field('title')) ?&gt;&lt;/h3&gt;
        &lt;/article&gt;
    &lt;?php endwhile; ?&gt;
&lt;/div&gt;
&lt;?php endif; ?&gt;</pre></div>
        </div>
    </div>

    <div class="callout callout-warn" style="margin-top: var(--space-5);">
        <span class="callout-icon">⚠</span>
        <div class="callout-body">
            <strong>Field keys are permanent.</strong> Once created, never regenerate them when editing. Renaming a field's <code>name</code> or <code>label</code> is safe; changing <code>key</code> orphans the existing data.
        </div>
    </div>
</section>
