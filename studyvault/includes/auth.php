<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

start_secure_session();

function current_user()
{
    static $loaded = false;
    static $user = null;

    if ($loaded) {
        return $user;
    }
    $loaded = true;

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    global $pdo;
    $stmt = $pdo->prepare(
        'SELECT id, full_name, email, roll_no, role, requested_role, account_status, reject_reason, strike_count, created_at
         FROM users WHERE id = ?'
    );
    $stmt->execute([$_SESSION['user_id']]);
    $found = $stmt->fetch();

    if (!$found) {
        unset($_SESSION['user_id']);
        return null;
    }

    $user = $found;
    return $user;
}

function login_user($user_id)
{
    start_secure_session();
    session_regenerate_id(true);
    unset($_SESSION['csrf']);
    $_SESSION['user_id'] = (int) $user_id;
}

function logout_user()
{
    start_secure_session();
    $_SESSION = [];
    session_regenerate_id(true);
}

function home_path($user = null)
{
    $user = $user ?? current_user();

    if (!$user || $user['account_status'] === 'banned') {
        return 'login.php';
    }
    if ($user['role'] === 'admin') {
        return 'admin/dashboard.php';
    }
    if (in_array($user['role'], ['student', 'teacher'], true) && $user['account_status'] === 'approved') {
        return 'member/dashboard.php';
    }
    return 'user/dashboard.php';
}

function deny_access()
{
    http_response_code(403);
    $page = __DIR__ . '/../403.php';
    if (is_file($page)) {
        require $page;
    } else {
        echo 'Access denied.';
    }
    exit;
}

function require_login()
{
    if (!current_user()) {
        flash_set('warning', 'Please log in to continue.');
        redirect('login.php');
    }
}

function require_guest()
{
    if (current_user()) {
        redirect(home_path());
    }
}

function require_role(array $roles)
{
    require_login();
    $user = current_user();
    if (!in_array($user['role'], $roles, true)) {
        deny_access();
    }
}

function has_role(array $roles)
{
    $user = current_user();
    return $user !== null && in_array($user['role'], $roles, true);
}

function is_owner_or_admin($owner_id)
{
    $user = current_user();
    return $user !== null && ($user['role'] === 'admin' || (int) $user['id'] === (int) $owner_id);
}

$auth_user = current_user();
if ($auth_user && $auth_user['account_status'] === 'banned') {
    logout_user();
    flash_set('error', 'Your account has been suspended. Please contact the administrator.');
    redirect('login.php');
}
