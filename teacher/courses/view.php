<?php
include("../../includes/connect.php");
$root = "../../";
$id = intval($_GET['id'] ?? 0);

$course = $conn->query("SELECT * FROM course WHERE course_id=$id")->fetch_assoc();
if (!$course) { echo "Course not found."; exit(); }

// Handle unenrollment
if (isset($_GET['unenroll'])) {
    $eid = intval($_GET['unenroll']);
    // Check if this student has results for this course
    $sid_q = $conn->query("SELECT student_id FROM enrollment WHERE enroll_id=$eid")->fetch_assoc();
    $sid = $sid_q['student_id'] ?? 0;
    
    $has_results = $conn->query("SELECT COUNT(*) c FROM result WHERE student_id=$sid AND course_id=$id")->fetch_assoc()['c'];
    
    if ($has_results > 0) {
        $msg = "error:Cannot remove student — they already have results entered for this course!";
    } else {
        $conn->query("DELETE FROM enrollment WHERE enroll_id=$eid");
        header("Location: view.php?id=$id&unregistered=1");
        exit();
    }
}
if (isset($_GET['unregistered'])) $msg = "success:Student removed from this course.";

// Get enrolled students
$students = $conn->query("
    SELECT e.enroll_id, s.name, s.register_number, s.student_id, s.department, s.batch
    FROM enrollment e
    JOIN student s ON e.student_id = s.student_id
    WHERE e.course_id = $id
    ORDER BY s.name
");
?>
<!DOCTYPE html>
<html>
<head>
<title>View Course - ScoreHive</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/layout.css?v=1.2">
<style>
    body { font-family: 'Inter', sans-serif; background-color: #f4f7f6; }
    .course-header {
        background: linear-gradient(135deg, #0f172a, #1e293b);
        border-radius: 16px; padding: 30px; margin-bottom: 30px; color: white;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.2);
    }
    .course-title-large { font-size: 28px; font-weight: 800; letter-spacing: -0.5px; margin: 0 0 10px; }
    .course-meta { display: flex; gap: 20px; font-size: 14px; opacity: 0.8; font-weight: 500; }
    
    .section-title { font-size:18px; font-weight:800; color:#1e293b; margin:30px 0 15px 0; display:flex; align-items:center; gap:10px; }
    .table-box { background:white; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,0.03); border:1px solid rgba(0,0,0,0.02); overflow:hidden; }
    table { width:100%; border-collapse:collapse; }
    th { padding:14px 20px; text-align:left; font-size:12px; color:#64748b; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid #e2e8f0; background:#f8fafc; }
    td { padding:16px 20px; font-size:14px; font-weight:500; color:#334155; border-bottom:1px solid #f1f5f9; }
</style>
</head>
<body>
<?php include("../../includes/header.php"); ?>
<div class="main">

    <div class="page-title">
        Course Details
        <a href="list.php" class="btn btn-dark">← Back to Courses</a>
    </div>

    <?php if (isset($msg)): list($type,$text) = explode(":",$msg,2); ?>
    <div class="alert alert-<?= $type==='success'?'success':'danger' ?>">
        <?= htmlspecialchars($text) ?>
    </div>
    <?php endif; ?>

    <div class="course-header">
        <div style="font-size: 12px; color: #3b82f6; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 5px;">
            <?= htmlspecialchars($course['course_code']) ?>
        </div>
        <h1 class="course-title-large"><?= htmlspecialchars($course['course_title']) ?></h1>
        <div class="course-meta">
            <span>📚 Semester <?= $course['semester'] ?></span>
            <span>⭐ <?= $course['credit'] ?> Credits</span>
            <span>🏷 <?= htmlspecialchars($course['category'] ?? 'Theory') ?></span>
        </div>
    </div>

    <div class="section-title">👥 Enrolled Students (<?= $students->num_rows ?>)</div>
    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Reg. No.</th>
                    <th>Student Name</th>
                    <th>Dept / Batch</th>
                    <th style="text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($students->num_rows > 0): ?>
                    <?php $i=1; while ($s = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td style="font-weight:700; color:#3b82f6;"><?= htmlspecialchars($s['register_number'] ?? 'N/A') ?></td>
                        <td><b><?= htmlspecialchars($s['name']) ?></b></td>
                        <td><?= $s['department'] ?> (<?= $s['batch'] ?>)</td>
                        <td style="text-align:center;">
                            <a href="../students/view.php?id=<?= $s['student_id'] ?>" class="btn btn-primary" style="padding:4px 10px; font-size:12px;">Profile</a>
                            <a href="view.php?id=<?= $id ?>&unenroll=<?= $s['enroll_id'] ?>" 
                               class="btn btn-danger" 
                               style="padding:4px 10px; font-size:12px;"
                               onclick="return confirm('Remove this student from the course?')">Remove</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;">No students enrolled in this course yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>
