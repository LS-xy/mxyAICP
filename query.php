<?php
require_once __DIR__ . '/inc/helpers.php';

$q = trim($_GET['q'] ?? '');
$site = null;
if ($q !== '') {
    $st = db()->prepare("SELECT * FROM sites WHERE code=? OR url=? LIMIT 1");
    $st->execute([$q, $q]);
    $site = $st->fetch();
}
$fields = display_fields();
$title = setting('site_name', 'mxyA');
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>查询 - <?= h($title) ?></title>
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
        <span class="brand"><?= h($title) ?></span>
        <a href="/index.php">首页</a>
        <a href="/apply.php">申请</a>
        <a href="/query.php">查询</a>
        <a href="/footers.php">页脚</a>
        <a href="https://qm.qq.com/q/IcCR2VZQUW" target="_blank" rel="noopener">官群</a>
        <a href="/admin/login.php">管理</a>
        <button type="button" class="theme-toggle" id="themeToggle">浅色</button>
    </div>
    <div class="wrap">
        <h1>查询结果</h1>
        <?php if ($q === ''): ?>
        <div class="card">
            <p>请输入编号或网址</p>
        </div>
        <?php elseif (!$site): ?>
        <div class="card">
            <p class="err">未找到记录</p>
        </div>
        <?php elseif ($site['status'] !== 'approved'): ?>
        <div class="card">
            <p class="err">该站点尚未通过审核</p>
        </div>
        <?php else: ?>
        <div class="card">
            <table class="kv">
                <?php if (in_array('name', $fields)): ?>
                <tr>
                    <th>站点名称</th>
                    <td><?= h($site['name']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if (in_array('url', $fields)): ?>
                <tr>
                    <th>站点网址</th>
                    <td><a href="<?= h($site['url']) ?>" rel="noopener" target="_blank"><?= h($site['url']) ?></a></td>
                </tr>
                <?php endif; ?>
                <?php if (in_array('code', $fields)): ?>
                <tr>
                    <th>编号</th>
                    <td><?= h($site['code']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if (in_array('status', $fields)): ?>
                <tr>
                    <th>状态</th>
                    <td><span class="tag approved">已通过</span></td>
                </tr>
                <?php endif; ?>
                <?php if (in_array('description', $fields)): ?>
                <tr>
                    <th>简介</th>
                    <td><?= nl2br(h($site['description'])) ?></td>
                </tr>
                <?php endif; ?>
                <?php if (in_array('created_at', $fields)): ?>
                <tr>
                    <th>申请时间</th>
                    <td><?= h($site['created_at']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if (in_array('reviewed_at', $fields)): ?>
                <tr>
                    <th>审核时间</th>
                    <td><?= h($site['reviewed_at']) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <?php
        $badgeHost = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'icp.imxy.cn');
        $badgeUrl = $badgeHost . '/query.php?q=' . rawurlencode($site['code']);
        $badgeText = $title . ' ' . $site['code'];
        $badgeStyle = 'display:inline-flex;align-items:center;gap:8px;padding:7px 13px;border:1px solid #e6e6e6;'
            . 'border-radius:999px;background:#fff;color:#141414;text-decoration:none;'
            . 'font:500 12px/1 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"PingFang SC","Microsoft YaHei",sans-serif;'
            . 'box-shadow:0 1px 2px rgba(16,24,40,.04)';
        $badgeDot = 'width:6px;height:6px;border-radius:50%;background:' . setting('badge_dot', '#16a34a') . ';flex:0 0 auto';
        $badgeCode = 'font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;color:#6b7280;letter-spacing:.02em';
        $badgeImg = (string)setting('badge_image', '');
        $badgeName = (string)setting('badge_text', '');
        if ($badgeName === '') {
            $badgeName = $title;
        }
        $badgeMark = $badgeImg !== ''
            ? '<img src="' . htmlspecialchars($badgeHost . $badgeImg, ENT_QUOTES) . '" alt="" style="width:16px;height:16px;object-fit:contain">'
            : '<span style="' . $badgeDot . '"></span>';
        $badgeSnippet = '<a href="' . $badgeUrl . '" target="_blank" rel="noopener" style="' . $badgeStyle . '">'
            . ($badgeImg !== ''
                ? '<img src="' . $badgeHost . $badgeImg . '" alt="" style="width:16px;height:16px;object-fit:contain">'
                : '<span style="' . $badgeDot . '"></span>')
            . '<span>' . $badgeName . '</span>'
            . '<span style="color:#c4c4c4">&middot;</span>'
            . '<span style="' . $badgeCode . '">' . $site['code'] . '</span></a>';
        ?>
        <div class="card">
            <h2>页脚标识</h2>
            <p class="muted">把下面这段代码放到你网站的页脚，访客点一下就能看到本站的标识记录。</p>
            <div class="badge-preview">
                <a href="<?= h($badgeUrl) ?>" target="_blank" rel="noopener" style="<?= h($badgeStyle) ?>">
                    <?= $badgeMark ?>
                    <span><?= h($badgeName) ?></span>
                    <span style="color:#c4c4c4">&middot;</span>
                    <span style="<?= h($badgeCode) ?>"><?= h($site['code']) ?></span>
                </a>
            </div>
            <div class="code-box">
                <textarea rows="4" readonly onclick="this.select()"><?= h($badgeSnippet) ?></textarea>
                <button type="button" class="copy-btn" data-copy=".code-box textarea">复制</button>
            </div>
        </div>
<?php endif; ?>
    </div>
    <script src="/assets/theme.js?v=20261116f"></script>
    <script src="/assets/app.js?v=20261116f"></script>
</body>
</html>