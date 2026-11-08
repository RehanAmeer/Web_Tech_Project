<?php
define('APP_NAME', 'StudyVault');
$document_root = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$app_root = realpath(__DIR__ . '/..');
$base_url = getenv('STUDYVAULT_BASE_URL') ?: '';
if ($base_url === '' && $document_root && $app_root && strpos($app_root, $document_root) === 0) {
    $base_url = str_replace('\\', '/', substr($app_root, strlen($document_root)));
}
define('BASE_URL', rtrim($base_url ?: '/studyvault', '/'));
define('APP_DEBUG', false);
define('APP_TIMEZONE', 'Asia/Karachi');

date_default_timezone_set(APP_TIMEZONE);
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

define('STORAGE_PATH', '/var/www/studyvault_storage/');
define('UPLOAD_PATH', STORAGE_PATH . 'uploads/');

define('FILE_TYPE_LABELS', [
    'pdf'   => 'PDF',
    'doc'   => 'Word',
    'excel' => 'Excel',
    'ppt'   => 'PowerPoint',
    'image' => 'Image',
]);

define('ALLOWED_FILES', [
    'pdf'  => ['type' => 'pdf', 'mimes' => ['application/pdf']],
    'doc'  => ['type' => 'doc', 'mimes' => ['application/msword', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/CDFV2']],
    'docx' => ['type' => 'doc', 'mimes' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip']],
    'xls'  => ['type' => 'excel', 'mimes' => ['application/vnd.ms-excel', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/CDFV2']],
    'xlsx' => ['type' => 'excel', 'mimes' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip']],
    'ppt'  => ['type' => 'ppt', 'mimes' => ['application/vnd.ms-powerpoint', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/CDFV2']],
    'pptx' => ['type' => 'ppt', 'mimes' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip']],
    'jpg'  => ['type' => 'image', 'mimes' => ['image/jpeg']],
    'jpeg' => ['type' => 'image', 'mimes' => ['image/jpeg']],
    'png'  => ['type' => 'image', 'mimes' => ['image/png']],
]);

define('MAX_FILE_SIZE', [
    'pdf'   => 100 * 1024 * 1024,
    'ppt'   => 100 * 1024 * 1024,
    'doc'   => 100 * 1024 * 1024,
    'excel' => 100 * 1024 * 1024,
    'image' => 100 * 1024 * 1024,
]);

define('PREVIEW_TYPES', ['pdf', 'image']);

define('REPORT_THRESHOLD', 3);
define('STRIKE_LIMIT', 3);
define('DAILY_UPLOAD_LIMIT', 5);

define('REPORT_REASONS', [
    'inappropriate' => 'Inappropriate content',
    'copyright'     => 'Copyright violation',
    'wrong_content' => 'Wrong or misleading content',
    'duplicate'     => 'Duplicate of another file',
    'spam'          => 'Spam',
    'other'         => 'Other',
]);

define('SEMESTER_MIN', 1);
define('SEMESTER_MAX', 8);
define('PER_PAGE', 20);
define('MAX_COMMENT_LENGTH', 1000);
define('MIN_PASSWORD_LENGTH', 8);

define('MAX_NAME_LENGTH', 100);
define('MAX_ROLL_LENGTH', 30);
define('MAX_PASSWORD_LENGTH', 72);
define('MAX_TITLE_LENGTH', 200);
define('MAX_DESCRIPTION_LENGTH', 2000);
define('MAX_INSTRUCTOR_LENGTH', 100);
define('MAX_TAGS', 5);
define('MAX_TAG_LENGTH', 30);
define('MAX_REASON_LENGTH', 255);
