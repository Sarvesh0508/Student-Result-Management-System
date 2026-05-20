<?php
include("../includes/connect.php");
$student_root = "../";
include("nav.php");

$rows = $conn->query("
    SELECT m.*, c.course_title, c.course_code
    FROM malpractice m
    JOIN course c ON m.course_id=c.course_id
    WHERE m.student_id=$sid
    ORDER BY m.malpractice_id DESC
");
?>
<!DOCTYPE html>
<html>
<head>
<title>Malpractice - ScoreHive</title>
<link rel="stylesheet" href="../assets/css/student_layout.css?v=1.2">
</head>
<body>
<div class="main">
<div class="page-title">🚫 Malpractice Cases</div>

<div class="alert alert-danger">
    ⚠ The following malpractice case(s) are recorded against your name.
    Contact your department for clarification.
</div>

<div class="card">
<div class="table-wrap">
<table>
    <tr>
        <th>#</th><th>Subject</th><th>Semester</th>
        <th>Type</th><th>Action Taken</th>
    </tr>
    <?php $i=1; while ($r = $rows->fetch_assoc()): ?>
    <tr style="background:#fff5f5;">
        <td><?= $i++ ?></td>
        <td>
            <b><?= htmlspecialchars($r['course_title']) ?></b><br>
            <small style="color:#888;"><?= $r['course_code'] ?></small>
        </td>
        <td>Semester <?= $r['semester'] ?></td>
        <td><span class="badge badge-fail">
            <?= htmlspecialchars($r['malpractice_type']) ?>
        </span></td>
        <td style="color:#856404;font-weight:bold;">
            <?= htmlspecialchars($r['action_taken']) ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
</div>
</div>

</div>
</body>
</html>


