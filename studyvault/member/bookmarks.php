<?php
require_once __DIR__ . '/../includes/auth.php'; require_role(['student', 'teacher']);
$stmt = $pdo->prepare("SELECT r.id, r.title, r.file_type, r.file_size, r.avg_rating, r.rating_count, r.download_count, r.created_at, c.code AS course_code FROM bookmarks b JOIN resources r ON r.id = b.resource_id JOIN courses c ON c.id = r.course_id WHERE b.user_id = ? AND r.status = 'published' ORDER BY b.created_at DESC"); $stmt->execute([current_user()['id']]); $rows = $stmt->fetchAll();
$page_title = 'Bookmarks'; require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><div><h1>Bookmarks</h1><p class="muted">Resources you saved for later.</p></div></div>
<?php if (!$rows): ?><div class="empty-state">You have no bookmarks yet.</div><?php else: ?><ul class="resource-list"><?php foreach ($rows as $row): ?><li class="resource-row"><div class="file-tile"><?= e($row['file_type']) ?></div><div class="resource-body"><h3 class="resource-title"><a href="<?= e(url('resource_details.php?id=' . $row['id'])) ?>"><?= e($row['title']) ?></a></h3><p class="resource-meta"><?= e($row['course_code']) ?> &middot; <?= e(format_bytes($row['file_size'])) ?> &middot; <?= e(format_rating($row['avg_rating'], $row['rating_count'])) ?> &middot; <?= (int) $row['download_count'] ?> downloads</p></div></li><?php endforeach; ?></ul><?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
