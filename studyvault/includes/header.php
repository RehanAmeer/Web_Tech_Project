<?php
require_once __DIR__ . '/auth.php';

$document_title = !empty($page_title) ? $page_title . ' | ' . APP_NAME : APP_NAME;
$css_file = __DIR__ . '/../assets/css/style.css';
$css_version = is_file($css_file) ? filemtime($css_file) : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d1b3e">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='7' fill='%230d1b3e'/%3E%3Ctext x='16' y='23' font-family='Arial,sans-serif' font-size='21' font-weight='bold' text-anchor='middle' fill='%23e0b341'%3ES%3C/text%3E%3C/svg%3E">
    <title><?= e($document_title) ?></title>
    <script>document.documentElement.className = 'js';</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>?v=<?= (int) $css_version ?>">
</head>
<body>
<svg class="icon-sprite" aria-hidden="true"><defs>
    <symbol id="i-home" viewBox="0 0 24 24"><path d="M3 11l9-8 9 8v10H3z"/></symbol>
    <symbol id="i-grid" viewBox="0 0 24 24"><path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/></symbol>
    <symbol id="i-book" viewBox="0 0 24 24"><path d="M4 5a2 2 0 012-2h13v16H6a2 2 0 00-2 2zM4 19V5"/></symbol>
    <symbol id="i-up" viewBox="0 0 24 24"><path d="M12 16V4m0 0L8 8m4-4l4 4M4 20h16"/></symbol>
    <symbol id="i-down" viewBox="0 0 24 24"><path d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/></symbol>
    <symbol id="i-files" viewBox="0 0 24 24"><path d="M3 6h6l2 2h10v11H3z"/></symbol>
    <symbol id="i-mark" viewBox="0 0 24 24"><path d="M6 3h12v18l-6-4-6 4z"/></symbol>
    <symbol id="i-star" viewBox="0 0 24 24"><path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/></symbol>
    <symbol id="i-out" viewBox="0 0 24 24"><path d="M10 17l5-5-5-5M15 12H3M14 3h7v18h-7"/></symbol>
    <symbol id="i-find" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></symbol>
    <symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0116 0"/></symbol>
    <symbol id="i-sidebar" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16"/></symbol>
</defs></svg>
<?php require __DIR__ . '/navbar.php'; ?>
<main class="page-content">
<?= flash_render() ?>
