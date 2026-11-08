<?php
require_once __DIR__ . '/../includes/auth.php'; require_login(); $user = current_user(); $page_title = 'Profile'; require __DIR__ . '/../includes/header.php';
?>
<div class="profile-card">
    <div class="banner"></div>
    <div class="profile-head"><span class="avatar avatar-xl"><?= e(strtoupper(substr($user['full_name'], 0, 1))) ?></span><div><h1><?= e($user['full_name']) ?></h1><p class="muted mb-0"><?= e($user['email']) ?></p><p><?= badge(ucfirst($user['role']), 'accent') ?> <?= status_badge($user['account_status']) ?></p></div></div>
    <div class="box"><h2>Account details</h2><dl class="meta-list"><dt>Full name</dt><dd><?= e($user['full_name']) ?></dd><dt>Email</dt><dd><?= e($user['email']) ?></dd><dt>Roll number</dt><dd><?= e($user['roll_no'] ?: 'Not provided') ?></dd><dt>Role</dt><dd><?= e(ucfirst($user['role'])) ?></dd><dt>Account status</dt><dd><?= status_badge($user['account_status']) ?></dd><dt>Joined</dt><dd><?= e(format_date($user['created_at'])) ?></dd></dl></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
