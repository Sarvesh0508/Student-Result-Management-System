<?php
include("../includes/connect.php");
$student_root = "../";
include("nav.php");

$filter_sem    = $_GET['semester'] ?? '';
$filter_status = $_GET['status']   ?? '';
$search        = $_GET['search']   ?? '';

// ── Get semesters that have results ──
$sems_res = $conn->query(
    "SELECT DISTINCT c.semester FROM result r
     JOIN course c ON r.course_id = c.course_id
     WHERE r.student_id = $sid ORDER BY c.semester"
);
$completed_sems = [];
if ($sems_res) {
    while ($s = $sems_res->fetch_assoc()) {
        $completed_sems[] = (int)$s['semester'];
    }
}

$max_sem = 8;

// ── Build WHERE for outer query ──
$extra = "";
if ($filter_sem)    $extra .= " AND c.semester = " . intval($filter_sem);
if ($filter_status) $extra .= " AND r.result_status = '" . $conn->real_escape_string($filter_status) . "'";
if ($search)        $extra .= " AND c.course_title LIKE '%" . $conn->real_escape_string($search) . "%'";

// ── Latest attempt per course only ──
$rows = $conn->query("
    SELECT r.result_id, r.student_id, r.course_id, r.marks, r.grade,
           r.attempt_no, r.result_status, r.backlog_status,
           r.exam_month_year, r.faculty_id,
           c.course_title, c.course_code, c.semester, c.credit,
           f.faculty_name
    FROM result r
    JOIN course c ON r.course_id = c.course_id
    LEFT JOIN faculty f ON r.faculty_id = f.faculty_id
    INNER JOIN (
        SELECT course_id, MAX(attempt_no) AS max_attempt
        FROM result
        WHERE student_id = $sid
        GROUP BY course_id
    ) latest ON r.course_id = latest.course_id
            AND r.attempt_no = latest.max_attempt
            AND r.student_id = $sid
    WHERE 1=1 $extra
    ORDER BY c.semester, c.course_title
");
?>
<!DOCTYPE html>
<html>
<head>
<title>My Results - ScoreHive</title>
<link rel="stylesheet" href="../assets/css/student_layout.css?v=1.2">
<style>
.sem-tabs { display:flex; flex-wrap:wrap; gap:7px; margin-bottom:18px; }
.sem-tab {
    padding:6px 14px; border-radius:20px; font-size:12px;
    border:1px solid #d1d5db; color:#6b7280; background:#fff;
    text-decoration:none; transition:all 0.15s; user-select:none; display:inline-block;
}
.sem-tab:hover { border-color:#7c3aed; color:#7c3aed; }
.sem-tab.active { background:#1a1f2e; color:#fff !important; border-color:#1a1f2e; }
.sem-tab.no-data { opacity:0.45; }
.sem-tab.no-data:hover { border-color:#d1d5db; color:#6b7280; cursor:default; }
.marks-bar-wrap { display:flex; align-items:center; gap:7px; }
.marks-bar { width:50px; height:6px; border-radius:3px; background:#e5e7eb; overflow:hidden; flex-shrink:0; }
.marks-bar-fill { height:100%; border-radius:3px; }
.badge { display:inline-flex; align-items:center; justify-content:center; padding:3px 10px; border-radius:12px; font-size:11px; font-weight:600; }
.badge-o       { background:#ede9fe; color:#5b21b6; }
.badge-a       { background:#d1fae5; color:#065f46; }
.badge-b       { background:#dbeafe; color:#1e40af; }
.badge-c       { background:#fef3c7; color:#92400e; }
.badge-f       { background:#fee2e2; color:#991b1b; }
.badge-pass    { background:#dcfce7; color:#15803d; }
.badge-fail    { background:#fee2e2; color:#991b1b; }
.badge-none    { background:#f3f4f6; color:#6b7280; }
.badge-cleared { background:#dcfce7; color:#15803d; }
.badge-active  { background:#fee2e2; color:#991b1b; }
@media print { .no-print { display:none !important; } }
</style>
</head>
<body>
<div class="main">

<div class="page-title">
    My Results
    <a href="marksheet.php" class="btn btn-print no-print">🖨 Print Marksheet</a>
</div>

<!-- Semester Tabs -->
<div class="sem-tabs no-print">
    <a href="results.php"
       class="sem-tab <?= ($filter_sem === '') ? 'active' : '' ?>">All</a>
    <?php for ($sem = 1; $sem <= $max_sem; $sem++):
        $has_data  = in_array($sem, $completed_sems);
        $is_active = ((int)$filter_sem === $sem);
    ?>
        <a href="results.php?semester=<?= $sem ?>"
           class="sem-tab <?= $is_active ? 'active' : '' ?> <?= !$has_data ? 'no-data' : '' ?>">
            Sem <?= $sem ?>
        </a>
    <?php endfor; ?>
</div>

<!-- Search + Filter -->
<div style="display:flex;gap:10px;margin-bottom:15px;flex-wrap:wrap;" class="no-print">
    <form method="GET" style="display:flex;gap:10px;flex:1;flex-wrap:wrap;">
        <?php if ($filter_sem): ?>
        <input type="hidden" name="semester" value="<?= $filter_sem ?>">
        <?php endif; ?>
        <input type="text" name="search" placeholder="Search subject..."
               value="<?= htmlspecialchars($search) ?>"
               style="flex:1;min-width:180px;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:13px;">
        <select name="status" style="padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:13px;">
            <option value="">All Status</option>
            <option value="PASS" <?= $filter_status==='PASS'?'selected':'' ?>>Pass Only</option>
            <option value="FAIL" <?= $filter_status==='FAIL'?'selected':'' ?>>Fail Only</option>
        </select>
        <button type="submit" class="btn btn-orange">Search</button>
        <a href="results.php" class="btn btn-dark">Reset</a>
    </form>
</div>

<!-- Results Table -->
<div class="card">
<div class="table-wrap">
<table>
    <tr>
        <th>#</th><th>Subject</th><th>Code</th><th>Sem</th><th>Credits</th>
        <th>Marks / 100</th><th>Grade</th><th>Status</th><th>Attempt</th>
        <th>Backlog</th><th>Faculty</th><th>Exam</th>
    </tr>
    <?php
    $i = 1; $row_count = 0;
    while ($r = $rows->fetch_assoc()):
        $row_count++;
        $pct       = (int)$r['marks'];
        $bar_color = $pct >= 75 ? '#28a745' : ($pct >= 50 ? '#ffc107' : '#dc3545');
        $backlog_val   = strtolower(trim($r['backlog_status'] ?? ''));
        $backlog_class = ($backlog_val === 'cleared') ? 'cleared' : (($backlog_val === 'active') ? 'active' : 'none');
        $backlog_label = strtoupper($r['backlog_status'] ?? 'NONE');
        $faculty = trim($r['faculty_name'] ?? '');
        if ($faculty === '') $faculty = '-';
    ?>
    <tr>
        <td><?= $i++ ?></td>
        <td><b><?= htmlspecialchars($r['course_title']) ?></b></td>
        <td style="color:#888;font-size:12px;"><?= htmlspecialchars($r['course_code']) ?></td>
        <td>Sem <?= (int)$r['semester'] ?></td>
        <td style="text-align:center;"><?= (int)$r['credit'] ?></td>
        <td>
            <div class="marks-bar-wrap">
                <span style="min-width:36px;font-weight:bold;color:<?= $bar_color ?>;"><?= $pct ?>.00</span>
                <div class="marks-bar">
                    <div class="marks-bar-fill" style="width:<?= $pct ?>%;background:<?= $bar_color ?>;"></div>
                </div>
            </div>
        </td>
        <td><span class="badge badge-<?= strtolower(htmlspecialchars($r['grade'])) ?>"><?= htmlspecialchars($r['grade']) ?></span></td>
        <td><span class="badge badge-<?= strtolower(htmlspecialchars($r['result_status'])) ?>"><?= htmlspecialchars($r['result_status']) ?></span></td>
        <td style="text-align:center;"><?= (int)$r['attempt_no'] ?></td>
        <td><span class="badge badge-<?= $backlog_class ?>"><?= htmlspecialchars($backlog_label) ?></span></td>
        <td style="font-size:12px;color:#555;"><?= htmlspecialchars($faculty) ?></td>
        <td style="font-size:12px;"><?= htmlspecialchars($r['exam_month_year'] ?? '') ?></td>
    </tr>
    <?php endwhile; ?>
    <?php if ($row_count === 0): ?>
    <tr>
        <td colspan="12" style="text-align:center;color:#888;padding:30px;">No results found.</td>
    </tr>
    <?php endif; ?>
</table>
</div>
</div>

</div>
</body>
</html>


