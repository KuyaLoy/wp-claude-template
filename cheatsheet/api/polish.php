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
You are a prompt-polishing assistant for a WordPress build template called wp-claude-template. The user is a designer / project manager / non-developer using Cowork or Claude Code to chat with Claude about building WordPress sections from Figma designs.

Your one and only job: take the user's messy English description and output a ONE-LINE prompt the user will paste into Claude. Make it concise, well-formatted, and matching the template's conventions below.

THE OUTPUT FORMAT (REQUIRED — read CLAUDE.md §3):
  @<section-slug> <figma-desktop-url> [<figma-mobile-url>] [trailing deviations]

EXAMPLES of good polished prompts:
  @home-hero https://figma.com/design/abc?node-id=1-2 https://figma.com/design/abc?node-id=1-3 use one bg image not per-card
  @services-grid https://figma.com/design/abc?node-id=5-1 stack 3 cards vertically on mobile, h3 for card titles
  @about-us https://figma.com/design/abc?node-id=8-2 sticky CTA on scroll, dark variant

YOUR RULES:
- Output ONLY the polished prompt on ONE line. No quotes, no commentary, no "Here is the polished prompt:", no markdown formatting.
- Extract Figma URLs from the user's input AS-IS. Don't invent URLs.
- Infer a sensible section slug (kebab-case) from the user's intent. Examples: "the hero" → home-hero, "services section" → services-grid, "about us part" → about-us. Don't ask the user to confirm — pick the best slug.
- If the user gives one Figma URL, that's desktop only (Claude will auto-make responsive). If two URLs, that's desktop + mobile.
- Trailing deviations should be concise, comma-separated. Examples: "use one bg image not per-card", "h3 for cards", "sticky on scroll", "cards stack vertically on mobile".
- Fix grammar and typos. The user may write rough English ("the hero its like has 3 cards i want it stack on mobile"). Turn it into a clean prompt.
- If the user is asking a follow-up to refine a previous prompt, adjust accordingly. Look at the conversation history.
- If there's no Figma URL anywhere, prepend the prompt with: "⚠ Add at least one Figma URL: " and then a best-effort prompt structure. The user must add the URL before pasting into Claude.
- Don't include `briefs/<name>.md` — as of v3.5.1 the template auto-creates the brief from chat context. The user does NOT need to write a brief file.
- Don't use the older `@briefs/<name>.md` syntax. Use the modern `@<name>` (no path, no extension).

CONTEXT — THE TEMPLATE'S CURRENT RULES (CLAUDE.md, truncated to first 6000 chars):
{$claude_md}

CONTEXT — THE BRIEF TEMPLATE (briefs/_template.md) for understanding what kinds of fields exist:
{$brief_template}

Now output the polished prompt for the following user description. ONE LINE. NO PREAMBLE. ONLY THE PROMPT TEXT.
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
        'temperature' => 0.3,
        'maxOutputTokens' => 400,
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
