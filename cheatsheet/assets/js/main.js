/* ============================================================
 *  main.js — tabs, search, scroll-spy, FAQ, copy buttons, theme
 * ============================================================ */

(function () {
    'use strict';

    function $(id) { return document.getElementById(id); }

    // ---------------- Theme toggle ----------------
    const THEME_KEY = 'wpct.theme';

    function applyTheme(t) {
        document.documentElement.removeAttribute('data-pre-theme');
        if (t === 'dark') {
            document.body.setAttribute('data-theme', 'dark');
        } else {
            document.body.removeAttribute('data-theme');
        }
        localStorage.setItem(THEME_KEY, t || 'light');
    }

    const savedTheme = localStorage.getItem(THEME_KEY);
    applyTheme(savedTheme === 'dark' ? 'dark' : 'light');

    const themeBtn = $('theme-toggle');
    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            const cur = document.body.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
            applyTheme(cur === 'dark' ? 'light' : 'dark');
            if (window.ScrollTrigger) setTimeout(() => window.ScrollTrigger.refresh(), 50);
        });
    }

    // ---------------- Platform tabs (Cowork / Code) ----------------
    const TAB_KEY = 'wpct.platform';
    const tabs = document.querySelectorAll('[data-tab]');

    function setPlatform(name) {
        document.body.setAttribute('data-platform', name);
        localStorage.setItem(TAB_KEY, name);
        tabs.forEach((t) => t.setAttribute('aria-selected', t.dataset.tab === name ? 'true' : 'false'));
        const q = $('search-input');
        if (q && q.value) applySearch(q.value);
        if (window.ScrollTrigger) setTimeout(() => window.ScrollTrigger.refresh(), 80);
    }

    tabs.forEach((t) => t.addEventListener('click', () => setPlatform(t.dataset.tab)));

    const savedTab = localStorage.getItem(TAB_KEY);
    setPlatform(savedTab === 'code' ? 'code' : 'cowork');

    // ---------------- Search ----------------
    const input = $('search-input');
    const clear = $('search-clear');
    const sections = document.querySelectorAll('[data-searchable]');

    function applySearch(q) {
        q = (q || '').trim().toLowerCase();
        if (clear) clear.classList.toggle('visible', !!q);
        if (!q) {
            sections.forEach((s) => s.classList.remove('hidden-by-search'));
            return;
        }
        sections.forEach((s) => {
            const text = ((s.dataset.search || '') + ' ' + s.textContent).toLowerCase();
            s.classList.toggle('hidden-by-search', !text.includes(q));
        });
    }

    if (input) input.addEventListener('input', (e) => applySearch(e.target.value));
    if (clear) clear.addEventListener('click', () => { input.value = ''; applySearch(''); input.focus(); });

    document.addEventListener('keydown', (e) => {
        if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
            e.preventDefault();
            input && input.focus();
            input && input.select();
        }
        if (e.key === 'Escape' && input && input.value) {
            input.value = '';
            applySearch('');
            input.blur();
        }
    });

    // ---------------- FAQ accordion ----------------
    document.querySelectorAll('.faq-item').forEach((item) => {
        const q = item.querySelector('.faq-question');
        if (!q) return;
        q.addEventListener('click', () => {
            item.dataset.open = item.dataset.open === 'true' ? 'false' : 'true';
        });
    });

    // ---------------- Copy buttons ----------------
    document.querySelectorAll('.codeblock pre, .chat-block').forEach((el) => {
        if (el.querySelector('.copy-btn')) return;
        const btn = document.createElement('button');
        btn.textContent = 'Copy';
        btn.className = 'copy-btn';
        btn.type = 'button';
        el.style.position = 'relative';
        el.appendChild(btn);
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const text = (el.textContent || '').replace(/Copy$/, '').trim();
            let success = false;
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                    success = true;
                } else {
                    const ta = document.createElement('textarea');
                    ta.value = text;
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
                btn.textContent = '✓ Copied';
                btn.classList.add('copied');
                setTimeout(() => { btn.textContent = 'Copy'; btn.classList.remove('copied'); }, 1400);
            } else {
                btn.textContent = 'Select + ⌘C';
                setTimeout(() => (btn.textContent = 'Copy'), 2400);
            }
        });
    });
})();
