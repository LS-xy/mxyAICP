<?php
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/mailer.php';

$msg = '';
$err = '';
$site = setting('site_name', 'mxyA');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '');

    if ($email === '' || $code === '' || $name === '' || $url === '' || $suffix === '') {
        $err = '请填写完整';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = '邮箱格式不正确';
    } elseif (!preg_match('#^https?://#i', $url)) {
        $err = '网址必须以 http 或 https 开头';
    } elseif (!preg_match('/^[A-Za-z0-9]{2,8}$/', $suffix)) {
        $err = '编号后缀需为 2 到 8 位字母或数字';
    } else {
        $hash = hash('sha256', $code . app_key());
        $st = db()->prepare("SELECT id FROM email_codes WHERE email=? AND code_hash=? AND used_at IS NULL AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
        $st->execute([$email, $hash]);
        $row = $st->fetch();

        if (!$row) {
            $err = '验证码错误或已过期';
        } else {
            $newCode = build_code($suffix);
            $st = db()->prepare("SELECT id FROM sites WHERE code=?");
            $st->execute([$newCode]);
            if ($st->fetch()) {
                $err = '该编号已被占用';
            } else {
                db()->prepare("UPDATE email_codes SET used_at=NOW() WHERE id=?")->execute([$row['id']]);
                $st = db()->prepare("INSERT INTO sites (name,url,description,email,code,status,created_at) VALUES (?,?,?,?,?,'pending',NOW())");
                $st->execute([$name, $url, $desc, $email, $newCode]);

                $notify = trim((string)setting('notify_email', ''));
                if ($notify === '') {
                    $acc = db()->query("SELECT username FROM mail_accounts WHERE status=1 ORDER BY sort ASC, id ASC LIMIT 1")->fetch();
                    $notify = $acc ? (string)$acc['username'] : '';
                }
                if ($notify !== '') {
                    $host = $_SERVER['HTTP_HOST'] ?? 'icp.imxy.cn';
                    $body = "站点名称：{$name}\n"
                        . "站点网址：{$url}\n"
                        . "编号：{$newCode}\n"
                        . "申请邮箱：{$email}\n"
                        . "简介：{$desc}\n\n"
                        . "审核地址：https://{$host}/admin/index.php";
                    @send_mail($notify, '新的标识申请：' . $name, $body);
                }

                $msg = '提交成功，编号为 ' . $newCode . '，等待审核';
            }
        }
    }
}

$prefix = setting('prefix', 'MOE');
$year = date('Y');
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>申请标识 - <?= h($site) ?></title>
    <script>
    (function () {
        var t = localStorage.getItem("theme") || "light";
        if (["light", "dark", "warm"].indexOf(t) === -1) t = "light";
        document.documentElement.setAttribute("data-theme", t);
    })();
    </script>
    <link rel="stylesheet" href="/assets/style.css?v=20261116f">
</head>
<body>
    <div class="topbar">
        <span class="brand"><?= h($site) ?></span>
        <a href="/index.php">首页</a>
        <a href="/apply.php">申请</a>
        <a href="/query.php">查询</a>
        <a href="/footers.php">页脚</a>
        <a href="https://qm.qq.com/q/IcCR2VZQUW" target="_blank" rel="noopener">官群</a>
        <a href="/admin/login.php">管理</a>
        <button type="button" class="theme-toggle" id="themeToggle">浅色</button>
    </div>
    <div class="wrap">
        <h1>申请标识</h1>
        <?php if ($msg): ?>
        <p class="ok"><?= h($msg) ?></p>
        <?php endif; ?>
        <?php if ($err): ?>
        <p class="err"><?= h($err) ?></p>
        <?php endif; ?>

        <div class="card">
            <form method="post" class="form-row" id="applyForm">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

                <div class="field">
                    <input placeholder=" " name="email" type="email" required autocomplete="email">
                    <label>邮箱</label>
                </div>

                <div class="send-row">
                    <div class="field">
                        <input placeholder=" " name="captcha" required maxlength="4" autocomplete="off" style="text-transform:uppercase">
                        <label>图形验证码</label>
                    </div>
                    <img src="/captcha.php" alt="图形验证码" class="captcha-img" id="captchaImg" title="点击刷新">
                </div>

                <div class="send-row">
                    <div class="field">
                        <input placeholder=" " name="code" required maxlength="6" inputmode="numeric" autocomplete="one-time-code">
                        <label>邮箱验证码</label>
                    </div>
                    <button type="button" class="ghost" id="sendCode">获取验证码</button>
                </div>

                <div class="field">
                    <input placeholder=" " name="name" required>
                    <label>站点名称</label>
                </div>

                <div class="field">
                    <input placeholder=" " name="url" required>
                    <label>站点网址</label>
                </div>

                <div class="field">
                    <input placeholder=" " name="suffix" required maxlength="8" autocomplete="off" data-code-suffix>
                    <label>编号后缀</label>
                </div>

                <p class="muted">编号：<strong data-code-preview data-prefix="<?= h($prefix) ?>" data-year="<?= h($year) ?>"><?= h($prefix) ?>-<?= h($year) ?>-XXXX</strong></p>

                <div class="field area">
                    <textarea placeholder=" " name="description" rows="3"></textarea>
                    <label>简介</label>
                </div>

                <p class="muted" id="sendHint"></p>

                <div class="actions">
                    <button>提交申请</button>
                </div>
            </form>
        </div>
    </div>
    <script src="/assets/theme.js?v=20261116f"></script>
    <script src="/assets/app.js?v=20261116f"></script>
    <script>
    (function () {
        var form = document.getElementById('applyForm');
        var btn = document.getElementById('sendCode');
        var img = document.getElementById('captchaImg');
        var hint = document.getElementById('sendHint');
        var timer = null;

        function refreshCaptcha() {
            img.src = '/captcha.php?t=' + Date.now();
        }

        img.addEventListener('click', refreshCaptcha);

        btn.addEventListener('click', function () {
            if (btn.disabled) return;
            var email = form.querySelector('[name=email]').value.trim();
            var captcha = form.querySelector('[name=captcha]').value.trim();

            if (!email) {
                hint.textContent = '请先填写邮箱';
                return;
            }
            if (!captcha) {
                hint.textContent = '请填写图形验证码';
                return;
            }

            btn.disabled = true;
            btn.textContent = '发送中';
            hint.textContent = '';

            var data = new FormData();
            data.append('csrf', form.querySelector('[name=csrf]').value);
            data.append('email', email);
            data.append('captcha', captcha);

            fetch('/ajax_send_code.php', { method: 'POST', body: data, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    hint.textContent = res.msg;
                    refreshCaptcha();
                    if (res.ok) {
                        var left = res.wait || 300;
                        btn.textContent = left + ' 秒后重发';
                        timer = setInterval(function () {
                            left--;
                            btn.textContent = left > 0 ? left + ' 秒后重发' : '获取验证码';
                            if (left <= 0) {
                                clearInterval(timer);
                                btn.disabled = false;
                            }
                        }, 1000);
                    } else {
                        btn.disabled = false;
                        btn.textContent = '获取验证码';
                    }
                })
                .catch(function () {
                    hint.textContent = '网络异常，请稍后再试';
                    btn.disabled = false;
                    btn.textContent = '获取验证码';
                });
        });
    })();
    </script>
</body>
</html>
