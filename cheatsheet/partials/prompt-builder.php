<?php /** @var bool $ai_enabled — passed from index.php; true if GEMINI_API_KEY is set */ ?>
<section id="prompt-builder" data-searchable data-search="prompt builder ai gemini polish chat generate">
    <div class="section-header">
        <span class="section-icon">✨</span>
        <div>
            <h2>AI Prompt Builder</h2>
            <p>Don't know how to phrase what you want? Type messy English, the AI polishes it into the exact prompt to paste into Claude — using your project's own CLAUDE.md rules as context.</p>
        </div>
    </div>

    <div class="card">
        <div style="display: flex; align-items: center; gap: var(--space-4); flex-wrap: wrap;">
            <div style="flex: 1; min-width: 240px;">
                <h3 style="margin-bottom: 8px;">How it works</h3>
                <p style="margin-bottom: 0; font-size: 0.875rem;">
                    Type whatever's on your mind — "I want to make a hero with 3 cards stack on mobile, figma is here ..." — typos welcome. Gemini reads your <code>CLAUDE.md</code> server-side and produces a clean, properly formatted prompt. Copy and paste into Claude.
                </p>
            </div>
            <button type="button" class="btn btn-primary" data-open-chat style="padding: 14px 24px; font-size: 1rem;">
                <span style="font-size: 1.1em;">✦</span> Open AI Prompt Builder
            </button>
        </div>

        <?php if (!$ai_enabled): ?>
        <div class="callout callout-warn" style="margin-top: var(--space-4); margin-bottom: 0;">
            <span class="callout-icon">⚠</span>
            <div class="callout-body">
                <strong>No API key configured.</strong> Open <code>cheatsheet/.env</code> and add your free Gemini API key. Get one at <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener">aistudio.google.com/app/apikey</a>, then refresh this page. The proxy reads the key server-side — it never leaves your machine.
            </div>
        </div>
        <?php else: ?>
        <div class="callout callout-success" style="margin-top: var(--space-4); margin-bottom: 0;">
            <span class="callout-icon">✓</span>
            <div class="callout-body">
                <strong>AI is ready.</strong> Gemini API key loaded from <code>.env</code>. The key never reaches your browser — all calls go through the local PHP proxy at <code>api/polish.php</code>.
            </div>
        </div>
        <?php endif; ?>
    </div>

    <p style="margin-top: var(--space-4); font-size: 0.8125rem; color: var(--color-text-dim);">
        <strong>Tip:</strong> the floating <span style="color: var(--color-accent);">✨</span> button in the bottom-right corner opens the chat from anywhere on the page.
    </p>
</section>
