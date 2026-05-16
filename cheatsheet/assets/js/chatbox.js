/* ============================================================
 *  chatbox.js — bottom-center floating chat for AI prompt polish
 *  Talks to local PHP proxy at api/polish.php (key lives in .env)
 * ============================================================ */

(function () {
    'use strict';

    function $(id) { return document.getElementById(id); }

    const popup = $('chat-popup');
    const fab = $('chat-fab');
    const closeBtns = document.querySelectorAll('[data-chat-close]');
    const chatInput = $('chat-input');
    const chatSend = $('chat-send');
    const chatMessages = $('chat-messages');

    if (!popup) return;

    // Open / close
    function openChat() {
        popup.classList.add('is-open');
        popup.setAttribute('aria-hidden', 'false');
        setTimeout(() => chatInput && chatInput.focus(), 180);
    }
    function closeChat() {
        popup.classList.remove('is-open');
        popup.setAttribute('aria-hidden', 'true');
    }

    if (fab) fab.addEventListener('click', openChat);
    closeBtns.forEach((b) => b.addEventListener('click', closeChat));

    // Open from any [data-open-chat] in page content (e.g. the prompt-builder section button)
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-open-chat]');
        if (trigger) {
            e.preventDefault();
            openChat();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && popup.classList.contains('is-open')) closeChat();
    });

    // ---------------- Chat conversation ----------------
    const chatHistory = [];

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }

    function addUserMsg(text) {
        const div = document.createElement('div');
        div.className = 'chat-msg chat-msg-user';
        div.innerHTML = `
            <div class="chat-msg-avatar">🧑</div>
            <div class="chat-msg-bubble">${escapeHtml(text)}</div>
        `;
        chatMessages.appendChild(div);
        scrollChatToBottom();
    }

    function addThinkingMsg() {
        const div = document.createElement('div');
        div.className = 'chat-msg chat-msg-assistant';
        div.dataset.thinking = 'true';
        div.innerHTML = `
            <div class="chat-msg-avatar">✦</div>
            <div class="chat-msg-bubble">
                <span class="chat-typing"><span></span><span></span><span></span></span>
            </div>
        `;
        chatMessages.appendChild(div);
        scrollChatToBottom();
        return div;
    }

    function addAssistantMsg(polished) {
        const div = document.createElement('div');
        div.className = 'chat-msg chat-msg-assistant';
        div.innerHTML = `
            <div class="chat-msg-avatar">✦</div>
            <div class="chat-msg-bubble">
                <p style="margin: 0 0 6px;">Here's your polished prompt — paste into Claude:</p>
                <div class="chat-msg-output">${escapeHtml(polished)}</div>
                <div class="chat-msg-output-actions">
                    <button type="button" data-copy>📋 Copy</button>
                    <button type="button" data-refine>♻ Refine</button>
                </div>
            </div>
        `;
        chatMessages.appendChild(div);

        const copyBtn = div.querySelector('[data-copy]');
        copyBtn.addEventListener('click', async () => {
            let success = false;
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(polished);
                    success = true;
                } else {
                    // Fallback for http://localhost or older browsers
                    const ta = document.createElement('textarea');
                    ta.value = polished;
                    ta.style.position = 'fixed';
                    ta.style.opacity = '0';
                    ta.style.top = '-1000px';
                    document.body.appendChild(ta);
                    ta.focus();
                    ta.select();
                    success = document.execCommand('copy');
                    document.body.removeChild(ta);
                }
            } catch (_) { success = false; }

            if (success) {
                copyBtn.textContent = '✓ Copied';
                copyBtn.classList.add('copied');
                setTimeout(() => { copyBtn.textContent = '📋 Copy'; copyBtn.classList.remove('copied'); }, 1400);
            } else {
                copyBtn.textContent = 'Select + ⌘C';
                setTimeout(() => { copyBtn.textContent = '📋 Copy'; }, 2400);
            }
        });

        div.querySelector('[data-refine]').addEventListener('click', () => {
            chatInput.value = '';
            chatInput.placeholder = 'How should I change it? (e.g. "make it shorter")';
            chatInput.focus();
        });

        scrollChatToBottom();
    }

    function addErrorMsg(message) {
        const div = document.createElement('div');
        div.className = 'chat-msg chat-msg-assistant';
        div.innerHTML = `
            <div class="chat-msg-avatar">⚠</div>
            <div class="chat-msg-bubble">
                <p style="margin: 0;">I couldn't polish that:</p>
                <div class="chat-error">${escapeHtml(message)}</div>
            </div>
        `;
        chatMessages.appendChild(div);
        scrollChatToBottom();
    }

    function scrollChatToBottom() {
        if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    async function polishViaProxy(prompt) {
        const res = await fetch('api/polish.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prompt, history: chatHistory }),
        });

        let data;
        try {
            data = await res.json();
        } catch (e) {
            throw new Error('Server returned non-JSON (HTTP ' + res.status + '). Open the cheatsheet via http:// not file://.');
        }
        if (!res.ok) throw new Error(data && data.error ? data.error : 'HTTP ' + res.status);
        if (!data || typeof data.polished !== 'string') throw new Error('Unexpected response shape');
        return data.polished.trim();
    }

    async function sendChat() {
        if (!chatInput) return;
        const text = chatInput.value.trim();
        if (!text) return;

        chatInput.value = '';
        chatInput.style.height = 'auto';

        addUserMsg(text);
        chatHistory.push({ role: 'user', text });

        const thinking = addThinkingMsg();
        chatSend.disabled = true;

        try {
            const polished = await polishViaProxy(text);
            thinking.remove();
            addAssistantMsg(polished);
            chatHistory.push({ role: 'model', text: polished });
        } catch (err) {
            thinking.remove();
            addErrorMsg(err.message || 'Unknown error');
        } finally {
            chatSend.disabled = false;
            chatInput.focus();
        }
    }

    if (chatSend) chatSend.addEventListener('click', sendChat);
    if (chatInput) {
        chatInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendChat();
            }
        });
        chatInput.addEventListener('input', () => {
            chatInput.style.height = 'auto';
            chatInput.style.height = Math.min(chatInput.scrollHeight, 200) + 'px';
        });
    }
})();
