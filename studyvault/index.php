<?php
require_once __DIR__ . '/includes/auth.php';

$page_title = 'Home';
$home_user = current_user();
$home_has = function ($file) {
    return is_file(__DIR__ . '/' . $file);
};
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <h1>Notes, slides and past papers, shared by your own institute</h1>
    <p>StudyVault is a place for students and teachers to find, share and rate study resources, organized by course and semester.</p>
    <div class="row">
        <?php if ($home_has('resources.php')): ?>
            <a class="btn" href="<?= e(url('resources.php')) ?>">Browse resources</a>
        <?php endif; ?>
        <?php if ($home_user): ?>
            <a class="btn btn-secondary" href="<?= e(url(home_path($home_user))) ?>">Go to my dashboard</a>
        <?php elseif ($home_has('register.php')): ?>
            <a class="btn btn-secondary" href="<?= e(url('register.php')) ?>">Create an account</a>
        <?php endif; ?>
    </div>
</section>

<div class="grid-3 mt-3">
    <div class="card">
        <h3>Find what you need</h3>
        <p>Search by course, semester or file type. PDF, Word, Excel, PowerPoint and image files are all supported.</p>
    </div>
    <div class="card">
        <h3>Share your own notes</h3>
        <p>Approved students and teachers can upload resources, rate them, bookmark favorites and leave comments.</p>
    </div>
    <div class="card">
        <h3>Kept trustworthy</h3>
        <p>Accounts are approved by an administrator, teachers get a verified badge, and resources reaching <?= (int) REPORT_THRESHOLD ?> reports are hidden for review.</p>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
