/* ============================================================
 *  chatbox.js — floating chat for AI prompt polish
 *  Calls local PHP proxy at api/polish.php (key in .env, server-side)
 * ============================================================ */
(function () {
    'use strict';
    const $ = (id) => document.getElementById(id);
    const popup = $('chat-popup'), fab = $('chat-fab');
    if (!popup) return;
    const closeBtns = document.querySelectorAll('[data-chat-close]');
    const chatInput = $('chat-input'), chatSend = $('chat-send'), chatMessages = $('chat-messages');

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
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-open-chat]');
        if (trigger) { e.preventDefault(); openChat(); }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && popup.classList.contains('is-open')) closeChat();
    });

    // --- chat state ---
    const chatHistory = [];
    const esc = (s) => String(s).replace(/[&<>"']/g, (c) =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function scrollBottom() {
        if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function addUser(text) {
        const div = document.createElement('div');
        div.className = 'chat-msg chat-msg-user';
        div.innerHTML = '<div class="chat-msg-avatar">🧑</div><div class="chat-msg-bubble">' + esc(text) + '</div>';
        chatMessages.appendChild(div);
        scrollBottom();
    }

    function addThinking() {
        const div = document.createElement('div');
        div.className = 'chat-msg chat-msg-assistant';
        div.dataset.thinking = 'true';
        div.innerHTML = '<div class="chat-msg-avatar">✦</div><div class="chat-msg-bubble"><span class="chat-typing"><span></span><span></span><span></span></span></div>';
        chatMessages.appendChild(div);
        scrollBottom();
        return div;
    }

    async function copyText(text) {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                return true;
            }
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.cssText = 'position:fixed;opacity:0;top:-1000px';
            document.body.appendChild(ta);
            ta.focus(); ta.select();
            const ok = document.execCommand('copy');
            document.body.removeChild(ta);
            return ok;
        } catch (_) { return false; }
    }

    function addAssistant(polished) {
        const div = document.createElement('div');
        div.className = 'chat-msg chat-msg-assistant';
        div.innerHTML =
            '<div class="chat-msg-avatar">✦</div>' +
            '<div class="chat-msg-bubble">' +
                '<p style="margin:0 0 6px;">Here\'s your polished prompt — paste into Claude:</p>' +
                '<div class="chat-msg-output">' + esc(polished) + '</div>' +
                '<div class="chat-msg-output-actions">' +
                    '<button type="button" data-copy>📋 Copy</button>' +
                    '<button type="button" data-refine>♻ Refine</button>' +
                '</div>' +
            '</div>';
        chatMessages.appendChild(div);

        const copyBtn = div.querySelector('[data-copy]');
        copyBtn.addEventListener('click', async () => {
            const ok = await copyText(polished);
            if (ok) {
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
        scrollBottom();
    }

    function addError(message) {
        const div = document.createElement('div');
        div.className = 'chat-msg chat-msg-assistant';
        div.innerHTML =
            '<div class="chat-msg-avatar">⚠</div>' +
            '<div class="chat-msg-bubble">' +
                '<p style="margin:0;">I couldn\'t polish that:</p>' +
                '<div class="chat-error">' + esc(message) + '</div>' +
            '</div>';
        chatMessages.appendChild(div);
        scrollBottom();
    }

    async function polishViaProxy(prompt) {
        const res = await fetch('api/polish.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prompt: prompt, history: chatHistory })
        });
        const rawBody = await res.text();
        let data = null;
        try { data = JSON.parse(rawBody); }
        catch (e) {
            const snippet = (rawBody || '').slice(0, 400).trim() || '(empty body)';
            throw new Error('Server returned non-JSON (HTTP ' + res.status + '). Most likely a PHP error/warning was printed before the JSON. Raw response (first 400 chars):\n\n' + snippet);
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
        addUser(text);
        chatHistory.push({ role: 'user', text: text });
        const thinking = addThinking();
        chatSend.disabled = true;
        try {
            const polished = await polishViaProxy(text);
            thinking.remove();
            addAssistant(polished);
            chatHistory.push({ role: 'model', text: polished });
        } catch (err) {
            thinking.remove();
            addError(err.message || 'Unknown error');
        } finally {
            chatSend.disabled = false;
            chatInput.focus();
        }
    }

    if (chatSend) chatSend.addEventListener('click', sendChat);
    if (chatInput) {
        chatInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendChat(); }
        });
        chatInput.addEventListener('input', () => {
            chatInput.style.height = 'auto';
            chatInput.style.height = Math.min(chatInput.scrollHeight, 200) + 'px';
        });
    }
})();
