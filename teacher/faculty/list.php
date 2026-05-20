<?php
include("../../includes/connect.php");
$root = "../../";

// Handle delete
if (isset($_GET['delete'])) {
    $did = intval($_GET['delete']);
    $has = $conn->query("SELECT COUNT(*) c FROM enrollment WHERE faculty_id=$did")->fetch_assoc()['c'];
    if ($has > 0) {
        $msg = "error:Cannot delete — faculty has enrolled courses!";
    } else {
        $conn->query("DELETE FROM faculty WHERE faculty_id=$did");
        header("Location: list.php?deleted=1");
        exit();
    }
}
if (isset($_GET['deleted'])) $msg = "success:Faculty deleted successfully!";

$rows = $conn->query("SELECT f.*, COUNT(e.enroll_id) courses
    FROM faculty f LEFT JOIN enrollment e ON f.faculty_id=e.faculty_id
    GROUP BY f.faculty_id ORDER BY f.faculty_name");
?>
<!DOCTYPE html>
<html>
<head>
<title>Faculty - ScoreHive</title>
<link rel="stylesheet" href="../../assets/css/layout.css?v=1.2">
</head>
<body>
<?php include("../../includes/header.php"); ?>
<div class="main">

<div class="page-title">
    Faculty
    <a href="add.php" class="btn btn-success">+ Add Faculty</a>
</div>

<?php if (isset($msg)): list($type,$text) = explode(":",$msg,2); ?>
<div class="alert alert-<?= $type==='success'?'success':'danger' ?>">
    <?= htmlspecialchars($text) ?>
</div>
<?php endif; ?>

<div class="table-box">
<table>
    <tr>
        <th>#</th><th>Name</th><th>Department</th>
        <th>Email</th><th>Courses</th><th>Action</th>
    </tr>
    <?php $i=1; while ($f = $rows->fetch_assoc()): ?>
    <tr>
        <td><?= $i++ ?></td>
        <td><b><?= htmlspecialchars($f['faculty_name']) ?></b></td>
        <td><?= $f['department'] ?></td>
        <td><?= $f['email'] ?></td>
        <td><?= $f['courses'] ?> course(s)</td>
        <td style="display:flex; gap:5px;">
            <a href="view.php?id=<?= $f['faculty_id'] ?>"
               class="btn btn-primary" style="padding:4px 9px;font-size:12px;">View</a>
            <a href="edit.php?id=<?= $f['faculty_id'] ?>"
               class="btn btn-warning" style="padding:4px 9px;font-size:12px;">Edit</a>
            <a href="list.php?delete=<?= $f['faculty_id'] ?>"
               class="btn btn-danger" style="padding:4px 9px;font-size:12px;"
               onclick="return confirm('Delete this faculty?')">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
</div>

</div>
</body>
</html>



