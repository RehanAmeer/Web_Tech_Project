<?php
require_once __DIR__ . '/../includes/auth.php';
require_post();
require_role(['student', 'teacher']);
$id = request_id('resource_id');
$check = $pdo->prepare("SELECT id FROM resources WHERE id = ? AND status = 'published'");
$check->execute([$id]);
if (!$id || !$check->fetch()) {
    redirect_with('error', 'Resource not found.', 'resources.php');
}

$user_id = current_user()['id'];
$stmt = $pdo->prepare('SELECT 1 FROM bookmarks WHERE user_id = ? AND resource_id = ?');
$stmt->execute([$user_id, $id]);
if ($stmt->fetchColumn()) {
    $pdo->prepare('DELETE FROM bookmarks WHERE user_id = ? AND resource_id = ?')
        ->execute([$user_id, $id]);
    flash_set('success', 'Bookmark removed.');
} else {
    $pdo->prepare('INSERT INTO bookmarks (user_id, resource_id) VALUES (?, ?)')
        ->execute([$user_id, $id]);
    flash_set('success', 'Resource bookmarked.');
}
redirect('resource_details.php?id=' . $id);
