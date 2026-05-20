<?php
include("../../includes/connect.php");
$root = "../../";
$id = intval($_GET['id'] ?? 0);
$student = $conn->query("SELECT * FROM student WHERE student_id=$id")->fetch_assoc();
if (!$student) { echo "Student not found."; exit(); }

// Summary counts (latest attempts only)
$summary = $conn->query("
    SELECT 
        COUNT(*) as total_c,
        SUM(IF(r.result_status='PASS', 1, 0)) as pass_c,
        SUM(IF(r.result_status='FAIL', 1, 0)) as fail_c,
        SUM(IF(r.backlog_status='ACTIVE', 1, 0)) as backl_c,
        ROUND(AVG(r.marks), 1) as avg_marks
    FROM result r
    JOIN (SELECT course_id, MAX(attempt_no) as max_attempt FROM result WHERE student_id = $id GROUP BY course_id) rm 
      ON r.course_id = rm.course_id AND r.attempt_no = rm.max_attempt
    WHERE r.student_id = $id
")->fetch_assoc();

$total = $summary['total_c'] ?? 0;
$pass  = $summary['pass_c'] ?? 0;
$fail  = $summary['fail_c'] ?? 0;
$backl = $summary['backl_c'] ?? 0;
$avg   = $summary['avg_marks'] ?? '-';

// Latest CGPA
$cgpa_row = $conn->query(
    "SELECT cgpa, sgpa FROM semester_result WHERE student_id=$id ORDER BY semester DESC LIMIT 1"
)->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
<title><?= htmlspecialchars($student['name']) ?> - ScoreHive</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/layout.css?v=1.2">
<style>
body { font-family: 'Inter', sans-serif; background-color: #f4f7f6; }

.page-title { display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; color:#1e293b; font-size:24px; font-weight:800; letter-spacing:-0.5px; }

.btn-dark {
    background: linear-gradient(135deg, #1e293b, #0f172a); color: white; border: none; padding: 10px 20px;
    border-radius: 8px; font-weight: 600; font-size: 13px; text-decoration: none; display:inline-block;
    transition: all 0.2s ease; box-shadow: 0 4px 15px rgba(15, 23, 42, 0.15);
}
.btn-dark:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(15, 23, 42, 0.2); }

/* ── Profile Header ── */
.profile-header {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border-radius: 16px; padding: 30px; display: flex; align-items: center; gap: 25px;
    margin-bottom: 30px; color: white; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.2);
    position: relative; overflow: hidden;
}
.profile-header::after {
    content:''; position:absolute; top:0; right:0; width:300px; height:100%;
    background:linear-gradient(90deg, transparent, rgba(255,255,255,0.05)); transform:skewX(-20deg);
}
.avatar {
    width: 85px; height: 85px; border-radius: 20px;
    background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
    display: flex; align-items: center; justify-content: center; color: white;
    font-size: 36px; font-weight: 800; flex-shrink: 0; box-shadow: 0 8px 20px rgba(253, 160, 133, 0.4);
}
.profile-name { font-size: 26px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 6px; }
.profile-meta { font-size: 14px; color: #94a3b8; font-weight: 500; margin-bottom: 8px; }
.profile-ids { font-size: 14px; color: #cbd5e1; font-weight: 600; }
.profile-ids b { color: #fcd34d; font-size: 15px; }

/* ── Quick Stats ── */
.summary-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:15px; margin-bottom:35px; }
.sum-card {
    background:white; border-radius:16px; padding:20px; display:flex; align-items:center; gap:16px; text-align:left;
    box-shadow:0 4px 20px rgba(0,0,0,0.03); border:1px solid rgba(0,0,0,0.02); transition:transform 0.2s;
}
.sum-card:hover { transform:translateY(-3px); }
.sum-icon {
    width:56px; height:56px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:24px; flex-shrink:0;
}
.bg-gradient-blue { background:linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%); }
.bg-gradient-orange { background:linear-gradient(135deg, #f6d365 0%, #fda085 100%); }
.bg-gradient-green { background:linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); }
.bg-gradient-red { background:linear-gradient(135deg, #ff9a9e 0%, #fecfef 99%, #fecfef 100%); }

.sum-val { font-size:26px; font-weight:800; color:#1e293b; line-height:1.1; margin-bottom:4px; }
.sum-lbl { font-size:12px; color:#64748b; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; }

/* ── Tables & Sections ── */
.section-title { font-size:18px; font-weight:800; color:#1e293b; margin:0 0 15px 0; display:flex; align-items:center; gap:10px; }
.table-box { background:white; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,0.03); border:1px solid rgba(0,0,0,0.02); overflow:hidden; margin-bottom:30px; }

.sem-header {
    background: #f8fafc; color: #334155; padding: 14px 20px; border-bottom: 1px solid #e2e8f0;
    font-weight: 800; font-size: 15px;
}
table { width:100%; border-collapse:collapse; }
th { padding:14px 20px; text-align:left; font-size:12px; color:#64748b; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid #e2e8f0; background:white; }
td { padding:16px 20px; font-size:14px; font-weight:500; color:#334155; border-bottom:1px solid #f1f5f9; }
tr:last-child td { border-bottom:none; }
tr:hover td { background:#f8fafc; }

/* ── Badges ── */
.badge { display:inline-flex; align-items:center; justify-content:center; padding:6px 12px; border-radius:20px; font-size:12px; font-weight:700; letter-spacing:0.5px; }
.badge-pass, .badge-cleared { background:#dcfce7; color:#166534; }
.badge-fail, .badge-active { background:#fee2e2; color:#991b1b; }
.badge-none { background:#f1f5f9; color:#64748b; }

.btn-warning { background:#fef3c7; color:#d97706; padding:6px 12px; border-radius:6px; font-weight:700; font-size:12px; text-decoration:none; transition:background 0.2s; }
.btn-warning:hover { background:#fde68a; }

.progress-bg { background:#f1f5f9; border-radius:3px; height:6px; width:60px; overflow:hidden; }
.progress-bar { height:100%; border-radius:3px; }
</style>
</head>
<body>
<?php include("../../includes/header.php"); ?>
<div class="main">

<div class="page-title">
    Student Profile
    <a href="list.php" class="btn btn-dark">← Back to Students</a>
</div>

<!-- ── Profile Header ── -->
<div class="profile-header">
    <div class="avatar"><?= strtoupper(substr($student['name'],0,1)) ?></div>
    <div style="flex:1; position:relative; z-index:1;">
        <div class="profile-name">
            <?= htmlspecialchars($student['name']) ?>
        </div>
        <div class="profile-meta">
            🏛 <?= htmlspecialchars($student['department']) ?>
            &nbsp;•&nbsp;
            🎓 Batch: <?= htmlspecialchars($student['batch'] ?? 'N/A') ?>
            &nbsp;•&nbsp;
            📱 <?= htmlspecialchars($student['mobile_number']) ?>
            &nbsp;•&nbsp;
            ✉ <?= htmlspecialchars($student['email']) ?>
        </div>
        <div class="profile-ids">
            🆔 Register No: <b><?= htmlspecialchars($student['register_number'] ?? $id) ?></b>
            <?php if ($cgpa_row): ?>
            &nbsp;•&nbsp; CGPA: <b><?= $cgpa_row['cgpa'] ?></b>
            &nbsp;•&nbsp; SGPA: <b><?= $cgpa_row['sgpa'] ?></b>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── Quick Stats ── -->
<div class="summary-grid">
    <div class="sum-card">
        <div class="sum-icon bg-gradient-blue">📚</div>
        <div>
            <div class="sum-val"><?= $total ?></div>
            <div class="sum-lbl">Total Subjects</div>
        </div>
    </div>
    <div class="sum-card">
        <div class="sum-icon bg-gradient-green">🎉</div>
        <div>
            <div class="sum-val"><?= $pass ?></div>
            <div class="sum-lbl">Passed</div>
        </div>
    </div>
    <div class="sum-card">
        <div class="sum-icon bg-gradient-red">📉</div>
        <div>
            <div class="sum-val"><?= $fail ?></div>
            <div class="sum-lbl">Failed</div>
        </div>
    </div>
    <div class="sum-card">
        <div class="sum-icon bg-gradient-orange">📊</div>
        <div>
            <div class="sum-val"><?= $avg ?></div>
            <div class="sum-lbl">Avg Marks</div>
        </div>
    </div>
    <div class="sum-card">
        <div class="sum-icon <?= $backl>0?'bg-gradient-red':'bg-gradient-green' ?>"><?= $backl>0?'⚠️':'✅' ?></div>
        <div>
            <div class="sum-val"><?= $backl ?></div>
            <div class="sum-lbl">Active Backlogs</div>
        </div>
    </div>
</div>

<!-- ── Enrollment Details ── -->
<?php
$enroll = $conn->query("
    SELECT e.enroll_id, e.enroll_status,
           c.course_title, c.course_code, c.semester, c.credit, c.category,
           f.faculty_name, f.department AS faculty_dept
    FROM enrollment e
    JOIN course  c ON e.course_id  = c.course_id
    LEFT JOIN faculty f ON e.faculty_id = f.faculty_id
    WHERE e.student_id = $id
    ORDER BY c.semester, c.course_title
");
if ($enroll->num_rows > 0):
    $enrollments_by_sem = [];
    while ($e = $enroll->fetch_assoc()) {
        $enrollments_by_sem[$e['semester']][] = $e;
    }
?>
<div class="section-title">📚 Enrolled Courses</div>
<?php foreach ($enrollments_by_sem as $sem => $courses): ?>
<div class="table-box">
    <div class="sem-header">Semester <?= $sem ?></div>
    <table>
        <tr>
            <th>#</th>
            <th>Course Code</th>
            <th>Course Title</th>
            <th style="text-align:center;">Credits</th>
            <th>Category</th>
            <th>Faculty</th>
            <th style="text-align:center;">Enroll Status</th>
        </tr>
        <?php $i=1; foreach ($courses as $e): ?>
        <tr>
            <td style="color:#64748b;"><?= $i++ ?></td>
            <td style="font-weight:700; color:#1e293b;"><?= htmlspecialchars($e['course_code']) ?></td>
            <td><?= htmlspecialchars($e['course_title']) ?></td>
            <td style="text-align:center; font-weight:700;"><?= $e['credit'] ?></td>
            <td><span class="badge badge-none"><?= $e['category'] ?></span></td>
            <td>
                <?php if ($e['faculty_name']): ?>
                    <div style="font-weight:600;"><?= htmlspecialchars($e['faculty_name']) ?></div>
                    <div style="font-size:12px;color:#94a3b8;"><?= $e['faculty_dept'] ?></div>
                <?php else: ?>
                    <span style="color:#cbd5e1;">—</span>
                <?php endif; ?>
            </td>
            <td style="text-align:center;">
                <span class="badge <?= $e['enroll_status']==='ENROLLED'?'badge-pass':'badge-none' ?>">
                    <?= $e['enroll_status'] ?>
                </span>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ── Semester Performance ── -->
<?php
$sem = $conn->query("SELECT * FROM semester_result WHERE student_id=$id ORDER BY semester");
if ($sem->num_rows > 0):
?>
<div class="section-title">📈 Semester Performance</div>
<div class="table-box">
    <table>
        <tr>
            <th>Semester</th>
            <th style="text-align:center;">SGPA</th>
            <th style="text-align:center;">CGPA</th>
            <th style="text-align:center;">Backlogs</th>
        </tr>
        <?php while ($sr = $sem->fetch_assoc()): ?>
        <tr>
            <td style="font-weight:700; color:#1e293b;">Semester <?= $sr['semester'] ?></td>
            <td style="text-align:center; font-weight:800; font-size:16px; color:#f59e0b;"><?= $sr['sgpa'] ?></td>
            <td style="text-align:center; font-weight:800; font-size:16px; color:#3b82f6;"><?= $sr['cgpa'] ?></td>
            <td style="text-align:center;">
                <?php if ($sr['backlog_count'] > 0): ?>
                    <span class="badge badge-active"><?= $sr['backlog_count'] ?> Active</span>
                <?php else: ?>
                    <span class="badge badge-pass">✅ Clear</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
<?php endif; ?>

<!-- ── Result Details ── -->
<?php
$res = $conn->query("
    SELECT r.*, c.course_title, c.course_code, c.semester, f.faculty_name
    FROM result r
    JOIN (SELECT course_id, MAX(attempt_no) as max_attempt FROM result WHERE student_id = $id GROUP BY course_id) rm 
      ON r.course_id = rm.course_id AND r.attempt_no = rm.max_attempt
    JOIN course c ON r.course_id = c.course_id
    LEFT JOIN enrollment e ON r.student_id=e.student_id AND r.course_id=e.course_id
    LEFT JOIN faculty f ON e.faculty_id=f.faculty_id
    WHERE r.student_id = $id
    ORDER BY c.semester, c.course_title
");
if ($res->num_rows > 0):
    $results_by_sem = [];
    while ($r = $res->fetch_assoc()) {
        $results_by_sem[$r['semester']][] = $r;
    }
?>
<div class="section-title">📄 Result Details</div>
<?php foreach ($results_by_sem as $sem => $results): ?>
<div class="table-box">
    <div class="sem-header">Semester <?= $sem ?></div>
    <table>
        <tr>
            <th>#</th>
            <th>Course</th>
            <th>Faculty</th>
            <th style="text-align:center;">Marks</th>
            <th style="text-align:center;">Grade</th>
            <th style="text-align:center;">Attempt</th>
            <th style="text-align:center;">Status</th>
            <th style="text-align:center;">Backlog</th>
            <th style="text-align:right;">Exam</th>
            <th style="text-align:center;">Action</th>
        </tr>
        <?php
        $i = 1;
        foreach ($results as $r):
            $bar = $r['marks'] >= 75 ? '#10b981' : ($r['marks'] >= 50 ? '#f59e0b' : '#ef4444');
        ?>
        <tr>
            <td style="color:#64748b;"><?= $i++ ?></td>
            <td>
                <div style="font-weight:700; color:#1e293b;"><?= htmlspecialchars($r['course_title']) ?></div>
                <div style="font-size:12px; color:#94a3b8;"><?= htmlspecialchars($r['course_code']) ?></div>
            </td>
            <td>
                <?php if ($r['faculty_name']): ?>
                    <div style="font-weight:600;"><?= htmlspecialchars($r['faculty_name']) ?></div>
                <?php else: ?>
                    <span style="color:#cbd5e1;">—</span>
                <?php endif; ?>
            </td>
            <td>
                <div style="display:flex; align-items:center; justify-content:center; gap:10px;">
                    <b style="color:<?= $bar ?>; font-size:15px; min-width:45px; text-align:right;"><?= $r['marks'] ?></b>
                    <div class="progress-bg">
                        <div class="progress-bar" style="width:<?= $r['marks'] ?>%; background:<?= $bar ?>;"></div>
                    </div>
                </div>
            </td>
            <td style="text-align:center;">
                <span class="badge" style="background:<?= $r['grade']==='O'?'#dcfce7':($r['grade']==='F'?'#fee2e2':'#eff6ff') ?>; color:<?= $r['grade']==='O'?'#166534':($r['grade']==='F'?'#991b1b':'#1e40af') ?>;">
                    <?= $r['grade'] ?>
                </span>
            </td>
            <td style="text-align:center; font-weight:700; color:#475569;"><?= $r['attempt_no'] ?></td>
            <td style="text-align:center;">
                <span class="badge <?= $r['result_status']==='PASS'?'badge-pass':'badge-fail' ?>">
                    <?= $r['result_status'] ?>
                </span>
            </td>
            <td style="text-align:center;">
                <span class="badge <?= $r['backlog_status']==='ACTIVE'?'badge-active':'badge-none' ?>">
                    <?= $r['backlog_status'] ?>
                </span>
            </td>
            <td style="text-align:right; font-size:13px; color:#94a3b8; font-weight:600;"><?= $r['exam_month_year'] ?></td>
            <td style="text-align:center;">
                <a href="../results/edit.php?id=<?= $r['result_id'] ?>" class="btn-warning">Edit</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ── Malpractice (if any) ── -->
<?php
$mal = $conn->query("SELECT m.*, c.course_title FROM malpractice m JOIN course c ON m.course_id=c.course_id WHERE m.student_id=$id");
if ($mal->num_rows > 0):
?>
<div class="section-title" style="color:#ef4444;">🚫 Malpractice Cases</div>
<div class="table-box" style="border-color:#fca5a5;">
    <table>
        <tr>
            <th>#</th>
            <th>Course</th>
            <th>Semester</th>
            <th>Type</th>
            <th>Action Taken</th>
        </tr>
        <?php $i=1; while ($m = $mal->fetch_assoc()): ?>
        <tr style="background:#fef2f2;">
            <td style="color:#991b1b;"><?= $i++ ?></td>
            <td style="font-weight:700; color:#991b1b;"><?= htmlspecialchars($m['course_title']) ?></td>
            <td style="color:#991b1b; font-weight:600;">Semester <?= $m['semester'] ?></td>
            <td><span class="badge badge-fail"><?= htmlspecialchars($m['malpractice_type']) ?></span></td>
            <td style="color:#991b1b;"><?= htmlspecialchars($m['action_taken']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
<?php endif; ?>

</div>
</body>
</html>



