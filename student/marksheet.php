<?php
date_default_timezone_set('Asia/Kolkata'); // IST timezone fix
include("../includes/connect.php");
$student_root = "../";
include("nav.php");

$student = $conn->query("SELECT * FROM student WHERE student_id=$sid")->fetch_assoc();

$sems_q = $conn->query("
    SELECT DISTINCT c.semester FROM result r
    JOIN course c ON r.course_id=c.course_id
    WHERE r.student_id=$sid ORDER BY c.semester
");
$all_available_sems = [];
while ($s = $sems_q->fetch_assoc()) $all_available_sems[] = $s['semester'];

$selected_sem = isset($_GET['semester']) ? intval($_GET['semester']) : 0;
$semesters = $selected_sem > 0 ? [$selected_sem] : $all_available_sems;
?>
<!DOCTYPE html>
<html>
<head>
<title>Marksheet - <?= htmlspecialchars($sname) ?></title>
<link rel="stylesheet" href="../assets/css/student_layout.css?v=1.2">
<style>
@media print {
    .no-print, .topbar, .sidebar { display:none !important; }
    .main { 
        margin:0 !important; 
        padding:0 !important; 
        width: 100% !important;
        position: static !important;
    }
    .marksheet { 
        box-shadow:none !important; 
        border:none !important;
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    body { background: white !important; }
}
.marksheet {
    background:white; max-width:800px; margin:0 auto;
    padding:30px; border-radius:12px;
    box-shadow:0 2px 8px rgba(0,0,0,0.1);
}
.ms-header {
    text-align:center; border-bottom:2px solid #0f2027;
    padding-bottom:15px; margin-bottom:20px;
}
.ms-logo { font-size:24px; font-weight:bold; }
.ms-sub  { font-size:13px; color:#666; margin-top:4px; }
.ms-title{ font-size:18px; font-weight:bold; margin-top:10px;
           color:#0f2027; letter-spacing:2px; }
.ms-info {
    display:grid; grid-template-columns:1fr 1fr;
    gap:15px; margin-bottom:20px;
    background:#f8f9fa; padding:20px; border-radius:8px;
}
.ms-info-row { font-size:13px; }
.ms-info-label { color:#888; font-size:12px; }
.sem-block { margin-bottom:20px; }
.sem-heading {
    background:#0f2027; color:white;
    padding:8px 15px; border-radius:6px 6px 0 0;
    font-size:14px; font-weight:bold;
}
.sem-table { width:100%; border-collapse:collapse; }
.sem-table th {
    background:#0f2027;
    color:#ffffff;
    padding:8px 10px;
    font-size:12px;
    text-align:left;
    border:1px solid #dee2e6;
}
.sem-table td { padding:8px 10px; font-size:13px;
               border:1px solid #dee2e6; }
.sem-table tr:nth-child(even) td { background:#f9f9f9; }
.sem-footer {
    background:#f8f9fa; padding:8px 15px;
    display:flex; gap:20px; font-size:13px;
    border:1px solid #dee2e6; border-top:none;
    border-radius:0 0 6px 6px;
}
.ms-footer {
    text-align:center; margin-top:30px;
    padding-top:15px; border-top:1px solid #dee2e6;
    font-size:12px; color:#888;
}
</style>
</head>
<body>
<div class="main">

<!-- Print & Filter Button -->
<div class="no-print" style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; background:white; padding:15px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05);">
    <div style="display:flex; gap:10px; align-items:center;">
        <form method="GET" style="display:flex; gap:10px; align-items:center;">
            <label style="font-size:13px; font-weight:700; color:#444; margin:0;">Select Semester:</label>
            <select name="semester" onchange="this.form.submit()" style="padding:6px 12px; border-radius:6px; border:1px solid #ddd; font-size:13px;">
                <option value="0">All Semesters</option>
                <?php foreach ($all_available_sems as $s): ?>
                    <option value="<?= $s ?>" <?= $selected_sem == $s ? 'selected' : '' ?>>Semester <?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div style="display:flex; gap:10px;">
        <button onclick="window.print()" class="btn btn-print">
            🖨 Print Marksheet
        </button>
        <a href="results.php" class="btn btn-dark">← Back</a>
    </div>
</div>

<!-- Marksheet -->
<div class="marksheet" id="marksheet">

    <!-- Header -->
    <div class="ms-header">
        <div class="ms-logo">
            <span style="color:#0f2027;">Score</span><span style="color:#ff8c00;">Hive</span>
        </div>
        <div class="ms-sub">Student Result Management System</div>
        <div class="ms-title">OFFICIAL MARK SHEET</div>
    </div>

    <!-- Student Info -->
    <div class="ms-info">
        <div class="ms-info-row">
            <div class="ms-info-label">Student Name</div>
            <b><?= htmlspecialchars($student['name']) ?></b>
        </div>
        <div class="ms-info-row">
              <div class="ms-info-label">Register Number</div>
              <b><?= $student['register_number'] ?? $sid ?></b>
        </div>
        <div class="ms-info-row">
            <div class="ms-info-label">Department</div>
            <b><?= $student['department'] ?></b>
        </div>
        <div class="ms-info-row">
            <div class="ms-info-label">Email</div>
            <b><?= $student['email'] ?></b>
        </div>
        <div class="ms-info-row">
            <div class="ms-info-label">Mobile</div>
            <b><?= $student['mobile_number'] ?></b>
        </div>
        <div class="ms-info-row">
            <div class="ms-info-label">Date Printed</div>
            <b><?= date('d-m-Y') ?></b>
        </div>
    </div>

    <!-- Semester-wise Results -->
    <?php foreach ($semesters as $sem):
        $rows = $conn->query("
            SELECT r.*, c.course_title, c.course_code, c.credit
            FROM result r 
            JOIN (SELECT course_id, MAX(attempt_no) as max_attempt FROM result WHERE student_id = $sid GROUP BY course_id) rm 
              ON r.course_id = rm.course_id AND r.attempt_no = rm.max_attempt
            JOIN course c ON r.course_id=c.course_id
            WHERE r.student_id=$sid AND c.semester=$sem
            ORDER BY c.course_title
        ");

        $sem_info = $conn->query(
            "SELECT * FROM semester_result
             WHERE student_id=$sid AND semester=$sem"
        )->fetch_assoc();

        $total_marks=0; $count=0; $pass_c=0; $fail_c=0;
        $all=[];
        while ($r=$rows->fetch_assoc()) {
            $all[]=$r;
            $total_marks+=$r['marks'];
            $count++;
            if ($r['result_status']==='PASS') $pass_c++; else $fail_c++;
        }
        if (empty($all)) continue;
    ?>
    <div class="sem-block">
        <div class="sem-heading">Semester <?= $sem ?></div>
        <table class="sem-table">
            <tr>
                <th>#</th><th>Subject Code</th><th>Subject Title</th>
                <th>Credits</th><th>Marks</th><th>Grade</th>
                <th>Status</th><th>Attempt</th>
            </tr>
            <?php $i=1; foreach ($all as $r): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= $r['course_code'] ?></td>
                <td><?= htmlspecialchars($r['course_title']) ?></td>
                <td style="text-align:center;"><?= $r['credit'] ?></td>
                <td style="text-align:center;font-weight:bold;"><?= $r['marks'] ?></td>
                <td style="text-align:center;">
                    <b style="color:<?= $r['result_status']==='PASS'?'#155724':'#721c24' ?>;">
                        <?= $r['grade'] ?>
                    </b>
                </td>
                <td style="text-align:center;">
                    <b style="color:<?= $r['result_status']==='PASS'?'#155724':'#721c24' ?>;">
                        <?= $r['result_status'] ?>
                    </b>
                </td>
                <td style="text-align:center;"><?= $r['attempt_no'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <div class="sem-footer">
            <span>Total Subjects: <b><?= $count ?></b></span>
            <span style="color:#28a745;">Passed: <b><?= $pass_c ?></b></span>
            <span style="color:#dc3545;">Failed: <b><?= $fail_c ?></b></span>
            <span>Avg: <b><?= $count>0?round($total_marks/$count,1):0 ?></b></span>
            <?php if ($sem_info): ?>
            <span>SGPA: <b style="color:#ff8c00;"><?= $sem_info['sgpa'] ?></b></span>
            <span>CGPA: <b style="color:#007bff;"><?= $sem_info['cgpa'] ?></b></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Footer -->
    <div class="ms-footer">
        This is a computer-generated marksheet from ScoreHive Result Management System.<br>
        Printed on: <?= date('d M Y, h:i A') ?>
    </div>

</div>
</div>
</body>
</html>


