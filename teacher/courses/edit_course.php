<?php
include("../../includes/connect.php");
$root = "../../";
$id = intval($_GET['id'] ?? 0);
$msg = "";

if ($id === 0) { header("Location: list.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code   = $conn->real_escape_string(strtoupper(trim($_POST['course_code'])));
    $title  = $conn->real_escape_string(trim($_POST['course_title']));
    $credit = intval($_POST['credit']);
    $cat    = $conn->real_escape_string($_POST['category']);
    $sem    = intval($_POST['semester']);

    $conn->query("
        UPDATE course SET course_code='$code', course_title='$title',
        credit=$credit, category='$cat', semester=$sem
        WHERE course_id=$id
    ");

    // Recalculate SGPA/CGPA for all students who took this course
    include_once("../includes/calculations.php");
    $affected_students = $conn->query("SELECT DISTINCT student_id FROM result WHERE course_id = $id");
    while ($s = $affected_students->fetch_assoc()) {
        recalculate_all_semesters($conn, $s['student_id']);
    }

    $msg = "success:Course updated and all student SGPA/CGPA recalculated!";
}

$row = $conn->query("SELECT * FROM course WHERE course_id=$id")->fetch_assoc();
if (!$row) { echo "Course not found."; exit(); }
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Course - ScoreHive</title>
<link rel="stylesheet" href="../../assets/css/layout.css?v=1.2">
</head>
<body>
<?php include("../../includes/header.php"); ?>
<div class="main">

<div class="page-title">
    Edit Course
    <a href="list.php" class="btn btn-dark">&#8592; Back to List</a>
</div>

<?php if ($msg): list($type,$text) = explode(":",$msg,2); ?>
<div class="alert alert-<?= $type==='success'?'success':'danger' ?>">
    <?= htmlspecialchars($text) ?>
</div>
<?php endif; ?>

<div class="form-box">
<form method="POST">
    <label>Course Code</label>
    <input type="text" name="course_code"
           value="<?= htmlspecialchars($row['course_code']) ?>" required>

    <label>Course Title</label>
    <input type="text" name="course_title"
           value="<?= htmlspecialchars($row['course_title']) ?>" required>

    <label>Semester</label>
    <select name="semester" required>
        <?php for($s=1;$s<=8;$s++): ?>
        <option value="<?=$s?>" <?= $row['semester']==$s?'selected':'' ?>>
            Semester <?=$s?>
        </option>
        <?php endfor; ?>
    </select>

    <label>Credits</label>
    <select name="credit" required>
        <?php for($c=1;$c<=5;$c++): ?>
        <option value="<?=$c?>" <?= $row['credit']==$c?'selected':'' ?>>
            <?=$c?> Credit<?= $c>1?'s':'' ?>
        </option>
        <?php endfor; ?>
    </select>

    <label>Category</label>
    <select name="category" required>
        <?php
        $cats = ['Core','Elective','Lab','Professional Core','Allied','Foundation','Open Elective'];
        foreach ($cats as $cat):
            $sel = ($row['category'] === $cat) ? 'selected' : '';
        ?>
        <option value="<?= $cat ?>" <?= $sel ?>><?= $cat ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="btn btn-primary"
            style="width:100%;padding:11px;font-size:15px;margin-top:12px;">
        &#128190; Update Course
    </button>
</form>
</div>

</div>
</body>
</html>



