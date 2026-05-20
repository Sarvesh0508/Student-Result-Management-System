<?php
include("../../includes/connect.php");
$root = "../../";
?>
<!DOCTYPE html>
<html>
<head>
<title>Backlogs - ScoreHive</title>
<link rel="stylesheet" href="../../assets/css/layout.css?v=1.2">
</head>
<body>
<?php include("../../includes/header.php"); ?>
<div class="main">

<div class="page-title">Active Backlogs</div>

<?php
$rows = $conn->query("
    SELECT s.name, s.department,
           c.course_title, c.course_code,
           r.marks, r.attempt_no, r.exam_month_year, r.result_id
    FROM result r
    JOIN student s ON r.student_id = s.student_id
    JOIN course  c ON r.course_id  = c.course_id
    WHERE r.backlog_status = 'ACTIVE'
    ORDER BY s.name
");
$total = $rows->num_rows;
?>

<p style="color:#856404;background:#fff3cd;padding:10px 15px;
          border-radius:6px;font-size:14px;margin-bottom:15px;">
    &#9888; <b><?= $total ?></b> active backlog(s) found
</p>

<div class="table-box">
<table>
    <tr>
        <th>#</th><th>Student</th><th>Department</th>
        <th>Course</th><th>Marks</th><th>Attempt</th><th>Exam</th><th>Action</th>
    </tr>
    <?php $i=1; while ($r = $rows->fetch_assoc()): ?>
    <tr>
        <td><?= $i++ ?></td>
        <td><b><?= htmlspecialchars($r['name']) ?></b></td>
        <td><?= $r['department'] ?></td>
        <td><?= htmlspecialchars($r['course_title']) ?><br>
            <small style="color:#888;"><?= $r['course_code'] ?></small></td>
        <td style="color:#dc3545;font-weight:bold;"><?= $r['marks'] ?></td>
        <td><?= $r['attempt_no'] ?></td>
        <td><?= $r['exam_month_year'] ?></td>
        <td>
            <a href="edit.php?id=<?= $r['result_id'] ?>"
               class="btn btn-warning" style="padding:4px 9px;font-size:12px;">
                Edit
            </a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
</div>

</div>
</body>
</html>



