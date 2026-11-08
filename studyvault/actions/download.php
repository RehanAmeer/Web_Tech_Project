<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$id = request_id();
$stmt = $pdo->prepare("SELECT id, user_id, title, file_ext, file_path, status FROM resources WHERE id = ?");
$stmt->execute([$id]); $resource = $stmt->fetch();
if (!$resource || $resource['status'] !== 'published') { redirect_with('error', 'This resource is not available.', 'resources.php'); }
$path = rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . basename($resource['file_path']);
if (!is_file($path) || !is_readable($path)) { error_log('StudyVault: missing resource file ' . $path); redirect_with('error', 'The resource file is unavailable.', 'resource_details.php?id=' . $id); }
$user_id = (int) current_user()['id'];
if ($user_id !== (int) $resource['user_id']) {
    $download = $pdo->prepare('SELECT 1 FROM downloads WHERE user_id = ? AND resource_id = ? LIMIT 1');
    $download->execute([$user_id, $id]);
    if (!$download->fetchColumn()) {
        $pdo->prepare('INSERT INTO downloads (user_id, resource_id) VALUES (?, ?)')->execute([$user_id, $id]);
        $pdo->prepare('UPDATE resources SET download_count = download_count + 1 WHERE id = ?')->execute([$id]);
    }
}
session_write_close();
header('Content-Type: application/octet-stream');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . addcslashes(download_filename($resource['title'], $resource['file_ext']), '"\\') . '"');
readfile($path);
exit;
