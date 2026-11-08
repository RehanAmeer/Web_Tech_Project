<?php
require_once __DIR__ . '/../includes/auth.php';
require_post();
require_role(['student', 'teacher']);
$id = request_id('resource_id');
$body = str_replace(["\r\n", "\r"], "\n", post_str('body'));
if (!$id || $body === '' || char_length($body) > MAX_COMMENT_LENGTH) {
    redirect_with('error', 'Please enter a comment of up to ' . MAX_COMMENT_LENGTH . ' characters.', 'resource_details.php?id=' . $id);
}
$check = $pdo->prepare("SELECT id FROM resources WHERE id = ? AND status = 'published'");
$check->execute([$id]);
if (!$check->fetch()) {
    redirect_with('error', 'Resource not found.', 'resources.php');
}
$pdo->beginTransaction();
$pdo->prepare('INSERT INTO comments (resource_id, user_id, body) VALUES (?, ?, ?)')
    ->execute([$id, current_user()['id'], $body]);
$pdo->prepare('UPDATE resources SET comment_count = comment_count + 1 WHERE id = ?')
    ->execute([$id]);
$pdo->commit();
redirect_with('success', 'Comment posted.', 'resource_details.php?id=' . $id);
