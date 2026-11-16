<?php
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/mailer.php';

header('Content-Type: application/json; charset=utf-8');

function json_out($ok, $msg, $extra = [])
{
    echo json_encode(array_merge(['ok' => $ok, 'msg' => $msg], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(false, '请求方式不正确');
}

$token = $_POST['csrf'] ?? '';
if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
    json_out(false, '页面已过期，请刷新重试');
}

$captcha = strtoupper(trim($_POST['captcha'] ?? ''));
$expect = strtoupper($_SESSION['captcha'] ?? '');
$at = (int)($_SESSION['captcha_at'] ?? 0);
$_SESSION['captcha'] = '';
$_SESSION['captcha_at'] = 0;

if ($expect === '' || $captcha !== $expect || time() - $at > 300) {
    json_out(false, '图形验证码不正确');
}

$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_out(false, '邮箱格式不正确');
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$pdo = db();

$st = $pdo->prepare("SELECT COUNT(*) FROM email_codes WHERE email=? AND created_at > DATE_SUB(NOW(), INTERVAL 300 SECOND)");
$st->execute([$email]);
if ((int)$st->fetchColumn() > 0) {
    json_out(false, '同一邮箱 5 分钟内只能发送一次');
}

$st = $pdo->prepare("SELECT COUNT(*) FROM email_codes WHERE ip=? AND created_at > DATE_SUB(NOW(), INTERVAL 300 SECOND)");
$st->execute([$ip]);
if ((int)$st->fetchColumn() > 0) {
    json_out(false, '当前网络 5 分钟内只能发送一次');
}

$st = $pdo->prepare("SELECT COUNT(*) FROM email_codes WHERE email=? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$st->execute([$email]);
if ((int)$st->fetchColumn() >= 6) {
    json_out(false, '该邮箱发送过于频繁，请稍后再试');
}

$st = $pdo->prepare("SELECT COUNT(*) FROM email_codes WHERE ip=? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$st->execute([$ip]);
if ((int)$st->fetchColumn() >= 20) {
    json_out(false, '当前网络发送过于频繁，请稍后再试');
}

$code = (string)random_int(100000, 999999);
$hash = hash('sha256', $code . app_key());
$st = $pdo->prepare("INSERT INTO email_codes (email, code_hash, purpose, expires_at, ip, created_at) VALUES (?,?,?,DATE_ADD(NOW(), INTERVAL 10 MINUTE),?,NOW())");
$st->execute([$email, $hash, 'apply', $ip]);

[$ok, $e] = send_mail($email, '您的验证码', "验证码：{$code}\n10 分钟内有效。");

if (!$ok) {
    json_out(false, '邮件发送失败，请稍后再试');
}

json_out(true, '验证码已发送，5 分钟内不可重复获取', ['wait' => 300]);
