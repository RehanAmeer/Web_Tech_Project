<?php
require_once __DIR__ . '/includes/auth.php';

require_guest();

$page_title = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<div class="card auth-card">
    <h1>Log in</h1>

    <form method="post" action="<?= e(url('actions/login_action.php')) ?>">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= old('email') ?>" maxlength="150" autocomplete="email" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>

        <button type="submit" class="btn btn-block">Log in</button>
    </form>

    <?php if (page_exists('register.php')): ?>
        <p class="mt-2 mb-0 small muted">New here? <a href="<?= e(url('register.php')) ?>">Create an account</a></p>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
