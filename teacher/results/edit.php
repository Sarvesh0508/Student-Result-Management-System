<?php
include("../../includes/connect.php");
include("../../includes/calculations.php");
$root = "../../";
$id = intval($_GET['id'] ?? 0);
$msg = "";

if ($id === 0) {
    header("Location: view.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['result_id'])) {
    $id      = intval($_POST['result_id']);
    $marks   = floatval($_POST['marks']);
    $attempt = intval($_POST['attempt_no']);
    $exam    = $conn->real_escape_string($_POST['exam_month_year']);
    $status_manual = $_POST['result_status'] ?? ''; // Manual override for WITHHELD/INCOMPLETE

    $conn->begin_transaction();
    try {
        // Lock this row
        $current = $conn->query("SELECT student_id, course_id FROM result WHERE result_id=$id FOR UPDATE")->fetch_assoc();
        if (!$current) throw new Exception("Result not found.");
        
        $sid = $current['student_id'];
        $cid = $current['course_id'];

        if      ($marks >= 90) $grade = 'O';
        elseif  ($marks >= 75) $grade = 'A';
        elseif  ($marks >= 60) $grade = 'B';
        elseif  ($marks >= 50) $grade = 'C';
        else                   $grade = 'F';

        // Logic: if manual status is set to special values, use it. Otherwise calculate.
        if (in_array($status_manual, ['WITHHELD', 'INCOMPLETE'])) {
            $status = $status_manual;
            $backlog = 'ACTIVE'; // Usually these count as active arrears
        } else {
            $status = ($marks >= 50) ? 'PASS' : 'FAIL';
            // Check previous fail
            $prev_fail = $conn->query("SELECT result_id FROM result WHERE student_id=$sid AND course_id=$cid AND result_status='FAIL' AND result_id != $id FOR UPDATE");
            $backlog = ($marks >= 50) ? ($prev_fail->num_rows > 0 ? 'CLEARED' : 'NONE') : 'ACTIVE';
        }

        $conn->query("
            UPDATE result SET
                marks='$marks', grade='$grade', attempt_no=$attempt,
                result_status='$status', backlog_status='$backlog',
                exam_month_year='$exam'
            WHERE result_id=$id
        ");

        if ($status === 'PASS' && $attempt > 1) {
            $conn->query("UPDATE result SET backlog_status='CLEARED' WHERE student_id=$sid AND course_id=$cid AND attempt_no < $attempt");
        }

        recalculate_all_semesters($conn, $sid);
        
        $conn->commit();
        $msg = "success:Result updated successfully with full ACID transactions!";
    } catch (Exception $e) {
        $conn->rollback();
        $msg = "error:Update failed! " . $e->getMessage();
    }
}

$row = $conn->query("
    SELECT r.*, s.name, c.course_title, m.malpractice_type, m.action_taken
    FROM result r
    JOIN student s ON r.student_id = s.student_id
    JOIN course  c ON r.course_id  = c.course_id
    LEFT JOIN malpractice m ON r.student_id = m.student_id AND r.course_id = m.course_id
    WHERE r.result_id = $id
")->fetch_assoc();

if (!$row) { echo "Result not found."; exit(); }
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
    <a href="view.php" class="btn btn-dark">&#8592; Back</a>
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
        <?php if ($row['malpractice_type']): ?>
            <div style="margin-top:8px; padding:8px; background:#fee2e2; color:#b91c1c; border-radius:6px; font-size:12px; font-weight:700;">
                ⚠️ ALERT: Student has a malpractice record for this course (<?= htmlspecialchars($row['action_taken']) ?>)
            </div>
        <?php endif; ?>
    </div>

    <form method="POST">
        <input type="hidden" name="result_id" value="<?= $row['result_id'] ?>">

        <label>Marks (0 – 100)</label>
        <input type="number" name="marks" min="0" max="100" step="0.01"
               value="<?= $row['marks'] ?>" required>

        <label>Result Status (Override)</label>
        <select name="result_status">
            <option value="">-- Auto Calculate --</option>
            <option value="PASS" <?= $row['result_status']==='PASS'?'selected':'' ?>>PASS</option>
            <option value="FAIL" <?= $row['result_status']==='FAIL'?'selected':'' ?>>FAIL</option>
            <option value="WITHHELD" <?= $row['result_status']==='WITHHELD'?'selected':'' ?>>WITHHELD</option>
            <option value="INCOMPLETE" <?= $row['result_status']==='INCOMPLETE'?'selected':'' ?>>INCOMPLETE</option>
        </select>

        <label>Attempt Number</label>
        <input type="number" name="attempt_no" min="1"
               value="<?= $row['attempt_no'] ?>" required>

        <label>Exam Month / Year</label>
        <input type="text" name="exam_month_year"
               value="<?= htmlspecialchars($row['exam_month_year']) ?>" required>

        <div style="background:#fff3cd;border-radius:6px;padding:10px;margin:12px 0;font-size:13px;">
            &#9432; Grade, Status and SGPA/CGPA will be auto-recalculated on save.
        </div>

        <button type="submit" class="btn btn-primary"
                style="width:100%;padding:11px;font-size:15px;">
            &#128190; Update Result
        </button>
    </form>
</div>

</div>
</body>
</html>



