<?php
require_once __DIR__ . '/../includes/auth.php';

require_role(['admin']);

$total = (int) $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn();
$pager = paginate($total);

$stmt = $pdo->prepare(
    'SELECT c.id, c.code, c.name, c.department, COUNT(r.id) AS resource_count
     FROM courses c
     LEFT JOIN resources r ON r.course_id = c.id
     GROUP BY c.id, c.code, c.name, c.department
     ORDER BY c.code ASC
     LIMIT ? OFFSET ?'
);
$stmt->bindValue(1, $pager['per_page'], PDO::PARAM_INT);
$stmt->bindValue(2, $pager['offset'], PDO::PARAM_INT);
$stmt->execute();
$courses = $stmt->fetchAll();

$page_title = 'Courses';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1>Courses</h1>
        <p class="muted"><?= $total ?> <?= $total === 1 ? 'course' : 'courses' ?>. Members pick one of these when they upload a resource.</p>
    </div>
</div>

<div class="card mb-2">
    <h2>Add a course</h2>
    <form method="post" action="<?= e(url('actions/admin/add_course.php')) ?>">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label for="code">Course code</label>
                <input type="text" id="code" name="code" value="<?= old('code') ?>" maxlength="20" placeholder="CS-301" autocomplete="off" required>
            </div>
            <div class="form-group">
                <label for="name">Course name</label>
                <input type="text" id="name" name="name" value="<?= old('name') ?>" maxlength="150" placeholder="Operating Systems" autocomplete="off" required>
            </div>
        </div>
        <div class="form-group">
            <label for="department">Department <span class="muted">(optional)</span></label>
            <input type="text" id="department" name="department" value="<?= old('department') ?>" maxlength="100" placeholder="Computer Science" autocomplete="off">
        </div>
        <button type="submit" class="btn">Add course</button>
    </form>
</div>

<?php if (!$courses): ?>
    <div class="empty-state">No courses yet. Add the first one above.</div>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Resources</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><strong><?= e($course['code']) ?></strong></td>
                        <td><?= e($course['name']) ?></td>
                        <td><?= $course['department'] !== null ? e($course['department']) : '<span class="muted">None</span>' ?></td>
                        <td><?= (int) $course['resource_count'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= render_pagination($pager) ?>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
