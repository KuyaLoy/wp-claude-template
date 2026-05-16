<?php
/**
 * /api/polish.php — server-side Gemini proxy.
 *
 * Loads the system prompt from polish-system-prompt.txt (kept separate so
 * polish.php stays small and doesn't risk truncation during file writes).
 * Substitutes {{CLAUDE_MD}} and {{BRIEF_TEMPLATE}} placeholders with the
 * actual project files at request time.
 *
 * Request:  POST { "prompt": "...rough description...", "history": [optional] }
 * Response: { "polished": "@<slug> <urls> ..." }
 *        OR { "error": "human message" }
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only.']);
    exit;
}

// .env loader
function load_env($path) {
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

$env   = load_env(__DIR__ . '/../.env');
$key   = $env['GEMINI_API_KEY'] ?? '';
$model = $env['GEMINI_MODEL'] ?? 'gemini-2.5-flash';

if (empty($key)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'No Gemini API key configured. Open cheatsheet/.env and add GEMINI_API_KEY=AIza... (get one free at https://aistudio.google.com/app/apikey, then refresh this page).',
    ]);
    exit;
}

// Parse request
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);
$user_prompt = is_array($body) ? trim((string) ($body['prompt'] ?? '')) : '';
$history     = is_array($body) && isset($body['history']) && is_array($body['history']) ? $body['history'] : [];

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

// Load files
function read_excerpt($path, $max = 4000) {
    if (!file_exists($path)) return '';
    $c = file_get_contents($path);
    return $c === false ? '' : substr($c, 0, $max);
}

$claude_md      = read_excerpt(__DIR__ . '/../../CLAUDE.md', 6000);
$brief_template = read_excerpt(__DIR__ . '/../../briefs/_template.md', 1200);

$prompt_template_path = __DIR__ . '/polish-system-prompt.txt';
if (!file_exists($prompt_template_path)) {
    http_response_code(500);
    echo json_encode(['error' => 'polish-system-prompt.txt is missing from cheatsheet/api/. Re-extract the zip or pull the latest from the repo.']);
    exit;
}
$system_prompt = file_get_contents($prompt_template_path);
$system_prompt = str_replace('{{CLAUDE_MD}}',      $claude_md,      $system_prompt);
$system_prompt = str_replace('{{BRIEF_TEMPLATE}}', $brief_template, $system_prompt);

// Build Gemini conversation
$contents = [
    ['role' => 'user',  'parts' => [['text' => $system_prompt]]],
    ['role' => 'model', 'parts' => [['text' => 'Understood. I will output polished prompts that preserve every Figma URL in full and every behavioral detail, using the @<slug> <urls> format from CLAUDE.md, with multi-line continuation for complex multi-state sections.']]],
];
foreach ($history as $turn) {
    if (!isset($turn['role'], $turn['text'])) continue;
    $role = $turn['role'] === 'model' ? 'model' : 'user';
    $contents[] = ['role' => $role, 'parts' => [['text' => (string) $turn['text']]]];
}
$contents[] = ['role' => 'user', 'parts' => [['text' => $user_prompt]]];

// Call Gemini
$endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($model) . ':generateContent?key=' . urlencode($key);
$payload  = [
    'contents' => $contents,
    'generationConfig' => ['temperature' => 0.4, 'maxOutputTokens' => 2000],
];

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 25,
]);
$resp   = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$cerr   = curl_error($ch);
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

$polished = isset($data['candidates'][0]['content']['parts'][0]['text']) ? trim($data['candidates'][0]['content']['parts'][0]['text']) : '';
$polished = trim($polished, " \t\n\r\0\x0B`\"'");
$polished = preg_replace('/^```[a-z]*\n?|\n?```$/i', '', $polished);
$polished = trim($polished);

if ($polished === '') {
    http_response_code(502);
    echo json_encode(['error' => 'Empty response from Gemini. Try a different description.']);
    exit;
}

echo json_encode(['polished' => $polished]);
