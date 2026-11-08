<?php
require_once __DIR__ . '/includes/auth.php';

http_response_code(403);
$page_title = 'Access denied';
$err_user = current_user();
$err_has_login = is_file(__DIR__ . '/login.php');
require __DIR__ . '/includes/header.php';
?>
<section class="error-page">
    <div class="error-code">403</div>
    <h1>Access denied</h1>
    <p class="muted">You do not have permission to view this page.</p>
    <div class="row">
        <?php if ($err_user): ?>
            <a class="btn" href="<?= e(url(home_path($err_user))) ?>">Go to my dashboard</a>
        <?php elseif ($err_has_login): ?>
            <a class="btn" href="<?= e(url('login.php')) ?>">Log in</a>
        <?php endif; ?>
        <a class="btn btn-secondary" href="<?= e(url()) ?>">Back to home</a>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
