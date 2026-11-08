<?php
require_once __DIR__ . '/../includes/auth.php';

require_role(['admin']);

$total = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND account_status = 'pending'")->fetchColumn();
$pager = paginate($total);

$stmt = $pdo->prepare(
    "SELECT id, full_name, email, roll_no, requested_role, created_at
     FROM users WHERE role = 'user' AND account_status = 'pending'
     ORDER BY created_at ASC, id ASC LIMIT ? OFFSET ?"
);
$stmt->bindValue(1, $pager['per_page'], PDO::PARAM_INT);
$stmt->bindValue(2, $pager['offset'], PDO::PARAM_INT);
$stmt->execute();
$pending = $stmt->fetchAll();

$page_title = 'Approvals';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1>Approvals</h1>
        <p class="muted"><?= $total ?> pending <?= $total === 1 ? 'request' : 'requests' ?>, oldest first.</p>
    </div>
</div>

<?php if (!$pending): ?>
    <div class="empty-state">No pending requests. New sign-ups will appear here.</div>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Roll no.</th>
                    <th>Applying as</th>
                    <th>Submitted</th>
                    <th>Decision</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending as $row): ?>
                    <tr>
                        <td><?= e($row['full_name']) ?></td>
                        <td><?= e($row['email']) ?></td>
                        <td><?= $row['roll_no'] !== null ? e($row['roll_no']) : '<span class="muted">None</span>' ?></td>
                        <td><?= badge(ucfirst($row['requested_role']), 'accent') ?></td>
                        <td><?= e(format_datetime($row['created_at'])) ?></td>
                        <td class="table-actions">
                            <form method="post" action="<?= e(url('actions/admin/approve_user.php')) ?>" class="inline-form" data-confirm="Approve <?= e($row['full_name']) ?> as a <?= e($row['requested_role']) ?>?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $row['id'] ?>">
                                <button type="submit" class="btn btn-sm">Approve</button>
                            </form>
                            <form method="post" action="<?= e(url('actions/admin/reject_user.php')) ?>" class="inline-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $row['id'] ?>">
                                <label class="sr-only" for="reason-<?= (int) $row['id'] ?>">Reason for rejecting <?= e($row['full_name']) ?></label>
                                <input type="text" id="reason-<?= (int) $row['id'] ?>" name="reason" class="input-sm w-auto" placeholder="Reason" maxlength="<?= (int) MAX_REASON_LENGTH ?>" required>
                                <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= render_pagination($pager) ?>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
