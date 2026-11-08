<?php
require_once __DIR__ . '/../includes/auth.php';
require_post();
require_role(['student', 'teacher']);

$id = request_id('comment_id');
$body = str_replace(["\r\n", "\r"], "\n", post_str('body'));
$redirect = 'resources.php';

if ($body === '' || char_length($body) > MAX_COMMENT_LENGTH) {
    redirect_with('error', 'Please enter a comment of up to ' . MAX_COMMENT_LENGTH . ' characters.', $redirect);
}

$stmt = $pdo->prepare(
    'SELECT c.resource_id
     FROM comments c
     WHERE c.id = ? AND c.user_id = ? AND c.is_hidden = 0'
);
$stmt->execute([$id, current_user()['id']]);
$comment = $stmt->fetch();
if (!$comment) {
    redirect_with('error', 'Comment not found or you do not have permission.', $redirect);
}

$pdo->prepare('UPDATE comments SET body = ? WHERE id = ? AND user_id = ? AND is_hidden = 0')
    ->execute([$body, $id, current_user()['id']]);
redirect_with('success', 'Comment updated.', 'resource_details.php?id=' . (int) $comment['resource_id']);
