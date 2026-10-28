<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';

header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

// ── auth ──────────────────────────────────────────────────────────────────────
session_name('ob_stats');
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/analytics/', 'httponly' => true, 'samesite' => 'Strict',
    'secure'   => (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off'),
]);
session_start();

$cfg_file = data_dir() . '/config.php';
$cfg = is_file($cfg_file) ? require $cfg_file : null;

function page_open(string $title): void { ?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?></title><meta name="robots" content="noindex">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<style>
:root{--ground:#f4f2ec;--inset:#ebe8e0;--rule:#dcd9d0;--ink:#191917;--ink3:#46443e;--muted:#64625b;--faint:#a8a59b;--orange:#c13f12;--blue:#2f2fd0;
--sans:system-ui,-apple-system,"Segoe UI",sans-serif;--mono:ui-monospace,"SF Mono",Menlo,Consolas,monospace}
*{box-sizing:border-box}body{margin:0;background:var(--ground);color:var(--ink);font:15px/1.5 var(--sans);-webkit-font-smoothing:antialiased}
a{color:var(--ink);text-underline-offset:3px;text-decoration-color:#c9c5ba}a:hover{color:var(--orange)}
.wrap{max-width:1040px;margin:0 auto;padding:28px 32px 96px}
.lbl{font:500 11px/1.4 var(--mono);letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
.tab{font-variant-numeric:tabular-nums}
header{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}
.logo{font:500 15px/1 var(--mono);text-decoration:none}.logo span{color:var(--orange)}
h1{margin:72px 0 8px;font:500 40px/1.1 var(--sans);letter-spacing:-.025em}
.insight{margin:0 0 40px;max-width:720px;color:var(--ink3);font-size:17px}
.range{display:flex;gap:18px}.range a{font:500 12px/1 var(--mono);letter-spacing:.06em;text-transform:uppercase;color:var(--muted);text-decoration:none;padding:12px 2px}
.range a.on{color:var(--ink);text-decoration:underline;text-decoration-color:var(--orange);text-decoration-thickness:1.5px;text-underline-offset:8px}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1px;background:var(--rule);box-shadow:0 0 0 1px var(--rule);margin-bottom:56px}
.card{background:var(--ground);padding:18px 18px 16px;display:flex;flex-direction:column;gap:6px}
.card .v{font:500 28px/1 var(--sans);letter-spacing:-.02em;font-variant-numeric:tabular-nums}
.d{font:500 11px/1 var(--mono);margin-left:6px}.d.up{color:#2e7d4f}.d.down{color:var(--orange)}.d.flat{color:var(--faint)}
section{margin-bottom:56px}
section>h2{display:flex;justify-content:space-between;align-items:baseline;margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid var(--ink);font:500 11px/1.4 var(--mono);letter-spacing:.08em;text-transform:uppercase}
section>h2 span{color:var(--muted);text-transform:none;letter-spacing:0}
.two{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(420px,100%),1fr));gap:0 48px}
.spark{display:flex;align-items:flex-end;gap:2px;height:96px}
.spark i{flex:1;background:var(--rule);min-height:2px}.spark i.pk{background:var(--orange)}.spark i:hover{background:var(--ink)}
.axis{display:flex;justify-content:space-between;margin-top:8px;font:400 11px/1 var(--mono);color:var(--muted)}
.row{padding:8px 0;border-bottom:1px solid var(--rule)}
.row .top{display:flex;justify-content:space-between;gap:12px;font-size:14px}
.row .top span:first-child{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.row .n{font:400 13px/1.5 var(--mono);font-variant-numeric:tabular-nums}
.bar{height:3px;background:var(--inset);margin-top:6px}.bar b{display:block;height:100%;background:var(--ink)}
.bar.w b{background:var(--blue)}.bar.e b{background:var(--orange)}
.meta{display:flex;gap:14px;margin-top:4px;font:400 12px/1.4 var(--mono);color:var(--muted)}
.good{color:#2e7d4f}
.empty{color:var(--muted);font:400 13px/1.5 var(--mono)}
form{display:flex;gap:10px;flex-wrap:wrap;margin-top:24px}
input{font:inherit;padding:12px 14px;min-width:240px;border:0;background:#fff;box-shadow:inset 0 0 0 1px var(--rule)}
button{font:500 12px/1 var(--mono);letter-spacing:.07em;text-transform:uppercase;padding:0 16px;min-height:44px;border:0;background:var(--ink);color:var(--ground);cursor:pointer}
button:hover{background:var(--orange)}
.err{color:var(--orange);font:400 13px/1.5 var(--mono)}
code{font:13px var(--mono);background:var(--inset);padding:2px 6px}
@media(max-width:720px){.wrap{padding:16px 20px 64px}h1{margin-top:40px;font-size:32px}}
</style></head><body><div class="wrap">
<header><a class="logo" href="/">1ob<span>/</span></a><span class="lbl">Analytics</span></header>
<?php }
function page_close(): void { echo '</div></body></html>'; }

// first run: no password configured yet
if (!$cfg || empty($cfg['password_hash'])) {
    http_response_code(503);
    page_open('Analytics — setup'); ?>
    <h1>Set a password</h1>
    <p class="insight">Create <code>data/config.php</code> on the server (via FTP) with a password hash. Generate the hash locally with
    <code>php -r "echo password_hash('your password', PASSWORD_DEFAULT);"</code> and see <code>config.example.php</code> for the format.</p>
    <?php page_close(); exit;
}

// brute-force protection: 5 failures per IP lock the login for 15 minutes
$lock_file = data_dir() . '/login.json';
$ip_key = substr(hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . secret_key()), 0, 16);
$locks = is_file($lock_file) ? (json_decode((string)file_get_contents($lock_file), true) ?: []) : [];
$locks = array_filter($locks, fn($l) => ($l['until'] ?? 0) > time() || ($l['t'] ?? 0) > time() - 900);
$locked = ($locks[$ip_key]['until'] ?? 0) > time();

if (isset($_GET['logout'])) { $_SESSION = []; session_destroy(); header('Location: /analytics/'); exit; }

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($locked) {
        $error = 'Too many attempts. Try again in a few minutes.';
    } elseif (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        $error = 'Session expired. Try again.';
    } elseif (password_verify((string)$_POST['password'], $cfg['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['auth'] = true;
        unset($locks[$ip_key]);
        @file_put_contents($lock_file, json_encode($locks), LOCK_EX);
        header('Location: /analytics/'); exit;
    } else {
        $n = ($locks[$ip_key]['n'] ?? 0) + 1;
        $locks[$ip_key] = ['n' => $n, 't' => time(), 'until' => $n >= 5 ? time() + 900 : 0];
        @file_put_contents($lock_file, json_encode($locks), LOCK_EX);
        usleep(400000);
        $error = 'Wrong password.';
    }
}

if (empty($_SESSION['auth'])) {
    page_open('Analytics — sign in'); ?>
    <h1>Analytics</h1>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
      <label class="lbl" for="pw" style="flex-basis:100%">Password</label>
      <input id="pw" type="password" name="password" autocomplete="current-password" autofocus required>
      <button type="submit">Enter</button>
    </form>
    <?php if ($error): ?><p class="err"><?= h($error) ?></p><?php endif;
    page_close(); exit;
}

// ── data ──────────────────────────────────────────────────────────────────────
$db   = db();
$d_in = (int)($_GET['days'] ?? 30);
$days = in_array($d_in, [7, 30, 90, 365], true) ? $d_in : 30;
$now  = time();
$from = $now - $days * 86400;
$prev = $from - $days * 86400;
$off  = (int)date('Z');     // local offset for day / hour buckets

function q(string $sql, array $p = []): array { $s = db()->prepare($sql); $s->execute($p); return $s->fetchAll(PDO::FETCH_ASSOC); }
function q1(string $sql, array $p = []): array { return q($sql, $p)[0] ?? []; }

function summary(int $a, int $b): array {
    $s = q1('SELECT COUNT(*) v, COUNT(DISTINCT visitor) u, AVG(duration) dur, AVG(scroll_depth) sc
             FROM views WHERE status = 200 AND ts > ? AND ts <= ?', [$a, $b]);
    $x = q1('SELECT COUNT(*) n, SUM(c = 1) b FROM (SELECT COUNT(*) c FROM views WHERE status = 200 AND ts > ? AND ts <= ? GROUP BY session)', [$a, $b]);
    $n = (int)($x['n'] ?? 0);
    return [
        'views' => (int)$s['v'], 'visitors' => (int)$s['u'], 'sessions' => $n,
        'bounce' => $n ? (int)$x['b'] / $n * 100 : null,
        'dur' => $s['dur'] !== null ? (float)$s['dur'] : null,
        'pps' => $n ? (int)$s['v'] / $n : null,
    ];
}
$cur = summary($from, $now);
$old = summary($prev, $from);

$titles = is_file(__DIR__ . '/titles.json') ? (json_decode((string)file_get_contents(__DIR__ . '/titles.json'), true) ?: []) : [];
$label  = fn(string $p) => $titles[$p] ?? $p;

$per_day = [];
foreach (q('SELECT date(ts + ?, "unixepoch") d, COUNT(*) c FROM views WHERE status = 200 AND ts > ? GROUP BY d', [$off, $from]) as $r) $per_day[$r['d']] = (int)$r['c'];
$series = [];
for ($i = $days - 1; $i >= 0; $i--) { $d = date('Y-m-d', $now - $i * 86400); $series[$d] = $per_day[$d] ?? 0; }
$max_day = max(1, max($series));

$pages   = q('SELECT page, COUNT(*) c, AVG(duration) dur, AVG(scroll_depth) sc FROM views WHERE status = 200 AND ts > ? GROUP BY page ORDER BY c DESC LIMIT 12', [$from]);
$reads   = q("SELECT page, COUNT(*) c, AVG(duration) dur, SUM(scroll_depth >= 70) rt, COUNT(scroll_depth) m
              FROM views WHERE status = 200 AND ts > ? AND page LIKE '/writing/_%' GROUP BY page ORDER BY c DESC LIMIT 12", [$from]);
$refs    = q('SELECT referrer_host h, COUNT(*) c FROM views WHERE status = 200 AND ts > ? AND referrer_host IS NOT NULL GROUP BY h ORDER BY c DESC LIMIT 10', [$from]);
$direct  = (int)(q1('SELECT COUNT(*) c FROM views WHERE status = 200 AND ts > ? AND is_entry = 1 AND referrer_host IS NULL', [$from])['c'] ?? 0);
$utm     = q('SELECT utm_source s, utm_medium m, COUNT(*) c FROM views WHERE ts > ? AND utm_source IS NOT NULL GROUP BY s, m ORDER BY c DESC LIMIT 6', [$from]);
$entries = q('SELECT page, COUNT(*) c FROM views WHERE status = 200 AND ts > ? AND is_entry = 1 GROUP BY page ORDER BY c DESC LIMIT 8', [$from]);
$countries = q('SELECT country k, COUNT(*) c FROM views WHERE status = 200 AND ts > ? AND country IS NOT NULL GROUP BY k ORDER BY c DESC LIMIT 10', [$from]);
$devices = q1('SELECT SUM(is_mobile) m, COUNT(*) - SUM(is_mobile) d FROM views WHERE status = 200 AND ts > ?', [$from]);
$browsers = q('SELECT browser k, COUNT(*) c FROM views WHERE status = 200 AND ts > ? GROUP BY k ORDER BY c DESC LIMIT 6', [$from]);
$missing = q('SELECT page, COUNT(*) c, MAX(referrer_host) r FROM views WHERE status = 404 AND ts > ? GROUP BY page ORDER BY c DESC LIMIT 10', [$from]);
$recent  = q('SELECT ts, page, country, referrer_host, duration, is_mobile FROM views WHERE ts > ? ORDER BY ts DESC LIMIT 15', [$from]);

function dur(?float $s): string { if ($s === null) return '—'; $s = (int)round($s); return $s < 60 ? "{$s}s" : intdiv($s, 60) . 'm ' . ($s % 60) . 's'; }
function delta(?float $a, ?float $b, bool $up_good = true): string {
    if ($a === null || $b === null || $b == 0.0) return '';
    $c = ($a - $b) / $b * 100;
    if (abs($c) < 0.5) return '<span class="d flat">±0%</span>';
    $cls = (($c > 0) === $up_good) ? 'up' : 'down';
    return '<span class="d ' . $cls . '">' . ($c > 0 ? '+' : '−') . abs(round($c)) . '%</span>';
}
function rows(array $items, string $key, string $cls = '', ?callable $lab = null, ?callable $meta = null): void {
    if (!$items) { echo '<p class="empty">No data yet.</p>'; return; }
    $max = max(1, ...array_map(fn($r) => (int)$r['c'], $items));
    foreach ($items as $r) {
        $name = $lab ? $lab($r[$key]) : (string)$r[$key];
        echo '<div class="row"><div class="top"><span title="' . h((string)$r[$key]) . '">' . h($name) . '</span><span class="n">' . number_format((int)$r['c']) . '</span></div>';
        echo '<div class="bar ' . $cls . '"><b style="width:' . round((int)$r['c'] / $max * 100) . '%"></b></div>';
        if ($meta) echo '<div class="meta">' . $meta($r) . '</div>';
        echo '</div>';
    }
}
function ago(int $t): string { $d = time() - $t; return $d < 60 ? "{$d}s" : ($d < 3600 ? intdiv($d, 60) . 'm' : ($d < 86400 ? intdiv($d, 3600) . 'h' : intdiv($d, 86400) . 'd')); }

// one-line summary
$best = null;
foreach ($reads as $r) if ((int)$r['m'] >= 3) { $rate = (int)$r['rt'] / (int)$r['m']; if (!$best || $rate > $best[1]) $best = [$r['page'], $rate]; }
$top_ref = $refs[0]['h'] ?? null;

page_open('Analytics'); ?>
  <h1>Analytics</h1>
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:12px">
    <nav class="range" aria-label="Range"><?php foreach ([7, 30, 90, 365] as $d): ?><a href="?days=<?= $d ?>" class="<?= $d === $days ? 'on' : '' ?>"><?= $d ?>d</a><?php endforeach ?></nav>
    <a class="lbl" href="?logout=1">Sign out</a>
  </div>
  <?php if ($cur['views'] > 0): ?>
  <p class="insight"><?= number_format($cur['views']) ?> views from <?= number_format($cur['visitors']) ?> visitors in <?= $days ?> days<?php
    if ($top_ref): ?>; most arrive from <strong><?= h($top_ref) ?></strong><?php endif;
    if ($best): ?>; the best-read post is <strong><?= h($label($best[0])) ?></strong> (<?= round($best[1] * 100) ?>% read to the end)<?php endif ?>.</p>
  <?php endif ?>

  <div class="cards">
    <div class="card"><span class="lbl">Views<?= delta((float)$cur['views'], (float)$old['views']) ?></span><span class="v"><?= number_format($cur['views']) ?></span></div>
    <div class="card"><span class="lbl">Visitors<?= delta((float)$cur['visitors'], (float)$old['visitors']) ?></span><span class="v"><?= number_format($cur['visitors']) ?></span></div>
    <div class="card"><span class="lbl">Sessions<?= delta((float)$cur['sessions'], (float)$old['sessions']) ?></span><span class="v"><?= number_format($cur['sessions']) ?></span></div>
    <div class="card"><span class="lbl">Pages / session</span><span class="v"><?= $cur['pps'] !== null ? number_format($cur['pps'], 1) : '—' ?></span></div>
    <div class="card"><span class="lbl">Bounce<?= delta($cur['bounce'], $old['bounce'], false) ?></span><span class="v"><?= $cur['bounce'] !== null ? round($cur['bounce']) . '%' : '—' ?></span></div>
    <div class="card"><span class="lbl">Engaged time<?= delta($cur['dur'], $old['dur']) ?></span><span class="v"><?= dur($cur['dur']) ?></span></div>
  </div>

  <section>
    <h2>Views per day <span><?= date('j M', $from + 86400) ?> – <?= date('j M') ?></span></h2>
    <div class="spark"><?php $pk = max($series); foreach ($series as $d => $c): ?><i class="<?= $c === $pk && $c > 0 ? 'pk' : '' ?>" style="height:<?= max(2, round($c / $max_day * 96)) ?>px" title="<?= $d ?>: <?= $c ?>"></i><?php endforeach ?></div>
    <div class="axis"><span><?= date('j M', strtotime(array_key_first($series))) ?></span><span>peak <?= $pk ?> · avg <?= number_format(array_sum($series) / max(1, $days), 1) ?>/day</span><span>today</span></div>
  </section>

  <section>
    <h2>Writing <span>do people read to the end?</span></h2>
    <?php rows($reads, 'page', 'w', $label, fn($r) =>
        ((int)$r['m'] ? '<span class="' . ((int)$r['rt'] / (int)$r['m'] >= .5 ? 'good' : '') . '">' . round((int)$r['rt'] / (int)$r['m'] * 100) . '% read through</span>' : '')
        . '<span>' . dur($r['dur'] !== null ? (float)$r['dur'] : null) . ' engaged</span>') ?>
  </section>

  <div class="two">
    <section><h2>Pages</h2><?php rows($pages, 'page', '', $label, fn($r) => '<span>' . dur($r['dur'] !== null ? (float)$r['dur'] : null) . '</span><span>' . ($r['sc'] !== null ? round((float)$r['sc']) . '% scroll' : '') . '</span>') ?></section>
    <section><h2>Sources <span><?= number_format($direct) ?> direct</span></h2><?php rows($refs, 'h', 'e') ?>
      <?php if ($utm): ?><h2 style="margin-top:28px">Campaigns</h2><?php rows(array_map(fn($r) => ['k' => $r['s'] . ($r['m'] ? ' / ' . $r['m'] : ''), 'c' => $r['c']], $utm), 'k') ?><?php endif ?>
    </section>
  </div>

  <div class="two">
    <section><h2>Entry pages <span>where visits start</span></h2><?php rows($entries, 'page', '', $label) ?></section>
    <section><h2>Countries</h2><?php rows($countries, 'k') ?></section>
  </div>

  <div class="two">
    <section><h2>Devices</h2><?php rows([['k' => 'Desktop', 'c' => (int)($devices['d'] ?? 0)], ['k' => 'Mobile', 'c' => (int)($devices['m'] ?? 0)]], 'k') ?>
      <h2 style="margin-top:28px">Browsers</h2><?php rows($browsers, 'k') ?></section>
    <section><h2>Broken links <span>404s, with where they came from</span></h2><?php rows($missing, 'page', 'e', null, fn($r) => $r['r'] ? '<span>from ' . h($r['r']) . '</span>' : '') ?></section>
  </div>

  <section>
    <h2>Recent</h2>
    <?php if (!$recent): ?><p class="empty">No data yet.</p><?php endif; foreach ($recent as $r): ?>
    <div class="row"><div class="top"><span><?= h($label($r['page'])) ?></span><span class="n"><?= ago((int)$r['ts']) ?></span></div>
    <div class="meta"><span><?= h($r['country'] ?? '—') ?></span><span><?= $r['referrer_host'] ? h($r['referrer_host']) : 'direct' ?></span><?php if ($r['duration'] !== null): ?><span><?= dur((float)$r['duration']) ?></span><?php endif ?><?php if ($r['is_mobile']): ?><span>mobile</span><?php endif ?></div></div>
    <?php endforeach ?>
  </section>
<?php page_close();
