<?php
require_once __DIR__ . '/../../includes/auth.php';

require_post();
require_role(['admin']);

$admin = current_user();
$user_id = request_id('user_id');

if ($user_id === 0) {
    redirect_with('error', 'Invalid user.', 'admin/approvals.php');
}

$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ? AND role = 'user' AND account_status = 'pending'");
$stmt->execute([$user_id]);
$target = $stmt->fetch();

if (!$target) {
    redirect_with('warning', 'That request was not found or has already been reviewed.', 'admin/approvals.php');
}

$update = $pdo->prepare(
    "UPDATE users
     SET role = requested_role, account_status = 'approved', reject_reason = NULL,
         reviewed_by = ?, reviewed_at = NOW()
     WHERE id = ? AND role = 'user' AND account_status = 'pending'"
);
$update->execute([$admin['id'], $user_id]);

if ($update->rowCount() === 1) {
    flash_set('success', $target['full_name'] . ' has been approved.');
} else {
    flash_set('warning', 'That request has already been reviewed.');
}
redirect('admin/approvals.php');
