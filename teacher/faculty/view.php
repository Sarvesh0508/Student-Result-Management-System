<?php
include("../../includes/connect.php");
$root = "../../";
$id = intval($_GET['id'] ?? 0);

$faculty = $conn->query("SELECT * FROM faculty WHERE faculty_id=$id")->fetch_assoc();
if (!$faculty) { echo "Faculty not found."; exit(); }

// Handle unenrollment
if (isset($_GET['unenroll'])) {
    $eid = intval($_GET['unenroll']);
    // Safety check: verify this enrollment belongs to this faculty
    $check = $conn->query("SELECT enroll_id FROM enrollment WHERE enroll_id=$eid AND faculty_id=$id")->num_rows;
    if ($check > 0) {
        $conn->query("DELETE FROM enrollment WHERE enroll_id=$eid");
        header("Location: view.php?id=$id&unregistered=1");
        exit();
    }
}
if (isset($_GET['unregistered'])) $msg = "success:Student removed from this faculty's course.";

// Get courses handled by this faculty (via enrollment)
$courses = $conn->query("
    SELECT DISTINCT c.* 
    FROM course c
    JOIN enrollment e ON c.course_id = e.course_id
    WHERE e.faculty_id = $id
    ORDER BY c.semester, c.course_title
");

// Get students handled by this faculty
$students = $conn->query("
    SELECT s.*, c.course_title, c.course_code, c.semester, e.enroll_id
    FROM student s
    JOIN enrollment e ON s.student_id = e.student_id
    JOIN course c ON e.course_id = c.course_id
    WHERE e.faculty_id = $id
    ORDER BY c.semester, s.name
");
?>
<!DOCTYPE html>
<html>
<head>
<title>View Faculty - ScoreHive</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/layout.css?v=1.2">
<style>
    body { font-family: 'Inter', sans-serif; background-color: #f4f7f6; }
    .profile-header {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        border-radius: 16px; padding: 30px; display: flex; align-items: center; gap: 25px;
        margin-bottom: 30px; color: white; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.2);
    }
    .avatar {
        width: 80px; height: 80px; border-radius: 20px;
        background: linear-gradient(135deg, #6366f1, #a855f7);
        display: flex; align-items: center; justify-content: center; color: white;
        font-size: 32px; font-weight: 800;
    }
    .profile-info h2 { margin: 0; font-size: 24px; font-weight: 800; }
    .profile-info p { margin: 5px 0 0; opacity: 0.8; font-size: 14px; }
    
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
        Faculty Profile
        <a href="list.php" class="btn btn-dark">← Back to List</a>
    </div>

    <?php if (isset($msg)): list($type,$text) = explode(":",$msg,2); ?>
    <div class="alert alert-<?= $type==='success'?'success':'danger' ?>">
        <?= htmlspecialchars($text) ?>
    </div>
    <?php endif; ?>

    <div class="profile-header">
        <div class="avatar"><?= strtoupper(substr($faculty['faculty_name'], 0, 1)) ?></div>
        <div class="profile-info">
            <h2><?= htmlspecialchars($faculty['faculty_name']) ?></h2>
            <p>🏛 <?= htmlspecialchars($faculty['department']) ?> Department</p>
            <div style="margin-top:10px; display:flex; gap:15px; font-size:13px; opacity:0.9;">
                <span>✉ <?= htmlspecialchars($faculty['email']) ?></span>
                <span>📱 <?= htmlspecialchars($faculty['mobile_number'] ?? 'N/A') ?></span>
            </div>
        </div>
    </div>

    <div class="section-title">👨‍🎓 Students Handled (<?= $students->num_rows ?>)</div>
    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student Name</th>
                    <th>Course Handled</th>
                    <th>Semester</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($students->num_rows > 0): ?>
                    <?php $i=1; while ($s = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><b><?= htmlspecialchars($s['name']) ?></b></td>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($s['course_title']) ?></div>
                            <div style="font-size:12px; color:#64748b;"><?= htmlspecialchars($s['course_code']) ?></div>
                        </td>
                        <td>Semester <?= $s['semester'] ?></td>
                        <td style="display:flex; gap:5px;">
                            <a href="../students/view.php?id=<?= $s['student_id'] ?>" class="btn btn-primary" style="padding:4px 10px; font-size:12px;">Profile</a>
                            <a href="view.php?id=<?= $id ?>&unenroll=<?= $s['enroll_id'] ?>" 
                               class="btn btn-danger" 
                               style="padding:4px 10px; font-size:12px;"
                               onclick="return confirm('Remove this student from this faculty\'s course?')">Remove</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;">No students assigned to this faculty yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>
