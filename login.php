<?php
require_once __DIR__ . '/../inc/helpers.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $st = db()->prepare("SELECT * FROM admin_users WHERE username=?");
    $st->execute([$username]);
    $u = $st->fetch();
    if ($u && password_verify($password, $u['password_hash'])) {
        $_SESSION['admin'] = ['id' => $u['id'], 'username' => $u['username'], 'role' => $u['role']];
        redirect('/admin/index.php');
    }
    $err = '用户名或密码错误';
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>登录 - <?= h(setting('site_name', 'mxyA')) ?></title>
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
        <span class="brand"><?= h(setting('site_name', 'mxyA')) ?></span>
        <a href="/index.php">首页</a>
        <a href="/apply.php">申请</a>
        <a href="/query.php">查询</a>
        <a href="/admin/login.php">管理</a>
        <button type="button" class="theme-toggle" id="themeToggle">浅色</button>
    </div>
    <div class="wrap">
        <h1>管理登录</h1>
        <?php if ($err): ?>
        <p class="err"><?= h($err) ?></p>
        <?php endif; ?>
        <div class="card">
            <form method="post" class="form-row">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <div class="field">
                    <input placeholder=" " name="username" required>
                    <label>用户名</label>
                </div>
                <div class="field">
                    <input placeholder=" " name="password" type="password" required>
                    <label>密码</label>
                </div>
                <div class="actions">
                    <button>登录</button>
                </div>
            </form>
        </div>
    </div>
    <script src="/assets/theme.js?v=20261116f"></script>
    <script src="/assets/app.js?v=20261116f"></script>
</body>
</html>