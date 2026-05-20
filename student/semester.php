<?php
include("../includes/connect.php");
$student_root = "../";
include("nav.php");

$sel_sem = intval($_GET['sem'] ?? 0);

// Get all semesters this student has results in
$sems_q = $conn->query("
    SELECT DISTINCT c.semester FROM result r
    JOIN course c ON r.course_id = c.course_id
    WHERE r.student_id = $sid ORDER BY c.semester
");
$semesters = [];
while ($s = $sems_q->fetch_assoc()) $semesters[] = $s['semester'];

if (!$sel_sem && count($semesters) > 0) $sel_sem = $semesters[0];
?>
<!DOCTYPE html>
<html>
<head>
<title>Semester Results - ScoreHive</title>
<link rel="stylesheet" href="../assets/css/student_layout.css?v=1.2">
<style>
.marks-bar-wrap { display:flex; align-items:center; gap:7px; }
.marks-bar { width:50px; height:6px; border-radius:3px; background:#e5e7eb; overflow:hidden; flex-shrink:0; }
.marks-bar-fill { height:100%; border-radius:3px; }
.badge { display:inline-flex; align-items:center; justify-content:center;
         padding:3px 10px; border-radius:12px; font-size:11px; font-weight:600; }
.badge-o       { background:#ede9fe; color:#5b21b6; }
.badge-a       { background:#d1fae5; color:#065f46; }
.badge-b       { background:#dbeafe; color:#1e40af; }
.badge-c       { background:#fef3c7; color:#92400e; }
.badge-f       { background:#fee2e2; color:#991b1b; }
.badge-pass    { background:#dcfce7; color:#15803d; }
.badge-fail    { background:#fee2e2; color:#991b1b; }

/* ── Semester Tabs ── */
.sem-tabs {
    display: flex; gap: 12px; margin-bottom: 25px;
    overflow-x: auto; padding-bottom: 5px;
}
.sem-tab {
    padding: 12px 24px; background: white; border-radius: 12px;
    color: #64748b; text-decoration: none; font-weight: 700; font-size: 14px;
    border: 1px solid #e2e8f0; transition: all 0.2s ease;
    white-space: nowrap; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
.sem-tab:hover {
    color: #ff8c00; border-color: #ff8c00;
    transform: translateY(-2px); box-shadow: 0 4px 10px rgba(255, 140, 0, 0.15);
}
.sem-tab.active {
    background: #ff8c00; color: white; border-color: #ff8c00;
    box-shadow: 0 6px 15px rgba(255, 140, 0, 0.3);
}

/* ── Animations ── */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-up {
    animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    opacity: 0;
}
.delay-1 { animation-delay: 0.1s; }
.delay-2 { animation-delay: 0.2s; }
.delay-3 { animation-delay: 0.3s; }
</style>
</head>
<body>
<div class="main">
<div class="page-title animate-fade-up">Semester-wise Results</div>

<!-- Semester Tabs -->
<div class="sem-tabs animate-fade-up delay-1">
    <?php foreach ($semesters as $s): ?>
    <a href="?sem=<?= $s ?>"
       class="sem-tab <?= $sel_sem == $s ? 'active' : '' ?>">
        Semester <?= $s ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if ($sel_sem > 0):

    // ── SGPA / CGPA from semester_result table ──
    $sem_info = $conn->query(
        "SELECT * FROM semester_result
         WHERE student_id = $sid AND semester = $sel_sem"
    )->fetch_assoc();

    // ── Backlog count: ACTIVE backlogs for this semester only ──
    $backlog_res = $conn->query("
        SELECT COUNT(*) AS cnt FROM result r
        JOIN course c ON r.course_id = c.course_id
        WHERE r.student_id = $sid
          AND c.semester = $sel_sem
          AND r.backlog_status = 'ACTIVE'
    ");
    $backlog_count = $backlog_res ? (int)$backlog_res->fetch_assoc()['cnt'] : 0;

    // ── Latest attempt per course only ──
    $results = $conn->query("
        SELECT r.result_id, r.marks, r.grade, r.attempt_no,
               r.result_status, r.backlog_status, r.exam_month_year,
               c.course_title, c.course_code, c.credit
        FROM result r
        JOIN course c ON r.course_id = c.course_id
        INNER JOIN (
            SELECT course_id, MAX(attempt_no) AS max_attempt
            FROM result
            WHERE student_id = $sid
            GROUP BY course_id
        ) latest ON r.course_id = latest.course_id
                AND r.attempt_no = latest.max_attempt
                AND r.student_id = $sid
        WHERE c.semester = $sel_sem
        ORDER BY c.course_title
    ");
?>

<!-- Semester Summary Cards -->
<div class="stat-grid stat-grid-4 animate-fade-up delay-2" style="margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-icon blue">📅</div>
        <div>
            <div class="stat-val"><?= $sel_sem ?></div>
            <div class="stat-lbl">Semester</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">⭐</div>
        <div>
            <div class="stat-val" style="color:#ff8c00;">
                <?= $sem_info ? $sem_info['sgpa'] : '-' ?>
            </div>
            <div class="stat-lbl">SGPA</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">📊</div>
        <div>
            <div class="stat-val" style="color:#6f42c1;">
                <?= $sem_info ? $sem_info['cgpa'] : '-' ?>
            </div>
            <div class="stat-lbl">CGPA</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">⚠</div>
        <div>
            <div class="stat-val" style="color:<?= $backlog_count > 0 ? '#dc3545' : '#28a745' ?>;">
                <?= $backlog_count ?>
            </div>
            <div class="stat-lbl">Backlogs</div>
        </div>
    </div>
</div>

<!-- Results Table -->
<div class="card animate-fade-up delay-3">
    <h3>📄 Semester <?= $sel_sem ?> — Subject Results</h3>
    <div class="table-wrap">
    <table>
        <tr>
            <th>#</th><th>Subject</th><th>Code</th>
            <th>Credits</th><th>Marks</th><th>Grade</th>
            <th>Status</th><th>Attempt</th><th>Exam</th>
        </tr>
        <?php
        $i           = 1;
        $total_marks = 0; $count = 0;
        $pass_count  = 0; $fail_count = 0;
        $all_rows    = [];
        while ($r = $results->fetch_assoc()) {
            $all_rows[]   = $r;
            $total_marks += $r['marks'];
            $count++;
            if ($r['result_status'] === 'PASS') $pass_count++;
            else $fail_count++;
        }
        foreach ($all_rows as $r):
            $pct       = (float)$r['marks'];
            $bar_color = $pct >= 75 ? '#28a745' : ($pct >= 50 ? '#ffc107' : '#dc3545');
        ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><b><?= htmlspecialchars($r['course_title']) ?></b></td>
            <td style="color:#888;font-size:12px;"><?= htmlspecialchars($r['course_code']) ?></td>
            <td style="text-align:center;"><?= (int)$r['credit'] ?></td>
            <td>
                <div class="marks-bar-wrap">
                    <span style="min-width:36px;font-weight:bold;color:<?= $bar_color ?>;">
                        <?= number_format($pct, 2) ?>
                    </span>
                    <div class="marks-bar">
                        <div class="marks-bar-fill"
                             style="width:<?= $pct ?>%;background:<?= $bar_color ?>;"></div>
                    </div>
                </div>
            </td>
            <td><span class="badge badge-<?= strtolower(htmlspecialchars($r['grade'])) ?>">
                <?= htmlspecialchars($r['grade']) ?></span></td>
            <td><span class="badge badge-<?= strtolower(htmlspecialchars($r['result_status'])) ?>">
                <?= htmlspecialchars($r['result_status']) ?></span></td>
            <td style="text-align:center;"><?= (int)$r['attempt_no'] ?></td>
            <td style="font-size:12px;"><?= htmlspecialchars($r['exam_month_year'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>

        <!-- Summary Row -->
        <?php if ($count > 0): ?>
        <tr style="background:#f8f9fa;font-weight:bold;">
            <td colspan="4" style="text-align:right;color:#555;">Summary:</td>
            <td style="color:#007bff;">Avg: <?= round($total_marks / $count, 1) ?></td>
            <td></td>
            <td>
                <span style="color:#28a745;"><?= $pass_count ?> Pass</span> /
                <span style="color:#dc3545;"><?= $fail_count ?> Fail</span>
            </td>
            <td colspan="2"></td>
        </tr>
        <?php endif; ?>
    </table>
    </div>
</div>

<?php endif; ?>
</div>
</body>
</html>


