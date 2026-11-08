<?php
require_once __DIR__ . '/../../includes/auth.php';
require_post();
require_role(['admin']);

$id = request_id('report_id');
$decision = post_str('decision');
if (!in_array($decision, ['upheld', 'dismissed'], true)) {
    redirect_with('error', 'Invalid report decision.', 'admin/reports.php');
}

$pdo->beginTransaction();
$stmt = $pdo->prepare(
    'SELECT p.resource_id, p.comment_id, r.status AS resource_status, r.user_id
     FROM reports p
     LEFT JOIN resources r ON r.id = p.resource_id
     WHERE p.id = ? AND p.status = \'open\'
     FOR UPDATE'
);
$stmt->execute([$id]);
$report = $stmt->fetch();
if (!$report) {
    $pdo->rollBack();
    redirect_with('warning', 'Report not found or already resolved.', 'admin/reports.php');
}

$pdo->prepare(
    'UPDATE reports SET status = ?, reviewed_by = ?, reviewed_at = NOW()
     WHERE id = ? AND status = \'open\''
)->execute([$decision, current_user()['id'], $id]);

if ($report['resource_id']) {
    if ($decision === 'upheld') {
        $upheld = $pdo->prepare(
            "SELECT COUNT(*) FROM reports
             WHERE resource_id = ? AND status = 'upheld'"
        );
        $upheld->execute([$report['resource_id']]);
        if ((int) $upheld->fetchColumn() === 1) {
            $pdo->prepare('UPDATE users SET strike_count = strike_count + 1 WHERE id = ?')
                ->execute([$report['user_id']]);
            $strike = $pdo->prepare('SELECT strike_count FROM users WHERE id = ? FOR UPDATE');
            $strike->execute([$report['user_id']]);
            if ((int) $strike->fetchColumn() >= STRIKE_LIMIT) {
                $pdo->prepare("UPDATE users SET account_status = 'banned' WHERE id = ?")
                    ->execute([$report['user_id']]);
                $pdo->prepare("UPDATE resources SET status = 'removed' WHERE user_id = ?")
                    ->execute([$report['user_id']]);
            }
        }
        $pdo->prepare("UPDATE resources SET status = 'removed' WHERE id = ?")
            ->execute([$report['resource_id']]);
    } elseif ($report['resource_status'] === 'under_review') {
        $open = $pdo->prepare(
            "SELECT COUNT(*) FROM reports
             WHERE resource_id = ? AND status = 'open'"
        );
        $open->execute([$report['resource_id']]);
        if ((int) $open->fetchColumn() === 0) {
            $pdo->prepare("UPDATE resources SET status = 'published' WHERE id = ? AND status = 'under_review'")
                ->execute([$report['resource_id']]);
        }
    }
}

if ($decision === 'upheld' && $report['comment_id']) {
    $comment = $pdo->prepare('SELECT resource_id FROM comments WHERE id = ? AND is_hidden = 0');
    $comment->execute([$report['comment_id']]);
    $comment_resource_id = $comment->fetchColumn();
    if ($comment_resource_id) {
        $pdo->prepare('UPDATE comments SET is_hidden = 1 WHERE id = ?')->execute([$report['comment_id']]);
        $pdo->prepare('UPDATE resources SET comment_count = GREATEST(comment_count - 1, 0) WHERE id = ?')
            ->execute([$comment_resource_id]);
    }
}

$pdo->commit();
redirect_with('success', 'Report ' . ($decision === 'upheld' ? 'upheld.' : 'dismissed.'), 'admin/reports.php');
