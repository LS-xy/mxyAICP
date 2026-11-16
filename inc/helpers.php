<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function app_key() {
    $c = require __DIR__ . '/../config.php';
    return $c['app']['key'];
}

function setting($k, $default = null) {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT k,v FROM settings') as $r) {
            $cache[$r['k']] = $r['v'];
        }
    }
    return $cache[$k] ?? $default;
}

function set_setting($k, $v) {
    $st = db()->prepare('INSERT INTO settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)');
    $st->execute([$k, $v]);
}

function redirect($u) {
    header('Location: ' . $u);
    exit;
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_check() {
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
        http_response_code(400);
        exit('bad csrf');
    }
}

function display_fields() {
    $v = json_decode(setting('display_fields', '[]'), true);
    return is_array($v) ? $v : [];
}

function build_code($suffix) {
    $prefix = setting('prefix', 'MOE');
    $year = date('Y');
    return $prefix . '-' . $year . '-' . strtoupper($suffix);
}

function code_format() {
    return setting('prefix', 'MOE') . '-' . date('Y') . '-序号';
}