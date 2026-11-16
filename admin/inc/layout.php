<?php
require_once __DIR__ . '/../../inc/auth.php';
require_login();

function admin_header($title) {
    $a = current_admin();
    $site = h(setting('site_name', 'mxyA'));
    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . h($title) . '</title><script>(function(){var t=localStorage.getItem("theme")||"light";if(["light","dark","warm"].indexOf(t)===-1)t="light";document.documentElement.setAttribute("data-theme",t);})();</script><link rel="stylesheet" href="/assets/style.css?v=20261116f"></head><body><div class="topbar">';
    echo '<span class="brand">' . $site . ' 管理后台</span>';
    echo '<a href="/admin/index.php">审核</a>';
    echo '<a href="/admin/sites.php">站点</a>';
    echo '<a href="/admin/settings.php">设置</a>';
    echo '<a href="/admin/badge.php">徽标</a>';
    echo '<a href="/admin/footers.php">页脚</a>';
    if ($a['role'] === 'owner') {
        echo '<a href="/admin/mail.php">邮箱</a>';
        echo '<a href="/admin/admins.php">管理员</a>';
    }
    echo '<a href="/admin/logout.php">退出</a>';
    echo '<button type="button" class="theme-toggle" id="themeToggle">浅色</button>';
    echo '</div><div class="wrap">';
}

function admin_footer() {
    echo '</div><script src="/assets/theme.js?v=20261116f"></script><script src="/assets/app.js?v=20261116f"></script></body></html>';
}
