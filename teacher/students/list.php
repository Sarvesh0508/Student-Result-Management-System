<?php
include("../../includes/connect.php");
$root = "../../";

// Handle delete
if (isset($_GET['delete'])) {
    $did = intval($_GET['delete']);
    // Check if student has results
    $has_results = $conn->query("SELECT COUNT(*) c FROM result WHERE student_id=$did")->fetch_assoc()['c'];
    if ($has_results > 0) {
        $msg = "error:Cannot delete — this student has results entered!";
    } else {
        $conn->query("DELETE FROM enrollment WHERE student_id=$did");
        $conn->query("DELETE FROM semester_result WHERE student_id=$did");
        $conn->query("DELETE FROM malpractice WHERE student_id=$did");
        // Delete from users table too
        $email = $conn->query("SELECT email FROM student WHERE student_id=$did")->fetch_assoc()['email'] ?? '';
        if ($email) $conn->query("DELETE FROM users WHERE email='$email'");
        $conn->query("DELETE FROM student WHERE student_id=$did");
        header("Location: list.php?deleted=1");
        exit();
    }
}
if (isset($_GET['deleted'])) $msg = "success:Student deleted successfully!";
if (isset($_GET['added'])) $msg = "success:Student added successfully!";
?>
<!DOCTYPE html>
<html>
<head>
<title>Students - ScoreHive</title>
<link rel="stylesheet" href="../../assets/css/layout.css?v=1.2">
</head>
<body>
<?php include("../../includes/header.php"); ?>
<div class="main">

<div class="page-title">
    Students
    <a href="add.php" class="btn btn-success">+ Add Student</a>
</div>

<?php if (isset($msg)): list($type,$text) = explode(":",$msg,2); ?>
<div class="alert alert-<?= $type==='success'?'success':'danger' ?>">
    <?= htmlspecialchars($text) ?>
</div>
<?php endif; ?>

<!-- Search -->
<div class="filter-bar">
    <form method="GET" style="display:flex;gap:10px;width:100%;">
        <input type="text" name="search" placeholder="Search by name or department..."
               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="list.php" class="btn btn-dark">Reset</a>
    </form>
</div>

<?php
$where = "WHERE 1=1";
if (!empty($_GET['search'])) {
    $s = $conn->real_escape_string($_GET['search']);
    $where .= " AND (name LIKE '%$s%' OR department LIKE '%$s%')";
}
$rows = $conn->query("
    SELECT s.*, 
           (SELECT exam_month_year FROM result WHERE student_id = s.student_id ORDER BY exam_month_year ASC LIMIT 1) as first_exam
    FROM student s
    $where ORDER BY s.name
");
?>

<div class="table-box">
<table>
    <tr>
        <th>#</th><th>Reg. No.</th><th>Name</th><th>Department</th><th>Batch</th>
        <th>Mobile</th><th>Email</th><th>Action</th>
    </tr>
    <?php $i=1; while ($s = $rows->fetch_assoc()): ?>
    <tr>
        <td><?= $i++ ?></td>
        <td style="font-weight:700; color:#3b82f6;"><?= htmlspecialchars($s['register_number'] ?? 'N/A') ?></td>
        <td><b><?= htmlspecialchars($s['name']) ?></b></td>
        <td><?= $s['department'] ?></td>
        <td><?= htmlspecialchars($s['batch'] ?? 'N/A') ?></td>
        <td><?= $s['mobile_number'] ?></td>
        <td><?= $s['email'] ?></td>
        <td style="display:flex;gap:5px;">
            <a href="view.php?id=<?= $s['student_id'] ?>"
               class="btn btn-primary" style="padding:4px 9px;font-size:12px;">View</a>
            <a href="edit.php?id=<?= $s['student_id'] ?>"
               class="btn btn-warning" style="padding:4px 9px;font-size:12px;">Edit</a>
            <a href="list.php?delete=<?= $s['student_id'] ?>"
               class="btn btn-danger" style="padding:4px 9px;font-size:12px;"
               onclick="return confirm('Delete this student? This cannot be undone!')">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
</div>

</div>
</body>
</html>



