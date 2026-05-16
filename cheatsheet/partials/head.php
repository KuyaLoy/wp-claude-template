<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>wp-claude-template · Cheatsheet</title>
<meta name="description" content="WordPress + ACF + Tailwind 4 build template. Cowork and Claude Code workflow, install, AI prompt builder, FAQ.">
<meta name="theme-color" content="#14b8a6">

<!-- Inter + JetBrains Mono -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="assets/css/styles.css">

<!-- Avoid flash of wrong theme: read localStorage early -->
<script>
(function () {
    try {
        var t = localStorage.getItem('wpct.theme');
        if (t === 'dark') document.documentElement.setAttribute('data-pre-theme', 'dark');
    } catch (_) {}
})();
</script>
<style>html[data-pre-theme="dark"] body { background: #0b0c10; color: #f8fafc; }</style>

<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0' x2='1'%3E%3Cstop offset='0' stop-color='%2314b8a6'/%3E%3Cstop offset='1' stop-color='%23f97316'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='22' fill='url(%23g)'/%3E%3Ctext x='50' y='66' text-anchor='middle' font-family='Inter, sans-serif' font-size='52' font-weight='800' fill='white'%3E✦%3C/text%3E%3C/svg%3E">
</head>
