<?php
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/footer_lib.php';
require_once __DIR__ . '/inc/mailer.php';

$fail = function ($msg) {
    header('Location: /footers.php?err=' . rawurlencode($msg));
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $fail('请求方式不正确');
}

csrf_check();

$name = trim($_POST['name'] ?? '');
$author = trim($_POST['author'] ?? '');
$html = (string)($_POST['html'] ?? '');

if ($name === '') {
    $fail('请填写页脚名称');
}
if (mb_strlen($name) > 40 || mb_strlen($author) > 40) {
    $fail('名称或作者过长');
}
if ($html === '') {
    $fail('请填写 HTML 片段');
}
if (strlen($html) > 20 * 1024) {
    $fail('片段超过 20KB');
}
if (substr_count($html, "\n") > 60) {
    $fail('片段超过 60 行');
}

$clean = footer_clean($html);
if ($clean === '') {
    $fail('片段内容为空或包含被禁止的标签');
}
if (preg_match('#<\s*(script|iframe|form)#i', $html)) {
    $fail('不允许 script / iframe / form');
}

$st = db()->prepare("INSERT INTO footers (name, author, html, status, created_at) VALUES (?,?,?,'pending',NOW())");
$st->execute([$name, $author, $clean]);

$notify = trim((string)setting('notify_email', ''));
if ($notify === '') {
    $acc = db()->query("SELECT username FROM mail_accounts WHERE status=1 ORDER BY sort ASC, id ASC LIMIT 1")->fetch();
    $notify = $acc ? (string)$acc['username'] : '';
}
if ($notify !== '') {
    $host = $_SERVER['HTTP_HOST'] ?? 'icp.imxy.cn';
    $body = "页脚名称：{$name}\n"
        . "作者：" . ($author !== '' ? $author : '匿名') . "\n"
        . "片段长度：" . strlen($clean) . " 字节\n\n"
        . "审核地址：https://{$host}/admin/footers.php";
    @send_mail($notify, '新的页脚上传：' . $name, $body);
}

header('Location: /footers.php?ok=upload');
exit;
