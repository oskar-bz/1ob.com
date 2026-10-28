<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405, 'method not allowed');

// Same-origin only (browsers always send Origin on POST).
$host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
$bare = preg_replace('/^www\./', '', $host);
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $oh = strtolower((string)parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST));
    if ($host !== '' && $oh !== $host) fail(403, 'cross-origin');
}

$ok = fn() => exit('{"ok":true}');

// Your own visits: a logged-in dashboard session is not counted.
if (isset($_COOKIE['ob_stats'])) {
    session_name('ob_stats');
    session_start(['read_and_close' => true, 'cookie_path' => '/analytics/']);
    if (!empty($_SESSION['auth'])) $ok();
}

// Opt-outs, prefetch / prerender, bots.
if (($_SERVER['HTTP_SEC_GPC'] ?? '') === '1' || ($_SERVER['HTTP_DNT'] ?? '') === '1') $ok();
$purpose = strtolower($_SERVER['HTTP_SEC_PURPOSE'] ?? $_SERVER['HTTP_PURPOSE'] ?? $_SERVER['HTTP_X_PURPOSE'] ?? $_SERVER['HTTP_X_MOZ'] ?? '');
if (str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender')) $ok();

$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
if ($ua === '') $ok();
$ual = strtolower($ua);
foreach ([
    'bot', 'crawl', 'spider', 'slurp', 'scraper', 'archive', 'wget', 'curl/', 'python-', 'ruby/', 'perl/',
    'java/', 'go-http', 'okhttp', 'scrapy', 'headless', 'phantomjs', 'puppeteer', 'playwright', 'selenium',
    'lighthouse', 'pagespeed', 'preview', 'facebookexternalhit', 'whatsapp', 'telegram', 'discord', 'slack',
    'ia_archiver', 'semrush', 'ahrefs', 'mj12', 'dotbot', 'nutch', 'nmap', 'masscan', 'gptbot', 'claude', 'perplexity',
] as $b) {
    if (str_contains($ual, $b)) $ok();
}

$raw = file_get_contents('php://input', false, null, 0, 2049);
if (strlen((string)$raw) > 2048) fail(413, 'payload too large');
$data = json_decode((string)$raw, true);
if (!is_array($data) || !isset($data['type'])) fail(400, 'bad request');

$db = db();

// ── leave: update engaged time and scroll depth (signed id, keep the maximum) ──
if ($data['type'] === 'leave') {
    $id  = (int)($data['id'] ?? 0);
    $sig = (string)($data['sig'] ?? '');
    if ($id > 0 && hash_equals(sign($id), $sig)) {
        // CAST: PDO binds as TEXT, and SQLite's MAX() ranks TEXT above INTEGER.
        $db->prepare('UPDATE views
                         SET duration     = MAX(COALESCE(duration, 0),     CAST(? AS INTEGER)),
                             scroll_depth = MAX(COALESCE(scroll_depth, 0), CAST(? AS INTEGER))
                       WHERE id = ?')
           ->execute([min((int)($data['duration'] ?? 0), 86400), min(max((int)($data['scroll_depth'] ?? 0), 0), 100), $id]);
    }
    $ok();
}

if ($data['type'] !== 'view') fail(400, 'unknown type');

// ── view ──
function browser_of(string $ua): string {
    foreach (['Edg/' => 'Edge', 'OPR/' => 'Opera', 'Firefox/' => 'Firefox', 'Chromium/' => 'Chromium', 'Chrome/' => 'Chrome', 'Safari/' => 'Safari'] as $k => $v)
        if (str_contains($ua, $k)) return $v;
    return 'Other';
}
function os_of(string $ua): string {
    if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) return 'iOS';
    foreach (['Android' => 'Android', 'Windows' => 'Windows', 'Mac OS X' => 'macOS', 'Macintosh' => 'macOS', 'CrOS' => 'ChromeOS', 'Linux' => 'Linux'] as $k => $v)
        if (str_contains($ua, $k)) return $v;
    return 'Other';
}
function country_of(string $ip): ?string {
    $dir = data_dir();
    $path = is_file("$dir/GeoLite2-Country.mmdb") ? "$dir/GeoLite2-Country.mmdb" : (is_file("$dir/GeoLite2-City.mmdb") ? "$dir/GeoLite2-City.mmdb" : null);
    if (!$path) return null;
    $v = __DIR__ . '/vendor/MaxMind/Db';
    foreach (['Reader/InvalidDatabaseException', 'Reader/Util', 'Reader/Metadata', 'Reader/Decoder', 'Reader'] as $f) require_once "$v/$f.php";
    try {
        if (stripos($ip, '::ffff:') === 0) $ip = substr($ip, 7);
        $r = (new \MaxMind\Db\Reader($path))->get($ip);
        return $r['country']['iso_code'] ?? null;     // country only; no city is stored
    } catch (\Throwable $e) {
        return null;
    }
}

$page = substr(preg_replace('/[^\x21-\x7E]/', '', (string)($data['page'] ?? '/')), 0, 255) ?: '/';
$ref  = strtolower((string)parse_url((string)($data['referrer'] ?? ''), PHP_URL_HOST));
$ref  = preg_replace('/^www\./', '', $ref);
if ($ref === $bare) $ref = '';
$screen = max(0, min((int)($data['screen_w'] ?? 0), 10000));
$os     = os_of($ua);
$mobile = (($screen > 0 && $screen < 768) || in_array($os, ['iOS', 'Android'], true)) ? 1 : 0;
$status = (int)($data['status'] ?? 200) === 404 ? 404 : 200;
$now    = time();

// Daily visitor hash: IP + UA + today's random salt. The salt is discarded tomorrow,
// so the hash can't be linked across days or reversed to an IP. The IP itself is never stored.
$ip      = $_SERVER['REMOTE_ADDR'] ?? '';
$visitor = substr(hash_hmac('sha256', $ip . '|' . $ua, daily_salt()), 0, 16);

// Session: continue this visitor's last session if it was active in the last 30 minutes.
$st = $db->prepare('SELECT session FROM views WHERE visitor = ? AND ts > ? ORDER BY ts DESC LIMIT 1');
$st->execute([$visitor, $now - SESSION_GAP]);
$session = $st->fetchColumn();
$entry   = $session ? 0 : 1;
if (!$session) $session = bin2hex(random_bytes(8));

$cut = fn(string $k) => substr((string)($data[$k] ?? ''), 0, 100) ?: null;
$db->prepare('INSERT INTO views (ts, page, status, referrer_host, utm_source, utm_medium, utm_campaign, screen_w, is_mobile, visitor, session, is_entry, browser, os, country)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
   ->execute([$now, $page, $status, $ref ?: null, $cut('utm_source'), $cut('utm_medium'), $cut('utm_campaign'),
              $screen ?: null, $mobile, $visitor, $session, $entry, browser_of($ua), $os, country_of($ip)]);
$id = (int)$db->lastInsertId();

// Retention: occasionally drop raw rows older than RETENTION_DAYS.
if (random_int(1, 200) === 1) {
    $db->prepare('DELETE FROM views WHERE ts < ?')->execute([$now - RETENTION_DAYS * 86400]);
}

echo json_encode(['id' => $id, 'sig' => sign($id)]);
