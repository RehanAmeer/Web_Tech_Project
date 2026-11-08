<?php
require_once __DIR__ . '/../config/constants.php';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url($path = '')
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset($path)
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect($path)
{
    $path = str_replace(["\r", "\n"], '', $path);
    header('Location: ' . url($path));
    exit;
}

function is_active($path)
{
    $current = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    return $current === url($path) ? ' active' : '';
}

function start_secure_session()
{
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function flash_set($type, $message)
{
    start_secure_session();
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_render()
{
    start_secure_session();
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $html = '';
    foreach ($_SESSION['flash'] as $flash) {
        $html .= '<div class="alert alert-' . e($flash['type']) . '" role="alert">' . e($flash['message']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

function old_set(array $data)
{
    start_secure_session();
    unset($data['password'], $data['confirm_password'], $data['csrf_token']);
    $_SESSION['old'] = array_filter($data, 'is_string');
}

function old($key, $default = '')
{
    static $data = null;
    if ($data === null) {
        start_secure_session();
        $data = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
    }
    return e($data[$key] ?? $default);
}

function csrf_token()
{
    start_secure_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify()
{
    start_secure_session();
    $sent = $_POST['csrf_token'] ?? '';
    return is_string($sent)
        && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $sent);
}

function require_post()
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        exit('Method not allowed.');
    }
    if (!csrf_verify()) {
        http_response_code(403);
        exit('Invalid request token.');
    }
}

function badge($text, $class)
{
    return '<span class="badge badge-' . e($class) . '">' . e($text) . '</span>';
}

function status_badge($status)
{
    $map = [
        'pending' => ['Pending', 'warning'],
        'approved' => ['Approved', 'success'],
        'rejected' => ['Rejected', 'danger'],
        'banned' => ['Banned', 'danger'],
        'published' => ['Published', 'success'],
        'under_review' => ['Under review', 'warning'],
        'removed' => ['Removed', 'danger'],
    ];
    [$label, $class] = $map[$status] ?? [ucfirst(str_replace('_', ' ', $status)), 'neutral'];
    return badge($label, $class);
}

function format_bytes($bytes)
{
    $bytes = (int) $bytes;
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024) . ' KB';
    }
    return $bytes . ' B';
}

function format_date($datetime)
{
    return $datetime ? date('j M Y', strtotime($datetime)) : '';
}

function post_str($key, $default = '')
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function get_str($key, $default = '')
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function request_id($key = 'id')
{
    $value = $_POST[$key] ?? ($_GET[$key] ?? '');
    return (is_string($value) && ctype_digit($value) && (int) $value > 0) ? (int) $value : 0;
}

function is_valid_email($email)
{
    return strlen($email) <= 150 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function page_exists($path)
{
    return is_file(__DIR__ . '/../' . ltrim($path, '/'));
}

function redirect_with($type, $message, $path)
{
    flash_set($type, $message);
    redirect($path);
}

function str_limit($text, $length = 120)
{
    $text = (string) $text;
    $count = preg_match_all('/./su', $text, $chars);
    if ($count === false || $count <= $length) {
        return $text;
    }
    return rtrim(implode('', array_slice($chars[0], 0, $length))) . '…';
}

function format_datetime($datetime)
{
    return $datetime ? date('j M Y, g:i A', strtotime($datetime)) : '';
}

function format_rating($average, $count)
{
    return (int) $count > 0 ? number_format((float) $average, 1) : 'No ratings yet';
}

function random_filename($ext)
{
    return bin2hex(random_bytes(16)) . '.' . strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $ext));
}

function download_filename($title, $ext)
{
    $base = preg_replace('/[^A-Za-z0-9 _.-]+/', '', (string) $title);
    $base = trim(preg_replace('/\s+/', '_', $base), '._-');
    if ($base === '') {
        $base = 'download';
    }
    return substr($base, 0, 80) . '.' . strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $ext));
}

function delete_stored_file($name, $dir = null)
{
    $name = basename((string) $name);
    if ($name === '' || $name === '.' || $name === '..') {
        return false;
    }
    $path = rtrim($dir ?? UPLOAD_PATH, '/') . '/' . $name;
    return is_file($path) ? unlink($path) : false;
}

function url_with(array $changes = [])
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: url();
    $query = array_filter(array_merge($_GET, $changes), function ($value) {
        return $value !== null && $value !== '' && $value !== [];
    });
    return $path . ($query ? '?' . http_build_query($query) : '');
}

function current_page()
{
    $page = $_GET['page'] ?? '1';
    return (is_string($page) && ctype_digit($page) && (int) $page > 0) ? (int) $page : 1;
}

function paginate($total, $per_page = PER_PAGE)
{
    $pages = max(1, (int) ceil($total / $per_page));
    $page = min(current_page(), $pages);
    return [
        'total' => (int) $total,
        'per_page' => (int) $per_page,
        'pages' => $pages,
        'page' => $page,
        'offset' => ($page - 1) * $per_page,
    ];
}

function render_pagination(array $p)
{
    if ($p['pages'] <= 1) {
        return '';
    }
    $link = function ($page, $label, $active = false) {
        $href = url_with(['page' => $page > 1 ? $page : null]);
        return '<a class="page-link' . ($active ? ' active" aria-current="page' : '') . '" href="' . e($href) . '">' . e($label) . '</a>';
    };
    $gap = '<span class="page-link disabled">…</span>';
    $start = max(1, $p['page'] - 2);
    $end = min($p['pages'], $p['page'] + 2);

    $html = '<nav class="pagination" aria-label="Pagination">';
    $html .= $p['page'] > 1 ? $link($p['page'] - 1, 'Previous') : '<span class="page-link disabled">Previous</span>';
    if ($start > 1) {
        $html .= $link(1, '1');
        $html .= $start > 2 ? $gap : '';
    }
    for ($i = $start; $i <= $end; $i++) {
        $html .= $link($i, (string) $i, $i === $p['page']);
    }
    if ($end < $p['pages']) {
        $html .= $end < $p['pages'] - 1 ? $gap : '';
        $html .= $link($p['pages'], (string) $p['pages']);
    }
    $html .= $p['page'] < $p['pages'] ? $link($p['page'] + 1, 'Next') : '<span class="page-link disabled">Next</span>';
    return $html . '</nav>';
}

function char_length($text)
{
    $count = preg_match_all('/./su', (string) $text);
    return $count === false ? strlen((string) $text) : $count;
}
