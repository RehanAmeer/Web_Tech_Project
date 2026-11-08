<?php
require_once __DIR__ . '/includes/auth.php';
$courses = $pdo->query("SELECT c.id, c.code, c.name, c.department, COUNT(r.id) AS resource_count FROM courses c LEFT JOIN resources r ON r.course_id = c.id AND r.status = 'published' GROUP BY c.id, c.code, c.name, c.department ORDER BY c.code")->fetchAll();
$page_title = 'Courses';
require __DIR__ . '/includes/header.php';
?>
<div class="page-header"><div><h1>Courses</h1><p class="muted">Browse published resources by course.</p></div></div>
<?php if (!$courses): ?><div class="empty-state">No courses have been added yet.</div><?php else: ?><div class="grid-3"><?php foreach ($courses as $course): ?><a class="card card-link" href="<?= e(url('resources.php?course_id=' . (int) $course['id'])) ?>"><h3><?= e($course['code']) ?></h3><p><?= e($course['name']) ?></p><p class="muted mb-0"><?= (int) $course['resource_count'] ?> resources<?= $course['department'] ? ' · ' . e($course['department']) : '' ?></p></a><?php endforeach; ?></div><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
