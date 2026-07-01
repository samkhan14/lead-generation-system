#!/usr/bin/env php
<?php

/**
 * Cursor Chat → Markdown Exporter
 *
 * Portable: derives the Cursor project directory automatically from the
 * git repo root path — no hardcoded paths needed.
 *
 * Behaviour on every run (called by the pre-commit hook):
 *   1. Read docs/cursor-chat-export.json for the primary chat UUID + title
 *   2. Find the transcript file for that UUID in Cursor's agent-transcripts/
 *   3. Wipe ALL existing .md files from docs/chats/
 *   4. Export the conversation as docs/chats/{title}.md (one file, always fresh)
 *
 * Portability: to use this script in any project, copy scripts/ and
 *   docs/cursor-chat-export.json, then update the JSON with the correct
 *   primaryComposerId and chatTitle for that project.
 */

define('DS', DIRECTORY_SEPARATOR);

$projectRoot = dirname(__DIR__);
$configPath  = $projectRoot . DS . 'docs' . DS . 'cursor-chat-export.json';
$outputDir   = $projectRoot . DS . 'docs' . DS . 'chats';

// ── 1. Load config ───────────────────────────────────────────────────

if (!is_readable($configPath)) {
    fwrite(STDERR, "cursor-chat-export: config not found at {$configPath}, skipping.\n");
    exit(0);
}

$config = json_decode(file_get_contents($configPath), true) ?? [];

$primaryUuid = trim((string) ($config['primaryComposerId'] ?? ''));
$chatTitle   = trim((string) ($config['chatTitle'] ?? ''));

if ($primaryUuid === '') {
    fwrite(STDERR, "cursor-chat-export: primaryComposerId not set in config, skipping.\n");
    exit(0);
}

// ── 2. Resolve Cursor project folder (portable, auto-derived) ───────

$transcriptFile = resolveTranscriptFile($projectRoot, $primaryUuid);

if (!$transcriptFile) {
    fwrite(STDERR, "cursor-chat-export: transcript for {$primaryUuid} not found, skipping.\n");
    exit(0);
}

// ── 3. Parse JSONL ───────────────────────────────────────────────────

