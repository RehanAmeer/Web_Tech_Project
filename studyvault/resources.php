<?php
require_once __DIR__ . '/includes/auth.php';

$query = get_str('q');
$course_id = get_str('course_id');
$semester = get_str('semester');
$file_type = get_str('file_type');
$sort = get_str('sort', 'latest');

$where = ["r.status = 'published'"];
$params = [];
if ($query !== '') {
    $search = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $query);
    $search = preg_replace('/\s+/u', ' ', trim($search));
    if ($search !== '') {
        $tokens = preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY);
        $boolean_query = '+' . implode(' +', array_map(function ($token) {
            return $token . '*';
        }, $tokens));
        $where[] = '(MATCH(r.title, r.description) AGAINST (? IN BOOLEAN MODE)
            OR r.title LIKE ? OR r.description LIKE ?
            OR EXISTS (SELECT 1 FROM resource_tags rt JOIN tags t ON t.id = rt.tag_id
                       WHERE rt.resource_id = r.id AND t.name LIKE ?))';
        $like = '%' . $search . '%';
        $params[] = $boolean_query;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    } else {
        $where[] = '1 = 0';
    }
}
if (ctype_digit($course_id) && (int) $course_id > 0) {
    $where[] = 'r.course_id = ?';
    $params[] = (int) $course_id;
} else {
    $course_id = '';
}
if (ctype_digit($semester) && (int) $semester >= SEMESTER_MIN && (int) $semester <= SEMESTER_MAX) {
    $where[] = 'r.semester = ?';
    $params[] = (int) $semester;
} else {
    $semester = '';
}
if (isset(FILE_TYPE_LABELS[$file_type])) {
    $where[] = 'r.file_type = ?';
    $params[] = $file_type;
} else {
    $file_type = '';
}
$order = $sort === 'popular' ? 'r.download_count DESC, r.created_at DESC' : ($sort === 'rated' ? 'r.avg_rating DESC, r.rating_count DESC, r.created_at DESC' : 'r.created_at DESC, r.id DESC');

$where_sql = implode(' AND ', $where);
$count = $pdo->prepare("SELECT COUNT(*) FROM resources r WHERE $where_sql");
$count->execute($params);
$pager = paginate((int) $count->fetchColumn());

$courses = $pdo->query('SELECT id, code, name FROM courses ORDER BY code')->fetchAll();
$stmt = $pdo->prepare(
    "SELECT r.id, r.title, r.description, r.semester, r.file_type, r.file_ext, r.file_size,
            r.avg_rating, r.rating_count, r.download_count, r.comment_count, r.created_at,
            c.code AS course_code, c.name AS course_name, u.full_name AS uploader, u.role AS uploader_role
     FROM resources r
     JOIN courses c ON c.id = r.course_id
     JOIN users u ON u.id = r.user_id
     WHERE $where_sql
     ORDER BY $order
     LIMIT ? OFFSET ?"
);
foreach ($params as $i => $value) {
    $stmt->bindValue($i + 1, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(count($params) + 1, $pager['per_page'], PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $pager['offset'], PDO::PARAM_INT);
$stmt->execute();
$resources = $stmt->fetchAll();

$page_title = 'Browse resources';
require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div><h1>Browse resources</h1><p class="muted">Find notes, past papers, lab manuals and reference material.</p></div>
</div>
<div class="card filter-card">
    <form method="get" class="filters">
        <div class="form-group"><label for="q">Search</label><input id="q" name="q" value="<?= e($query) ?>" placeholder="Title, description or tag"></div>
        <div class="form-row">
            <div class="form-group"><label for="course_id">Course</label><select id="course_id" name="course_id"><option value="">All courses</option><?php foreach ($courses as $course): ?><option value="<?= (int) $course['id'] ?>"<?= $course_id === (string) $course['id'] ? ' selected' : '' ?>><?= e($course['code'] . ' - ' . $course['name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label for="semester">Semester</label><select id="semester" name="semester"><option value="">All semesters</option><?php for ($s = SEMESTER_MIN; $s <= SEMESTER_MAX; $s++): ?><option value="<?= $s ?>"<?= $semester === (string) $s ? ' selected' : '' ?>>Semester <?= $s ?></option><?php endfor; ?></select></div>
            <div class="form-group"><label for="file_type">Type</label><select id="file_type" name="file_type"><option value="">All types</option><?php foreach (FILE_TYPE_LABELS as $type => $label): ?><option value="<?= e($type) ?>"<?= $file_type === $type ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="form-actions"><button class="btn" type="submit">Search</button><a class="btn btn-secondary" href="<?= e(url('resources.php')) ?>">Clear</a></div>
    </form>
</div>
<div class="tools mt-3">
    <h2 class="mb-0"><?= (int) $pager['total'] ?> resources</h2>
    <form method="get" class="inline-form"><input type="hidden" name="q" value="<?= e($query) ?>"><input type="hidden" name="course_id" value="<?= e($course_id) ?>"><input type="hidden" name="semester" value="<?= e($semester) ?>"><input type="hidden" name="file_type" value="<?= e($file_type) ?>"><label for="sort" class="sr-only">Sort</label><select id="sort" name="sort" onchange="this.form.submit()"><option value="latest"<?= $sort === 'latest' ? ' selected' : '' ?>>Latest</option><option value="popular"<?= $sort === 'popular' ? ' selected' : '' ?>>Most downloaded</option><option value="rated"<?= $sort === 'rated' ? ' selected' : '' ?>>Highest rated</option></select></form>
</div>
<?php if (!$resources): ?><div class="empty-state">No published resources match your search.</div><?php else: ?><div class="grid"><?php foreach ($resources as $resource): ?><article class="card"><div class="file-cover <?= e($resource['file_type']) ?>"><?= e(strtoupper($resource['file_ext'])) ?></div><div class="card-body"><h3><a href="<?= e(url('resource_details.php?id=' . (int) $resource['id'])) ?>"><?= e($resource['title']) ?></a></h3><small class="muted"><?= e($resource['course_code']) ?> &middot; Semester <?= (int) $resource['semester'] ?></small><p class="resource-meta">Uploaded by <?= e($resource['uploader']) ?><?= $resource['uploader_role'] === 'teacher' ? ' ' . badge('✓ Verified teacher', 'success') : '' ?></p><small class="muted"><?= e(format_bytes($resource['file_size'])) ?> &middot; <?= e(format_date($resource['created_at'])) ?></small></div><div class="card-footer"><span class="rating">&#9733; <?= e(format_rating($resource['avg_rating'], $resource['rating_count'])) ?></span><span><?= (int) $resource['download_count'] ?> downloads</span></div></article><?php endforeach; ?></div><?= render_pagination($pager) ?><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
