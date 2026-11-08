<?php
require_once __DIR__ . '/../includes/auth.php';

require_post();
require_role(['user']);

$user = current_user();

if ($user['account_status'] !== 'rejected') {
    redirect_with('warning', 'Only rejected requests can be re-submitted.', 'user/dashboard.php');
}

$requested_role = post_str('requested_role');
$roll_no = strtoupper(post_str('roll_no'));
$errors = [];

if (!in_array($requested_role, ['student', 'teacher'], true)) {
    $errors[] = 'Please choose whether you are applying as a student or a teacher.';
}

if ($roll_no === '') {
    if ($requested_role === 'student') {
        $errors[] = 'Roll number is required for students.';
    }
} elseif (strlen($roll_no) > MAX_ROLL_LENGTH || !preg_match('/^[A-Z0-9][A-Z0-9\/-]*$/', $roll_no)) {
    $errors[] = 'Roll number can contain only letters, numbers, hyphens and slashes (up to ' . MAX_ROLL_LENGTH . ' characters).';
}

$roll_value = $roll_no === '' ? null : $roll_no;

if (!$errors && $roll_value !== null) {
    $check = $pdo->prepare('SELECT id FROM users WHERE roll_no = ? AND id <> ?');
    $check->execute([$roll_value, $user['id']]);
    if ($check->fetch()) {
        $errors[] = 'Another account already uses this roll number.';
    }
}

if ($errors) {
    foreach ($errors as $message) {
        flash_set('error', $message);
    }
    redirect('user/dashboard.php');
}

try {
    $update = $pdo->prepare(
        "UPDATE users
         SET requested_role = ?, roll_no = ?, account_status = 'pending',
             reject_reason = NULL, reviewed_by = NULL, reviewed_at = NULL
         WHERE id = ? AND role = 'user' AND account_status = 'rejected'"
    );
    $update->execute([$requested_role, $roll_value, $user['id']]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        redirect_with('error', 'Another account already uses this roll number.', 'user/dashboard.php');
    }
    throw $e;
}

if ($update->rowCount() === 1) {
    flash_set('success', 'Your request has been submitted again and is waiting for review.');
} else {
    flash_set('warning', 'Your request could not be re-submitted. Please try again.');
}
redirect('user/dashboard.php');
