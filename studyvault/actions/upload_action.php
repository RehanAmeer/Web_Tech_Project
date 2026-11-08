<?php
require_once __DIR__ . '/../includes/auth.php';

function upload_fail(array $messages)
{
    foreach ($messages as $message) {
        flash_set('error', $message);
    }
    old_set($_POST);
    redirect('member/upload.php');
}

$is_post = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
if ($is_post && empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    require_role(['student', 'teacher']);
    upload_fail(['The upload is too large.']);
}

require_post();
require_role(['student', 'teacher']);

$user = current_user();
$user_id = (int) $user['id'];

$title = preg_replace('/\s+/u', ' ', post_str('title'));
$description = str_replace(["\r\n", "\r"], "\n", post_str('description'));
$instructor = preg_replace('/\s+/u', ' ', post_str('instructor_name'));
$course_id = post_str('course_id');
$semester = post_str('semester');
$tags_input = post_str('tags');
$rights = post_str('rights');

$errors = [];

$title_length = char_length($title);
if ($title_length < 2 || $title_length > MAX_TITLE_LENGTH) {
    $errors[] = 'Title must be between 2 and ' . MAX_TITLE_LENGTH . ' characters.';
}

if (char_length($description) > MAX_DESCRIPTION_LENGTH) {
    $errors[] = 'Description must be at most ' . MAX_DESCRIPTION_LENGTH . ' characters.';
}

if ($instructor !== '') {
    if (char_length($instructor) > MAX_INSTRUCTOR_LENGTH || !preg_match('/^\p{L}[\p{L}\p{M} .\'-]*$/u', $instructor)) {
        $errors[] = 'Instructor name can contain only letters, spaces, dots, hyphens and apostrophes (up to ' . MAX_INSTRUCTOR_LENGTH . ' characters).';
    }
}

if (!ctype_digit($semester) || (int) $semester < SEMESTER_MIN || (int) $semester > SEMESTER_MAX) {
    $errors[] = 'Please choose a semester between ' . SEMESTER_MIN . ' and ' . SEMESTER_MAX . '.';
}

if (!ctype_digit($course_id) || (int) $course_id < 1) {
    $errors[] = 'Please choose a course.';
}

if ($rights !== '1') {
    $errors[] = 'Please confirm that you have the right to share this file.';
}

$tags = [];
if ($tags_input !== '') {
    foreach (explode(',', $tags_input) as $raw_tag) {
        $tag = preg_replace('/\s+/u', ' ', trim($raw_tag));
        if ($tag === '') {
            continue;
        }
        if (char_length($tag) > MAX_TAG_LENGTH || !preg_match('/^[\p{L}\p{N}][\p{L}\p{N} .+#-]*$/u', $tag)) {
            $errors[] = 'Each tag can contain only letters, numbers, spaces, dots, plus signs, hash signs and hyphens (up to ' . MAX_TAG_LENGTH . ' characters).';
            $tags = [];
            break;
        }
        $tags[strtolower($tag)] = $tag;
    }
    if (count($tags) > MAX_TAGS) {
        $errors[] = 'You can add at most ' . MAX_TAGS . ' tags.';
    }
}

$file = $_FILES['file'] ?? null;
$file_ok = false;
$ext = '';
$type = '';

if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) {
    $errors[] = 'Please choose a file to upload.';
} else {
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            $file_ok = true;
            break;
        case UPLOAD_ERR_NO_FILE:
            $errors[] = 'Please choose a file to upload.';
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $errors[] = 'The file is larger than the server allows. The largest allowed file is ' . format_bytes(max(MAX_FILE_SIZE)) . '.';
            break;
        default:
            $errors[] = 'The upload did not finish. Please try again.';
    }
}

