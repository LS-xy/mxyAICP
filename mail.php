<?php
require_once __DIR__ . '/inc/layout.php';
require_once __DIR__ . '/../inc/mailer.php';
require_owner();

$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $st = db()->prepare("INSERT INTO mail_accounts (host,port,secure,username,password,from_name,status,sort,created_at) VALUES (?,?,?,?,?,?,1,?,NOW())");
        $st->execute([
            trim($_POST['host'] ?? ''),
            (int)($_POST['port'] ?? 465),
            $_POST['secure'] ?? 'ssl',
            trim($_POST['username'] ?? ''),
            trim($_POST['password'] ?? ''),
            trim($_POST['from_name'] ?? 'mxyA'),
            (int)($_POST['sort'] ?? 0),
        ]);
        audit('mail_add', trim($_POST['username'] ?? ''));
        redirect('/admin/mail.php');
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare("DELETE FROM mail_accounts WHERE id=?")->execute([$id]);
        audit('mail_delete', (string)$id);
        redirect('/admin/mail.php');
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare("UPDATE mail_accounts SET status = 1 - status WHERE id=?")->execute([$id]);
        audit('mail_toggle', (string)$id);
        redirect('/admin/mail.php');
    } elseif ($action === 'test') {
        $to = trim($_POST['to'] ?? '');
        [$ok, $e] = send_mail($to, '测试邮件', '这是一封测试邮件。');
        if ($ok) {
            $msg = '测试邮件已发送';
        } else {
            $err = '发送失败：' . $e;
        }
    }
}

admin_header('邮箱');
$rows = db()->query("SELECT * FROM mail_accounts ORDER BY sort ASC, id ASC")->fetchAll();
?>
<h1>邮箱账号</h1>
<p class="muted">当前模式：<?= setting('mail_mode', 'backup') === 'rotate' ? '轮换模式' : '备用模式' ?></p>
<?php if ($msg): ?>
<p class="ok"><?= h($msg) ?></p>
<?php endif; ?>
<?php if ($err): ?>
<p class="err"><?= h($err) ?></p>
<?php endif; ?>
<div class="card">
    <h2>添加邮箱</h2>
    <form method="post" class="form-row cols-2">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <div class="field">
                    <input placeholder=" " name="host" value="smtp.qq.com" required>
                    <label>SMTP 地址</label>
                </div>
        <div class="field">
                    <input placeholder=" " name="port" value="465" required>
                    <label>端口</label>
                </div>
        <div class="field">
                    <input placeholder=" " name="username" required>
                    <label>加密</label>
            <select name="secure">
                <option value="ssl">ssl</option>
                <option value="tls">tls</option>
                <option value="none">none</option>
            </select>
        </div>
        <div class="field">
            <label>邮箱账号</label>
                </div>
        <div class="field">
                    <input placeholder=" " name="password" required>
                    <label>授权码</label>
                </div>
        <div class="field">
                    <input placeholder=" " name="from_name" value="mxyA">
                    <label>发件人名称</label>
                </div>
        <div class="field">
                    <input placeholder=" " name="sort" value="0">
                    <label>排序</label>
                </div>
        <div class="actions">
            <button>添加</button>
        </div>
    </form>
</div>
<div class="card mobile-cards">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>账号</th>
                <th>服务器</th>
                <th>加密</th>
                <th>排序</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td data-label="ID"><?= (int)$r['id'] ?></td>
                <td data-label="账号"><?= h($r['username']) ?></td>
                <td data-label="服务器"><?= h($r['host']) ?>:<?= (int)$r['port'] ?></td>
                <td data-label="加密"><?= h($r['secure']) ?></td>
                <td data-label="排序"><?= (int)$r['sort'] ?></td>
                <td data-label="状态"><?= $r['status'] ? '启用' : '停用' ?></td>
                <td data-label="操作">
                    <div class="actions">
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button>切换</button>
                        </form>
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button>删除</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="card">
    <h2>发送测试</h2>
    <form method="post" class="form-row">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="test">
        <div class="field">
                    <input placeholder=" " name="to" type="email" required>
                    <label>测试收件邮箱</label>
                </div>
        <div class="actions">
            <button>发送测试</button>
        </div>
    </form>
</div>
<?php admin_footer(); ?>