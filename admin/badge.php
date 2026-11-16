<?php
require_once __DIR__ . '/inc/layout.php';

$a = current_admin();
$msg = '';
$err = '';
$dir = __DIR__ . '/../assets/badge';

if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (($_POST['action'] ?? '') === 'save') {
        set_setting('badge_text', trim($_POST['badge_text'] ?? ''));
        set_setting('badge_dot', trim($_POST['badge_dot'] ?? '#16a34a'));
        $msg = '已保存';
    }

    if (($_POST['action'] ?? '') === 'upload' && isset($_FILES['badge'])) {
        $f = $_FILES['badge'];
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $err = '上传失败，请重试';
        } elseif ($f['size'] > 512 * 1024) {
            $err = '文件不能超过 512KB';
        } else {
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['png', 'svg', 'jpg', 'jpeg', 'webp'], true)) {
                $err = '只支持 png / svg / jpg / webp';
            } else {
                $name = 'badge-' . date('YmdHis') . '.' . $ext;
                if (@move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
                    set_setting('badge_image', '/assets/badge/' . $name);
                    $msg = '徽标已更新';
                } else {
                    $err = '写入失败，请检查 assets/badge 目录权限';
                }
            }
        }
    }

    if (($_POST['action'] ?? '') === 'clear') {
        set_setting('badge_image', '');
        $msg = '已恢复默认样式';
    }

    audit('badge', 'style');
}

admin_header('页脚徽标');
$image = (string)setting('badge_image', '');
$text = (string)setting('badge_text', '');
$dot = (string)setting('badge_dot', '#16a34a');
$site = setting('site_name', 'mxyA');
$code = 'MOE-' . date('Y') . '-7XK';
?>
<h1>页脚徽标</h1>

<?php if ($msg): ?><p class="ok"><?= h($msg) ?></p><?php endif; ?>
<?php if ($err): ?><p class="err"><?= h($err) ?></p><?php endif; ?>

<div class="card">
    <h2>预览</h2>
    <div class="badge-preview">
        <a href="/query.php?q=<?= h($code) ?>" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:8px;padding:7px 13px;border:1px solid #e6e6e6;border-radius:999px;background:#fff;color:#141414;text-decoration:none;font:500 12px/1 -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'PingFang SC','Microsoft YaHei',sans-serif">
            <?php if ($image): ?>
            <img src="<?= h($image) ?>" alt="" style="width:16px;height:16px;object-fit:contain">
            <?php else: ?>
            <span style="width:6px;height:6px;border-radius:50%;background:<?= h($dot) ?>"></span>
            <?php endif; ?>
            <span><?= h($text !== '' ? $text : $site) ?></span>
            <span style="color:#c4c4c4">&middot;</span>
            <span style="font-family:ui-monospace,Menlo,Consolas,monospace;color:#6b7280"><?= h($code) ?></span>
        </a>
    </div>
</div>

<div class="card">
    <h2>上传徽标图片</h2>
    <p class="muted">支持 png / svg / jpg / webp，建议正方形、不超过 512KB。上传后会替换默认的圆点。</p>
    <form method="post" enctype="multipart/form-data" class="form-row">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="upload">
        <input type="file" name="badge" accept=".png,.svg,.jpg,.jpeg,.webp" required>
        <div class="actions">
            <button>上传</button>
        </div>
    </form>
</div>

<div class="card">
    <h2>样式设置</h2>
    <form method="post" class="form-row">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <div class="field">
            <input name="badge_text" placeholder=" " value="<?= h($text) ?>">
            <label>徽标文字（留空用站点名）</label>
        </div>
        <div class="field">
            <input name="badge_dot" placeholder=" " value="<?= h($dot) ?>">
            <label>圆点颜色（如 #16a34a）</label>
        </div>
        <div class="actions">
            <button>保存</button>
        </div>
    </form>
</div>

<div class="card">
    <h2>重置</h2>
    <form method="post" class="form-row">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="clear">
        <p class="muted">删除已上传的图片，恢复默认的圆点样式。</p>
        <div class="actions">
            <button type="submit" class="ghost">恢复默认</button>
        </div>
    </form>
</div>
<?php admin_footer(); ?>
