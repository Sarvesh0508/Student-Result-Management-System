<?php
include("../../includes/connect.php");
$root = "../../";
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid  = intval($_POST['student_id']);
    $cid  = intval($_POST['course_id']);
    $sem  = intval($_POST['semester']);
    $type = $conn->real_escape_string($_POST['malpractice_type']);
    $act  = $conn->real_escape_string($_POST['action_taken']);

    $conn->query("
        INSERT INTO malpractice
        (student_id, course_id, semester, malpractice_type, action_taken)
        VALUES ($sid, $cid, $sem, '$type', '$act')
    ");
    $msg = "success:Malpractice case recorded successfully.";
}

$students = $conn->query("SELECT student_id, name FROM student ORDER BY name");
$courses  = $conn->query("SELECT course_id, course_title FROM course ORDER BY course_title");
?>
<!DOCTYPE html>
<html>
<head>
<title>Add Malpractice - ScoreHive</title>
<link rel="stylesheet" href="../../assets/css/layout.css?v=1.2">
</head>
<body>
<?php include("../../includes/header.php"); ?>
<div class="main">

<div class="page-title">
    Add Malpractice Case
    <a href="list.php" class="btn btn-dark">&#8592; Back</a>
</div>

<?php if ($msg): list($type,$text) = explode(":",$msg,2); ?>
<div class="alert alert-<?= $type==='success'?'success':'danger' ?>">
    <?= htmlspecialchars($text) ?>
</div>
<?php endif; ?>

<div class="form-box">
<form method="POST">

    <label>Student</label>
    <select name="student_id" required>
        <option value="">-- Select Student --</option>
        <?php while ($s = $students->fetch_assoc()): ?>
        <option value="<?= $s['student_id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
        <?php endwhile; ?>
    </select>

    <label>Course</label>
    <select name="course_id" required>
        <option value="">-- Select Course --</option>
        <?php while ($c = $courses->fetch_assoc()): ?>
        <option value="<?= $c['course_id'] ?>"><?= htmlspecialchars($c['course_title']) ?></option>
        <?php endwhile; ?>
    </select>

    <label>Semester</label>
    <input type="number" name="semester" min="1" max="8" required>

    <label>Malpractice Type</label>
    <select name="malpractice_type" required>
        <option value="">-- Select Type --</option>
        <option>Copying in Exam</option>
        <option>Mobile Phone Usage</option>
        <option>Impersonation</option>
        <option>Possession of Cheat Sheet</option>
        <option>Other</option>
    </select>

    <label>Action Taken</label>
    <select name="action_taken" required>
        <option value="">-- Select Action --</option>
        <option>Warning Issued</option>
        <option>Result Withheld</option>
        <option>Exam Cancelled</option>
        <option>Suspended</option>
        <option>Under Investigation</option>
    </select>

    <button type="submit" class="btn btn-danger"
            style="width:100%;padding:11px;font-size:15px;margin-top:12px;">
        &#128683; Record Case
    </button>
</form>
</div>

</div>
</body>
</html>



