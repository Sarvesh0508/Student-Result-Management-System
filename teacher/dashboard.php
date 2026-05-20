<?php
include("../includes/connect.php");
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php"); exit();
}
$root = "../";

/* ── Data ── */
$students  = $conn->query("SELECT COUNT(*) c FROM student")->fetch_assoc()['c'];
$courses   = $conn->query("SELECT COUNT(*) c FROM course")->fetch_assoc()['c'];
$results   = $conn->query("SELECT COUNT(*) c FROM result")->fetch_assoc()['c'];
$pass      = $conn->query("SELECT COUNT(*) c FROM result WHERE result_status='PASS'")->fetch_assoc()['c'];
$fail      = $conn->query("SELECT COUNT(*) c FROM result WHERE result_status='FAIL'")->fetch_assoc()['c'];
$backlog   = $conn->query("SELECT COUNT(*) c FROM result WHERE backlog_status='ACTIVE'")->fetch_assoc()['c'];
$mal       = $conn->query("SELECT COUNT(*) c FROM malpractice")->fetch_assoc()['c'];
$low       = $conn->query("
    SELECT COUNT(DISTINCT r.student_id) c 
    FROM result r
    JOIN (SELECT student_id, course_id, MAX(attempt_no) as max_attempt FROM result GROUP BY student_id, course_id) rm 
      ON r.student_id = rm.student_id AND r.course_id = rm.course_id AND r.attempt_no = rm.max_attempt
    WHERE r.marks < 50
")->fetch_assoc()['c'];
$o_grade   = $conn->query("SELECT COUNT(*) c FROM result WHERE grade='O'")->fetch_assoc()['c'];
$pending   = $conn->query("SELECT COUNT(DISTINCT e.student_id, e.course_id) c FROM enrollment e LEFT JOIN result r ON e.student_id=r.student_id AND e.course_id=r.course_id WHERE r.result_id IS NULL")->fetch_assoc()['c'];

// NEW METRICS
$first_attempt_pass = $conn->query("SELECT COUNT(*) c FROM result WHERE attempt_no=1 AND result_status='PASS'")->fetch_assoc()['c'];
$total_grades = $conn->query("SELECT COUNT(*) c FROM result WHERE grade IS NOT NULL AND grade != ''")->fetch_assoc()['c'];

$pass_pct  = $results > 0 ? round(($pass/$results)*100) : 0;
$fail_pct  = $results > 0 ? round(($fail/$results)*100) : 0;

/* Topper */
$topper = $conn->query("
    SELECT s.name, ROUND(AVG(r.marks),1) avg_m
    FROM result r JOIN student s ON r.student_id=s.student_id
    GROUP BY r.student_id ORDER BY avg_m DESC LIMIT 1
")->fetch_assoc();

/* Grade chart data */
$gq = $conn->query("SELECT grade, COUNT(*) c FROM result GROUP BY grade ORDER BY grade");
$g_labels=[]; $g_data=[];
while($g=$gq->fetch_assoc()){ $g_labels[]=$g['grade']; $g_data[]=(int)$g['c']; }

/* Recent results */
$recent = $conn->query("
    SELECT s.name, c.course_title, r.marks, r.grade, r.result_status, r.exam_month_year
    FROM result r
    JOIN student s ON r.student_id=s.student_id
    JOIN course c ON r.course_id=c.course_id
    ORDER BY r.result_id DESC LIMIT 5
");

// Greeting
$hour = date('H');
$greeting = 'Good evening';
if ($hour < 12) $greeting = 'Good morning';
elseif ($hour < 17) $greeting = 'Good afternoon';
?>
<!DOCTYPE html>
<html>
<head>
<title>ScoreHive - Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/layout.css?v=1.2">
<style>
* { box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; background-color: #f4f7f6; }

/* ── Typography & Headers ── */
.dash-header { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:30px; }
.dash-title { margin:0; color:#1e293b; font-size:32px; font-weight:800; letter-spacing:-0.5px; }
.dash-subtitle { margin:8px 0 0; color:#64748b; font-size:16px; font-weight:400; }
.dash-subtitle strong { color:#334155; font-weight:600; }
.dash-date { color:#94a3b8; font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:1.2px; margin-bottom:10px; display:block; }

.btn-modern {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    color: white !important; border: none; padding: 12px 24px;
    border-radius: 10px; font-weight: 600; font-size: 14px;
    box-shadow: 0 4px 15px rgba(15, 23, 42, 0.15);
    transition: all 0.2s ease; display: inline-block; text-decoration: none;
}
.btn-modern:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(15, 23, 42, 0.2); }

/* ── Summary Cards ── */
.summary-grid {
    display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:20px; margin-bottom:30px;
}
.sum-card {
    background:white; border-radius:16px; padding:24px; display:flex; align-items:center; gap:20px;
    box-shadow:0 4px 20px rgba(0,0,0,0.03); border:1px solid rgba(0,0,0,0.02); transition:transform 0.2s ease;
}
.sum-card:hover { transform:translateY(-4px); box-shadow:0 12px 30px rgba(0,0,0,0.06); }
.sum-icon {
    width:64px; height:64px; border-radius:18px; display:flex; align-items:center; justify-content:center; font-size:28px; flex-shrink:0;
}
.bg-gradient-blue { background:linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%); }
.bg-gradient-orange { background:linear-gradient(135deg, #f6d365 0%, #fda085 100%); }
.bg-gradient-green { background:linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); }
.bg-gradient-red { background:linear-gradient(135deg, #ff9a9e 0%, #fecfef 99%, #fecfef 100%); }

.sum-val { font-size:36px; font-weight:800; color:#1e293b; line-height:1.1; }
.sum-lbl { font-size:13px; color:#64748b; font-weight:600; margin-top:6px; text-transform:uppercase; letter-spacing:0.8px; }

/* ── Result Overview ── */
.ov-grid {
    display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:30px;
}
.ov-card {
    background:white; border-radius:16px; padding:24px 20px; text-align:center;
    box-shadow:0 4px 20px rgba(0,0,0,0.03); border:1px solid rgba(0,0,0,0.02);
    position:relative; overflow:hidden; transition:transform 0.2s ease;
}
.ov-card:hover { transform:translateY(-3px); }
.ov-card::before {
    content:''; position:absolute; top:0; left:0; width:100%; height:4px;
}
.border-pass::before { background:#10b981; }
.border-fail::before { background:#ef4444; }
.border-warn::before { background:#f59e0b; }
.border-info::before { background:#3b82f6; }
.border-purple::before { background:#8b5cf6; }

.ov-icon { font-size: 32px; margin-bottom: 15px; display:inline-block; }
.ov-val { font-size:28px; font-weight:800; color:#1e293b; margin-bottom:6px; }
.ov-lbl { font-size:14px; color:#475569; font-weight:700; margin-bottom:8px; }
.ov-sub { font-size:13px; color:#94a3b8; font-weight:500; }

.progress-bg { margin-top:15px; background:#f1f5f9; border-radius:6px; height:6px; overflow:hidden; }
.progress-bar { height:100%; border-radius:6px; }

/* ── Alerts ── */
.alert-section { display: flex; flex-direction: column; gap: 15px; margin-bottom: 30px; }
.alert-item {
    padding: 18px 24px; border-radius: 12px; font-size: 14.5px; font-weight: 600;
    display: flex; justify-content: space-between; align-items: center;
}
.al-red { background: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444; }
.al-yellow { background: #fffbeb; color: #92400e; border-left: 4px solid #f59e0b; }
.al-blue { background: #eff6ff; color: #1e40af; border-left: 4px solid #3b82f6; }
.alert-item a {
    text-decoration: none; font-size: 13px; font-weight: 700; padding: 6px 12px;
    border-radius: 6px; transition: 0.2s;
}
.alert-icon {
    display: flex; align-items: center; justify-content: center;
    width: 36px; height: 36px; border-radius: 10px; margin-right: 14px;
    font-size: 18px; flex-shrink: 0;
}
.al-blue .alert-icon { background: rgba(59,130,246,0.15); }
.al-red .alert-icon { background: rgba(239,68,68,0.15); }
.al-yellow .alert-icon { background: rgba(245,158,11,0.15); }
.alert-content { display: flex; align-items: center; }
.al-red a  { color:#ef4444; background:rgba(239,68,68,0.1); }
.al-red a:hover { background:rgba(239,68,68,0.2); }
.al-yellow a { color:#d97706; background:rgba(245,158,11,0.1); }
.al-yellow a:hover { background:rgba(245,158,11,0.2); }
.al-blue a { color:#3b82f6; background:rgba(59,130,246,0.1); }
.al-blue a:hover { background:rgba(59,130,246,0.2); }
.al-green  { background:#f0fdf4; border-left-color:#10b981; color:#166534; padding:18px 24px; border-radius:12px; margin-bottom:30px; font-weight:600; }

/* ── Topper ── */
.topper-bar {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 16px; padding: 24px 30px; color: white; display:flex; align-items:center; justify-content:space-between;
    box-shadow: 0 10px 25px rgba(99,102,241,0.2); margin-bottom: 30px;
}
.topper-title { font-size: 20px; font-weight: 800; margin-bottom: 5px; }
.topper-sub { font-size: 14px; font-weight: 500; opacity: 0.9; }
.topper-sub b { color:#fcd34d; font-size:16px; }

/* ── Charts ── */
.charts-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(350px, 1fr)); gap:20px; margin-bottom:30px; }
.chart-card {
    background:white; border-radius:16px; padding:24px; box-shadow:0 4px 20px rgba(0,0,0,0.03); border:1px solid rgba(0,0,0,0.02);
}
.chart-title { margin:0 0 20px; font-size:16px; color:#1e293b; font-weight:800; display:flex; align-items:center; gap:8px; }

/* ── Recent Results ── */
.recent-card {
    background:white; border-radius:16px; padding:24px; box-shadow:0 4px 20px rgba(0,0,0,0.03); border:1px solid rgba(0,0,0,0.02); margin-bottom:40px;
}
.recent-card h4 {
    margin:0 0 20px; font-size:18px; color:#1e293b; font-weight:800; display:flex; justify-content:space-between; align-items:center;
}
.recent-card table { width:100%; border-collapse:separate; border-spacing:0; }
.recent-card th { padding:14px 16px; text-align:left; font-size:13px; color:#64748b; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; border-bottom:2px solid #f1f5f9; }
.recent-card td { padding:16px; font-size:14px; font-weight:500; color:#334155; border-bottom:1px solid #f1f5f9; }
.recent-card tr:last-child td { border-bottom:none; }
.recent-card tr { transition:background 0.2s; }
.recent-card tr:hover td { background:#f8fafc; }

.badge { display:inline-flex; align-items:center; justify-content:center; padding:6px 12px; border-radius:20px; font-size:12px; font-weight:700; letter-spacing:0.5px; }
.badge-pass { background:#dcfce7; color:#166534; }
.badge-fail { background:#fee2e2; color:#991b1b; }

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

/* ── Teacher Max UI Styles ── */
.dash-title {
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-size: 32px;
}
.sum-card, .ov-card, .chart-card, .recent-card {
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    transition: all 0.3s ease;
}
.sum-card:hover, .ov-card:hover, .chart-card:hover, .recent-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
}
</style>
</head>
<body>
<?php include("../includes/header.php"); ?>
<div class="main">

<!-- Page Title -->
<div class="dash-header animate-fade-up">
    <div>
        <h2 class="dash-title">Dashboard</h2>
        <p class="dash-subtitle">
            <?= $greeting ?>, <strong>ADMIN</strong>! Here's an overview of your classes.
        </p>
    </div>
    <div style="text-align:right;">
        <span class="dash-date"><?= date('l, F j, Y') ?></span>
        <a href="reports/index.php" class="btn-modern">
            📊 View Full Reports
        </a>
    </div>
</div>

<!-- ══ ALERTS ══ -->
<div class="alert-section animate-fade-up delay-1">
<?php if ($pending > 0): ?>
<div class="alert-item al-blue">
    <div class="alert-content">
        <div class="alert-icon">⏳</div>
        <span><b><?= $pending ?></b> results are currently pending — they have not been entered yet.</span>
    </div>
    <a href="results/enter.php">Enter Results →</a>
</div>
<?php endif; ?>

<?php if ($backlog > 0): ?>
<div class="alert-item al-red">
    <div class="alert-content">
        <div class="alert-icon">⚠️</div>
        <span><b><?= $backlog ?></b> active backlog(s) need attention.</span>
    </div>
    <a href="results/backlogs.php">View Backlogs →</a>
</div>
<?php endif; ?>

<?php if ($low > 0): ?>
<div class="alert-item al-yellow">
    <div class="alert-content">
        <div class="alert-icon">📉</div>
        <span><b><?= $low ?></b> student(s) scored below 50 marks and may need support.</span>
    </div>
    <a href="results/view.php?status=FAIL">View Details →</a>
</div>
<?php endif; ?>

<?php if ($mal > 0): ?>
<div class="alert-item al-red">
    <div class="alert-content">
        <div class="alert-icon">🚫</div>
        <span><b><?= $mal ?></b> malpractice case(s) recorded.</span>
    </div>
    <a href="malpractice/list.php">View Cases →</a>
</div>
<?php endif; ?>
</div>

<?php if ($pending==0 && $backlog==0 && $low==0): ?>
<div class="alert-item al-green" style="border-left-color:#10b981;">
    <span>✨ Everything is up to date. You have no pending actions!</span>
</div>
<?php endif; ?>
<!-- Closing alert-section is above at line 260, no extra div needed here -->

<!-- ══ SUMMARY CARDS ══ -->
<div class="summary-grid animate-fade-up delay-1">
    <div class="sum-card">
        <div class="sum-icon bg-gradient-blue">👨‍🎓</div>
        <div>
            <div class="sum-val"><?= $students ?></div>
            <div class="sum-lbl">Total Students</div>
        </div>
    </div>
    <div class="sum-card">
        <div class="sum-icon bg-gradient-orange">📚</div>
        <div>
            <div class="sum-val"><?= $courses ?></div>
            <div class="sum-lbl">Total Courses</div>
        </div>
    </div>
    <div class="sum-card">
        <div class="sum-icon bg-gradient-green">📑</div>
        <div>
            <div class="sum-val"><?= $results ?></div>
            <div class="sum-lbl">Results Entered</div>
        </div>
    </div>
    <div class="sum-card">
        <div class="sum-icon bg-gradient-red">⏳</div>
        <div>
            <div class="sum-val"><?= $pending ?></div>
            <div class="sum-lbl">Results Pending</div>
        </div>
    </div>
</div>

<!-- ══ RESULT OVERVIEW ══ -->
<div class="ov-grid animate-fade-up delay-2">
    <!-- Card 1: Passed -->
    <div class="ov-card border-pass">
        <div class="ov-icon">🎉</div>
        <div class="ov-val"><?= $pass ?></div>
        <div class="ov-lbl">Students Passed</div>
        <div class="ov-sub"><?= $pass_pct ?>% pass rate</div>
        <div class="progress-bg">
            <div class="progress-bar" style="width:<?= $pass_pct ?>%;background:#10b981;"></div>
        </div>
    </div>
    <!-- Card 2: Failed -->
    <div class="ov-card border-fail">
        <div class="ov-icon">📉</div>
        <div class="ov-val"><?= $fail ?></div>
        <div class="ov-lbl">Students Failed</div>
        <div class="ov-sub"><?= $fail_pct ?>% fail rate</div>
        <div class="progress-bg">
            <div class="progress-bar" style="width:<?= $fail_pct ?>%;background:#ef4444;"></div>
        </div>
    </div>
    <!-- Card 3: Active Backlogs -->
    <div class="ov-card border-warn">
        <div class="ov-icon">⚠️</div>
        <div class="ov-val"><?= $backlog ?></div>
        <div class="ov-lbl">Active Backlogs</div>
        <div class="ov-sub"><?= $results>0?round(($backlog/$results)*100):0 ?>% of all exams</div>
        <div class="progress-bg">
            <div class="progress-bar" style="width:<?= $results>0?round(($backlog/$results)*100):0 ?>%;background:#f59e0b;"></div>
        </div>
    </div>
    <!-- Card 4: First Attempt Clears (NEW) -->
    <div class="ov-card border-info">
        <div class="ov-icon">🚀</div>
        <div class="ov-val"><?= $first_attempt_pass ?></div>
        <div class="ov-lbl">First Attempt Clears</div>
        <div class="ov-sub"><?= $results>0?round(($first_attempt_pass/$results)*100):0 ?>% clear rate</div>
        <div class="progress-bg">
            <div class="progress-bar" style="width:<?= $results>0?round(($first_attempt_pass/$results)*100):0 ?>%;background:#3b82f6;"></div>
        </div>
    </div>
    <!-- Card 5: Top Grades ('O') -->
    <div class="ov-card border-purple">
        <div class="ov-icon">🏅</div>
        <div class="ov-val"><?= $o_grade ?></div>
        <div class="ov-lbl">'O' Grades Awarded</div>
        <div class="ov-sub">Outstanding performances</div>
        <div class="progress-bg">
            <div class="progress-bar" style="width:<?= $results>0?round(($o_grade/$results)*100):0 ?>%;background:#8b5cf6;"></div>
        </div>
    </div>
</div>

<!-- ══ QUICK ACTIONS ══ -->
<div style="margin-bottom:30px;" class="animate-fade-up delay-2">
    <div style="font-size:13px; font-weight:700; color:#64748b; margin-bottom:15px; text-transform:uppercase; letter-spacing:1.2px;">Quick Actions</div>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:15px;">
        <a href="results/enter.php" style="text-decoration:none; background:white; padding:20px; border-radius:18px; display:flex; align-items:center; gap:15px; box-shadow:0 4px 15px rgba(0,0,0,0.03); border:1px solid rgba(0,0,0,0.02); transition:all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 25px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.03)';">
            <div style="width:45px; height:45px; border-radius:14px; background:rgba(59, 130, 246, 0.1); display:flex; align-items:center; justify-content:center; font-size:20px;">📝</div>
            <div>
                <div style="font-weight:700; color:#1e293b; font-size:14px;">Enter Results</div>
                <div style="font-size:12px; color:#64748b;">Add new marks</div>
            </div>
        </a>
        <a href="students/add.php" style="text-decoration:none; background:white; padding:20px; border-radius:18px; display:flex; align-items:center; gap:15px; box-shadow:0 4px 15px rgba(0,0,0,0.03); border:1px solid rgba(0,0,0,0.02); transition:all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 25px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.03)';">
            <div style="width:45px; height:45px; border-radius:14px; background:rgba(16, 185, 129, 0.1); display:flex; align-items:center; justify-content:center; font-size:20px;">➕</div>
            <div>
                <div style="font-weight:700; color:#1e293b; font-size:14px;">Add Student</div>
                <div style="font-size:12px; color:#64748b;">New enrollment</div>
            </div>
        </a>
        <a href="results/backlogs.php" style="text-decoration:none; background:white; padding:20px; border-radius:18px; display:flex; align-items:center; gap:15px; box-shadow:0 4px 15px rgba(0,0,0,0.03); border:1px solid rgba(0,0,0,0.02); transition:all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 25px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.03)';">
            <div style="width:45px; height:45px; border-radius:14px; background:rgba(239, 68, 68, 0.1); display:flex; align-items:center; justify-content:center; font-size:20px;">🚨</div>
            <div>
                <div style="font-weight:700; color:#1e293b; font-size:14px;">View Backlogs</div>
                <div style="font-size:12px; color:#64748b;">Track failures</div>
            </div>
        </a>
        <a href="reports/index.php" style="text-decoration:none; background:white; padding:20px; border-radius:18px; display:flex; align-items:center; gap:15px; box-shadow:0 4px 15px rgba(0,0,0,0.03); border:1px solid rgba(0,0,0,0.02); transition:all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 25px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.03)';">
            <div style="width:45px; height:45px; border-radius:14px; background:rgba(139, 92, 246, 0.1); display:flex; align-items:center; justify-content:center; font-size:20px;">📊</div>
            <div>
                <div style="font-weight:700; color:#1e293b; font-size:14px;">Analytics</div>
                <div style="font-size:12px; color:#64748b;">Full reports</div>
            </div>
        </a>
    </div>
</div>

<!-- ══ TOPPER ══ -->
<?php if ($topper): ?>
<div class="topper-bar animate-fade-up delay-2">
    <div class="trophy">🏆</div>
    <div style="flex:1;">
        <div class="topper-name">
            Class Topper — <?= htmlspecialchars($topper['name']) ?>
        </div>
        <div class="topper-sub">
            Average Score: <b><?= $topper['avg_m'] ?> / 100</b>
            &nbsp;•&nbsp; Highest performing student
        </div>
    </div>
    <a href="reports/index.php"
       style="background:rgba(255,255,255,0.2);color:white;
              padding:10px 20px;border-radius:10px;font-weight:600;
              text-decoration:none;font-size:14px;transition:background 0.2s;">
        See Full Rankings →
    </a>
</div>
<?php endif; ?>

<!-- ══ CHARTS ══ -->
<div class="charts-grid animate-fade-up delay-3">
    <div class="chart-card">
        <h4 class="chart-title">📊 Pass vs Fail Overview</h4>
        <canvas id="passFailChart" height="200"></canvas>
    </div>
    <div class="chart-card">
        <h4 class="chart-title">🎓 Grade Distribution</h4>
        <canvas id="gradeChart" height="200"></canvas>
    </div>
</div>

<!-- ══ RECENT RESULTS ══ -->
<div class="recent-card animate-fade-up delay-4">
    <h4>
        <span>🕐 Recently Entered Results</span>
        <a href="results/view.php"
           style="font-size:14px;font-weight:600;color:#3b82f6;text-decoration:none;background:#eff6ff;padding:8px 16px;border-radius:8px;">
            View All →
        </a>
    </h4>
    <table>
        <tr>
            <th>Student</th>
            <th>Course</th>
            <th style="text-align:center;">Marks</th>
            <th style="text-align:center;">Grade</th>
            <th style="text-align:center;">Status</th>
            <th style="text-align:right;">Exam</th>
        </tr>
        <?php while($r = $recent->fetch_assoc()):
            $marks_color = $r['marks']>=75?'#166534':($r['marks']>=50?'#b45309':'#991b1b');
        ?>
        <tr>
            <td>
                <div style="font-weight:700;color:#1e293b;"><?= htmlspecialchars($r['name']) ?></div>
            </td>
            <td>
                <div style="color:#475569;"><?= htmlspecialchars($r['course_title']) ?></div>
            </td>
            <td style="text-align:center;">
                <span style="font-weight:800;font-size:16px;color:<?= $marks_color ?>;">
                    <?= $r['marks'] ?>
                </span>
            </td>
            <td style="text-align:center;font-weight:700;font-size:15px;color:#1e293b;">
                <?= $r['grade'] ?>
            </td>
            <td style="text-align:center;">
                <span class="badge badge-<?= strtolower($r['result_status']) ?>">
                    <?= $r['result_status'] ?>
                </span>
            </td>
            <td style="text-align:right;font-size:13px;color:#94a3b8;font-weight:600;">
                <?= $r['exam_month_year'] ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#64748b';

new Chart(document.getElementById('passFailChart'), {
    type: 'doughnut',
    data: {
        labels: ['Pass (<?= $pass ?>)', 'Fail (<?= $fail ?>)'],
        datasets:[{
            data: [<?= $pass ?>, <?= $fail ?>],
            backgroundColor: ['#10b981','#ef4444'],
            borderWidth: 0,
            hoverOffset: 6
        }]
    },
    options: {
        plugins: {
            legend: { position:'bottom', labels: { font: { weight: 600, size: 13 }, padding: 20 } },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.9)', padding: 12, titleFont: { size: 14 }, bodyFont: { size: 14 },
                callbacks: {
                    label: function(c) { return ' ' + c.label + ' — ' + c.raw + ' students'; }
                }
            }
        },
        cutout: '70%'
    }
});

new Chart(document.getElementById('gradeChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($g_labels) ?>,
        datasets:[{
            label: 'Number of Students',
            data: <?= json_encode($g_data) ?>,
            backgroundColor: ['#3b82f6','#8b5cf6','#f59e0b','#10b981','#06b6d4','#ef4444'],
            borderRadius: 6,
            borderSkipped: false
        }]
    },
    options: {
        plugins: { 
            legend: { display:false },
            tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', padding: 12, titleFont: { size: 14 }, bodyFont: { size: 14 } }
        },
        scales: {
            y: { beginAtZero:true, ticks:{ stepSize:1, font: { weight: 500 } }, grid: { color:'#f1f5f9', drawBorder: false } },
            x: { grid: { display:false }, ticks: { font: { weight: 700 } } }
        }
    }
});
</script>
</div>
</body>
</html>



