<?php
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/footer_lib.php';

header('Content-Type: application/json; charset=utf-8');

function out($ok, $msg, $snippet = '')
{
    echo json_encode(['ok' => $ok, 'msg' => $msg, 'snippet' => $snippet], JSON_UNESCAPED_UNICODE);
    exit;
}

$code = strtoupper(trim($_GET['code'] ?? ''));
$footerId = (int)($_GET['footer'] ?? 0);
$site = setting('site_name', 'mxyA');

if ($code === '') {
    out(false, '请填写编号');
}

$st = db()->prepare("SELECT * FROM sites WHERE code=? AND status='approved' LIMIT 1");
$st->execute([$code]);
$row = $st->fetch();

if (!$row) {
    out(false, '没有找到这个编号，或者它还没通过审核');
}

$st = db()->prepare("SELECT * FROM footers WHERE id=? AND status='approved' LIMIT 1");
$st->execute([$footerId]);
$footer = $st->fetch();

if (!$footer) {
    out(false, '这个页脚已下架，换一个试试');
}

$host = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'icp.imxy.cn');
$link = $host . '/query.php?q=' . rawurlencode($row['code']);
$snippet = footer_render($footer['html'], $row['code'], $link, $site);

out(true, '已找到「' . $row['name'] . '」，代码已生成', $snippet);
