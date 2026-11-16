<?php
require_once __DIR__ . '/inc/layout.php';

$a = current_admin();
$fields = [
    'name' => '站点名称',
    'url' => '站点网址',
    'code' => '编号',
    'status' => '状态',
    'description' => '简介',
    'created_at' => '申请时间',
    'reviewed_at' => '审核时间',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    set_setting('site_name', trim($_POST['site_name'] ?? 'mxyA'));
    set_setting('notice', trim($_POST['notice'] ?? ''));
    set_setting('notify_email', trim($_POST['notify_email'] ?? ''));
    $selected = array_values(array_intersect(array_keys($fields), $_POST['display'] ?? []));
    set_setting('display_fields', json_encode($selected, JSON_UNESCAPED_UNICODE));
    if ($a['role'] === 'owner') {
        $prefix = preg_replace('/[^A-Za-z0-9]/', '', $_POST['prefix'] ?? 'MOE');
        set_setting('prefix', $prefix !== '' ? $prefix : 'MOE');
        $mode = ($_POST['mail_mode'] ?? 'backup') === 'rotate' ? 'rotate' : 'backup';
        set_setting('mail_mode', $mode);
    }
    audit('settings', 'global');
    redirect('/admin/settings.php');
}

admin_header('设置');
$current = display_fields();
?>
<h1>设置</h1>
<div class="card">
    <form method="post" class="form-row">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <div class="field">
            <label>站点名称</label>
            <input placeholder=" " name="site_name" value="<?= h(setting('site_name', 'mxyA')) ?>">
        </div>

        <div class="field">
            <input name="notify_email" placeholder=" " value="<?= h(setting('notify_email', '')) ?>">
            <label>通知邮箱</label>
        </div>
        <div class="field">
            <label>公告</label>
            <textarea name="notice" rows="3"><?= h(setting('notice', '')) ?></textarea>
        </div>
        <div class="field">
            <label>公开页显示字段</label>
            <?php foreach ($fields as $k => $label): ?>
            <label class="checkline">
                <input type="checkbox" name="display[]" value="<?= h($k) ?>" <?= in_array($k, $current) ? 'checked' : '' ?>>
                <?= h($label) ?>
            </label>
            <?php endforeach; ?>
        </div>
        <?php if ($a['role'] === 'owner'): ?>
        <div class="field">
            <label>编号前缀</label>
            <input placeholder=" " name="prefix" value="<?= h(setting('prefix', 'MOE')) ?>">
        </div>
        <div class="field">
            <label>邮件模式</label>
            <select name="mail_mode">
                <option value="backup" <?= setting('mail_mode') === 'backup' ? 'selected' : '' ?>>备用模式</option>
                <option value="rotate" <?= setting('mail_mode') === 'rotate' ? 'selected' : '' ?>>轮换模式</option>
            </select>
        </div>
        <?php endif; ?>
        <div class="actions">
            <button>保存</button>
        </div>
    </form>
</div>
<?php admin_footer(); ?>