<?php
require_once __DIR__ . '/../includes/auth.php';

require_login();

if (current_user()['role'] !== 'user') {
    redirect(home_path());
}

$user = current_user();
$page_title = 'My account';
$first_name = explode(' ', $user['full_name'])[0];
$role_label = $user['requested_role'] === 'teacher' ? 'teacher' : 'student';
require __DIR__ . '/../includes/header.php';
?>
<div class="hero">
    <div>
        <h1>Welcome, <?= e($first_name) ?></h1>
        <p class="muted">Your account request and its current status.</p>
    </div>
    <?php if (page_exists('resources.php')): ?>
        <a class="btn btn-secondary" href="<?= e(url('resources.php')) ?>">Browse resources</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="row">
        <h2 class="mb-0">Request status</h2>
        <?= status_badge($user['account_status']) ?>
    </div>

    <dl class="meta-list mt-2">
        <dt>Applying as</dt>
        <dd><?= e(ucfirst($role_label)) ?></dd>
        <dt>Roll number</dt>
        <dd><?= $user['roll_no'] !== null ? e($user['roll_no']) : '<span class="muted">Not provided</span>' ?></dd>
        <dt>Submitted</dt>
        <dd><?= e(format_datetime($user['created_at'])) ?></dd>
    </dl>

    <?php if ($user['account_status'] === 'pending'): ?>
        <p class="mt-2 mb-0">An administrator is reviewing your request to join as a <?= e($role_label) ?>. Once it is approved you can upload, rate and bookmark resources. After approval, reload this page and you will be taken to your dashboard.</p>
    <?php elseif ($user['account_status'] === 'rejected'): ?>
        <div class="alert alert-error mt-2" role="alert">
            <strong>Your request was not approved.</strong>
            <?php if ($user['reject_reason'] !== null && $user['reject_reason'] !== ''): ?>
                <br>Reason: <?= e($user['reject_reason']) ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($user['account_status'] === 'rejected' && page_exists('actions/reapply_action.php')): ?>
    <div class="card mt-2">
        <h2>Apply again</h2>
        <p class="muted">Correct your details and submit the request again for review.</p>
        <form method="post" action="<?= e(url('actions/reapply_action.php')) ?>">
            <?= csrf_field() ?>
            <div class="form-row">
                <div class="form-group">
                    <label for="requested_role">I am a</label>
                    <select id="requested_role" name="requested_role" required>
                        <option value="student"<?= $user['requested_role'] === 'teacher' ? '' : ' selected' ?>>Student</option>
                        <option value="teacher"<?= $user['requested_role'] === 'teacher' ? ' selected' : '' ?>>Teacher</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="roll_no">Roll number</label>
                    <input type="text" id="roll_no" name="roll_no" value="<?= e($user['roll_no'] ?? '') ?>" maxlength="<?= (int) MAX_ROLL_LENGTH ?>" autocomplete="off">
                    <p class="form-hint">Required for students.</p>
                </div>
            </div>
            <button type="submit" class="btn">Submit request again</button>
        </form>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
