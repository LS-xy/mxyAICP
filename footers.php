<?php
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/footer_lib.php';

$site = setting('site_name', 'mxyA');
$rows = db()->query("SELECT * FROM footers WHERE status='approved' ORDER BY id ASC")->fetchAll();
$rules = footer_rules();
$groupUrl = 'https://qm.qq.com/q/IcCR2VZQUW';
$msg = $_GET['ok'] ?? '';
$err = $_GET['err'] ?? '';
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>页脚 - <?= h($site) ?></title>
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
        <a href="/admin/login.php">管理</a>
        <a href="<?= h($groupUrl) ?>" data-icon="group">官群</a>
        <button type="button" class="theme-toggle" id="themeToggle">浅色</button>
    </div>
    <div class="wrap">
        <div class="hero">
            <h1>页脚</h1>
        </div>

        <?php if ($msg === 'upload'): ?><p class="ok">提交成功，审核通过后就会出现在这里。</p><?php endif; ?>
        <?php if ($err !== ''): ?><p class="err"><?= h($err) ?></p><?php endif; ?>

        <div class="card">
            <div class="card-head">
                <div>
                    <h2>选一个页脚</h2>
                    <p class="muted">点任意一个卡片，输入你的备案号就能生成对应代码。</p>
                </div>
                <button type="button" id="openUpload">上传页脚</button>
            </div>
        </div>

        <div class="footer-grid">
            <?php foreach ($rows as $r): ?>
            <article class="footer-item" data-id="<?= (int)$r['id'] ?>" data-name="<?= h($r['name']) ?>">
                <div class="footer-sample"><?= footer_render($r['html'], 'MOE-2026-7XK', '#', $site) ?></div>
                <div class="footer-meta">
                    <strong><?= h($r['name']) ?></strong>
                    <span class="muted"><?= h($r['author'] !== '' ? $r['author'] : '匿名') ?></span>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <h2>加入官群</h2>
            <p class="muted">问题、建议、想加新样式，都可以进群说。</p>
            <div class="actions">
                <a class="ghost" href="<?= h($groupUrl) ?>" target="_blank" rel="noopener">加入群聊：呆呆待用群4</a>
            </div>
        </div>
    </div>

    <div class="modal-mask" id="codeModal">
        <div class="modal">
            <h2 id="codeTitle">生成页脚代码</h2>
            <div class="form-row">
                <div class="field">
                    <input placeholder=" " id="codeInput" autocomplete="off">
                    <label>输入你的编号</label>
                </div>
                <p class="muted" id="codeHint">例如 MOE-2026-7XK</p>
                <div class="actions">
                    <button type="button" class="ghost" id="codeCancel">取消</button>
                    <button type="button" id="codeSubmit">生成</button>
                </div>
                <div class="code-box" id="codeResult" hidden>
                    <textarea rows="4" readonly id="codeText"></textarea>
                    <button type="button" class="copy-btn" data-copy="#codeText">复制</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-mask" id="uploadModal">
        <div class="modal wide">
            <h2>上传页脚</h2>
            <div class="upload-rules">
                <strong>上传规则</strong>
                <ul>
                    <?php foreach ($rules as $r): ?><li><?= h($r) ?></li><?php endforeach; ?>
                </ul>
            </div>
            <form method="post" action="/ajax_footer_save.php" class="form-row">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <div class="field">
                    <input placeholder=" " name="name" required maxlength="40">
                    <label>页脚名称</label>
                </div>
                <div class="field">
                    <input placeholder=" " name="author" maxlength="40">
                    <label>作者（可留空）</label>
                </div>
                <div class="field area">
                    <textarea placeholder=" " name="html" rows="6" required></textarea>
                    <label>HTML 片段（用 __CODE__ / __LINK__ 占位）</label>
                </div>
                <div class="actions">
                    <button type="button" class="ghost" id="uploadCancel">取消</button>
                    <button>提交审核</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/assets/theme.js?v=20261116f"></script>
    <script src="/assets/app.js?v=20261116f"></script>
    <script>
    (function () {
        var codeModal = document.getElementById('codeModal');
        var uploadModal = document.getElementById('uploadModal');
        var codeTitle = document.getElementById('codeTitle');
        var codeInput = document.getElementById('codeInput');
        var codeHint = document.getElementById('codeHint');
        var codeResult = document.getElementById('codeResult');
        var codeText = document.getElementById('codeText');
        var activeId = 0;

        function show(mask) {
            mask.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function hide(mask) {
            mask.classList.remove('show');
            document.body.style.overflow = '';
        }

        Array.prototype.forEach.call(document.querySelectorAll('.footer-item'), function (item) {
            item.addEventListener('click', function () {
                activeId = item.getAttribute('data-id');
                codeTitle.textContent = '生成页脚代码 · ' + item.getAttribute('data-name');
                codeInput.value = '';
                codeHint.textContent = '输入编号后点生成，例如 ' + (document.body.dataset.sample || '');
                codeResult.hidden = true;
                show(codeModal);
                setTimeout(function () { codeInput.focus(); }, 200);
            });
        });

        document.getElementById('openUpload').addEventListener('click', function () { show(uploadModal); });
        document.getElementById('uploadCancel').addEventListener('click', function () { hide(uploadModal); });
        document.getElementById('codeCancel').addEventListener('click', function () { hide(codeModal); });

        [codeModal, uploadModal].forEach(function (mask) {
            mask.addEventListener('click', function (e) {
                if (e.target === mask) hide(mask);
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                hide(codeModal);
                hide(uploadModal);
            }
        });

        document.getElementById('codeSubmit').addEventListener('click', function () {
            var code = codeInput.value.trim().toUpperCase();
            if (!code) {
                codeHint.textContent = '请先填写编号';
                return;
            }
            codeHint.textContent = '正在查询…';
            fetch('/ajax_footer_code.php?footer=' + encodeURIComponent(activeId) + '&code=' + encodeURIComponent(code))
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (!res.ok) {
                        codeHint.textContent = res.msg;
                        codeResult.hidden = true;
                        return;
                    }
                    codeHint.textContent = res.msg;
                    codeText.value = res.snippet;
                    codeResult.hidden = false;
                })
                .catch(function () {
                    codeHint.textContent = '网络异常，请稍后再试';
                });
        });
    })();
    </script>
</body>
</html>
