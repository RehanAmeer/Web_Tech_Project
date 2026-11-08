<?php
require_once __DIR__ . '/includes/auth.php';

require_guest();

$page_title = 'Create an account';
$old_role = old('requested_role', 'student');
require __DIR__ . '/includes/header.php';
?>
<div class="card auth-card">
    <h1>Create an account</h1>
    <p class="muted">An administrator reviews every new account before it can upload or rate resources.</p>

    <form method="post" action="<?= e(url('actions/register_action.php')) ?>">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="full_name">Full name</label>
            <input type="text" id="full_name" name="full_name" value="<?= old('full_name') ?>" maxlength="<?= (int) MAX_NAME_LENGTH ?>" autocomplete="name" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= old('email') ?>" maxlength="150" autocomplete="email" required>
        </div>

        <div class="form-group">
            <label for="requested_role">I am a</label>
            <select id="requested_role" name="requested_role" required>
                <option value="student"<?= $old_role === 'teacher' ? '' : ' selected' ?>>Student</option>
                <option value="teacher"<?= $old_role === 'teacher' ? ' selected' : '' ?>>Teacher</option>
            </select>
        </div>

        <div class="form-group">
            <label for="roll_no">Roll number</label>
            <input type="text" id="roll_no" name="roll_no" value="<?= old('roll_no') ?>" maxlength="<?= (int) MAX_ROLL_LENGTH ?>" autocomplete="off">
            <p class="form-hint">Required for students. Teachers can leave it empty.</p>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" minlength="<?= (int) MIN_PASSWORD_LENGTH ?>" maxlength="<?= (int) MAX_PASSWORD_LENGTH ?>" autocomplete="new-password" required>
            <p class="form-hint">At least <?= (int) MIN_PASSWORD_LENGTH ?> characters.</p>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm password</label>
            <input type="password" id="confirm_password" name="confirm_password" minlength="<?= (int) MIN_PASSWORD_LENGTH ?>" maxlength="<?= (int) MAX_PASSWORD_LENGTH ?>" autocomplete="new-password" required>
        </div>

        <button type="submit" class="btn btn-block">Create account</button>
    </form>

    <p class="mt-2 mb-0 small muted">Already registered? <a href="<?= e(url('login.php')) ?>">Log in</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
