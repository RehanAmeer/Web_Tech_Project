<?php
require_once __DIR__ . '/../includes/auth.php';

require_role(['student', 'teacher']);

$user = current_user();
$uid = (int) $user['id'];

$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS uploads, COALESCE(SUM(download_count), 0) AS downloads_received
     FROM resources WHERE user_id = ? AND status <> 'removed'"
);
$stmt->execute([$uid]);
$mine = $stmt->fetch();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM bookmarks WHERE user_id = ?');
$stmt->execute([$uid]);
$bookmark_total = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM downloads WHERE user_id = ?');
$stmt->execute([$uid]);
$download_total = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT id, title, file_type, status, download_count, created_at
     FROM resources WHERE user_id = ? AND status <> 'removed'
     ORDER BY created_at DESC, id DESC LIMIT 5"
);
$stmt->execute([$uid]);
$recent = $stmt->fetchAll();

$page_title = 'Dashboard';
$first_name = explode(' ', $user['full_name'])[0];
require __DIR__ . '/../includes/header.php';
?>
<section class="hero">
    <div>
        <h1>Welcome back, <?= e($first_name) ?></h1>
        <p>Share knowledge, discover useful study material and keep your learning organised.</p>
        <div class="row">
        <?php if (page_exists('member/upload.php')): ?>
            <a class="btn" href="<?= e(url('member/upload.php')) ?>">Upload a resource</a>
        <?php endif; ?>
        <?php if (page_exists('resources.php')): ?>
            <a class="btn btn-secondary" href="<?= e(url('resources.php')) ?>">Browse</a>
        <?php endif; ?>
        </div>
    </div>
    <div class="quota"><span>Resources shared</span><b><?= (int) $mine['uploads'] ?></b><small>Keep sharing helpful material</small></div>
</section>

<?php if ((int) $user['strike_count'] > 0): ?>
    <div class="alert alert-warning" role="alert">
        You have <?= (int) $user['strike_count'] ?> of <?= (int) STRIKE_LIMIT ?> strikes. Reaching <?= (int) STRIKE_LIMIT ?> strikes suspends your account.
    </div>
<?php endif; ?>

<div class="stat-grid">
    <div class="card">
        <div class="stat-value"><?= (int) $mine['uploads'] ?></div>
        <div class="stat-label">My uploads</div>
    </div>
    <div class="card">
        <div class="stat-value"><?= (int) $mine['downloads_received'] ?></div>
        <div class="stat-label">Downloads of my files</div>
    </div>
    <div class="card">
        <div class="stat-value"><?= $bookmark_total ?></div>
        <div class="stat-label">Bookmarks</div>
    </div>
    <div class="card">
        <div class="stat-value"><?= $download_total ?></div>
        <div class="stat-label">Files I downloaded</div>
    </div>
</div>

<div class="row-between mb-2">
    <h2 class="mb-0">Recent uploads</h2>
    <?php if (page_exists('member/my_uploads.php')): ?>
        <a href="<?= e(url('member/my_uploads.php')) ?>">View all</a>
    <?php endif; ?>
</div>

<?php if (!$recent): ?>
    <div class="empty-state">You have not uploaded anything yet.</div>
<?php else: ?>
    <ul class="resource-list">
        <?php foreach ($recent as $row): ?>
            <li class="resource-row">
                <div class="file-tile"><?= e($row['file_type']) ?></div>
                <div class="resource-body">
                    <h3 class="resource-title">
                        <?php if (page_exists('resource_details.php')): ?>
                            <a href="<?= e(url('resource_details.php?id=' . (int) $row['id'])) ?>"><?= e($row['title']) ?></a>
                        <?php else: ?>
                            <?= e($row['title']) ?>
                        <?php endif; ?>
                    </h3>
                    <p class="resource-meta"><?= e(format_date($row['created_at'])) ?> &middot; <?= (int) $row['download_count'] ?> downloads</p>
                </div>
                <div class="resource-actions"><?= status_badge($row['status']) ?></div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
