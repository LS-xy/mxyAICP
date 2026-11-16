<?php
require_once __DIR__ . '/inc/helpers.php';
$site = setting('site_name', 'mxyA');
$notice = setting('notice', '');
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= h($site) ?></title>
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
        <div class="hero">
            <h1><?= h($site) ?></h1>
        </div>

        <div class="card">
            <h2>查询标识</h2>
            <form method="get" action="/query.php" class="form-row">
                <div class="field">
                    <input name="q" placeholder=" " required autocomplete="off">
                    <label>编号或网址</label>
                </div>
                <div class="actions">
                    <button>查询</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>申请标识</h2>
            <p class="muted">填写邮箱接收验证码，验证后提交站点信息，等待管理员审核。</p>
            <div class="actions">
                <a href="/apply.php">去申请</a>
            </div>
        </div>

        <div class="footer"><?= h($site) ?></div>
    </div>

    <?php if ($notice): ?>
    <div class="modal-mask notice">
        <div class="modal" role="dialog" aria-modal="true">
            <h2>公告</h2>
            <p><?= nl2br(h($notice)) ?></p>
            <div class="actions">
                <button type="button" data-close>知道了</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="/assets/theme.js?v=20261116f"></script>
    <script src="/assets/app.js?v=20261116f"></script>
</body>
</html>
