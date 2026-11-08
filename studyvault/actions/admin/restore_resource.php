<?php
require_once __DIR__ . '/../../includes/auth.php';
require_post();
require_role(['admin']);
$id = request_id('resource_id');
$check = $pdo->prepare(
    "SELECT status,
            EXISTS (
                SELECT 1 FROM reports
                WHERE resource_id = resources.id AND status = 'open'
            ) AS has_open_reports
     FROM resources
     WHERE id = ?"
);
$check->execute([$id]);
$resource = $check->fetch();
if (!$resource) {
    redirect_with('warning', 'Resource not found.', 'admin/resources.php');
}
if ((int) $resource['has_open_reports'] === 1) {
    redirect_with('warning', 'Resolve the open reports first.', 'admin/resources.php');
}
$stmt = $pdo->prepare(
    "UPDATE resources
     SET status = 'published'
     WHERE id = ?
       AND status IN ('removed', 'under_review')"
);
$stmt->execute([$id]);
redirect_with($stmt->rowCount() ? 'success' : 'warning', $stmt->rowCount() ? 'Resource restored.' : 'Resource is already published.', 'admin/resources.php');
