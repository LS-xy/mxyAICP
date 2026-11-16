<?php
require_once __DIR__ . '/helpers.php';

function current_admin() {
    return $_SESSION['admin'] ?? null;
}

function require_login() {
    if (!current_admin()) {
        redirect('/admin/login.php');
    }
}

function require_owner() {
    require_login();
    if ((current_admin()['role'] ?? '') !== 'owner') {
        http_response_code(403);
        exit('forbidden');
    }
}

function audit($action, $target, $detail = '') {
    $a = current_admin();
    $st = db()->prepare('INSERT INTO audit_logs (admin_id, action, target, detail, created_at) VALUES (?,?,?,?,NOW())');
    $st->execute([$a['id'] ?? null, $action, $target, $detail]);
}