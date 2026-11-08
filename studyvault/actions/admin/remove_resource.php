<?php
require_once __DIR__ . '/../../includes/auth.php';
require_post();
require_role(['admin']);

$id = request_id('resource_id');
$stmt = $pdo->prepare("UPDATE resources SET status = 'removed' WHERE id = ? AND status <> 'removed'");
$stmt->execute([$id]);

redirect_with(
    $stmt->rowCount() ? 'success' : 'warning',
    $stmt->rowCount() ? 'Resource removed.' : 'Resource not found.',
    'admin/resources.php'
);
