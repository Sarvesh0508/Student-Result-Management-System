<?php
include("../../includes/connect.php");
$root = "../../";

// Handle delete
if (isset($_GET['delete'])) {
    $did = intval($_GET['delete']);
    $conn->query("DELETE FROM malpractice WHERE malpractice_id=$did");
    header("Location: list.php?deleted=1");
    exit();
}
if (isset($_GET['deleted'])) $msg = "success:Malpractice case deleted!";
?>
<!DOCTYPE html>
<html>
<head>
<title>Malpractice - ScoreHive</title>
<link rel="stylesheet" href="../../assets/css/layout.css?v=1.2">
</head>
<body>
<?php include("../../includes/header.php"); ?>
<div class="main">

<div class="page-title">
    Malpractice Cases
    <a href="add.php" class="btn btn-danger">+ Add Case</a>
</div>

<?php if (isset($msg)): list($type,$text) = explode(":",$msg,2); ?>
<div class="alert alert-<?= $type==='success'?'success':'danger' ?>">
    <?= htmlspecialchars($text) ?>
</div>
<?php endif; ?>

<?php
$rows = $conn->query("
    SELECT m.*, s.name, c.course_title
    FROM malpractice m
    JOIN student s ON m.student_id = s.student_id
    JOIN course  c ON m.course_id  = c.course_id
    ORDER BY m.malpractice_id DESC
");
?>

<div class="table-box">
<table>
    <tr>
        <th>#</th><th>Student</th><th>Course</th>
        <th>Semester</th><th>Type</th><th>Action Taken</th><th>Action</th>
    </tr>
    <?php $i=1; while ($r = $rows->fetch_assoc()): ?>
    <tr>
        <td><?= $i++ ?></td>
        <td><b><?= htmlspecialchars($r['name']) ?></b></td>
        <td><?= htmlspecialchars($r['course_title']) ?></td>
        <td>Semester <?= $r['semester'] ?></td>
        <td><span class="badge badge-fail"><?= htmlspecialchars($r['malpractice_type']) ?></span></td>
        <td><?= htmlspecialchars($r['action_taken']) ?></td>
        <td>
            <a href="edit_malpractice.php?id=<?= $r['malpractice_id'] ?>"
               class="btn btn-warning" style="padding:4px 9px;font-size:12px;">Edit</a>
            <a href="list.php?delete=<?= $r['malpractice_id'] ?>"
               class="btn btn-danger" style="padding:4px 9px;font-size:12px;"
               onclick="return confirm('Delete this case?')">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
</div>

</div>
</body>
</html>



