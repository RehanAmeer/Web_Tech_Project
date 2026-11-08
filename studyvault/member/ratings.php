<?php
require_once __DIR__ . '/../includes/auth.php'; require_role(['student', 'teacher']);
$stmt = $pdo->prepare('SELECT r.id, r.title, rt.score, rt.created_at, c.code AS course_code FROM ratings rt JOIN resources r ON r.id = rt.resource_id JOIN courses c ON c.id = r.course_id WHERE rt.user_id = ? ORDER BY rt.created_at DESC'); $stmt->execute([current_user()['id']]); $rows = $stmt->fetchAll();
$page_title = 'My ratings'; require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><div><h1>My ratings</h1><p class="muted">Your ratings across StudyVault.</p></div></div>
<?php if (!$rows): ?><div class="empty-state">You have not rated any resources yet.</div><?php else: ?><div class="table-wrap"><table class="table"><thead><tr><th>Resource</th><th>Course</th><th>Rating</th><th>Rated</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><a href="<?= e(url('resource_details.php?id=' . $row['id'])) ?>"><?= e($row['title']) ?></a></td><td><?= e($row['course_code']) ?></td><td><?= (int) $row['score'] ?> / 5</td><td><?= e(format_date($row['created_at'])) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
