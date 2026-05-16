<!-- Floating Action Button (always visible) -->
<button type="button" id="chat-fab" class="chat-fab" aria-label="Open AI Prompt Builder">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 2L13.5 8.5L20 10L13.5 11.5L12 18L10.5 11.5L4 10L10.5 8.5L12 2Z" fill="currentColor"/>
    </svg>
    <span>Ask AI</span>
</button>

<!-- Centered popup card (hidden by default) -->
<div class="chat-popup" id="chat-popup" aria-hidden="true" role="dialog" aria-labelledby="chat-popup-title">
    <div class="chat-popup-overlay" data-chat-close></div>

    <div class="chat-popup-panel">
        <div class="chat-popup-header">
            <div class="chat-popup-title-block">
                <span class="chat-popup-icon">✦</span>
                <div>
                    <h3 id="chat-popup-title">AI Prompt Builder</h3>
                    <p>Type rough English. I'll polish it using your CLAUDE.md rules.</p>
                </div>
            </div>
            <button type="button" class="chat-popup-close" data-chat-close aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="chat-popup-body" id="chat-messages">
            <div class="chat-msg chat-msg-assistant">
                <div class="chat-msg-avatar">✦</div>
                <div class="chat-msg-bubble">
                    <p>Tell me what you want to build. Include the Figma URL in your message — typos and rough grammar welcome, that's what I'm here to fix.</p>
                    <p style="font-size: 0.8125rem; opacity: 0.75; margin-bottom: 0;"><strong>Examples:</strong></p>
                    <ul style="margin: 4px 0 0; padding-left: 18px; font-size: 0.8125rem; color: var(--ink-soft);">
                        <li>"hero section with bg image, figma here: <i>your-url</i>"</li>
                        <li>"the services grid w 3 cards stack on mobile, h3 not h2"</li>
                        <li>"about us sticky cta on scroll dark variant figma <i>url</i>"</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="chat-popup-input-wrap">
            <textarea
                id="chat-input"
                class="chat-input"
                placeholder="Describe what you want to build (include Figma URL)…"
                rows="3"
                autocomplete="off"
                spellcheck="false"
            ></textarea>
            <div class="chat-popup-actions">
                <span class="chat-hint" id="chat-hint"><kbd>Enter</kbd> to send · <kbd>Shift+Enter</kbd> for new line</span>
                <button type="button" id="chat-send" class="btn btn-primary btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    Polish
                </button>
            </div>
        </div>
    </div>
</div>
