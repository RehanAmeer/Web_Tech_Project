<?php
require_once __DIR__ . '/../../includes/auth.php';
require_post();
require_role(['admin']);

$id = request_id('user_id');
$stmt = $pdo->prepare(
    "UPDATE users
     SET account_status = 'approved'
     WHERE id = ? AND role <> 'admin' AND account_status = 'banned'"
);
$stmt->execute([$id]);

redirect_with(
    $stmt->rowCount() ? 'success' : 'warning',
    $stmt->rowCount() ? 'User unbanned.' : 'User not found or not banned.',
    'admin/users.php'
);
