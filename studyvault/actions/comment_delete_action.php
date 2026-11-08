<?php
require_once __DIR__ . '/../includes/auth.php';
require_post();
require_role(['student', 'teacher', 'admin']);

$id = request_id('comment_id');
$user = current_user();
$pdo->beginTransaction();
$stmt = $pdo->prepare(
    'SELECT c.resource_id, c.user_id, r.user_id AS resource_owner_id
     FROM comments c
     JOIN resources r ON r.id = c.resource_id
     WHERE c.id = ? AND c.is_hidden = 0
     FOR UPDATE'
);
$stmt->execute([$id]);
$comment = $stmt->fetch();
if (!$comment) {
    $pdo->rollBack();
    redirect_with('error', 'Comment not found.', 'resources.php');
}

$can_delete = $user['role'] === 'admin'
    || (int) $comment['user_id'] === (int) $user['id']
    || (int) $comment['resource_owner_id'] === (int) $user['id'];
if (!$can_delete) {
    $pdo->rollBack();
    redirect_with('error', 'You do not have permission to delete this comment.', 'resource_details.php?id=' . (int) $comment['resource_id']);
}

$delete = $pdo->prepare('DELETE FROM comments WHERE id = ? AND is_hidden = 0');
$delete->execute([$id]);
if (!$delete->rowCount()) {
    $pdo->rollBack();
    redirect_with('warning', 'Comment was already deleted.', 'resource_details.php?id=' . (int) $comment['resource_id']);
}
$pdo->prepare('UPDATE resources SET comment_count = GREATEST(comment_count - 1, 0) WHERE id = ?')
    ->execute([$comment['resource_id']]);
$pdo->commit();

redirect_with('success', 'Comment deleted.', 'resource_details.php?id=' . (int) $comment['resource_id']);
