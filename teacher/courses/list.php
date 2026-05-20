<?php
include("../../includes/connect.php");
$root = "../../";

// Handle delete
if (isset($_GET['delete'])) {
    $did = intval($_GET['delete']);
    // Check if course has results
    $has_results = $conn->query("SELECT COUNT(*) c FROM result WHERE course_id=$did")->fetch_assoc()['c'];
    if ($has_results > 0) {
        $msg = "error:Cannot delete — this course has results entered!";
    } else {
        $conn->query("DELETE FROM enrollment WHERE course_id=$did");
        $conn->query("DELETE FROM course WHERE course_id=$did");
        header("Location: list.php?deleted=1");
        exit();
    }
}
if (isset($_GET['deleted'])) $msg = "success:Course deleted successfully!";
?>
<!DOCTYPE html>
<html>
<head>
<title>Courses - ScoreHive</title>
<link rel="stylesheet" href="../../assets/css/layout.css?v=1.2">
</head>
<body>
<?php include("../../includes/header.php"); ?>
<div class="main">

<div class="page-title">
    Courses
    <a href="add.php" class="btn btn-success">+ Add Course</a>
</div>

<?php if (isset($msg)): list($type,$text) = explode(":",$msg,2); ?>
<div class="alert alert-<?= $type==='success'?'success':'danger' ?>">
    <?= htmlspecialchars($text) ?>
</div>
<?php endif; ?>

<div class="filter-bar">
<form method="GET" style="display:flex;gap:10px;width:100%;">
    <input type="text" name="search"
           placeholder="Search by title or code..."
           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
    <select name="semester">
        <option value="">All Semesters</option>
        <?php for($s=1;$s<=8;$s++): ?>
        <option value="<?=$s?>" <?= ($_GET['semester']??'')==$s?'selected':'' ?>>
            Semester <?=$s?>
        </option>
        <?php endfor; ?>
    </select>
    <button type="submit" class="btn btn-primary">Filter</button>
    <a href="list.php" class="btn btn-dark">Reset</a>
</form>
</div>

<?php
$where = "WHERE 1=1";
if (!empty($_GET['search'])) {
    $s = $conn->real_escape_string($_GET['search']);
    $where .= " AND (course_title LIKE '%$s%' OR course_code LIKE '%$s%')";
}
if (!empty($_GET['semester'])) {
    $sm = intval($_GET['semester']);
    $where .= " AND semester=$sm";
}
$rows = $conn->query("SELECT * FROM course $where ORDER BY semester, course_code");
?>

<div class="table-box">
<table>
    <tr>
        <th>#</th><th>Code</th><th>Title</th>
        <th>Semester</th><th>Credits</th><th>Category</th>
        <th>Enrolled</th><th>Action</th>
    </tr>
    <?php $i=1; while ($c = $rows->fetch_assoc()):
        $enrolled = $conn->query(
            "SELECT COUNT(*) c FROM enrollment WHERE course_id={$c['course_id']}"
        )->fetch_assoc()['c'];
    ?>
    <tr>
        <td><?= $i++ ?></td>
        <td><b><?= htmlspecialchars($c['course_code']) ?></b></td>
        <td><?= htmlspecialchars($c['course_title']) ?></td>
        <td>Semester <?= $c['semester'] ?></td>
        <td><?= $c['credit'] ?></td>
        <td><span class="badge badge-none"><?= htmlspecialchars($c['category'] ?? 'Core') ?></span></td>
        <td><?= $enrolled ?> students</td>
        <td style="display:flex; gap:5px;">
            <a href="view.php?id=<?= $c['course_id'] ?>"
               class="btn btn-primary" style="padding:4px 9px;font-size:12px;">View</a>
            <a href="edit_course.php?id=<?= $c['course_id'] ?>"
               class="btn btn-warning" style="padding:4px 9px;font-size:12px;">Edit</a>
            <a href="list.php?delete=<?= $c['course_id'] ?>"
               class="btn btn-danger" style="padding:4px 9px;font-size:12px;"
               onclick="return confirm('Delete this course?')">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
</div>

</div>
</body>
</html>



