<?php
require_once __DIR__ . '/includes/auth.php';

http_response_code(404);
$page_title = 'Page not found';
$err_has_browse = is_file(__DIR__ . '/resources.php');
require __DIR__ . '/includes/header.php';
?>
<section class="error-page">
    <div class="error-code">404</div>
    <h1>Page not found</h1>
    <p class="muted">The page you are looking for does not exist or has been moved.</p>
    <div class="row">
        <a class="btn" href="<?= e(url()) ?>">Back to home</a>
        <?php if ($err_has_browse): ?>
            <a class="btn btn-secondary" href="<?= e(url('resources.php')) ?>">Browse resources</a>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
