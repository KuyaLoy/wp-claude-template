/* ============================================================
 *  animations.js — GSAP entrance + scroll-triggered reveals
 *  Graceful fallback: if GSAP didn't load, IntersectionObserver
 *  fakes the reveal so the page still works.
 * ============================================================ */

(function () {
    'use strict';

    function fallbackReveal() {
        const els = document.querySelectorAll('.reveal');
        if (!els.length) return;
        const io = new IntersectionObserver(
            (entries) => entries.forEach((e) => e.isIntersecting && e.target.classList.add('is-visible')),
            { threshold: 0.1 }
        );
        els.forEach((el) => io.observe(el));
    }

    function run() {
        // Cheatsheets are reference docs — content must be visible immediately.
        // We do NOT use entrance animations that hide content. The fallback reveal
        // for elements with class="reveal" is still available if any partial opts in.
        fallbackReveal();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
