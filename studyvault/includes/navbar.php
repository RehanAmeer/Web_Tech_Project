<?php
$nav_user = current_user();
$nav_role = $nav_user['role'] ?? 'guest';
$nav_root = __DIR__ . '/../';

$nav_member = [
    ['member/dashboard.php', 'Dashboard'],
    ['resources.php', 'Browse'],
    ['courses.php', 'Courses'],
    ['member/upload.php', 'Upload'],
    ['member/my_uploads.php', 'My uploads'],
    ['member/bookmarks.php', 'Bookmarks'],
    ['member/ratings.php', 'My ratings'],
];

$nav_menus = [
    'guest' => [
        ['resources.php', 'Browse'],
        ['courses.php', 'Courses'],
    ],
    'user' => [
        ['user/dashboard.php', 'Dashboard'],
        ['resources.php', 'Browse'],
        ['courses.php', 'Courses'],
    ],
    'student' => $nav_member,
    'teacher' => $nav_member,
    'admin' => [
        ['admin/dashboard.php', 'Dashboard'],
        ['admin/approvals.php', 'Approvals'],
        ['admin/users.php', 'Users'],
        ['admin/resources.php', 'Resources'],
        ['admin/reports.php', 'Reports'],
        ['admin/courses.php', 'Courses'],
    ],
];

$nav_account = $nav_user
    ? [
        ['account/profile.php', 'Profile'],
        ['account/downloads.php', 'Downloads'],
    ]
    : [
        ['login.php', 'Log in'],
        ['register.php', 'Register'],
    ];

$nav_exists = function ($item) use ($nav_root) {
    return is_file($nav_root . $item[0]);
};
$nav_main = array_filter($nav_menus[$nav_role] ?? $nav_menus['guest'], $nav_exists);
$nav_side = array_filter($nav_account, $nav_exists);

$nav_initials = '';
if ($nav_user) {
    foreach (array_slice(preg_split('/\s+/', trim($nav_user['full_name'])), 0, 2) as $nav_word) {
        if (preg_match('/./su', $nav_word, $nav_match)) {
            $nav_initials .= $nav_match[0];
        }
    }
}
?>
<?php
$nav_icon = function ($path) {
    $icons = [
        'dashboard' => 'i-home', 'resources' => 'i-grid', 'courses' => 'i-book',
        'my_uploads' => 'i-files', 'upload' => 'i-up', 'profile' => 'i-user',
        'bookmarks' => 'i-mark',
        'ratings' => 'i-star', 'downloads' => 'i-down', 'logout' => 'i-out',
        'approvals' => 'i-user', 'users' => 'i-user', 'reports' => 'i-files',
    ];
    foreach ($icons as $key => $icon) {
        if (strpos($path, $key) !== false) {
            return $icon;
        }
    }
    return 'i-grid';
};
?>
<?php if ($nav_user): ?>
<button class="nav-toggle" type="button" aria-expanded="true" aria-controls="nav-menu" aria-label="Collapse sidebar" title="Collapse sidebar"><svg class="icon"><use href="#i-sidebar"></use></svg></button>
<aside class="sidebar">
    <a class="brand sidebar-brand" href="<?= e(url()) ?>"><span class="brand-mark">S</span><?= e(APP_NAME) ?></a>
    <nav class="sidebar-nav nav-menu" id="nav-menu" aria-label="Main navigation">
        <?php foreach ($nav_main as $nav_item): ?>
            <a class="nav-link<?= is_active($nav_item[0]) ?>" href="<?= e(url($nav_item[0])) ?>"><svg class="icon"><use href="#<?= e($nav_icon($nav_item[0])) ?>"></use></svg><?= e($nav_item[1]) ?></a>
        <?php endforeach; ?>
        <?php foreach ($nav_side as $nav_item): ?>
            <a class="nav-link<?= is_active($nav_item[0]) ?>" href="<?= e(url($nav_item[0])) ?>"><svg class="icon"><use href="#<?= e($nav_icon($nav_item[0])) ?>"></use></svg><?= e($nav_item[1]) ?></a>
        <?php endforeach; ?>
        <?php if (is_file($nav_root . 'actions/logout_action.php')): ?>
            <form method="post" action="<?= e(url('actions/logout_action.php')) ?>">
                <?= csrf_field() ?><button type="submit" class="nav-link nav-button"><svg class="icon"><use href="#i-out"></use></svg>Log out</button>
            </form>
        <?php endif; ?>
    </nav>
    <a class="user-box" href="<?= e(url('account/profile.php')) ?>"><span class="avatar"><?= e($nav_initials) ?></span><span><strong><?= e($nav_user['full_name']) ?></strong><small><?= e(ucfirst($nav_role)) ?><?php if ($nav_role === 'user'): ?> &middot; <?= e(ucfirst($nav_user['account_status'])) ?><?php endif; ?></small></span></a>
</aside>
<div class="app-main">
    <header class="topbar">
        <strong><?= e($page_title ?? APP_NAME) ?></strong>
        <form class="search-form" method="get" action="<?= e(url('resources.php')) ?>"><svg class="icon"><use href="#i-find"></use></svg><input name="q" value="<?= e(get_str('q')) ?>" placeholder="Search notes, papers and tags" aria-label="Search resources"></form>
    </header>
<?php else: ?>
<header class="guest-header">
    <a class="brand" href="<?= e(url()) ?>"><span class="brand-mark">S</span><?= e(APP_NAME) ?></a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-menu">Menu</button>
    <nav class="guest-nav nav-menu" id="nav-menu" aria-label="Main navigation">
        <?php foreach ($nav_main as $nav_item): ?><a class="nav-link<?= is_active($nav_item[0]) ?>" href="<?= e(url($nav_item[0])) ?>"><?= e($nav_item[1]) ?></a><?php endforeach; ?>
        <?php foreach ($nav_side as $nav_item): ?><a class="nav-link<?= is_active($nav_item[0]) ?>" href="<?= e(url($nav_item[0])) ?>"><?= e($nav_item[1]) ?></a><?php endforeach; ?>
    </nav>
</header>
<div class="app-main guest-main">
    <header class="topbar"><strong><?= e($page_title ?? APP_NAME) ?></strong><form class="search-form" method="get" action="<?= e(url('resources.php')) ?>"><svg class="icon"><use href="#i-find"></use></svg><input name="q" value="<?= e(get_str('q')) ?>" placeholder="Search notes, papers and tags" aria-label="Search resources"></form></header>
<?php endif; ?>
