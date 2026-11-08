<?php
require_once __DIR__ . '/../includes/auth.php';
require_post(); require_role(['student', 'teacher']);
$id = request_id('resource_id'); $score = post_str('score');
if (!$id || !ctype_digit($score) || (int) $score < 1 || (int) $score > 5) { redirect_with('error', 'Please choose a rating from 1 to 5.', 'resources.php'); }
$check = $pdo->prepare("SELECT id, user_id FROM resources WHERE id = ? AND status = 'published'"); $check->execute([$id]);
$resource = $check->fetch();
if (!$resource) { redirect_with('error', 'Resource not found.', 'resources.php'); }
if ((int) $resource['user_id'] === (int) current_user()['id']) { redirect_with('error', 'You cannot rate your own resource.', 'resource_details.php?id=' . $id); }
$pdo->beginTransaction();
$pdo->prepare('INSERT INTO ratings (resource_id, user_id, score) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE score = VALUES(score), created_at = NOW()')->execute([$id, current_user()['id'], (int) $score]);
$s = $pdo->prepare('SELECT COALESCE(AVG(score), 0), COUNT(*) FROM ratings WHERE resource_id = ?'); $s->execute([$id]); $rating = $s->fetch(PDO::FETCH_NUM);
$pdo->prepare('UPDATE resources SET avg_rating = ?, rating_count = ? WHERE id = ?')->execute([$rating[0], $rating[1], $id]); $pdo->commit();
redirect_with('success', 'Your rating has been saved.', 'resource_details.php?id=' . $id);
