<?php
require_once __DIR__ . '/../includes/auth.php';
require_post();
require_role(['student', 'teacher', 'admin']);

$id = request_id('resource_id');
$stmt = $pdo->prepare('SELECT user_id, file_path FROM resources WHERE id = ?');
$stmt->execute([$id]);
$resource = $stmt->fetch();
if (!$resource || !is_owner_or_admin($resource['user_id'])) {
    redirect_with('error', 'Resource not found or you do not have permission.', 'member/my_uploads.php');
}

$pdo->beginTransaction();
$pdo->prepare('DELETE FROM resources WHERE id = ?')->execute([$id]);
$pdo->commit();

delete_stored_file($resource['file_path']);
redirect_with('success', 'Resource deleted.', has_role(['admin']) ? 'admin/resources.php' : 'member/my_uploads.php');
