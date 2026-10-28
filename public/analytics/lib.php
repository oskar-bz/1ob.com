<?php
declare(strict_types=1);

// Shared helpers for the beacon and the dashboard.
// All state lives in /data (denied to the web by data/.htaccess):
//   analytics.db   SQLite database
//   secret.key     random key for signing visit ids (created on first use)
//   salt.json      today's random salt for visitor hashes (replaced daily, old one discarded)
//   config.php     dashboard password hash (create it yourself — see config.example.php)
//   GeoLite2-Country.mmdb or GeoLite2-City.mmdb   optional, for country lookup

date_default_timezone_set('Europe/Berlin');

const RETENTION_DAYS = 400;
const SESSION_GAP    = 1800;   // 30 min of inactivity starts a new session

function data_dir(): string {
    return dirname(__DIR__) . '/data';
}

function fail(int $code, string $msg): never {
    http_response_code($code);
    exit(json_encode(['error' => $msg]));
}

function db(): PDO {
    static $db = null;
    if ($db) return $db;
    if (!extension_loaded('pdo_sqlite')) fail(500, 'storage unavailable');
    $dir = data_dir();
    if (!is_dir($dir) || !is_writable($dir)) fail(500, 'storage unavailable');

    $db = new PDO('sqlite:' . $dir . '/analytics.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA busy_timeout=3000');
    $db->exec('CREATE TABLE IF NOT EXISTS views (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        ts            INTEGER NOT NULL,
        page          TEXT    NOT NULL,
        status        INTEGER NOT NULL DEFAULT 200,
        referrer_host TEXT,
        utm_source    TEXT,
        utm_medium    TEXT,
        utm_campaign  TEXT,
        duration      INTEGER,
        scroll_depth  INTEGER,
        screen_w      INTEGER,
        is_mobile     INTEGER NOT NULL DEFAULT 0,
        visitor       TEXT,
        session       TEXT,
        is_entry      INTEGER NOT NULL DEFAULT 0,
        browser       TEXT,
        os            TEXT,
        country       TEXT
    )');
    $db->exec('CREATE INDEX IF NOT EXISTS i_ts      ON views(ts)');
    $db->exec('CREATE INDEX IF NOT EXISTS i_visitor ON views(visitor, ts)');
    $db->exec('CREATE INDEX IF NOT EXISTS i_session ON views(session)');
    $db->exec('CREATE INDEX IF NOT EXISTS i_page    ON views(page)');
    return $db;
}

/** Persistent random key, used to sign visit ids handed to the browser. */
function secret_key(): string {
    $f = data_dir() . '/secret.key';
    if (!is_file($f)) {
        @file_put_contents($f, bin2hex(random_bytes(32)), LOCK_EX);
        @chmod($f, 0600);
    }
    return (string)file_get_contents($f);
}

function sign(int $id): string {
    return substr(hash_hmac('sha256', (string)$id, secret_key()), 0, 24);
}

/**
 * Today's salt. A new random salt is generated each day and the previous one is
 * overwritten, so a visitor hash can't be recomputed from an IP afterwards.
 */
function daily_salt(): string {
    $f = data_dir() . '/salt.json';
    $today = date('Y-m-d');
    $cur = is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
    if (!is_array($cur) || ($cur['day'] ?? '') !== $today) {
        $cur = ['day' => $today, 'salt' => bin2hex(random_bytes(16))];
        @file_put_contents($f, json_encode($cur), LOCK_EX);
        @chmod($f, 0600);
    }
    return $cur['salt'];
}

function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
