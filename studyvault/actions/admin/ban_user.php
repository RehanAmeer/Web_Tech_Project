<?php
require_once __DIR__ . '/../../includes/auth.php';
require_post();
require_role(['admin']);

$id = request_id('user_id');
if (!$id || $id === (int) current_user()['id']) {
    redirect_with('error', 'Invalid user.', 'admin/users.php');
}

$pdo->beginTransaction();
$stmt = $pdo->prepare("UPDATE users SET account_status = 'banned' WHERE id = ? AND role <> 'admin'");
$stmt->execute([$id]);
if ($stmt->rowCount()) {
    $pdo->prepare("UPDATE resources SET status = 'removed' WHERE user_id = ?")
        ->execute([$id]);
}
$pdo->commit();
redirect_with($stmt->rowCount() ? 'success' : 'warning', $stmt->rowCount() ? 'User banned.' : 'User not found.', 'admin/users.php');
