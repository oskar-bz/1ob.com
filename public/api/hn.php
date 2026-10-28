<?php
declare(strict_types=1);

// Mirrors a Hacker News thread as finished, sanitised HTML.
//   GET /api/hn.php?id=<item id>   →   { html, points, count, shown, synced }
// Cached for five minutes in /data/hn-cache. On upstream errors the last good copy is served.
// To hide a comment (and its replies), add its id to /data/hn-hidden.json, e.g. [41234567].

const TTL       = 300;
const MAX_TOP   = 30;        // top-level threads rendered; the rest are one click away on HN
const AUTHOR    = '';        // your HN username: those comments get an "Author" tag
const UPSTREAM  = 'https://hn.algolia.com/api/v1/items/';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$id = (string)($_GET['id'] ?? '');
if (!preg_match('/^(\d{1,12}|fixture)$/', $id)) { http_response_code(400); exit('{"error":"bad id"}'); }

$data_dir  = dirname(__DIR__) . '/data';
$cache_dir = $data_dir . '/hn-cache';
if (!is_dir($cache_dir)) @mkdir($cache_dir, 0750, true);
$cache = "$cache_dir/$id.json";

if (is_file($cache) && time() - filemtime($cache) < TTL) {
    header('Cache-Control: public, max-age=60');
    readfile($cache);
    exit;
}

// ── fetch ─────────────────────────────────────────────────────────────────────
function fetch_json(string $url): ?array {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 6, CURLOPT_CONNECTTIMEOUT => 3,
                                CURLOPT_USERAGENT => '1ob.com comment mirror', CURLOPT_FOLLOWLOCATION => true]);
        $raw = curl_exec($ch);
        $ok  = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => ['timeout' => 6, 'header' => "User-Agent: 1ob.com comment mirror\r\n"]]);
        $raw = @file_get_contents($url, false, $ctx);
        $ok  = $raw !== false;
    }
    $j = $ok ? json_decode((string)$raw, true) : null;
    return is_array($j) ? $j : null;
}

$item = $id === 'fixture'
    ? json_decode((string)@file_get_contents("$data_dir/hn-fixture.json"), true)
    : fetch_json(UPSTREAM . $id);

if (!is_array($item)) {
    if (is_file($cache)) { touch($cache); readfile($cache); exit; }   // stale copy, retry in TTL
    http_response_code(502);
    exit('{"error":"upstream unavailable"}');
}

// ── sanitise: keep p, i/em, b/strong, code, pre, br and http(s) links ──────────
function clean(string $html): string {
    if ($html === '') return '';
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
    libxml_clear_errors();
    $root = $doc->getElementsByTagName('div')->item(0);
    return $root ? walk($root) : '';
}
function walk(DOMNode $n): string {
    $out = '';
    foreach ($n->childNodes as $c) {
        if ($c instanceof DOMText) { $out .= htmlspecialchars($c->nodeValue ?? '', ENT_QUOTES, 'UTF-8'); continue; }
        if (!$c instanceof DOMElement) continue;
        $tag = strtolower($c->tagName);
        if (in_array($tag, ['script', 'style', 'iframe', 'object', 'template', 'noscript'], true)) continue;   // drop with content
        $inner = walk($c);
        switch ($tag) {
            case 'p': case 'pre': case 'code': case 'i': case 'em': case 'b': case 'strong':
                $out .= "<$tag>$inner</$tag>"; break;
            case 'br':
                $out .= '<br>'; break;
            case 'a':
                $href = $c->getAttribute('href');
                $out .= preg_match('#^https?://#i', $href)
                    ? '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" rel="nofollow ugc noopener" target="_blank">' . $inner . '</a>'
                    : $inner;
                break;
            default:
                $out .= $inner;
        }
    }
    return $out;
}

function ago(int $t): string {
    $d = max(0, time() - $t);
    if ($d < 3600)  return max(1, intdiv($d, 60)) . ' min ago';
    if ($d < 86400) return intdiv($d, 3600) . ' h ago';
    return intdiv($d, 86400) . ' d ago';
}

// ── render ─────────────────────────────────────────────────────────────────────
$hidden = json_decode((string)@file_get_contents("$data_dir/hn-hidden.json"), true) ?: [];
$hidden = array_flip(array_map('intval', is_array($hidden) ? $hidden : []));

function count_all(array $c): int {
    $n = 0;
    foreach ($c['children'] ?? [] as $k) $n += (!empty($k['text']) ? 1 : 0) + count_all($k);
    return $n;
}

function render(array $c, array $hidden, int &$shown): string {
    if (isset($hidden[(int)($c['id'] ?? 0)])) return '';
    $kids = '';
    foreach ($c['children'] ?? [] as $k) $kids .= render($k, $hidden, $shown);
    $text = trim((string)($c['text'] ?? ''));
    if ($text === '' && $kids === '') return '';           // deleted, nothing below
    if ($text !== '') $shown++;
    $n = count_all($c);
    $user = $text === '' ? '[deleted]' : htmlspecialchars((string)($c['author'] ?? ''), ENT_QUOTES, 'UTF-8');
    $op = (AUTHOR !== '' && ($c['author'] ?? '') === AUTHOR) ? '<span class="op">Author</span>' : '';
    $when = '<a class="tab" href="https://news.ycombinator.com/item?id=' . (int)$c['id'] . '" rel="noopener" target="_blank" style="color:inherit;text-decoration:none">' . ago((int)($c['created_at_i'] ?? time())) . '</a>';
    $hid = $n ? '<span class="hid">· ' . $n . ($n === 1 ? ' reply' : ' replies') . ' hidden</span>' : '';
    return '<details class="c" open><summary><span class="u">' . $user . '</span>' . $op . $when . $hid . '</summary>'
         . ($text !== '' ? '<div class="tx">' . clean($text) . '</div>' : '')
         . $kids . '</details>';
}

$shown = 0;
$html  = '';
foreach (array_slice($item['children'] ?? [], 0, MAX_TOP) as $top) $html .= render($top, $hidden, $shown);

$out = json_encode([
    'html'   => $html,
    'points' => (int)($item['points'] ?? 0),
    'count'  => count_all($item),
    'shown'  => $shown,
    'synced' => time(),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

@file_put_contents($cache, $out, LOCK_EX);
header('Cache-Control: public, max-age=60');
echo $out;
