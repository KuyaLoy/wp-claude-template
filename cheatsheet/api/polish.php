<?php
/**
 * /api/polish.php — server-side Gemini proxy with CLAUDE.md context
 *
 * Browser POSTs a rough description; this file:
 *   1. Reads GEMINI_API_KEY from ../.env
 *   2. Reads ../../CLAUDE.md (the project rules) to give Gemini real context
 *   3. Reads ../../briefs/_template.md if it exists
 *   4. Calls Gemini with rich system context, returns polished prompt
 *
 * The user's description can be messy English with typos — Gemini's job is
 * to extract intent, detect Figma URLs, fix grammar, and produce a prompt
 * matching the format CLAUDE.md §3 specifies.
 *
 * Request:  POST { "prompt": "...rough description...", "history": [optional chat turns] }
 * Response: { "polished": "@hero https://... use one bg image" }
 *        OR { "error": "human message" }
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only.']);
    exit;
}

// ---------------------------------------------------------------
// .env loader
// ---------------------------------------------------------------
function load_env($path)
{
    if (!file_exists($path)) return [];
    $vars = [];
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
        $vars[$key] = $val;
    }
    return $vars;
}

$env = load_env(__DIR__ . '/../.env');
$key = $env['GEMINI_API_KEY'] ?? '';
$model = $env['GEMINI_MODEL'] ?? 'gemini-2.5-flash';

if (empty($key)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'No Gemini API key configured. Open `cheatsheet/.env` and add GEMINI_API_KEY=AIza... (get one free at https://aistudio.google.com/app/apikey, then refresh this page).',
    ]);
    exit;
}

// ---------------------------------------------------------------
// Parse incoming request
// ---------------------------------------------------------------
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
$user_prompt = is_array($body) ? trim((string) ($body['prompt'] ?? '')) : '';
$history = is_array($body) && isset($body['history']) && is_array($body['history']) ? $body['history'] : [];

if ($user_prompt === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Please describe what you want to build.']);
    exit;
}

if (strlen($user_prompt) > 4000) {
    http_response_code(413);
    echo json_encode(['error' => 'Description too long (max 4000 chars). Trim it down.']);
    exit;
}

// ---------------------------------------------------------------
// Build context from CLAUDE.md (the template's rules)
// ---------------------------------------------------------------
function read_file_excerpt($path, $max_chars = 4000) {
    if (!file_exists($path)) return '';
    $content = file_get_contents($path);
    return $content === false ? '' : substr($content, 0, $max_chars);
}

// CLAUDE.md sits two levels up (cheatsheet/api/polish.php -> ../../CLAUDE.md)
$claude_md = read_file_excerpt(__DIR__ . '/../../CLAUDE.md', 6000);
$brief_template = read_file_excerpt(__DIR__ . '/../../briefs/_template.md', 1200);

// ---------------------------------------------------------------
// System prompt — teaches Gemini the template conventions
// ---------------------------------------------------------------
$system_prompt = <<<TXT
You polish messy English prompts for wp-claude-template (WordPress + ACF + Tailwind 4 build template). The user pastes a description; you output the EXACT text they'll paste into Claude. Output the prompt text ONLY — no preamble, no markdown fences, no commentary.

═════════════════════════════════════════════════════════════════
THE #1 ABSOLUTE RULE — DO NOT VIOLATE
═════════════════════════════════════════════════════════════════

EVERY Figma URL the user gave you must appear in your output IN FULL — the entire string including `?node-id=NNNN-NNN` and any other query parameters. Truncating any URL makes the output worthless to the user. If they gave 4 URLs, ALL 4 appear in your output. If they gave 1, that 1 appears in full. PRESERVE URLS WORD-FOR-WORD.

═════════════════════════════════════════════════════════════════
WORKED EXAMPLE — STUDY THIS BEFORE DOING ANYTHING ELSE
═════════════════════════════════════════════════════════════════

USER INPUT (rough, with typos):
i will start now to develop the header navigation  this is the figma for web  https://www.figma.com/design/EXguo0Lf2swbHTUWeY1dz4/Robin---Workspace?node-id=5471-104&m=dev  also web will change to like this afteer scroll as this is fixed or stikcy nav https://www.figma.com/design/EXguo0Lf2swbHTUWeY1dz4/Robin---Workspace?node-id=5471-117&m=dev  this is mobile  https://www.figma.com/design/EXguo0Lf2swbHTUWeY1dz4/Robin---Workspace?node-id=5471-146&m=dev  mobile when scroll https://www.figma.com/design/EXguo0Lf2swbHTUWeY1dz4/Robin---Workspace?node-id=5471-152&m=dev  for the open navigation just create slider left to right for mobile like 80% open like that  and also you will create menu for me using wordpress you have access on my wordpress this is when we will dynamic

CORRECT POLISHED OUTPUT (copy this format precisely):
@site-header https://www.figma.com/design/EXguo0Lf2swbHTUWeY1dz4/Robin---Workspace?node-id=5471-104&m=dev https://www.figma.com/design/EXguo0Lf2swbHTUWeY1dz4/Robin---Workspace?node-id=5471-146&m=dev

Additional behaviors:
- Sticky/fixed nav on scroll. When scrolled, the design changes:
  - Desktop scrolled state: https://www.figma.com/design/EXguo0Lf2swbHTUWeY1dz4/Robin---Workspace?node-id=5471-117&m=dev
  - Mobile scrolled state: https://www.figma.com/design/EXguo0Lf2swbHTUWeY1dz4/Robin---Workspace?node-id=5471-152&m=dev
- Mobile menu: slide-in drawer from left, ~80% viewport width when open
- Populate menu items from WordPress nav menu (wire up in the dynamic phase, with WP-admin access)

Notice in the example above:
- ALL 4 full Figma URLs are present
- Section slug `site-header` was inferred (NOT asked)
- Two canonical URLs on first line (desktop normal + mobile normal)
- Other URLs preserved as scroll-state references on continuation lines
- Every behavior (sticky, scrolled state change, mobile drawer 80% width, WP menu) is captured
- Typos fixed silently ("stikcy" → "sticky", "afteer" → "after"), tone normalized

═════════════════════════════════════════════════════════════════
RULES
═════════════════════════════════════════════════════════════════

1. PRESERVE EVERY URL IN FULL (the #1 rule above).
2. PRESERVE EVERY DETAIL the user wrote — behaviors, states, integrations, responsive notes, constraints, future plans.
3. INFER a slug (kebab-case) from intent: "header navigation" → site-header, "the hero" → home-hero, "services" → services-grid, "about" → about-us, "testimonials" → testimonials, "FAQ" → faq, "contact form" → contact. Never ask.
4. FORMAT depends on complexity:
   - Single section + 1-2 URLs + simple tweaks → SINGLE LINE: `@<slug> <url1> [<url2>] tweak1, tweak2`
   - Multiple Figma states (e.g. scrolled state), drawer/modal behaviors, CMS integration, multi-step intent → MULTI-LINE: first line is `@<slug> <canonical-url> [<canonical-mobile-url>]`, then a section header like "Additional behaviors:" followed by bulleted notes.
5. NEVER use `@briefs/<name>.md` syntax (legacy). Use `@<name>` (no path, no extension). Briefs auto-create from chat context as of v3.5.1.
6. Output ONLY the polished prompt. No "Here is the polished prompt:" preface. No code fences. No commentary.
7. Fix grammar and typos silently. Don't add disclaimers about the user's writing.
8. If the user gave ZERO Figma URLs, prepend the output with: `⚠ No Figma URL detected — paste one before sending to Claude. Best-effort structure:` then produce the prompt with `<paste-figma-url-here>` placeholders.
9. For follow-ups ("make it shorter", "add sticky on scroll"), modify the previous polished prompt instead of regenerating from scratch.

═════════════════════════════════════════════════════════════════
COMMAND CHEAT (use these when intent matches)
═════════════════════════════════════════════════════════════════

- Building a section from Figma → `@<slug> <urls> [tweaks]`
- Already-built static, make dynamic → `Make <slug> dynamic`
- Full chained pipeline (static → pause → dynamic → seed) → `/build <slug> <urls>`
- Seed real content into ACF fields → `Seed <slug> with: heading=..., body=..., image=<id>, cta=Get a quote/#quote`
- Upload static images to WP media library → `/upload-images <slug>`
- Production sweep → `/cleanup-section <slug>`
- Compare live to Figma → `/pixel-check <slug>`
- Pre-deploy audit → `/ship-check`

═════════════════════════════════════════════════════════════════
THE TEMPLATE'S CURRENT RULES (from CLAUDE.md, first 6000 chars)
═════════════════════════════════════════════════════════════════

{$claude_md}

═════════════════════════════════════════════════════════════════
THE BRIEF TEMPLATE (briefs/_template.md)
═════════════════════════════════════════════════════════════════

{$brief_template}

═════════════════════════════════════════════════════════════════

Now produce the polished prompt for the user's next message. Preserve every URL in full. Preserve every behavior. Output the prompt text only.
TXT;

// ---------------------------------------------------------------
// Build the full conversation for Gemini
// ---------------------------------------------------------------
$contents = [
    [
        'role' => 'user',
        'parts' => [['text' => $system_prompt]],
    ],
    [
        'role' => 'model',
        'parts' => [['text' => 'Understood. I will output one-line polished prompts following the @slug <urls> format, inferring slugs and tweaks from messy descriptions, and using CLAUDE.md rules as context.']],
    ],
];

// Append any prior chat turns (for refine)
foreach ($history as $turn) {
    if (!isset($turn['role'], $turn['text'])) continue;
    $role = $turn['role'] === 'model' ? 'model' : 'user';
    $contents[] = ['role' => $role, 'parts' => [['text' => (string) $turn['text']]]];
}

// Latest user input
$contents[] = ['role' => 'user', 'parts' => [['text' => $user_prompt]]];

// ---------------------------------------------------------------
// Call Gemini
// ---------------------------------------------------------------
$endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($model) . ':generateContent?key=' . urlencode($key);

$payload = [
    'contents' => $contents,
    'generationConfig' => [
        'temperature' => 0.4,
        'maxOutputTokens' => 2000,
    ],
];

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 25,
]);
$resp = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$cerr = curl_error($ch);
curl_close($ch);

if ($resp === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Network error reaching Gemini: ' . $cerr]);
    exit;
}

$data = json_decode($resp, true);

if ($status < 200 || $status >= 300) {
    $msg = $data['error']['message'] ?? ('Gemini API returned HTTP ' . $status);
    http_response_code(502);
    echo json_encode(['error' => $msg]);
    exit;
}

$polished = '';
if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
    $polished = trim($data['candidates'][0]['content']['parts'][0]['text']);
}

// Clean up: strip surrounding quotes, code fences, leading "@" duplication, etc.
$polished = trim($polished, " \t\n\r\0\x0B`\"'");
// If Gemini added "```" code fences, strip the fence markers
$polished = preg_replace('/^```[a-z]*\n?|\n?```$/i', '', $polished);
$polished = trim($polished);

if ($polished === '') {
    http_response_code(502);
    echo json_encode(['error' => 'Empty response from Gemini. Try a different description.']);
    exit;
}

echo json_encode(['polished' => $polished]);
