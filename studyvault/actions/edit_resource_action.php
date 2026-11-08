<?php
require_once __DIR__ . '/../includes/auth.php';
require_post(); require_role(['student', 'teacher', 'admin']);
$id = request_id('resource_id'); $title = preg_replace('/\s+/u', ' ', post_str('title')); $description = str_replace(["\r\n", "\r"], "\n", post_str('description')); $instructor = preg_replace('/\s+/u', ' ', post_str('instructor_name')); $course_id = post_str('course_id'); $semester = post_str('semester');
$errors = [];
if (char_length($title) < 2 || char_length($title) > MAX_TITLE_LENGTH) $errors[] = 'Title must be between 2 and ' . MAX_TITLE_LENGTH . ' characters.';
if (char_length($description) > MAX_DESCRIPTION_LENGTH) $errors[] = 'Description is too long.';
if (!ctype_digit($course_id) || (int) $course_id < 1) $errors[] = 'Please choose a course.';
if (!ctype_digit($semester) || (int) $semester < SEMESTER_MIN || (int) $semester > SEMESTER_MAX) $errors[] = 'Please choose a valid semester.';
if ($instructor !== '' && (char_length($instructor) > MAX_INSTRUCTOR_LENGTH || !preg_match('/^\p{L}[\p{L}\p{M} .\'-]*$/u', $instructor))) $errors[] = 'Instructor name can contain only letters, spaces, dots, hyphens and apostrophes (up to ' . MAX_INSTRUCTOR_LENGTH . ' characters).';
$is_admin = current_user()['role'] === 'admin';
$stmt = $pdo->prepare($is_admin ? 'SELECT id, file_path FROM resources WHERE id = ?' : 'SELECT id, file_path FROM resources WHERE id = ? AND user_id = ?'); $stmt->execute($is_admin ? [$id] : [$id, current_user()['id']]);
$existing = $stmt->fetch();
if (!$existing) $errors[] = 'Resource not found or you do not have permission.';
$stmt = $pdo->prepare('SELECT id FROM courses WHERE id = ?'); $stmt->execute([(int) $course_id]); if (!$stmt->fetch()) $errors[] = 'Selected course does not exist.';
$file = $_FILES['file'] ?? null; $replace_file = is_array($file) && (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);
$new_name = null; $new_ext = null; $new_type = null; $new_size = null; $new_hash = null;
if ($replace_file) {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
        $errors[] = 'The replacement file could not be uploaded. Please try again.';
    } else {
        $new_ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!isset(ALLOWED_FILES[$new_ext])) $errors[] = 'This file type is not allowed.';
        elseif (!class_exists('finfo')) { error_log('StudyVault: the PHP fileinfo extension is not installed.'); $errors[] = 'Uploads are temporarily unavailable. Please contact the administrator.'; }
        else {
            $new_type = ALLOWED_FILES[$new_ext]['type']; $detected = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']); $new_size = (int) filesize($file['tmp_name']);
            if (!in_array($detected, ALLOWED_FILES[$new_ext]['mimes'], true)) $errors[] = 'The content of this file does not match its extension.';
            elseif ($new_size < 1 || $new_size > MAX_FILE_SIZE[$new_type]) $errors[] = FILE_TYPE_LABELS[$new_type] . ' files can be at most ' . format_bytes(MAX_FILE_SIZE[$new_type]) . '.';
            else {
                if ($new_type === 'image') {
                    $dimensions = @getimagesize($file['tmp_name']);
                    if (!$dimensions || $dimensions[0] * $dimensions[1] > 25000000) $errors[] = 'This image is invalid or has too many pixels.';
                }
                if (!$errors) {
                    $new_hash = hash_file('sha256', $file['tmp_name']);
                    $duplicate = $pdo->prepare('SELECT id FROM resources WHERE file_hash = ? AND id <> ?'); $duplicate->execute([$new_hash, $id]);
                    if ($duplicate->fetch()) $errors[] = 'This exact file has already been uploaded.';
                }
            }
        }
    }
}
if ($errors) { foreach ($errors as $error) flash_set('error', $error); old_set($_POST); redirect('member/edit_resource.php?id=' . $id); }
$old_path = $existing['file_path'] ?? null;
if ($replace_file) {
    if (!is_dir(UPLOAD_PATH) || !is_writable(UPLOAD_PATH)) { $errors[] = 'Uploads are temporarily unavailable. Please contact the administrator.'; }
    else { $new_name = random_filename($new_ext); if (!move_uploaded_file($file['tmp_name'], UPLOAD_PATH . $new_name)) { $errors[] = 'The replacement file could not be saved. Please try again.'; } else { chmod(UPLOAD_PATH . $new_name, 0640); } }
}
if ($errors) { foreach ($errors as $error) flash_set('error', $error); old_set($_POST); redirect('member/edit_resource.php?id=' . $id); }
$update = $is_admin
    ? $pdo->prepare($replace_file ? 'UPDATE resources SET title = ?, description = ?, course_id = ?, semester = ?, instructor_name = ?, file_type = ?, file_ext = ?, file_size = ?, file_hash = ?, file_path = ? WHERE id = ?' : 'UPDATE resources SET title = ?, description = ?, course_id = ?, semester = ?, instructor_name = ? WHERE id = ?')
    : $pdo->prepare($replace_file ? 'UPDATE resources SET title = ?, description = ?, course_id = ?, semester = ?, instructor_name = ?, file_type = ?, file_ext = ?, file_size = ?, file_hash = ?, file_path = ? WHERE id = ? AND user_id = ?' : 'UPDATE resources SET title = ?, description = ?, course_id = ?, semester = ?, instructor_name = ? WHERE id = ? AND user_id = ?');
$values = [$title, $description ?: null, (int) $course_id, (int) $semester, $instructor ?: null];
if ($replace_file) $values = array_merge($values, [$new_type, $new_ext, $new_size, $new_hash, $new_name]);
$values[] = $id; if (!$is_admin) $values[] = current_user()['id']; $update->execute($values);
if ($replace_file && $old_path) delete_stored_file($old_path);
redirect_with('success', 'Resource updated.', 'resource_details.php?id=' . $id);
