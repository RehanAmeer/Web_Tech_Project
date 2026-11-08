<?php
require_once __DIR__ . '/constants.php';

$db_host = 'localhost';
$db_name = 'studyvault';
$db_user = 'studyvault_user';
$db_pass = 'password';
if (!$db_pass) {
    throw new RuntimeException('STUDYVAULT_DB_PASSWORD is not configured.');
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        $options
    );
    $pdo->exec("SET time_zone = '" . (new DateTime('now', new DateTimeZone(APP_TIMEZONE)))->format('P') . "'");
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed.');
}
