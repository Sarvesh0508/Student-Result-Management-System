<?php
include("../../includes/connect.php");
$root = "../../";
$msg  = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code  = $conn->real_escape_string(strtoupper(trim($_POST['course_code'])));
    $title = $conn->real_escape_string(trim($_POST['course_title']));
    $credit= intval($_POST['credit']);
    $cat   = $conn->real_escape_string($_POST['category']);
    $sem   = intval($_POST['semester']);

    $dup = $conn->query("SELECT course_id FROM course WHERE course_code='$code'");
    if ($dup->num_rows > 0) {
        $msg = "error:Course code $code already exists.";
    } else {
        $conn->query("
            INSERT INTO course (course_code, course_title, credit, category, semester)
            VALUES ('$code','$title',$credit,'$cat',$sem)
        ");
        $msg = "success:Course '$title' added successfully!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Add Course - ScoreHive</title>
<link rel="stylesheet" href="../../assets/css/layout.css?v=1.2">
</head>
<body>
<?php include("../../includes/header.php"); ?>
<div class="main">

<div class="page-title">
    Add New Course
    <a href="list.php" class="btn btn-dark">&#8592; Back to List</a>
</div>

<?php if ($msg): list($type,$text) = explode(":",$msg,2); ?>
<div class="alert alert-<?= $type==='success'?'success':'danger' ?>">
    <?= htmlspecialchars($text) ?>
    <?php if ($type==='success'): ?>
        &nbsp; <a href="list.php">View all courses &rarr;</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="form-box">
<form method="POST">

    <label>Course Code</label>
    <input type="text" name="course_code"
           value="<?= htmlspecialchars($_POST['course_code'] ?? '') ?>"
           placeholder="e.g. DBMS101" required>

    <label>Course Title</label>
    <input type="text" name="course_title"
           value="<?= htmlspecialchars($_POST['course_title'] ?? '') ?>"
           placeholder="e.g. Database Management Systems" required>

    <label>Semester</label>
    <select name="semester" required>
        <option value="">-- Select Semester --</option>
        <?php for($s=1;$s<=8;$s++): ?>
        <option value="<?=$s?>"
            <?= (($_POST['semester'] ?? '')==$s)?'selected':'' ?>>
            Semester <?=$s?>
        </option>
        <?php endfor; ?>
    </select>

    <label>Credits</label>
    <select name="credit" required>
        <?php for($c=1;$c<=5;$c++): ?>
        <option value="<?=$c?>"
            <?= (($_POST['credit'] ?? 3)==$c)?'selected':'' ?>>
            <?=$c?> Credit<?= $c>1?'s':'' ?>
        </option>
        <?php endfor; ?>
    </select>

    <label>Category</label>
    <select name="category" required>
        <?php
        $cats = ['Core','Elective','Lab','Professional Core',
                 'Allied','Foundation','Open Elective'];
        foreach ($cats as $cat):
            $sel = (($_POST['category'] ?? '') === $cat) ? 'selected' : '';
        ?>
        <option value="<?= $cat ?>" <?= $sel ?>><?= $cat ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="btn btn-success"
            style="width:100%;padding:11px;font-size:15px;margin-top:12px;">
        &#10003; Add Course
    </button>
</form>
</div>

</div>
</body>
</html>



