<?php
require_once __DIR__ . '/../../includes/auth.php';

require_post();
require_role(['admin']);

$max_code = 20;
$max_name = 150;
$max_department = 100;

$code = strtoupper(preg_replace('/\s+/u', ' ', post_str('code')));
$name = preg_replace('/\s+/u', ' ', post_str('name'));
$department = preg_replace('/\s+/u', ' ', post_str('department'));

$errors = [];

if ($code === '' || strlen($code) < 2 || strlen($code) > $max_code) {
    $errors[] = 'Course code must be between 2 and ' . $max_code . ' characters.';
} elseif (!preg_match('/^[A-Z0-9][A-Z0-9 -]*$/', $code)) {
    $errors[] = 'Course code can contain only letters, numbers, spaces and hyphens, for example CS-301.';
}

$name_length = char_length($name);
if ($name_length < 2 || $name_length > $max_name) {
    $errors[] = 'Course name must be between 2 and ' . $max_name . ' characters.';
}

if (char_length($department) > $max_department) {
    $errors[] = 'Department must be at most ' . $max_department . ' characters.';
}

function course_fail(array $errors)
{
    foreach ($errors as $message) {
        flash_set('error', $message);
    }
    old_set($_POST);
    redirect('admin/courses.php');
}

if ($errors) {
    course_fail($errors);
}

$department_value = $department === '' ? null : $department;

$check = $pdo->prepare('SELECT id FROM courses WHERE code = ?');
$check->execute([$code]);
if ($check->fetch()) {
    course_fail(['A course with the code ' . $code . ' already exists.']);
}

try {
    $insert = $pdo->prepare('INSERT INTO courses (code, name, department) VALUES (?, ?, ?)');
    $insert->execute([$code, $name, $department_value]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        course_fail(['A course with the code ' . $code . ' already exists.']);
    }
    throw $e;
}

redirect_with('success', 'Course ' . $code . ' has been added.', 'admin/courses.php');
