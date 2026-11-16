<?php
require_once __DIR__ . '/inc/layout.php';
require_once __DIR__ . '/../inc/footer_lib.php';

$a = current_admin();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $act = $_POST['act'] ?? '';

    if ($act === 'approve' && $id > 0) {
        db()->prepare("UPDATE footers SET status='approved' WHERE id=?")->execute([$id]);
        $msg = '已通过';
    } elseif ($act === 'hide' && $id > 0) {
        db()->prepare("UPDATE footers SET status='hidden' WHERE id=?")->execute([$id]);
        $msg = '已下架';
    } elseif ($act === 'delete' && $id > 0 && $a['role'] === 'owner') {
        db()->prepare("DELETE FROM footers WHERE id=?")->execute([$id]);
        $msg = '已删除';
    }
    audit('footer', $act);
}

$rows = db()->query("SELECT * FROM footers ORDER BY status='pending' DESC, id DESC")->fetchAll();
admin_header('页脚');
?>
<h1>页脚</h1>

<?php if ($msg): ?><p class="ok"><?= h($msg) ?></p><?php endif; ?>

<p class="muted">待审核 <?= count(array_filter($rows, function ($r) { return $r['status'] === 'pending'; })) ?> 条。通过后才会出现在公开页脚页。</p>

<?php foreach ($rows as $r): ?>
<div class="card">
    <div class="card-head">
        <div>
            <h2><?= h($r['name']) ?>
                <span class="tag tag-<?= $r['status'] === 'approved' ? 'ok' : 'warn' ?>"><?= h($r['status']) ?></span>
            </h2>
            <p class="muted"><?= h($r['author'] !== '' ? $r['author'] : '匿名') ?> · <?= h($r['created_at']) ?></p>
        </div>
    </div>
    <div class="footer-sample"><?= footer_render($r['html'], 'MOE-2026-7XK', '#', setting('site_name', 'mxyA')) ?></div>
    <form method="post" class="actions">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <?php if ($r['status'] !== 'approved'): ?>
        <button name="act" value="approve">通过</button>
        <?php else: ?>
        <button name="act" value="hide" class="ghost">下架</button>
        <?php endif; ?>
        <?php if ($a['role'] === 'owner'): ?>
        <button name="act" value="delete" class="ghost">删除</button>
        <?php endif; ?>
    </form>
</div>
<?php endforeach; ?>
<?php admin_footer(); ?>
