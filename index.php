<?php
require_once __DIR__ . '/inc/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'approve') {
        db()->prepare("UPDATE sites SET status='approved', reviewed_at=NOW(), reject_reason=NULL WHERE id=?")->execute([$id]);
        audit('approve', 'site:' . $id);
    } elseif ($action === 'reject') {
        $reason = trim($_POST['reason'] ?? '');
        db()->prepare("UPDATE sites SET status='rejected', reviewed_at=NOW(), reject_reason=? WHERE id=?")->execute([$reason, $id]);
        audit('reject', 'site:' . $id, $reason);
    }
    redirect('/admin/index.php');
}

admin_header('审核');
$rows = db()->query("SELECT * FROM sites WHERE status='pending' ORDER BY id ASC")->fetchAll();
?>
<h1>待审核</h1>
<div class="card mobile-cards">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>名称</th>
                <th>网址</th>
                <th>邮箱</th>
                <th>编号</th>
                <th>时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td data-label="ID"><?= (int)$r['id'] ?></td>
                <td data-label="名称"><?= h($r['name']) ?></td>
                <td data-label="网址"><?= h($r['url']) ?></td>
                <td data-label="邮箱"><?= h($r['email']) ?></td>
                <td data-label="编号"><?= h($r['code']) ?></td>
                <td data-label="时间"><?= h($r['created_at']) ?></td>
                <td data-label="操作">
                    <div class="actions">
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button>通过</button>
                        </form>
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <input type="hidden" name="action" value="reject">
                            <input name="reason" placeholder="理由" style="width:120px">
                            <button>拒绝</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
            <tr>
                <td colspan="7">暂无待审核记录</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php admin_footer(); ?>