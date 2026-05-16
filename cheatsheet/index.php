<?php
/**
 * wp-claude-template cheatsheet — main entry
 *
 * Single-column, Notion-style. Light theme default, dark via toggle.
 * Component partials live in partials/. JS + CSS in assets/.
 *
 * URL: http://<your-project>.test/wp-content/themes/<theme>/.claude/cheatsheet/
 */

// .env loader (shared with api/polish.php)
function wpct_load_env() {
    static $env = null;
    if ($env !== null) return $env;
    $env = [];
    $path = __DIR__ . '/.env';
    if (!file_exists($path)) return $env;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $eq = strpos($line, '=');
        if ($eq === false) continue;
        $key = trim(substr($line, 0, $eq));
        $val = trim(substr($line, $eq + 1));
        if (strlen($val) >= 2 && ($val[0] === '"' || $val[0] === "'") && $val[strlen($val) - 1] === $val[0]) {
            $val = substr($val, 1, -1);
        }
        $env[$key] = $val;
    }
    return $env;
}

$env = wpct_load_env();
$ai_enabled = !empty($env['GEMINI_API_KEY']);

function wpct_partial($name, $vars = []) {
    extract($vars);
    include __DIR__ . '/partials/' . $name . '.php';
}
?>
<!doctype html>
<html lang="en">
<?php wpct_partial('head'); ?>
<body data-platform="cowork">

<?php wpct_partial('header'); ?>

<div class="layout">
    <?php wpct_partial('sidebar'); ?>

    <main class="main">
        <?php wpct_partial('intro'); ?>
        <?php wpct_partial('quickstart'); ?>
        <?php wpct_partial('install'); ?>
        <?php wpct_partial('workflow'); ?>
        <?php wpct_partial('prompt-builder', ['ai_enabled' => $ai_enabled]); ?>
        <?php wpct_partial('commands'); ?>
        <?php wpct_partial('acf'); ?>
        <?php wpct_partial('images'); ?>
        <?php wpct_partial('tailwind'); ?>
        <?php wpct_partial('filetree'); ?>
        <?php wpct_partial('faq'); ?>
        <?php wpct_partial('troubleshooting'); ?>
        <?php wpct_partial('call-dev'); ?>
        <?php wpct_partial('retro'); ?>
    </main>
</div>

<?php wpct_partial('footer'); ?>

<?php wpct_partial('chat-popup'); ?>

<!-- GSAP for animations -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

<!-- App scripts -->
<script src="assets/js/main.js"></script>
<script src="assets/js/chatbox.js"></script>
<script src="assets/js/animations.js"></script>

</body>
</html>
