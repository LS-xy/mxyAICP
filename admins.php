<?php
require_once __DIR__ . '/inc/layout.php';
require_owner();

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (strlen($username) < 3 || strlen($password) < 6) {
            $err = '用户名至少3位，密码至少6位';
        } else {
            try {
                $st = db()->prepare("INSERT INTO admin_users (username,password_hash,role,created_at) VALUES (?,?,'admin',NOW())");
                $st->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
                audit('admin_add', $username);
                redirect('/admin/admins.php');
            } catch (Exception $e) {
                $err = '用户名已存在';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $st = db()->prepare("SELECT role FROM admin_users WHERE id=?");
        $st->execute([$id]);
        if ($st->fetchColumn() !== 'owner') {
            db()->prepare("DELETE FROM admin_users WHERE id=?")->execute([$id]);
            audit('admin_delete', (string)$id);
        }
        redirect('/admin/admins.php');
    } elseif ($action === 'reset') {
        $id = (int)($_POST['id'] ?? 0);
        $password = $_POST['password'] ?? '';
        if (strlen($password) >= 6) {
            db()->prepare("UPDATE admin_users SET password_hash=? WHERE id=?")->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            audit('admin_reset', (string)$id);
        }
        redirect('/admin/admins.php');
    }
}

admin_header('管理员');
$rows = db()->query("SELECT id,username,role,created_at FROM admin_users ORDER BY id ASC")->fetchAll();
?>
<h1>管理员</h1>
<?php if ($err): ?>
<p class="err"><?= h($err) ?></p>
<?php endif; ?>
<div class="card">
    <form method="post" class="form-row">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <div class="field">
                    <input placeholder=" " name="username" required>
                    <label>用户名</label>
                </div>
        <div class="field">
                    <input placeholder=" " name="password" type="password" required>
                    <label>密码</label>
                </div>
        <div class="actions">
            <button>添加管理员</button>
        </div>
    </form>
</div>
<div class="card mobile-cards">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>用户名</th>
                <th>角色</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td data-label="ID"><?= (int)$r['id'] ?></td>
                <td data-label="用户名"><?= h($r['username']) ?></td>
                <td data-label="角色"><?= $r['role'] === 'owner' ? '站长' : '管理员' ?></td>
                <td data-label="创建时间"><?= h($r['created_at']) ?></td>
                <td data-label="操作">
                    <div class="actions">
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="action" value="reset">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <input name="password" placeholder="新密码" style="width:120px">
                            <button>重置</button>
                        </form>
                        <?php if ($r['role'] !== 'owner'): ?>
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button>删除</button>
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