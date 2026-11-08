<?php
require_once __DIR__ . '/../includes/auth.php';

require_role(['student', 'teacher']);

$user = current_user();
$uid = (int) $user['id'];

$stmt = $pdo->prepare('SELECT COUNT(*) FROM resources WHERE user_id = ?');
$stmt->execute([$uid]);
$total = (int) $stmt->fetchColumn();
$pager = paginate($total);

$stmt = $pdo->prepare(
    'SELECT r.id, r.title, r.semester, r.file_type, r.file_ext, r.file_size, r.status,
            r.avg_rating, r.rating_count, r.download_count, r.comment_count, r.created_at,
            c.code AS course_code
     FROM resources r
     JOIN courses c ON c.id = r.course_id
     WHERE r.user_id = ?
     ORDER BY r.created_at DESC, r.id DESC
     LIMIT ? OFFSET ?'
);
$stmt->bindValue(1, $uid, PDO::PARAM_INT);
$stmt->bindValue(2, $pager['per_page'], PDO::PARAM_INT);
$stmt->bindValue(3, $pager['offset'], PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$can_view = page_exists('resource_details.php');
$can_edit = page_exists('member/edit_resource.php');
$can_delete = page_exists('actions/delete_resource.php');

$page_title = 'My uploads';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1>My uploads</h1>
        <p class="muted"><?= $total ?> <?= $total === 1 ? 'resource' : 'resources' ?> uploaded.</p>
    </div>
    <?php if (page_exists('member/upload.php')): ?>
        <a class="btn" href="<?= e(url('member/upload.php')) ?>">Upload a resource</a>
    <?php endif; ?>
</div>

<?php if (!$rows): ?>
    <div class="empty-state">You have not uploaded anything yet.</div>
<?php else: ?>
    <ul class="resource-list">
        <?php foreach ($rows as $row): ?>
            <li class="resource-row">
                <div class="file-tile"><?= e($row['file_ext']) ?></div>
                <div class="resource-body">
                    <h3 class="resource-title">
                        <?php if ($can_view): ?>
                            <a href="<?= e(url('resource_details.php?id=' . (int) $row['id'])) ?>"><?= e($row['title']) ?></a>
                        <?php else: ?>
                            <?= e($row['title']) ?>
                        <?php endif; ?>
                    </h3>
                    <p class="resource-meta">
                        <?= e($row['course_code']) ?> &middot; Semester <?= (int) $row['semester'] ?> &middot;
                        <?= e(FILE_TYPE_LABELS[$row['file_type']] ?? $row['file_type']) ?>, <?= e(format_bytes($row['file_size'])) ?> &middot;
                        <?= e(format_date($row['created_at'])) ?>
                    </p>
                    <p class="resource-meta mb-0">
                        <?= status_badge($row['status']) ?>
                        <span class="rating"><?= e(format_rating($row['avg_rating'], $row['rating_count'])) ?></span> &middot;
                        <?= (int) $row['download_count'] ?> downloads &middot; <?= (int) $row['comment_count'] ?> comments
                    </p>
                </div>
                <div class="resource-actions">
                    <?php if ($can_edit): ?>
                        <a class="btn btn-secondary btn-sm" href="<?= e(url('member/edit_resource.php?id=' . (int) $row['id'])) ?>">Edit</a>
                    <?php endif; ?>
                    <?php if ($can_delete): ?>
                        <form method="post" action="<?= e(url('actions/delete_resource.php')) ?>" class="inline-form" data-confirm="Delete this resource and its file? This cannot be undone.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="resource_id" value="<?= (int) $row['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
    <?= render_pagination($pager) ?>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
