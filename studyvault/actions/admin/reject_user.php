<?php
require_once __DIR__ . '/../../includes/auth.php';

require_post();
require_role(['admin']);

$admin = current_user();
$user_id = request_id('user_id');
$reason = preg_replace('/\s+/u', ' ', post_str('reason'));

if ($user_id === 0) {
    redirect_with('error', 'Invalid user.', 'admin/approvals.php');
}

if ($reason === '') {
    redirect_with('error', 'Please give a reason for rejecting the request.', 'admin/approvals.php');
}

if (char_length($reason) > MAX_REASON_LENGTH) {
    redirect_with('error', 'The reason must be at most ' . MAX_REASON_LENGTH . ' characters.', 'admin/approvals.php');
}

$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ? AND role = 'user' AND account_status = 'pending'");
$stmt->execute([$user_id]);
$target = $stmt->fetch();

if (!$target) {
    redirect_with('warning', 'That request was not found or has already been reviewed.', 'admin/approvals.php');
}

$update = $pdo->prepare(
    "UPDATE users
     SET account_status = 'rejected', reject_reason = ?, reviewed_by = ?, reviewed_at = NOW()
     WHERE id = ? AND role = 'user' AND account_status = 'pending'"
);
$update->execute([$reason, $admin['id'], $user_id]);

if ($update->rowCount() === 1) {
    flash_set('success', $target['full_name'] . ' has been rejected.');
} else {
    flash_set('warning', 'That request has already been reviewed.');
}
redirect('admin/approvals.php');