$lines = file($transcriptFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if (empty($lines)) {
    exit(0);
}

if ($chatTitle === '') {
    $chatTitle = deriveTitleFromLines($lines) ?: 'Cursor chat export';
}

// ── 4. Wipe docs/chats/*.md (keep .gitkeep) ─────────────────────────

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

foreach (glob($outputDir . DS . '*.md') ?: [] as $old) {
    @unlink($old);
}

// ── 5. Build Markdown ────────────────────────────────────────────────

$md  = "# {$chatTitle}\n\n";
$md .= "> **Composer ID:** `{$primaryUuid}`  \n";
$md .= "> **Last exported:** " . date('Y-m-d H:i:s') . "\n\n";
$md .= "---\n\n";

$entries = parseEntries($lines);
$grouped = groupAssistantMessages($entries);

foreach ($grouped as $entry) {
    if ($entry['role'] === 'user') {
        $text = extractUserQuery($entry['text']);
        if (trim($text) === '') {
            continue;
        }
        $md .= "## User\n\n" . trim($text) . "\n\n---\n\n";
    } elseif ($entry['role'] === 'assistant') {
        $text = cleanAssistantText($entry['text']);
        if (trim($text) === '') {
            continue;
        }
        $md .= "## Assistant\n\n" . trim($text) . "\n\n---\n\n";
    }
}

// ── 6. Write single output file ──────────────────────────────────────

$slug       = slugify($chatTitle);
$outputFile = $outputDir . DS . $slug . '.md';
file_put_contents($outputFile, $md);

echo "cursor-chat-export: docs/chats/" . basename($outputFile) . " ✓\n";

// ═══════════════════════════════════════════════════════════════════
// Helpers
// ═══════════════════════════════════════════════════════════════════

/**
 * Convert a Windows/Unix absolute path to the slug Cursor uses for its
 * project folder, e.g.:
 *   D:\herd\testapp  →  d-herd-testapp
 *   /home/user/myapp  →  home-user-myapp
 */
function cursorProjectSlug(string $absPath): string
{
    $path = str_replace('\\', '/', $absPath);

    // Remove drive colon on Windows (C:/ → c/)
    $path = preg_replace('/^([A-Za-z]):\//', '$1/', $path);
    $path = ltrim($path, '/');

    // Replace path separators with dashes, lowercase
    $slug = strtolower(str_replace('/', '-', $path));

    // Collapse multiple dashes
    return preg_replace('/-+/', '-', $slug);
}

function resolveTranscriptFile(string $projectRoot, string $uuid): ?string
{
    $slug = cursorProjectSlug($projectRoot);

    $candidate = implode(DS, [
        getenv('USERPROFILE') ?: getenv('HOME'),
        '.cursor', 'projects', $slug, 'agent-transcripts',
        $uuid, $uuid . '.jsonl',
    ]);

    return (is_file($candidate) && is_readable($candidate)) ? $candidate : null;
}

/**
 * @param  list<string> $lines raw JSONL lines
 * @return list<array{role:string,text:string}>
 */
function parseEntries(array $lines): array
{
    $out = [];
    foreach ($lines as $line) {
        $obj = json_decode($line, true);
        if (!$obj || !isset($obj['role'], $obj['message']['content'][0]['text'])) {
            continue;
        }
        $out[] = ['role' => $obj['role'], 'text' => $obj['message']['content'][0]['text']];
    }

    return $out;
}

/**
 * Consecutive assistant turns: keep only the longest (the real final response).
 *
 * @param  list<array{role:string,text:string}> $entries
 * @return list<array{role:string,text:string}>
 */
function groupAssistantMessages(array $entries): array
{
    $out   = [];
    $i     = 0;
    $total = count($entries);

    while ($i < $total) {
        $e = $entries[$i];
        if ($e['role'] === 'assistant') {
            $best = $e;
            while ($i + 1 < $total && $entries[$i + 1]['role'] === 'assistant') {
                $i++;
                if (strlen($entries[$i]['text']) > strlen($best['text'])) {
                    $best = $entries[$i];
                }
            }
            $out[] = $best;
        } else {
            $out[] = $e;
        }
        $i++;
    }

    return $out;
}

/** Extract the real user query, stripping Cursor-injected system blocks. */
function extractUserQuery(string $text): string
{
    $systemTags = [
        'system_reminder', 'open_and_recently_viewed_files', 'external_links',
        'attached_files', 'git_status', 'user_info', 'agent_transcripts',
        'agent_skills', 'task_notification', 'mode_selection', 'rules',
    ];
    foreach ($systemTags as $tag) {
        $text = preg_replace('/<' . $tag . '>.*?<\/' . $tag . '>/s', '', $text);
    }
    if (preg_match('/<user_query>\s*(.*?)\s*<\/user_query>/s', $text, $m)) {
        return trim($m[1]);
    }

    return trim($text);
}

/** Strip trailing thinking/reasoning paragraphs that leaked into the response. */
function cleanAssistantText(string $text): string
{
    $paragraphs = preg_split('/\n{2,}/', $text);
    $count      = count($paragraphs);
    $cutIndex   = $count;
    $inZone     = false;

    for ($i = $count - 1; $i >= 0; $i--) {
        $para = trim($paragraphs[$i]);
        if ($para === '') {
            if ($inZone) {
                $cutIndex = $i;
            }
            continue;
        }

        if (isThinkingParagraph($para)) {
            $cutIndex = $i;
            $inZone   = true;
        } elseif ($inZone) {
            if (
                preg_match('/^\d+[\.\)]/', $para) ||
                preg_match('/^[-*+]\s/', $para) ||
                (!hasMarkdownFormatting($para) && strlen($para) < 300)
            ) {
                $cutIndex = $i;
            } else {
                break;
            }
        } else {
            break;
        }
    }

    return trim(implode("\n\n", array_slice($paragraphs, 0, $cutIndex)));
}

function isThinkingParagraph(string $para): bool
{
    static $starters = [
        'The user', 'The transcript', 'The issue', 'The thinking', 'The pattern',
        'The challenge', 'The cleanest', 'The most', 'The key', 'The main',
        'The real', 'The implementation', 'The problem', 'The approach', 'The error',
        'The DB', 'The migration', 'The upgrade', 'The fundamental', 'The simplest',
        'The best', 'The parsing', 'The hook', 'The export', 'The script',
        'The current', 'The package', 'The auth', 'The admin', 'The only',

        'Let me ', 'I need', "I'm ", "I'll ", 'I should', 'I also', 'I just',
        'I already', 'I have', 'I can', 'I realize', 'I notice', 'I see',
        'I was', 'I think', 'I want',

        'Now I', 'Now the', 'Now let',
        'Actually', 'Wait,', 'Wait ', 'Hmm', 'OK ', 'Oh ',
        'Since ', 'For the', 'For a ', 'For now', 'For this',
        'Looking at', 'Looking ahead', 'But ', 'So the', 'So I', 'So my', 'So we',
        'This is', 'This makes', 'This simplifies', 'This approach', 'This means',
        'This way', 'A better', 'One more', 'My best', 'My current',
        'Moving into', 'Starting with', 'Before diving', 'Phase ', 'Given ', 'Based on',
    ];

    foreach ($starters as $s) {
        if (str_starts_with($para, $s)) {
            return true;
        }
    }

    return false;
}

function hasMarkdownFormatting(string $para): bool
{
    return (bool) preg_match('/^#{1,6}\s|^\|.*\||```|^\*\*.*\*\*|^>\s|^\[.*\]\(/', $para);
}

function deriveTitleFromLines(array $lines): string
{
    foreach ($lines as $line) {
        $obj = json_decode($line, true);
        if (!$obj || ($obj['role'] ?? '') !== 'user') {
            continue;
        }
        $q = extractUserQuery($obj['message']['content'][0]['text'] ?? '');
        $q = preg_replace('/\s+/', ' ', trim($q));
        if ($q !== '') {
            return strlen($q) > 90 ? substr($q, 0, 87) . '...' : $q;
        }
    }

    return '';
}

function slugify(string $title): string
{
    $s = strtolower($title);
    $s = preg_replace('/[^\p{L}\p{N}]+/u', '-', $s);
    $s = trim($s, '-');
    $s = preg_replace('/-+/', '-', $s);

    return $s !== '' ? substr($s, 0, 72) : 'cursor-chat';
}
