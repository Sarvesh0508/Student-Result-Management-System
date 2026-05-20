<?php
include("../includes/connect.php");
$student_root = "../";
include("nav.php");

/*
 * CORE FIX: All stats must use only the LATEST attempt per course.
 */
$latest_subq = "
    SELECT r.*
    FROM result r
    INNER JOIN (
        SELECT course_id, MAX(attempt_no) AS max_attempt
        FROM result
        WHERE student_id = $sid
        GROUP BY course_id
    ) la ON r.course_id = la.course_id
        AND r.attempt_no = la.max_attempt
    WHERE r.student_id = $sid
";

// Stats based on latest attempts only
$stats_row = $conn->query("
    SELECT
        COUNT(*)                        AS total,
        SUM(result_status = 'PASS')     AS pass,
        SUM(result_status = 'FAIL')     AS fail,
        ROUND(AVG(marks), 1)            AS avg_marks,
        MAX(marks)                      AS highest,
        MIN(marks)                      AS lowest
    FROM ($latest_subq) AS latest
")->fetch_assoc();

$total   = $stats_row['total']     ?? 0;
$pass    = $stats_row['pass']      ?? 0;
$fail    = $stats_row['fail']      ?? 0;
$avg     = $stats_row['avg_marks'] ?? 0;
$highest = $stats_row['highest']   ?? 0;
$lowest  = $stats_row['lowest']    ?? 0;

$pass_perc = ($total > 0) ? round(($pass / $total) * 100) : 0;

// Low marks alert
$low = $conn->query("
    SELECT COUNT(*) c FROM ($latest_subq) AS latest
    WHERE marks < 50 AND result_status = 'FAIL'
")->fetch_assoc()['c'] ?? 0;

// CGPA from semester_result
$cgpa_row = $conn->query("
    SELECT cgpa, sgpa, semester FROM semester_result
    WHERE student_id=$sid ORDER BY semester DESC LIMIT 1
")->fetch_assoc();
$cgpa        = $cgpa_row['cgpa']     ?? 'N/A';
$sgpa        = $cgpa_row['sgpa']     ?? 'N/A';
$current_sem = $cgpa_row['semester'] ?? 1;

// Calculate Batch Year based on EARLIEST exam record
$earliest_exam = $conn->query("
    SELECT exam_month_year FROM result 
    WHERE student_id = $sid 
    ORDER BY exam_month_year ASC LIMIT 1
")->fetch_assoc()['exam_month_year'] ?? '';

$batch_year = $info['batch'] ?? 'Unknown';
if (!$batch_year || $batch_year === 'Unknown') {
    if ($earliest_exam) {
        $parts = explode('-', $earliest_exam);
        $batch_year = end($parts);
    }
}

// Rank calculation
$rank_q = $conn->query("
    SELECT student_id, ROUND(AVG(marks), 2) avg_m
    FROM (
        SELECT r.*
        FROM result r
        INNER JOIN (
            SELECT student_id, course_id, MAX(attempt_no) AS max_attempt
            FROM result
            GROUP BY student_id, course_id
        ) la ON r.student_id = la.student_id
            AND r.course_id  = la.course_id
            AND r.attempt_no = la.max_attempt
    ) AS all_latest
    GROUP BY student_id
    ORDER BY avg_m DESC
");
$total_students = $rank_q->num_rows;
$rank = 1;
while ($rr = $rank_q->fetch_assoc()) {
    if ($rr['student_id'] == $sid) break;
    $rank++;
}

// Chart data
$chart_q = $conn->query("
    SELECT c.course_title, r.marks, r.result_status
    FROM result r
    JOIN course c ON r.course_id = c.course_id
    WHERE r.student_id = $sid
      AND r.attempt_no = (
          SELECT MAX(r2.attempt_no)
          FROM result r2
          WHERE r2.student_id = r.student_id
            AND r2.course_id  = r.course_id
      )
    ORDER BY c.course_title
");
$c_labels = []; $c_marks = []; $c_colors = [];
while ($cr = $chart_q->fetch_assoc()) {
    $title      = strlen($cr['course_title']) > 12 ? substr($cr['course_title'], 0, 12) . '...' : $cr['course_title'];
    $c_labels[] = $title;
    $c_marks[]  = $cr['marks'];
    $c_colors[] = $cr['result_status'] === 'PASS' ? '#10b981' : '#ef4444';
}
$c_labels_json = json_encode($c_labels);
$c_marks_json  = json_encode($c_marks);
$c_colors_json = json_encode($c_colors);

// Recent results (latest attempt per course only)
$recent = $conn->query("
    SELECT r.*, c.course_title
    FROM ($latest_subq) r 
    JOIN course c ON r.course_id = c.course_id
    ORDER BY r.exam_month_year DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html>
<head>
<title>Dashboard - ScoreHive</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/student_layout.css?v=1.2">
<style>
    /* ── DASHBOARD UI SYNCED WITH TEACHER ── */
    .dash-header { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:30px; }
    .dash-title { margin:0; color:#1e293b; font-size:28px; font-weight:800; }
    .dash-subtitle { margin:5px 0 0; color:#64748b; font-size:15px; }

    .profile-banner {
        background: linear-gradient(135deg, #0f2027, #203a43);
        border-radius: 16px; padding: 25px; color: white; display:flex; align-items:center; gap:25px; margin-bottom: 30px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .profile-avatar-big { width:80px; height:80px; border-radius:50%; background:#ff8c00; display:flex; align-items:center; justify-content:center; font-size:32px; font-weight:800; box-shadow: 0 0 0 5px rgba(255,255,255,0.1); }
    .profile-info h2 { margin:0; font-size:24px; font-weight:800; }
    .profile-info p { margin:5px 0 0; opacity:0.8; font-size:14px; }

    /* Summary Cards */
    .summary-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:20px; margin-bottom:30px; }
    .sum-card { background:white; border-radius:16px; padding:24px; display:flex; align-items:center; gap:20px; box-shadow:0 4px 20px rgba(0,0,0,0.03); border:1px solid rgba(0,0,0,0.02); }
    .sum-icon { width:56px; height:56px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:24px; flex-shrink:0; }
    .bg-blue { background:#eff6ff; color:#3b82f6; }
    .bg-green { background:#f0fdf4; color:#10b981; }
    .bg-orange { background:#fff7ed; color:#f97316; }
    .bg-purple { background:#f5f3ff; color:#8b5cf6; }
    .sum-val { font-size:28px; font-weight:800; color:#1e293b; line-height:1; }
    .sum-lbl { font-size:12px; color:#64748b; font-weight:700; margin-top:5px; text-transform:uppercase; letter-spacing:0.5px; }

    /* Performance Grid */
    .ov-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:20px; margin-bottom:30px; }
    .ov-card { background:white; border-radius:16px; padding:20px; text-align:center; box-shadow:0 4px 20px rgba(0,0,0,0.03); border:1px solid rgba(0,0,0,0.02); position:relative; overflow:hidden; }
    .ov-card::before { content:''; position:absolute; top:0; left:0; width:100%; height:4px; }
    .border-pass::before { background:#10b981; }
    .border-fail::before { background:#ef4444; }
    .border-rank::before { background:#8b5cf6; }
    .ov-icon { font-size: 24px; margin-bottom: 10px; display:inline-block; }
    .ov-val { font-size:24px; font-weight:800; color:#1e293b; margin-bottom:4px; }
    .ov-lbl { font-size:12px; color:#64748b; font-weight:700; text-transform:uppercase; }

    /* Alerts */
    .alert-section { display: flex; flex-direction: column; gap: 12px; margin-bottom: 30px; }
    .alert-item { padding: 15px 20px; border-radius: 12px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 15px; border-left: 5px solid transparent; }
    .al-red { background: #fef2f2; color: #991b1b; border-left-color: #ef4444; }
    .al-yellow { background: #fffbeb; color: #92400e; border-left-color: #f59e0b; }
    .al-blue { background: #eff6ff; color: #1e40af; border-left-color: #3b82f6; }

    /* Tables */
    .table-box { background:white; border-radius:16px; padding:20px; box-shadow:0 4px 20px rgba(0,0,0,0.03); border:1px solid rgba(0,0,0,0.02); }
    .table-title { margin:0 0 15px; font-size:16px; font-weight:800; color:#1e293b; display:flex; align-items:center; gap:8px; }

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
    .delay-4 { animation-delay: 0.4s; }
</style>
</head>
<body>
<div class="main">

    <div class="dash-header animate-fade-up">
        <div>
            <div class="dash-title">Student Dashboard</div>
            <p class="dash-subtitle">Welcome back, <b><?= htmlspecialchars($sname) ?></b>!</p>
        </div>
        <div style="font-size:12px; color:#94a3b8; font-weight:700; text-transform:uppercase;"><?= date('l, d M Y') ?></div>
    </div>

    <!-- Profile Banner -->
    <div class="profile-banner animate-fade-up delay-1">
        <div class="profile-avatar-big"><?= $initials ?></div>
        <div class="profile-info">
            <h2><?= htmlspecialchars($sname) ?></h2>
            <p><?= $dept ?> Department &nbsp;|&nbsp; Semester <?= $current_sem ?></p>
            <div style="margin-top:10px; display:flex; gap:10px;">
                <span style="background:rgba(255,255,255,0.1); padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700;">🎓 <?= $batch_year ?></span>
                <span style="background:rgba(255,255,255,0.1); padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700;">CGPA: <?= $cgpa ?></span>
            </div>
        </div>
        <div style="margin-left:auto; text-align:right;">
            <div style="font-size:32px; font-weight:800; color:#ff8c00;"><?= $rank ?></div>
            <div style="font-size:11px; opacity:0.8; font-weight:700; text-transform:uppercase;">Class Rank</div>
        </div>
    </div>

    <!-- Alerts Section -->
    <div class="alert-section animate-fade-up delay-2">
        <?php if ($backlog_count > 0): ?>
        <div class="alert-item al-red">
            <span>⚠️</span> You have <b><?= $backlog_count ?></b> active backlog(s). <a href="backlogs.php" style="margin-left:auto; color:inherit;">View Details &rarr;</a>
        </div>
        <?php endif; ?>
        <?php if ($info['is_detained']): ?>
        <div class="alert-item al-red">
            <span>🚫</span> <b>DETAINED:</b> You are currently marked as detained. Please contact the department head immediately.
        </div>
        <?php endif; ?>
        <?php if ($mal_count > 0): ?>
        <div class="alert-item al-yellow">
            <span>🚫</span> <b><?= $mal_count ?></b> malpractice case(s) on record. <a href="malpractice.php" style="margin-left:auto; color:inherit;">View Details &rarr;</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Summary Grid -->
    <div class="summary-grid animate-fade-up delay-2">
        <div class="sum-card">
            <div class="sum-icon bg-blue">📚</div>
            <div>
                <div class="sum-val"><?= $total ?></div>
                <div class="sum-lbl">Total Subjects</div>
            </div>
        </div>
        <div class="sum-card">
            <div class="sum-icon bg-green">✅</div>
            <div>
                <div class="sum-val"><?= $pass ?></div>
                <div class="sum-lbl">Passed</div>
            </div>
        </div>
        <div class="sum-card">
            <div class="sum-icon bg-orange">📈</div>
            <div>
                <div class="sum-val"><?= $avg ?></div>
                <div class="sum-lbl">Avg Marks</div>
            </div>
        </div>
        <div class="sum-card">
            <div class="sum-icon bg-purple">🏆</div>
            <div>
                <div class="sum-val"><?= $cgpa ?></div>
                <div class="sum-lbl">Current CGPA</div>
            </div>
        </div>
    </div>

    <!-- Performance Overview -->
    <div class="ov-grid animate-fade-up delay-3">
        <div class="ov-card border-pass">
            <span class="ov-icon">✨</span>
            <div class="ov-val"><?= $highest ?></div>
            <div class="ov-lbl">Highest Mark</div>
        </div>
        <div class="ov-card border-fail">
            <span class="ov-icon">📉</span>
            <div class="ov-val"><?= $lowest ?></div>
            <div class="ov-lbl">Lowest Mark</div>
        </div>
        <div class="ov-card border-rank">
            <span class="ov-icon">🥇</span>
            <div class="ov-val">#<?= $rank ?></div>
            <div class="ov-lbl">Position</div>
        </div>
        <div class="ov-card border-pass">
            <span class="ov-icon">✔️</span>
            <div class="ov-val"><?= $pass_perc ?>%</div>
            <div class="ov-lbl">Pass Rate</div>
        </div>
    </div>

    <!-- Charts & Table Row -->
    <div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px; margin-bottom:30px;" class="animate-fade-up delay-4">
        <div class="table-box">
            <h3 class="table-title">📄 Recent Results</h3>
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:2px solid #f1f5f9;">
                        <th style="text-align:left; padding:12px; font-size:12px; color:#64748b;">SUBJECT</th>
                        <th style="text-align:center; padding:12px; font-size:12px; color:#64748b;">MARKS</th>
                        <th style="text-align:center; padding:12px; font-size:12px; color:#64748b;">GRADE</th>
                        <th style="text-align:center; padding:12px; font-size:12px; color:#64748b;">STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = $recent->fetch_assoc()): ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:12px; font-size:13px; font-weight:600;"><?= htmlspecialchars($r['course_title']) ?></td>
                        <td style="padding:12px; text-align:center; font-weight:700;"><?= $r['marks'] ?></td>
                        <td style="padding:12px; text-align:center;"><span class="badge badge-<?= strtolower($r['grade']) ?>"><?= $r['grade'] ?></span></td>
                        <td style="padding:12px; text-align:center;"><span class="badge badge-<?= strtolower($r['result_status']) ?>"><?= $r['result_status'] ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div style="margin-top:15px; text-align:right;">
                <a href="results.php" style="font-size:12px; font-weight:700; color:#3b82f6; text-decoration:none;">View All Results &rarr;</a>
            </div>
        </div>
        <div class="table-box">
            <h3 class="table-title">📊 Marks Split</h3>
            <canvas id="passFailChart" height="250"></canvas>
        </div>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('passFailChart'), {
    type: 'doughnut',
    data: {
        labels:['Pass','Fail'],
        datasets:[{
            data:[<?= $pass ?>, <?= $fail ?>],
            backgroundColor:['#10b981','#ef4444'],
            borderWidth:0,
            hoverOffset: 6
        }]
    },
    options:{
        plugins:{ legend:{ position:'bottom', labels:{ font:{ family:'Inter', size:11, weight:600 } } } },
        cutout:'70%'
    }
});
</script>
</body>
</html>


