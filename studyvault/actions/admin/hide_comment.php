<?php
require_once __DIR__ . '/../../includes/auth.php';
require_post();
require_role(['admin']);

$id = request_id('comment_id');
$stmt = $pdo->prepare('SELECT resource_id FROM comments WHERE id = ? AND is_hidden = 0');
$stmt->execute([$id]);
$comment = $stmt->fetch();
if (!$comment) {
    redirect_with('warning', 'Comment not found.', 'admin/reports.php');
}

$pdo->prepare('UPDATE comments SET is_hidden = 1 WHERE id = ?')->execute([$id]);
$pdo->prepare('UPDATE resources SET comment_count = GREATEST(comment_count - 1, 0) WHERE id = ?')
    ->execute([$comment['resource_id']]);

redirect_with('success', 'Comment hidden.', 'admin/reports.php');
