<?php
include("../../includes/connect.php");
$root = "../../";
$id = intval($_GET['id'] ?? 0);
$msg = "";

if ($id === 0) { header("Location: list.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type   = $conn->real_escape_string($_POST['malpractice_type']);
    $action = $conn->real_escape_string($_POST['action_taken']);
    $sem    = intval($_POST['semester']);

    $conn->query("
        UPDATE malpractice SET malpractice_type='$type', action_taken='$action', semester=$sem
        WHERE malpractice_id=$id
    ");
    $msg = "success:Malpractice case updated!";
}

$row = $conn->query("
    SELECT m.*, s.name, c.course_title FROM malpractice m
    JOIN student s ON m.student_id=s.student_id
    JOIN course c ON m.course_id=c.course_id
    WHERE m.malpractice_id=$id
")->fetch_assoc();
if (!$row) { echo "Case not found."; exit(); }
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Malpractice - ScoreHive</title>
<link rel="stylesheet" href="../../assets/css/layout.css?v=1.2">
</head>
<body>
<?php include("../../includes/header.php"); ?>
<div class="main">

<div class="page-title">
    Edit Malpractice Case
    <a href="list.php" class="btn btn-dark">&#8592; Back</a>
</div>

<?php if ($msg): list($type,$text) = explode(":",$msg,2); ?>
<div class="alert alert-<?= $type==='success'?'success':'danger' ?>">
    <?= htmlspecialchars($text) ?>
</div>
<?php endif; ?>

<div class="form-box">
    <div style="background:#f8f9fa;border-radius:6px;padding:12px;margin-bottom:15px;">
        <b>Student:</b> <?= htmlspecialchars($row['name']) ?> &nbsp;|&nbsp;
        <b>Course:</b> <?= htmlspecialchars($row['course_title']) ?>
    </div>

    <form method="POST">
        <label>Semester</label>
        <input type="number" name="semester" min="1" max="8"
               value="<?= $row['semester'] ?>" required>

        <label>Malpractice Type</label>
        <select name="malpractice_type" required>
            <?php
            $types = ['Copying in Exam','Mobile Phone Usage','Impersonation',
                      'Plagiarism','Result Tampering','Other'];
            foreach ($types as $t):
                $sel = ($row['malpractice_type']===$t)?'selected':'';
            ?>
            <option value="<?= $t ?>" <?= $sel ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>

        <label>Action Taken</label>
        <select name="action_taken" required>
            <?php
            $actions = ['Warning Issued','Result Withheld','Exam Cancelled',
                        'Suspended','Expelled','Under Investigation'];
            foreach ($actions as $a):
                $sel = ($row['action_taken']===$a)?'selected':'';
            ?>
            <option value="<?= $a ?>" <?= $sel ?>><?= $a ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-primary"
                style="width:100%;padding:11px;font-size:15px;margin-top:12px;">
            &#128190; Update Case
        </button>
    </form>
</div>

</div>
</body>
</html>



