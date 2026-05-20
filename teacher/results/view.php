<?php
include("../../includes/connect.php");
include("../../includes/calculations.php");
$root = "../../";

// Handle delete
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);

    // Get student_id and semester before deleting
    $info = $conn->query("
        SELECT r.student_id, c.semester FROM result r
        JOIN course c ON r.course_id = c.course_id
        WHERE r.result_id = $del_id
    ")->fetch_assoc();

    $conn->query("DELETE FROM result WHERE result_id=$del_id");

    // Recalculate after delete
    if ($info) {
        recalculate_all_semesters($conn, $info['student_id']);
    }

    header("Location: view.php?deleted=1");
    exit();
}

if (isset($_GET['deleted'])) $msg = "success:Result deleted and SGPA/CGPA recalculated!";
?>
<!DOCTYPE html>
<html>
<head>
<title>View Results - ScoreHive</title>
<link rel="stylesheet" href="../../assets/css/layout.css?v=1.2">
</head>
<body>
<?php include("../../includes/header.php"); ?>
<div class="main">

<div class="page-title">
    All Results
    <a href="enter.php" class="btn btn-success">+ Enter Result</a>
</div>

<?php if (isset($msg)): list($type,$text) = explode(":",$msg,2); ?>
<div class="alert alert-<?= $type==='success'?'success':'danger' ?>">
    <?= htmlspecialchars($text) ?>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="filter-bar">
<form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;width:100%;">
    <input type="text" name="search"
           placeholder="Search student name..."
           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
    <select name="status">
        <option value="">All Status</option>
        <option value="PASS" <?= ($_GET['status']??'')==='PASS'?'selected':'' ?>>Pass</option>
        <option value="FAIL" <?= ($_GET['status']??'')==='FAIL'?'selected':'' ?>>Fail</option>
    </select>
    <select name="backlog">
        <option value="">All Backlogs</option>
        <option value="ACTIVE"  <?= ($_GET['backlog']??'')==='ACTIVE' ?'selected':'' ?>>Active</option>
        <option value="CLEARED" <?= ($_GET['backlog']??'')==='CLEARED'?'selected':'' ?>>Cleared</option>
        <option value="NONE"    <?= ($_GET['backlog']??'')==='NONE'   ?'selected':'' ?>>None</option>
    </select>
    <select name="semester">
        <option value="">All Semesters</option>
        <?php for($s=1; $s<=8; $s++): ?>
        <option value="<?= $s ?>" <?= ($_GET['semester']??'') == $s ? 'selected' : '' ?>>Semester <?= $s ?></option>
        <?php endfor; ?>
    </select>
    <button type="submit" class="btn btn-primary">Filter</button>
    <a href="view.php" class="btn btn-dark">Reset</a>
</form>
</div>

<?php
$where = "WHERE 1=1";
if (!empty($_GET['search'])) {
    $s = $conn->real_escape_string($_GET['search']);
    $where .= " AND st.name LIKE '%$s%'";
}
if (!empty($_GET['status'])) {
    $st = $conn->real_escape_string($_GET['status']);
    $where .= " AND r.result_status='$st'";
}
if (!empty($_GET['backlog'])) {
    $bl = $conn->real_escape_string($_GET['backlog']);
    $where .= " AND r.backlog_status='$bl'";
}
if (!empty($_GET['semester'])) {
    $sem = intval($_GET['semester']);
    $where .= " AND c.semester=$sem";
}

$rows = $conn->query("
    SELECT r.result_id, st.name, st.student_id,
           c.course_title, r.marks, r.grade,
           r.attempt_no, r.result_status,
           r.backlog_status, r.exam_month_year,
           c.semester
    FROM result r
    JOIN (SELECT student_id, course_id, MAX(attempt_no) as max_attempt FROM result GROUP BY student_id, course_id) rm 
      ON r.student_id = rm.student_id AND r.course_id = rm.course_id AND r.attempt_no = rm.max_attempt
    JOIN student st ON r.student_id = st.student_id
    JOIN course  c  ON r.course_id  = c.course_id
    $where
    ORDER BY c.semester, st.name, r.exam_month_year DESC
");
?>

<div class="table-box">
<table>
    <tr>
        <th>#</th><th>Sem</th><th>Student</th><th>Course</th><th>Marks</th>
        <th>Grade</th><th>Attempt</th><th>Status</th>
        <th>Backlog</th><th>Exam</th><th>Action</th>
    </tr>
    <?php 
    $i=1; 
    $last_sem = 0;
    while ($r = $rows->fetch_assoc()): 
        if ($r['semester'] != $last_sem):
            $last_sem = $r['semester'];
    ?>
    <tr style="background:#f8fafc; font-weight:800; color:#1e293b;">
        <td colspan="11" style="padding:10px 20px; border-bottom:2px solid #e2e8f0;">
            Semester <?= $last_sem ?>
        </td>
    </tr>
    <?php endif; ?>
    <tr>
        <td><?= $i++ ?></td>
        <td style="font-weight:700; color:#3b82f6;">S<?= $r['semester'] ?></td>
        <td>
            <a href="../students/view.php?id=<?= $r['student_id'] ?>"
               style="color:#007bff;text-decoration:none;">
                <?= htmlspecialchars($r['name']) ?>
            </a>
        </td>
        <td><?= htmlspecialchars($r['course_title']) ?></td>
        <td><b><?= $r['marks'] ?></b></td>
        <td><?= $r['grade'] ?></td>
        <td><?= $r['attempt_no'] ?></td>
        <td>
            <span class="badge <?= $r['result_status']==='PASS'?'badge-pass':'badge-fail' ?>">
                <?= $r['result_status'] ?>
            </span>
        </td>
        <td>
            <span class="badge <?= $r['backlog_status']==='ACTIVE'?'badge-active':'badge-none' ?>">
                <?= $r['backlog_status'] ?>
            </span>
        </td>
        <td><?= $r['exam_month_year'] ?></td>
        <td style="display:flex;gap:4px;">
            <a href="edit.php?id=<?= $r['result_id'] ?>"
               class="btn btn-warning" style="padding:4px 9px;font-size:12px;">Edit</a>
            <a href="view.php?delete=<?= $r['result_id'] ?>"
               class="btn btn-danger" style="padding:4px 9px;font-size:12px;"
               onclick="return confirm('Delete this result? SGPA/CGPA will be recalculated!')">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
</div>

</div>
</body>
</html>



