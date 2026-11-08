<?php
require_once __DIR__ . '/includes/auth.php';

$id = request_id();
if (!$id) {
    redirect('resources.php');
}

$stmt = $pdo->prepare(
    'SELECT r.*, c.code AS course_code, c.name AS course_name, u.full_name AS uploader, u.role AS uploader_role
     FROM resources r
     JOIN courses c ON c.id = r.course_id
     JOIN users u ON u.id = r.user_id
     WHERE r.id = ?'
);
$stmt->execute([$id]);
$resource = $stmt->fetch();
if (!$resource || ($resource['status'] !== 'published' && !is_owner_or_admin($resource['user_id']))) {
    deny_access();
}

$tags_stmt = $pdo->prepare(
    'SELECT t.name FROM tags t JOIN resource_tags rt ON rt.tag_id = t.id
     WHERE rt.resource_id = ? ORDER BY t.name'
);
$tags_stmt->execute([$id]);
$tags = $tags_stmt->fetchAll(PDO::FETCH_COLUMN);

$comments_stmt = $pdo->prepare(
    'SELECT c.id, c.user_id, c.body, c.created_at, u.full_name
     FROM comments c JOIN users u ON u.id = c.user_id
     WHERE c.resource_id = ? AND c.is_hidden = 0 ORDER BY c.created_at DESC'
);
$comments_stmt->execute([$id]);
$comments = $comments_stmt->fetchAll();

$user = current_user();
$can_interact = has_role(['student', 'teacher']);
$is_owner = (int) $resource['user_id'] === (int) ($user['id'] ?? 0);
$has_reported_resource = false;
$reported_comment_ids = [];
if ($can_interact) {
    $reported = $pdo->prepare('SELECT 1 FROM reports WHERE resource_id = ? AND reporter_id = ?');
    $reported->execute([$id, $user['id']]);
    $has_reported_resource = (bool) $reported->fetchColumn();
    if ($comments) {
        $comment_ids = array_column($comments, 'id');
        $placeholders = implode(',', array_fill(0, count($comment_ids), '?'));
        $reported_comments = $pdo->prepare(
            "SELECT comment_id FROM reports WHERE reporter_id = ? AND comment_id IN ($placeholders)"
        );
        $reported_comments->execute(array_merge([$user['id']], $comment_ids));
        $reported_comment_ids = array_map('intval', $reported_comments->fetchAll(PDO::FETCH_COLUMN));
    }
}
$bookmarked = false;
$my_rating = 0;
if ($can_interact) {
    $s = $pdo->prepare('SELECT 1 FROM bookmarks WHERE user_id = ? AND resource_id = ?');
    $s->execute([$user['id'], $id]);
    $bookmarked = (bool) $s->fetchColumn();
    $s = $pdo->prepare('SELECT score FROM ratings WHERE user_id = ? AND resource_id = ?');
    $s->execute([$user['id'], $id]);
    $my_rating = (int) $s->fetchColumn();
}

$page_title = $resource['title'];
require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div>
        <p class="muted"><a href="<?= e(url('resources.php')) ?>">Resources</a> / <?= e($resource['course_code']) ?></p>
        <h1><?= e($resource['title']) ?></h1>
        <p class="muted"><?= e($resource['course_name']) ?> &middot; Semester <?= (int) $resource['semester'] ?> &middot; Uploaded by <?= e($resource['uploader']) ?><?= $resource['uploader_role'] === 'teacher' ? ' ' . badge('✓ Verified teacher', 'success') : '' ?></p>
    </div>
</div>
<div class="card">
    <p><?= nl2br(e($resource['description'] ?? 'No description provided.')) ?></p>
    <?php if (in_array($resource['file_type'], PREVIEW_TYPES, true)): ?>
        <?php if ($resource['file_type'] === 'pdf'): ?>
            <iframe src="<?= e(url('actions/preview.php?id=' . $id)) ?>" title="Preview of <?= e($resource['title']) ?>" class="resource-preview" loading="lazy"></iframe>
        <?php else: ?>
            <img src="<?= e(url('actions/preview.php?id=' . $id)) ?>" alt="Preview of <?= e($resource['title']) ?>" class="resource-image">
        <?php endif; ?>
    <?php else: ?>
        <p class="muted">Preview is not available for this file type. Download it to view.</p>
    <?php endif; ?>
    <dl class="meta-list">
        <dt>File</dt><dd><?= e(FILE_TYPE_LABELS[$resource['file_type']] ?? $resource['file_type']) ?> (<?= e(format_bytes($resource['file_size'])) ?>)</dd>
        <dt>Instructor</dt><dd><?= e($resource['instructor_name'] ?: 'Not specified') ?></dd>
        <dt>Rating</dt><dd><?= e(format_rating($resource['avg_rating'], $resource['rating_count'])) ?></dd>
        <dt>Downloads</dt><dd><?= (int) $resource['download_count'] ?></dd>
    </dl>
    <?php if ($tags): ?><p><?php foreach ($tags as $tag): ?><a href="<?= e(url('resources.php?q=' . rawurlencode($tag))) ?>"><?= badge($tag, 'neutral') ?></a> <?php endforeach; ?></p><?php endif; ?>
    <div class="row mt-2">
        <?php if ($resource['status'] === 'published'): ?><?php if ($user): ?><a class="btn" href="<?= e(url('actions/download.php?id=' . $id)) ?>">Download</a><?php else: ?><a class="btn" href="<?= e(url('login.php')) ?>">Log in to download</a><?php endif; ?><?php endif; ?>
        <?php if ($can_interact && !$is_owner): ?>
            <form method="post" action="<?= e(url('actions/bookmark_action.php')) ?>"><input type="hidden" name="resource_id" value="<?= $id ?>"><?= csrf_field() ?><button class="btn btn-secondary" type="submit"><?= $bookmarked ? 'Remove bookmark' : 'Bookmark' ?></button></form>
        <?php endif; ?>
        <?php if (is_owner_or_admin($resource['user_id']) && page_exists('member/edit_resource.php')): ?><a class="btn btn-secondary" href="<?= e(url('member/edit_resource.php?id=' . $id)) ?>">Edit</a><?php endif; ?>
    </div>
