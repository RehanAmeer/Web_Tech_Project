<?php
require_once __DIR__ . '/../includes/auth.php';

require_post();

if (current_user()) {
    redirect(home_path());
}

define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCK_SECONDS', 300);
define('LOGIN_DUMMY_HASH', '$2y$10$Uxl63l7VgMIplyJYtFxeEuRRBOjMOHKgQB1jWLbk9nEDA9x1.A./K');

function login_fail($message, $email)
{
    flash_set('error', $message);
    old_set(['email' => $email]);
    redirect('login.php');
}

$email = strtolower(post_str('email'));
$password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

$locked_until = (int) ($_SESSION['login_locked_until'] ?? 0);
if ($locked_until > time()) {
    $minutes = (int) ceil(($locked_until - time()) / 60);
    login_fail('Too many failed attempts. Please try again in ' . $minutes . ' minute' . ($minutes === 1 ? '' : 's') . '.', $email);
}

if ($email === '' || $password === '' || strlen($password) > 1024) {
    login_fail('Please enter your email and password.', $email);
}

$stmt = $pdo->prepare('SELECT id, password_hash, role, account_status FROM users WHERE email = ?');
$stmt->execute([$email]);
$found = $stmt->fetch();

$valid = password_verify($password, $found ? $found['password_hash'] : LOGIN_DUMMY_HASH);

if (!$found || !$valid) {
    $attempts = (int) ($_SESSION['login_attempts'] ?? 0) + 1;
    if ($attempts >= LOGIN_MAX_ATTEMPTS) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_locked_until'] = time() + LOGIN_LOCK_SECONDS;
    } else {
        $_SESSION['login_attempts'] = $attempts;
    }
    login_fail('Incorrect email or password.', $email);
}

if ($found['account_status'] === 'banned') {
    login_fail('Your account has been suspended. Please contact the administrator.', $email);
}

unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);
login_user($found['id']);

redirect(home_path($found));
