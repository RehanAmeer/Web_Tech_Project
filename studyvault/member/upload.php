<?php
require_once __DIR__ . '/../includes/auth.php';

require_role(['student', 'teacher']);

$user = current_user();

$courses = $pdo->query('SELECT id, code, name FROM courses ORDER BY code ASC')->fetchAll();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM resources WHERE user_id = ? AND created_at >= CURDATE()');
$stmt->execute([$user['id']]);
$uploaded_today = (int) $stmt->fetchColumn();
$limit_reached = $uploaded_today >= DAILY_UPLOAD_LIMIT;

$old_course = old('course_id');
$old_semester = old('semester');
$old_rights = old('rights') === '1';

$size_notes = [];
foreach (MAX_FILE_SIZE as $category => $bytes) {
    $exts = [];
    foreach (ALLOWED_FILES as $extension => $rule) {
        if ($rule['type'] === $category) {
            $exts[] = $extension;
        }
    }
    $size_notes[] = FILE_TYPE_LABELS[$category] . ' (' . implode(', ', $exts) . ') up to ' . format_bytes($bytes);
}
$accept = '.' . implode(',.', array_keys(ALLOWED_FILES));

$page_title = 'Upload a resource';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1>Upload a resource</h1>
        <p class="muted">Share notes, slides, past papers or other study material. You have used <?= $uploaded_today ?> of <?= (int) DAILY_UPLOAD_LIMIT ?> uploads today.</p>
    </div>
</div>

<?php if (!$courses): ?>
    <div class="empty-state">No courses have been added yet, so uploads are not open. Please ask an administrator to add courses.</div>
<?php elseif ($limit_reached): ?>
    <div class="alert alert-warning" role="alert">You have reached today's limit of <?= (int) DAILY_UPLOAD_LIMIT ?> uploads. Please come back tomorrow.</div>
<?php else: ?>
    <div class="cols upload-layout">
    <div class="card">
        <form method="post" action="<?= e(url('actions/upload_action.php')) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?= old('title') ?>" maxlength="<?= (int) MAX_TITLE_LENGTH ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="course_id">Course</label>
                    <select id="course_id" name="course_id" required>
                        <option value="">Choose a course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= (int) $course['id'] ?>"<?= $old_course === (string) (int) $course['id'] ? ' selected' : '' ?>><?= e($course['code'] . ' - ' . $course['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="semester">Semester</label>
                    <select id="semester" name="semester" required>
                        <option value="">Choose a semester</option>
                        <?php for ($s = SEMESTER_MIN; $s <= SEMESTER_MAX; $s++): ?>
                            <option value="<?= $s ?>"<?= $old_semester === (string) $s ? ' selected' : '' ?>>Semester <?= $s ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="instructor_name">Instructor <span class="muted">(optional)</span></label>
                <input type="text" id="instructor_name" name="instructor_name" value="<?= old('instructor_name') ?>" maxlength="<?= (int) MAX_INSTRUCTOR_LENGTH ?>">
            </div>

            <div class="form-group">
                <label for="description">Description <span class="muted">(optional)</span></label>
                <textarea id="description" name="description" maxlength="<?= (int) MAX_DESCRIPTION_LENGTH ?>"><?= old('description') ?></textarea>
            </div>

            <div class="form-group">
                <label for="tags">Tags <span class="muted">(optional)</span></label>
                <input type="text" id="tags" name="tags" value="<?= old('tags') ?>" placeholder="midterm, chapter 3, past paper">
                <p class="form-hint">Separate with commas. Up to <?= (int) MAX_TAGS ?> tags.</p>
            </div>

            <div class="form-group">
                <label class="drop" for="file">
                    <span class="brand-mark">↑</span>
                    <b>Choose a file to upload</b>
                    <span class="file-prompt">PDF, Word, Excel, PowerPoint or image files up to 100 MB.</span>
                    <span class="selected-file-row" id="selected-file-row" aria-live="polite">
                        <span class="selected-file" id="selected-file">No file selected</span>
                        <button class="clear-file" id="clear-file" type="button" aria-label="Remove selected file" title="Remove selected file">&times;</button>
                    </span>
                    <input type="file" id="file" name="file" accept="<?= e($accept) ?>" required>
                </label>
                <p class="form-hint">Allowed: <?= e(implode('; ', $size_notes)) ?>.</p>
            </div>

            <div class="form-group">
                <label class="checkbox" for="rights">
                    <input type="checkbox" id="rights" name="rights" value="1"<?= $old_rights ? ' checked' : '' ?> required>
                    <span>I have the right to share this file, and it does not break anyone's copyright.</span>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">Upload</button>
                <?php if (page_exists('member/my_uploads.php')): ?>
                    <a class="btn btn-secondary" href="<?= e(url('member/my_uploads.php')) ?>">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <aside class="stick">
        <div class="box"><h2>Daily quota</h2><p><strong><?= $uploaded_today ?></strong> of <?= (int) DAILY_UPLOAD_LIMIT ?> uploads used today.</p><div class="meter"><i style="width: <?= min(100, ($uploaded_today / max(1, DAILY_UPLOAD_LIMIT)) * 100) ?>%"></i></div></div>
        <div class="box"><h2>Upload tips</h2><ul><li>Use a clear, descriptive title.</li><li>Add course and semester details.</li><li>Only upload files you have permission to share.</li></ul></div>
    </aside>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
