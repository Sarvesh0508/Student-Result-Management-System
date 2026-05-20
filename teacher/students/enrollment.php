<?php
include("../../includes/connect.php");
$root = "../../";
$msg = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_student'])) {
    $student_id = intval($_POST['student_id']);
    $course_id  = intval($_POST['course_id']);
    $faculty_id = intval($_POST['faculty_id']);
    $status     = 'ENROLLED';

    $check = $conn->query(
        "SELECT enroll_id FROM enrollment
         WHERE student_id=$student_id AND course_id=$course_id"
    );
    if ($check->num_rows > 0) {
        $msg = "error:Student is already enrolled in this course!";
    } else {
        $conn->query("
            INSERT INTO enrollment (student_id, course_id, faculty_id, enroll_status)
            VALUES ($student_id, $course_id, $faculty_id, '$status')
        ");
        $msg = "success:Student enrolled successfully!";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    
    // Safety Check: Check if results exist for this enrollment
    $enroll_info = $conn->query("SELECT student_id, course_id FROM enrollment WHERE enroll_id=$del_id")->fetch_assoc();
    if ($enroll_info) {
        $sid = $enroll_info['student_id'];
        $cid = $enroll_info['course_id'];
        $res_check = $conn->query("SELECT result_id FROM result WHERE student_id=$sid AND course_id=$cid");
        
        if ($res_check->num_rows > 0) {
            header("Location: enrollment.php?error=result_exists");
            exit();
        }
    }

    $conn->query("DELETE FROM enrollment WHERE enroll_id=$del_id");
    header("Location: enrollment.php?deleted=1");
    exit();
}

if (isset($_GET['deleted'])) {
    $msg = "success:Enrollment removed successfully!";
}
if (isset($_GET['error']) && $_GET['error'] === 'result_exists') {
    $msg = "error:Cannot remove enrollment! Marks have already been recorded for this course. Please delete the results first.";
}

// 1. Load Students who still have courses left to enroll in
$students_dd = $conn->query("
    SELECT s.student_id, s.name 
    FROM student s 
    WHERE (SELECT COUNT(*) FROM course) > (SELECT COUNT(*) FROM enrollment e WHERE e.student_id = s.student_id)
    ORDER BY s.name
");

$faculty_dd = $conn->query("SELECT faculty_id, faculty_name FROM faculty ORDER BY faculty_name");

// Load ALL students with enrollment status
$all_students = $conn->query("
    SELECT s.student_id, s.name, s.department, s.mobile_number, s.email,
           COUNT(e.enroll_id) as enrolled_count
    FROM student s
    LEFT JOIN enrollment e ON s.student_id = e.student_id
    GROUP BY s.student_id
    ORDER BY s.name
");

// Load all enrollments
$enrollments = $conn->query("
    SELECT e.enroll_id, s.name as student_name, c.course_title,
           c.course_code, f.faculty_name, e.enroll_status
    FROM enrollment e
    JOIN student s ON e.student_id = s.student_id
    JOIN course  c ON e.course_id  = c.course_id
    JOIN faculty f ON e.faculty_id = f.faculty_id
    ORDER BY e.enroll_id DESC
");

// Total course count for comparison
$total_course_count = $conn->query("SELECT COUNT(*) FROM course")->fetch_row()[0];
?>
<!DOCTYPE html>
<html>
<head>
<title>Enrollment Management - ScoreHive</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/layout.css?v=1.2">
<style>
    body { font-family: 'Inter', sans-serif; background-color: #f4f7f6; }
    .page-title { margin-bottom:25px; color:#1e293b; font-size:24px; font-weight:800; letter-spacing:-0.5px; }

    .card { background:white; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,0.03); border:1px solid rgba(0,0,0,0.02); overflow:hidden; margin-bottom:25px; }
    .card-header { padding:15px 20px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; }
    .card-header h3 { margin:0; font-size:16px; color:#1e293b; font-weight:700; }

    .form-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:15px; padding:20px; }
    .form-group { display:flex; flex-direction:column; }
    .form-group label { font-size:12px; font-weight:700; color:#64748b; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px; }
    
    select {
        padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; color: #1e293b;
        background-color: #f8fafc; transition: all 0.2s;
    }
    select:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,0.1); background-color:#fff; }
    select:disabled { background-color:#f1f5f9; cursor:not-allowed; opacity:0.7; }

    .btn-submit {
        background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none;
        padding: 12px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer;
        transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }

    table { width:100%; border-collapse:collapse; }
    th { padding:14px 20px; text-align:left; font-size:12px; color:#64748b; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid #f1f5f9; background:#f8fafc; }
    td { padding:12px 20px; font-size:14px; color:#334155; border-bottom:1px solid #f1f5f9; }
    tr:last-child td { border-bottom:none; }
    tr:hover td { background:#fbfcfe; }

    .badge { padding:4px 10px; border-radius:6px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; }
    .badge-pass { background:#dcfce7; color:#15803d; }
    .badge-fail { background:#fee2e2; color:#b91c1c; }
    .badge-info { background:#eff6ff; color:#1d4ed8; }

    .enroll-btn-wrap { padding: 0 20px 20px; }
</style>
</head>
<body>
<?php include("../../includes/header.php"); ?>
<div class="main">

<div class="page-title">Enrollment Management</div>

<?php if ($msg): list($type,$text) = explode(":",$msg,2); ?>
<div class="alert alert-<?= $type==='success'?'success':'danger' ?>" style="margin-bottom:20px; padding:12px 20px; border-radius:10px;">
    <?= $type==='success'?'✅':'❌' ?> <?= htmlspecialchars($text) ?>
</div>
<?php endif; ?>

<!-- Enrollment Form -->
<div class="card">
    <div class="card-header">
        <h3>➕ New Enrollment</h3>
    </div>
    <form method="POST" id="enrollForm">
        <div class="form-grid">
            <div class="form-group">
                <label>Select Student</label>
                <select name="student_id" id="student_select" required>
                    <option value="">-- Choose Student --</option>
                    <?php while ($s = $students_dd->fetch_assoc()): ?>
                    <option value="<?= $s['student_id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Select Course</label>
                <select name="course_id" id="course_select" required disabled>
                    <option value="">-- Select Student First --</option>
                </select>
            </div>

            <div class="form-group">
                <label>Assign Faculty</label>
                <select name="faculty_id" required>
                    <option value="">-- Select Faculty --</option>
                    <?php while ($f = $faculty_dd->fetch_assoc()): ?>
                    <option value="<?= $f['faculty_id'] ?>"><?= htmlspecialchars($f['faculty_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="enroll-btn-wrap">
            <button type="submit" name="enroll_student" class="btn-submit">
                ✓ Complete Enrollment
            </button>
        </div>
    </form>
</div>

<!-- All Students Status -->
<div class="card">
    <div class="card-header">
        <h3>👥 Student Enrollment Progress</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Department</th>
                <th>Courses</th>
                <th>Progress</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($s = $all_students->fetch_assoc()): 
                $pct = ($total_course_count > 0) ? ($s['enrolled_count'] / $total_course_count) * 100 : 0;
            ?>
            <tr>
                <td>
                    <div style="font-weight:700; color:#1e293b;"><?= htmlspecialchars($s['name']) ?></div>
                    <div style="font-size:11px; color:#94a3b8;"><?= $s['email'] ?></div>
                </td>
                <td style="font-weight:500;"><?= $s['department'] ?></td>
                <td>
                    <span style="font-weight:700;"><?= $s['enrolled_count'] ?></span> / <?= $total_course_count ?>
                </td>
                <td style="width:200px;">
                    <div style="background:#f1f5f9; border-radius:10px; height:8px; width:100%; overflow:hidden;">
                        <div style="background:linear-gradient(to right, #3b82f6, #2563eb); width:<?= $pct ?>%; height:100%; border-radius:10px;"></div>
                    </div>
                </td>
                <td>
                    <?php if ($s['enrolled_count'] >= $total_course_count): ?>
                    <span class="badge badge-pass">FULLY ENROLLED</span>
                    <?php else: ?>
                    <span class="badge badge-info"><?= $total_course_count - $s['enrolled_count'] ?> Left</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- History -->
<div class="card">
    <div class="card-header">
        <h3>📋 Enrollment History</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Student</th>
                <th>Course</th>
                <th>Faculty</th>
                <th style="text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php $i=1; while ($e = $enrollments->fetch_assoc()): ?>
            <tr>
                <td style="color:#94a3b8;"><?= $i++ ?></td>
                <td style="font-weight:700;"><?= htmlspecialchars($e['student_name']) ?></td>
                <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($e['course_title']) ?></div>
                    <div style="font-size:11px; color:#94a3b8;"><?= $e['course_code'] ?></div>
                </td>
                <td><?= htmlspecialchars($e['faculty_name']) ?></td>
                <td style="text-align: right;">
                    <a href="enrollment.php?delete=<?= $e['enroll_id'] ?>" 
                       style="display:inline-block; background:rgba(239, 68, 68, 0.1); color:#ef4444; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:700; text-decoration:none; transition:all 0.3s ease;"
                       onmouseover="this.style.background='#ef4444'; this.style.color='white'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(239, 68, 68, 0.3)';"
                       onmouseout="this.style.background='rgba(239, 68, 68, 0.1)'; this.style.color='#ef4444'; this.style.transform='translateY(0)'; this.style.boxShadow='none';"
                       onclick="return confirm('Delete this enrollment?')">
                       Remove
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</div>

<script>
document.getElementById('student_select').addEventListener('change', function() {
    const studentId = this.value;
    const courseSelect = document.getElementById('course_select');
    
    courseSelect.innerHTML = '<option value="">-- Loading Courses... --</option>';
    courseSelect.disabled = true;

    if (!studentId) {
        courseSelect.innerHTML = '<option value="">-- Select Student First --</option>';
        return;
    }

    fetch('get_available_courses.php?student_id=' + studentId)
        .then(response => response.json())
        .then(data => {
            courseSelect.innerHTML = '<option value="">-- Select Course --</option>';
            if (data.length > 0) {
                data.forEach(course => {
                    const option = document.createElement('option');
                    option.value = course.course_id;
                    option.textContent = course.course_title + ' (' + course.course_code + ')';
                    courseSelect.appendChild(option);
                });
                courseSelect.disabled = false;
            } else {
                courseSelect.innerHTML = '<option value="">-- All Courses Enrolled --</option>';
            }
        })
        .catch(error => {
            console.error('Error fetching courses:', error);
            courseSelect.innerHTML = '<option value="">-- Error Loading Courses --</option>';
        });
});
</script>

</body>
</html>



