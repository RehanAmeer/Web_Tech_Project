<?php
require_once __DIR__ . '/../includes/auth.php';

require_post();

if (current_user()) {
    redirect(home_path());
}

$full_name = preg_replace('/\s+/u', ' ', post_str('full_name'));
$email = strtolower(post_str('email'));
$roll_no = strtoupper(post_str('roll_no'));
$requested_role = post_str('requested_role');
$password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
$confirm_password = isset($_POST['confirm_password']) && is_string($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

$errors = [];

$name_length = char_length($full_name);
if ($name_length < 2 || $name_length > MAX_NAME_LENGTH) {
    $errors[] = 'Full name must be between 2 and ' . MAX_NAME_LENGTH . ' characters.';
} elseif (!preg_match('/^\p{L}[\p{L}\p{M} .\'-]*$/u', $full_name)) {
    $errors[] = 'Full name can contain only letters, spaces, dots, hyphens and apostrophes.';
}

if (!is_valid_email($email)) {
    $errors[] = 'Please enter a valid email address.';
}

if (!in_array($requested_role, ['student', 'teacher'], true)) {
    $errors[] = 'Please choose whether you are registering as a student or a teacher.';
}

if ($roll_no === '') {
    if ($requested_role === 'student') {
        $errors[] = 'Roll number is required for students.';
    }
} elseif (strlen($roll_no) > MAX_ROLL_LENGTH || !preg_match('/^[A-Z0-9][A-Z0-9\/-]*$/', $roll_no)) {
    $errors[] = 'Roll number can contain only letters, numbers, hyphens and slashes (up to ' . MAX_ROLL_LENGTH . ' characters).';
}

if (strlen($password) < MIN_PASSWORD_LENGTH) {
    $errors[] = 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters.';
} elseif (strlen($password) > MAX_PASSWORD_LENGTH) {
    $errors[] = 'Password must be at most ' . MAX_PASSWORD_LENGTH . ' characters.';
} elseif ($password !== $confirm_password) {
    $errors[] = 'Password and confirmation do not match.';
}

function register_fail(array $errors)
{
    foreach ($errors as $message) {
        flash_set('error', $message);
    }
    old_set($_POST);
    redirect('register.php');
}

if ($errors) {
    register_fail($errors);
}

$roll_value = $roll_no === '' ? null : $roll_no;

$stmt = $pdo->prepare('SELECT email, roll_no FROM users WHERE email = ? OR (roll_no IS NOT NULL AND roll_no = ?)');
$stmt->execute([$email, $roll_value]);
foreach ($stmt->fetchAll() as $existing) {
    if ($existing['email'] === $email) {
        $errors[] = 'An account with this email already exists.';
    }
    if ($roll_value !== null && $existing['roll_no'] === $roll_value) {
        $errors[] = 'An account with this roll number already exists.';
    }
}
if ($errors) {
    register_fail(array_values(array_unique($errors)));
}

try {
    $insert = $pdo->prepare(
        "INSERT INTO users (full_name, email, password_hash, roll_no, role, requested_role, account_status)
         VALUES (?, ?, ?, ?, 'user', ?, 'pending')"
    );
    $insert->execute([
        $full_name,
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        $roll_value,
        $requested_role,
    ]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        register_fail(['An account with this email or roll number already exists.']);
    }
    throw $e;
}

redirect_with('success', 'Registration received. An administrator will review your request, and you can log in meanwhile to check its status.', 'login.php');
