<?php
require_once __DIR__ . '/../includes/auth.php';

$id = request_id();
$stmt = $pdo->prepare(
    'SELECT user_id, file_path, file_ext, file_type, status
     FROM resources
     WHERE id = ?'
);
$stmt->execute([$id]);
$resource = $stmt->fetch();

$viewer = current_user();
if (
    !$resource
    || !in_array($resource['file_type'], PREVIEW_TYPES, true)
    || ($resource['status'] !== 'published'
        && (!$viewer || !is_owner_or_admin($resource['user_id'])))
) {
    http_response_code(404);
    exit;
}

$path = rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . basename($resource['file_path']);
if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit;
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
    http_response_code(404);
    exit;
}

session_write_close();
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="preview.' . $resource['file_ext'] . '"');
readfile($path);
exit;