</div>
<?php if ($can_interact && (int) $resource['user_id'] !== (int) $user['id']): ?>
<div class="card mt-2">
    <h2>Rate this resource</h2>
    <form method="post" action="<?= e(url('actions/rating_action.php')) ?>" class="row"><?= csrf_field() ?><input type="hidden" name="resource_id" value="<?= $id ?>"><select name="score" aria-label="Rating"><?php for ($score = 1; $score <= 5; $score++): ?><option value="<?= $score ?>"<?= $my_rating === $score ? ' selected' : '' ?>><?= $score ?> / 5</option><?php endfor; ?></select><button class="btn btn-sm" type="submit">Save rating</button></form>
</div>
<?php endif; ?>
<?php if ($can_interact && !$is_owner && $resource['uploader_role'] !== 'teacher' && $resource['status'] === 'published'): ?>
<div class="card mt-2">
    <h2>Report this resource</h2>
    <?php if ($has_reported_resource): ?>
        <p class="muted">You have reported this resource.</p>
    <?php else: ?>
    <form method="post" action="<?= e(url('actions/report_action.php')) ?>">
        <?= csrf_field() ?><input type="hidden" name="resource_id" value="<?= $id ?>">
        <div class="form-row"><div class="form-group"><label for="resource-report-reason">Reason</label><select id="resource-report-reason" name="reason"><?php foreach (REPORT_REASONS as $reason => $label): ?><option value="<?= e($reason) ?>"><?= e($label) ?></option><?php endforeach; ?></select></div><div class="form-group"><label for="resource-report-details">Details (optional)</label><input id="resource-report-details" name="details" maxlength="<?= MAX_REASON_LENGTH ?>"></div></div>
        <button class="btn btn-sm btn-danger" type="submit">Submit report</button>
    </form>
    <?php endif; ?>
</div>
<?php endif; ?>
<div class="card mt-2">
    <h2>Comments (<?= count($comments) ?>)</h2>
    <?php if ($can_interact && $resource['status'] === 'published'): ?><form method="post" action="<?= e(url('actions/comment_action.php')) ?>"><input type="hidden" name="resource_id" value="<?= $id ?>"><?= csrf_field() ?><div class="form-group"><label for="body">Add a comment</label><textarea id="body" name="body" maxlength="<?= MAX_COMMENT_LENGTH ?>" required></textarea></div><button class="btn btn-sm" type="submit">Post comment</button></form><?php endif; ?>
    <?php foreach ($comments as $comment): ?>
        <?php $is_comment_owner = $user && (int) $comment['user_id'] === (int) $user['id']; ?>
        <?php $can_delete_comment = $is_comment_owner || ($user && (int) $resource['user_id'] === (int) $user['id']) || ($user && $user['role'] === 'admin'); ?>
        <article class="comment">
            <div class="comment-meta"><?= e($comment['full_name']) ?> &middot; <?= e(format_datetime($comment['created_at'])) ?></div>
            <?php if ($is_comment_owner): ?>
                <form method="post" action="<?= e(url('actions/comment_edit_action.php')) ?>">
                    <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>"><?= csrf_field() ?>
                    <textarea name="body" maxlength="<?= MAX_COMMENT_LENGTH ?>" required><?= e($comment['body']) ?></textarea>
                    <button class="btn btn-sm" type="submit">Save edit</button>
                </form>
            <?php else: ?>
                <p><?= nl2br(e($comment['body'])) ?></p>
            <?php endif; ?>
            <div class="row">
                <?php if ($can_delete_comment): ?>
                    <form method="post" action="<?= e(url('actions/comment_delete_action.php')) ?>" class="inline-form" data-confirm="Delete this comment?">
                        <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>"><?= csrf_field() ?>
                        <button class="btn-link" type="submit">Delete</button>
                    </form>
                <?php endif; ?>
                <?php if ($can_interact && !$is_comment_owner): ?>
                    <?php if (in_array((int) $comment['id'], $reported_comment_ids, true)): ?>
                        <span class="muted">You have reported this comment.</span>
                    <?php else: ?>
                        <form method="post" action="<?= e(url('actions/report_action.php')) ?>" class="inline-form">
                            <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>"><?= csrf_field() ?>
                            <select name="reason" aria-label="Report reason"><?php foreach (REPORT_REASONS as $reason => $label): ?><option value="<?= e($reason) ?>"><?= e($label) ?></option><?php endforeach; ?></select>
                            <button class="btn-link" type="submit">Report</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
