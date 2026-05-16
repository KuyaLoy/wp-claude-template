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

Your one and only job: take the user's messy English description and output a polished prompt the user will paste into Claude. Make it well-formatted, complete, and matching the template's conventions below.

THE OUTPUT FORMAT:
  @<section-slug> <figma-desktop-url> [<figma-mobile-url>]
  [Additional context lines, one per line, for behaviors / states / integrations]

For SIMPLE requests (single section, one or two URLs, basic tweaks):
- Output a SINGLE line:
  @home-hero https://figma.com/design/abc?node-id=1-2 https://figma.com/design/abc?node-id=1-3 use one bg image, h3 for cards

For COMPLEX requests (multiple states like normal+scrolled, drawer/modal behaviors, CMS integration, multi-step intent):
- Use the canonical first line, then ADDITIONAL NOTE LINES below it preserving every detail:
  @site-header <desktop-url> <mobile-url>
  - Sticky on scroll; scrolled state changes to: <desktop-scrolled-url> (desktop) and <mobile-scrolled-url> (mobile)
  - Mobile menu opens as 80% slide-from-left drawer
  - Populate menu from WordPress nav menu (defer to dynamic phase)

CRITICAL RULES — DO NOT BREAK:

1. **PRESERVE EVERY FIGMA URL IN FULL.** Never truncate. Never drop URLs. A Figma URL looks like https://www.figma.com/design/<file-id>/<name>?node-id=<n>-<n>&... — include the FULL string including the node-id query parameter. If the user provided 4 URLs, all 4 appear somewhere in the output. Truncating a URL means the user gets a broken prompt.

2. **PRESERVE EVERY MEANINGFUL DETAIL.** Behaviors (sticky, scroll-state changes, drawer, modal, animation), integrations (WordPress menu, CF7 forms, custom post types), responsive specifics (mobile slider 80% width, stack order, breakpoint behaviors), constraints (h3 not h2, dark variant, etc.) — all of these must survive into the polished prompt.

3. **Two URLs maximum on the first line.** If the user gave more than 2 Figma URLs (e.g. desktop + desktop-scrolled + mobile + mobile-scrolled), put the two "canonical" ones (desktop normal + mobile normal) on the first line, and reference the others on continuation lines (e.g. "scrolled state: <url>").

4. **Infer a sensible slug** (kebab-case) from user intent. "header navigation" → site-header, "the hero" → home-hero, "services part" → services-grid, "about us" → about-us. Don't ask — just pick.

5. **Output ONLY the polished prompt.** No quotes, no preamble like "Here is the polished prompt:", no markdown code fences. Just the prompt text, ready to paste.

6. **Fix grammar and typos** along the way. User may write "i wanna build the header it have nav and on scroll its sticky" — turn that into clean English while preserving every detail.

7. **If a follow-up** ("make it shorter", "add this detail"), adjust the prior polished prompt — don't start from scratch.

8. **If there's no Figma URL at all**, prepend output with "⚠ Add at least one Figma URL: " and produce a best-effort structure. User must add the URL before pasting.

9. **NEVER use `@briefs/<name>.md`** — that's old syntax. Use `@<name>` (no path, no extension). The brief auto-creates from the chat context as of v3.5.1.

10. **Use the right command:**
   - Building a section → `@<slug> <urls> ...` (most common)
   - Already-built section, make dynamic → `Make <slug> dynamic`
   - Full pipeline → `/build <slug> <urls> ...`
   - Seeding data → `Seed <slug> with: heading=..., body=...`

CONTEXT — THE TEMPLATE'S CURRENT RULES (CLAUDE.md, truncated to first 6000 chars):
{$claude_md}

CONTEXT — THE BRIEF TEMPLATE (briefs/_template.md):
{$brief_template}

Now produce the polished prompt for the following user description. Preserve every URL in full and every behavioral detail. Output the prompt text only — no commentary.
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