if ($file_ok) {
    $tmp = (string) ($file['tmp_name'] ?? '');
    $original = (string) ($file['name'] ?? '');
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

    if (!is_uploaded_file($tmp)) {
        $errors[] = 'The upload could not be verified. Please try again.';
        $file_ok = false;
    } elseif (!isset(ALLOWED_FILES[$ext])) {
        $errors[] = 'This file type is not allowed. Allowed types: ' . implode(', ', array_keys(ALLOWED_FILES)) . '.';
        $file_ok = false;
    } elseif (!class_exists('finfo')) {
        error_log('StudyVault: the PHP fileinfo extension is not installed.');
        $errors[] = 'Uploads are temporarily unavailable. Please contact the administrator.';
        $file_ok = false;
    } else {
        $type = ALLOWED_FILES[$ext]['type'];
        $detected = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $size = (int) filesize($tmp);

        if (!in_array($detected, ALLOWED_FILES[$ext]['mimes'], true)) {
            $errors[] = 'The content of this file does not match its ' . strtoupper($ext) . ' extension.';
            $file_ok = false;
        } elseif ($size < 1) {
            $errors[] = 'The file is empty.';
            $file_ok = false;
        } elseif ($size > MAX_FILE_SIZE[$type]) {
            $errors[] = FILE_TYPE_LABELS[$type] . ' files can be at most ' . format_bytes(MAX_FILE_SIZE[$type]) . '.';
            $file_ok = false;
        } elseif ($type === 'image') {
            $dimensions = @getimagesize($tmp);
            if (!$dimensions || $dimensions[0] * $dimensions[1] > 25000000) {
                $errors[] = 'This image is invalid or has too many pixels.';
                $file_ok = false;
            }
        }
    }
}

if (!$errors) {
    $stmt = $pdo->prepare('SELECT id FROM courses WHERE id = ?');
    $stmt->execute([(int) $course_id]);
    if (!$stmt->fetch()) {
        $errors[] = 'The selected course does not exist.';
    }
}

if (!$errors) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM resources WHERE user_id = ? AND created_at >= CURDATE()');
    $stmt->execute([$user_id]);
    if ((int) $stmt->fetchColumn() >= DAILY_UPLOAD_LIMIT) {
        $errors[] = 'You have reached the limit of ' . DAILY_UPLOAD_LIMIT . ' uploads per day. Please try again tomorrow.';
    }
}

$file_hash = '';
if (!$errors) {
    $file_hash = hash_file('sha256', $file['tmp_name']);
    $stmt = $pdo->prepare('SELECT id FROM resources WHERE file_hash = ?');
    $stmt->execute([$file_hash]);
    if ($stmt->fetch()) {
        $errors[] = 'This exact file has already been uploaded.';
    }
}

if ($errors) {
    upload_fail($errors);
}

if (!is_dir(UPLOAD_PATH) || !is_writable(UPLOAD_PATH)) {
    error_log('StudyVault: upload folder is missing or not writable: ' . UPLOAD_PATH);
    upload_fail([APP_DEBUG
        ? 'The storage folder is missing or not writable: ' . UPLOAD_PATH
        : 'Uploads are temporarily unavailable. Please contact the administrator.']);
}

$stored_name = random_filename($ext);
$destination = UPLOAD_PATH . $stored_name;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    error_log('StudyVault: could not move the uploaded file to ' . $destination);
    upload_fail(['The file could not be saved. Please try again.']);
}
chmod($destination, 0640);

try {
    $pdo->beginTransaction();

    $insert = $pdo->prepare(
        'INSERT INTO resources
            (user_id, course_id, title, description, semester, instructor_name,
             file_type, file_ext, file_size, file_hash, file_path)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $insert->execute([
        $user_id,
        (int) $course_id,
        $title,
        $description === '' ? null : $description,
        (int) $semester,
        $instructor === '' ? null : $instructor,
        $type,
        $ext,
        (int) filesize($destination),
        $file_hash,
        $stored_name,
    ]);
    $resource_id = (int) $pdo->lastInsertId();

    if ($tags) {
        $add_tag = $pdo->prepare('INSERT IGNORE INTO tags (name) VALUES (?)');
        $find_tag = $pdo->prepare('SELECT id FROM tags WHERE name = ?');
        $link_tag = $pdo->prepare('INSERT IGNORE INTO resource_tags (resource_id, tag_id) VALUES (?, ?)');
        foreach ($tags as $tag) {
            $add_tag->execute([$tag]);
            $find_tag->execute([$tag]);
            $link_tag->execute([$resource_id, (int) $find_tag->fetchColumn()]);
        }
    }

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    delete_stored_file($stored_name);
    if ($e->getCode() === '23000') {
        upload_fail(['This exact file has already been uploaded.']);
    }
    throw $e;
}

$next = page_exists('member/my_uploads.php') ? 'member/my_uploads.php' : 'member/dashboard.php';
redirect_with('success', 'Your resource "' . $title . '" has been published.', $next);
