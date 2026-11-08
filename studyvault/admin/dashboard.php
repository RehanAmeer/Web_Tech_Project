<?php
require_once __DIR__ . '/../includes/auth.php';

require_role(['admin']);

$stats = $pdo->query(
    "SELECT
        (SELECT COUNT(*) FROM users WHERE role = 'user' AND account_status = 'pending') AS pending_users,
        (SELECT COUNT(*) FROM users WHERE role IN ('student', 'teacher') AND account_status = 'approved') AS members,
        (SELECT COUNT(*) FROM users WHERE account_status = 'banned') AS banned_users,
        (SELECT COUNT(*) FROM resources WHERE status = 'published') AS published_resources,
        (SELECT COUNT(*) FROM resources WHERE status = 'under_review') AS review_resources,
        (SELECT COUNT(*) FROM reports WHERE status = 'open') AS open_reports"
)->fetch();

$page_title = 'Admin dashboard';

$tiles = [
    ['Pending approvals', $stats['pending_users'], 'admin/approvals.php'],
    ['Open reports', $stats['open_reports'], 'admin/reports.php'],
    ['Files under review', $stats['review_resources'], 'admin/resources.php'],
    ['Published files', $stats['published_resources'], 'admin/resources.php'],
    ['Approved members', $stats['members'], 'admin/users.php'],
    ['Banned accounts', $stats['banned_users'], 'admin/users.php'],
];

require __DIR__ . '/../includes/header.php';
?>
<div class="hero">
    <div>
        <h1>Admin dashboard</h1>
        <p class="muted">What needs attention and how the site is doing.</p>
    </div>
    <?php if (page_exists('admin/courses.php')): ?>
        <a class="btn btn-secondary" href="<?= e(url('admin/courses.php')) ?>">Manage courses</a>
    <?php endif; ?>
</div>

<div class="stat-grid">
    <?php foreach ($tiles as $tile): ?>
        <?php if (page_exists($tile[2])): ?>
            <a class="card card-link" href="<?= e(url($tile[2])) ?>">
        <?php else: ?>
            <div class="card">
        <?php endif; ?>
            <div class="stat-value"><?= (int) $tile[1] ?></div>
            <div class="stat-label"><?= e($tile[0]) ?></div>
        <?php if (page_exists($tile[2])): ?>
            </a>
        <?php else: ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
