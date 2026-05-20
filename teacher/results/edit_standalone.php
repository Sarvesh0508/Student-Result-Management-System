<?php
include("../../includes/connect.php");
include("../../includes/calculations.php");
$root = "../../";
$msg = "";

// Step 3: Handle form submission (update result)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['result_id'])) {
    $id      = intval($_POST['result_id']);
    $marks   = floatval($_POST['marks']);
    $attempt = intval($_POST['attempt_no']);
    $exam    = $conn->real_escape_string($_POST['exam_month_year']);

    if      ($marks >= 90) $grade = 'O';
    elseif  ($marks >= 75) $grade = 'A';
    elseif  ($marks >= 60) $grade = 'B';
    elseif  ($marks >= 50) $grade = 'C';
    else                   $grade = 'F';

    $status  = ($marks >= 50) ? 'PASS' : 'FAIL';
    $backlog = ($marks >= 50) ? 'CLEARED' : 'ACTIVE';
    $conn->query("
        UPDATE result SET
            marks='$marks', grade='$grade', attempt_no=$attempt,
            result_status='$status', backlog_status='$backlog',
            exam_month_year='$exam'
        WHERE result_id=$id
    ");

    // Recalculate SGPA/CGPA
    $info = $conn->query("
        SELECT r.student_id, c.semester FROM result r
        JOIN course c ON r.course_id = c.course_id
        WHERE r.result_id = $id
    ")->fetch_assoc();
    if ($info) {
        recalculate_all_semesters($conn, $info['student_id']);
    }

    $msg = "success:Result updated successfully!";
}

// Step 2: Student selected — load their results
$selected_student = intval($_GET['student_id'] ?? 0);
$selected_result  = intval($_GET['id'] ?? 0);

// Load all students for dropdown
$students = $conn->query("SELECT student_id, name FROM student ORDER BY name");

// Load results for selected student
$results = [];
if ($selected_student > 0) {
    $res = $conn->query("
        SELECT r.result_id, c.course_title, r.marks, r.attempt_no,
               r.exam_month_year, r.result_status, r.grade
        FROM result r
        JOIN course c ON r.course_id = c.course_id
        WHERE r.student_id = $selected_student
        ORDER BY r.exam_month_year DESC
    ");
    while ($row = $res->fetch_assoc()) {
        $results[] = $row;
    }
}

// Load specific result for editing
$edit_row = null;
if ($selected_result > 0) {
    $edit_row = $conn->query("
        SELECT r.*, s.name, c.course_title
        FROM result r
        JOIN student s ON r.student_id = s.student_id
        JOIN course  c ON r.course_id  = c.course_id
        WHERE r.result_id = $selected_result
    ")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Result - ScoreHive</title>
<link rel="stylesheet" href="../../assets/css/layout.css?v=1.2">
</head>
<body>
<?php include("../../includes/header.php"); ?>
<div class="main">

<div class="page-title">
    Edit Result
    <a href="view.php" class="btn btn-dark">&#8592; Back to Results</a>
</div>

<?php if ($msg): list($type,$text) = explode(":",$msg,2); ?>
<div class="alert alert-<?= $type==='success'?'success':'danger' ?>">
    <?= htmlspecialchars($text) ?>
</div>
<?php endif; ?>

<!-- Step 1: Select Student -->
<div class="form-box" style="margin-bottom:20px;">
    <h3 style="margin-top:0;margin-bottom:15px;font-size:16px;color:#333;">
        &#128100; Step 1: Select Student
    </h3>
    <form method="GET">
        <label>Student Name</label>
        <select name="student_id" onchange="this.form.submit()" required>
            <option value="">-- Select Student --</option>
            <?php
            $students->data_seek(0);
            while ($s = $students->fetch_assoc()):
                $sel = ($selected_student === intval($s['student_id'])) ? 'selected' : '';
            ?>
            <option value="<?= $s['student_id'] ?>" <?= $sel ?>>
                <?= htmlspecialchars($s['name']) ?>
            </option>
            <?php endwhile; ?>
        </select>
    </form>
</div>

<!-- Step 2: Select Course/Result -->
<?php if ($selected_student > 0): ?>
<div class="form-box" style="margin-bottom:20px;">
    <h3 style="margin-top:0;margin-bottom:15px;font-size:16px;color:#333;">
        &#128218; Step 2: Select Course to Edit
    </h3>
    <?php if (empty($results)): ?>
        <p style="color:#888;">No results found for this student.</p>
    <?php else: ?>
    <table style="width:100%;border-collapse:collapse;font-size:14px;">
        <tr style="background:#f1f1f1;">
            <th style="padding:10px;text-align:left;">Course</th>
            <th style="padding:10px;">Marks</th>
            <th style="padding:10px;">Grade</th>
            <th style="padding:10px;">Attempt</th>
            <th style="padding:10px;">Exam</th>
            <th style="padding:10px;">Status</th>
            <th style="padding:10px;">Action</th>
        </tr>
        <?php foreach ($results as $r): ?>
        <tr style="border-bottom:1px solid #eee;">
            <td style="padding:10px;"><?= htmlspecialchars($r['course_title']) ?></td>
            <td style="padding:10px;text-align:center;"><b><?= $r['marks'] ?></b></td>
            <td style="padding:10px;text-align:center;"><?= $r['grade'] ?></td>
            <td style="padding:10px;text-align:center;"><?= $r['attempt_no'] ?></td>
            <td style="padding:10px;text-align:center;"><?= $r['exam_month_year'] ?></td>
            <td style="padding:10px;text-align:center;">
                <span class="badge <?= $r['result_status']==='PASS'?'badge-pass':'badge-fail' ?>">
                    <?= $r['result_status'] ?>
                </span>
            </td>
            <td style="padding:10px;text-align:center;">
                <a href="edit.php?student_id=<?= $selected_student ?>&id=<?= $r['result_id'] ?>"
                   class="btn btn-warning" style="padding:4px 12px;font-size:12px;">
                   Edit
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Step 3: Edit Form -->
<?php if ($edit_row): ?>
<div class="form-box">
    <h3 style="margin-top:0;margin-bottom:15px;font-size:16px;color:#333;">
        &#9998; Step 3: Update Result
    </h3>

    <div style="background:#f8f9fa;border-radius:6px;padding:12px;margin-bottom:15px;">
        <b>Student:</b> <?= htmlspecialchars($edit_row['name']) ?> &nbsp;|&nbsp;
        <b>Course:</b> <?= htmlspecialchars($edit_row['course_title']) ?>
    </div>

    <form method="POST">
        <input type="hidden" name="result_id" value="<?= $edit_row['result_id'] ?>">

        <label>Marks (0 – 100)</label>
        <input type="number" name="marks" min="0" max="100" step="0.01"
               value="<?= $edit_row['marks'] ?>" required>

        <label>Attempt Number</label>
        <input type="number" name="attempt_no" min="1"
               value="<?= $edit_row['attempt_no'] ?>" required>

        <label>Exam Month / Year</label>
        <input type="text" name="exam_month_year"
               value="<?= htmlspecialchars($edit_row['exam_month_year']) ?>" required>

        <div style="background:#fff3cd;border-radius:6px;padding:10px;
                    margin:12px 0;font-size:13px;">
            &#9432; Grade and Status will be auto-recalculated on save.
        </div>

        <button type="submit" class="btn btn-primary"
                style="width:100%;padding:11px;font-size:15px;">
            &#128190; Update Result
        </button>
    </form>
</div>
<?php endif; ?>

</div>
</body>
</html>



