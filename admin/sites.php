<?php
require_once __DIR__ . '/inc/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'suspend') {
        db()->prepare("UPDATE sites SET status='suspended' WHERE id=?")->execute([$id]);
        audit('suspend', 'site:' . $id);
    } elseif ($action === 'restore') {
        db()->prepare("UPDATE sites SET status='approved' WHERE id=?")->execute([$id]);
        audit('restore', 'site:' . $id);
    }
    redirect('/admin/sites.php');
}

admin_header('站点');
$rows = db()->query("SELECT * FROM sites ORDER BY id DESC LIMIT 200")->fetchAll();
$labels = ['pending' => '待审核', 'approved' => '已通过', 'rejected' => '已拒绝', 'suspended' => '已停用'];
?>
<h1>站点列表</h1>
<div class="card mobile-cards">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>名称</th>
                <th>网址</th>
                <th>编号</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td data-label="ID"><?= (int)$r['id'] ?></td>
                <td data-label="名称"><?= h($r['name']) ?></td>
                <td data-label="网址"><?= h($r['url']) ?></td>
                <td data-label="编号"><?= h($r['code']) ?></td>
                <td data-label="状态"><span class="tag <?= h($r['status']) ?>"><?= h($labels[$r['status']] ?? $r['status']) ?></span></td>
                <td data-label="操作">
                    <div class="actions">
                        <?php if ($r['status'] === 'approved'): ?>
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <input type="hidden" name="action" value="suspend">
                            <button>停用</button>
                        </form>
                        <?php elseif ($r['status'] === 'suspended'): ?>
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <input type="hidden" name="action" value="restore">
                            <button>恢复</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php admin_footer(); ?>