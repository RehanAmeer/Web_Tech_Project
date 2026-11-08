<?php
require_once __DIR__ . '/../includes/auth.php';
require_post(); require_role(['student', 'teacher']);
$resource_id = request_id('resource_id'); $comment_id = request_id('comment_id'); $reason = post_str('reason'); $details = preg_replace('/\s+/u', ' ', post_str('details'));
if (($resource_id && $comment_id) || (!$resource_id && !$comment_id) || !isset(REPORT_REASONS[$reason])) { redirect_with('error', 'Invalid report.', 'resources.php'); }
if (char_length($details) > MAX_REASON_LENGTH) { redirect_with('error', 'Report details are too long.', $resource_id ? 'resource_details.php?id=' . $resource_id : 'resources.php'); }
if ($resource_id) {
    $s = $pdo->prepare(
        "SELECT r.user_id AS owner_id, u.role AS owner_role
         FROM resources r
         JOIN users u ON u.id = r.user_id
         WHERE r.id = ? AND r.status = 'published'"
    );
    $s->execute([$resource_id]);
} else {
    $s = $pdo->prepare(
        'SELECT user_id AS owner_id, NULL AS owner_role
         FROM comments
         WHERE id = ? AND is_hidden = 0'
    );
    $s->execute([$comment_id]);
}
$target = $s->fetch();
if (!$target) {
    redirect_with('error', 'The reported item was not found.', 'resources.php');
}
if ((int) $target['owner_id'] === (int) current_user()['id'] || ($resource_id && $target['owner_role'] === 'teacher')) {
    redirect_with('error', 'You cannot report this item.', $resource_id ? 'resource_details.php?id=' . $resource_id : 'resources.php');
}
try {
    $pdo->prepare('INSERT INTO reports (resource_id, comment_id, reporter_id, reason, details) VALUES (?, ?, ?, ?, ?)')->execute([$resource_id ?: null, $comment_id ?: null, current_user()['id'], $reason, $details ?: null]);
    if ($resource_id) {
        $pdo->prepare(
            "UPDATE resources
             SET report_count = report_count + 1,
                 status = CASE WHEN report_count + 1 >= ? AND status = 'published' THEN 'under_review' ELSE status END
             WHERE id = ?"
        )->execute([REPORT_THRESHOLD, $resource_id]);
    }
    redirect_with('success', 'Thank you. Your report has been submitted for review.', $resource_id ? 'resource_details.php?id=' . $resource_id : 'resources.php');
} catch (PDOException $e) { if ($e->getCode() === '23000') { redirect_with('warning', 'You have already reported this item.', $resource_id ? 'resource_details.php?id=' . $resource_id : 'resources.php'); } throw $e; }
