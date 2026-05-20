<?php
include("../../includes/connect.php");
include("../../includes/calculations.php");
$root = "../../";
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam = $conn->real_escape_string($_POST['exam_month_year']);
    $success_count = 0;
    $students_to_recalc = [];

    $conn->begin_transaction();
    try {
        if (isset($_POST['marks']) && is_array($_POST['marks'])) {
            foreach ($_POST['marks'] as $index => $mark_val) {
                if ($mark_val === '' || $mark_val === null) continue;

                $sid     = intval($_POST['student_id'][$index]);
                $cid     = intval($_POST['course_id'][$index]);
                $attempt = intval($_POST['attempt_no'][$index]);
                $marks   = floatval($mark_val);

                // Grade calculation
                if      ($marks >= 90) $grade = 'O';
                elseif  ($marks >= 75) $grade = 'A';
                elseif  ($marks >= 60) $grade = 'B';
                elseif  ($marks >= 50) $grade = 'C';
                else                   $grade = 'F';

                $status = ($marks >= 50) ? 'PASS' : 'FAIL';

                // Row Locking (FOR UPDATE)
                $prev_fail = $conn->query("SELECT result_id FROM result WHERE student_id=$sid AND course_id=$cid AND result_status='FAIL' FOR UPDATE");
                $backlog = ($marks >= 50) ? ($prev_fail->num_rows > 0 ? 'CLEARED' : 'NONE') : 'ACTIVE';

                $check = $conn->query("SELECT result_id FROM result WHERE student_id=$sid AND course_id=$cid AND attempt_no=$attempt FOR UPDATE");
                if ($check->num_rows == 0) {
                    $conn->query("INSERT INTO result (student_id, course_id, marks, grade, attempt_no, result_status, backlog_status, exam_month_year) VALUES ($sid, $cid, $marks, '$grade', $attempt, '$status', '$backlog', '$exam')");
                    if ($status === 'PASS' && $attempt > 1) {
                        $conn->query("UPDATE result SET backlog_status='CLEARED' WHERE student_id=$sid AND course_id=$cid AND attempt_no < $attempt");
                    }
                    $students_to_recalc[] = $sid;
                    $success_count++;
                }
            }
        }

        foreach (array_unique($students_to_recalc) as $student_id) {
            recalculate_all_semesters($conn, $student_id);
        }

        $conn->commit();
        $msg = $success_count > 0 ? "success:Successfully saved $success_count result(s) with ACID transactions!" : "error:No new results were entered.";
    } catch (Exception $e) {
        $conn->rollback();
        $msg = "error:Transaction failed! " . $e->getMessage();
    }
}

// Fetch Pending Enrollments with Malpractice Check
$pending = $conn->query("
    SELECT DISTINCT e.student_id, e.course_id, s.name, s.register_number, c.course_title, c.course_code, c.semester,
           m.malpractice_type, m.action_taken
    FROM enrollment e
    JOIN student s ON e.student_id = s.student_id
    JOIN course c ON e.course_id = c.course_id
    LEFT JOIN result r ON e.student_id = r.student_id AND e.course_id = r.course_id
    LEFT JOIN malpractice m ON e.student_id = m.student_id AND e.course_id = m.course_id
    WHERE r.result_id IS NULL
    ORDER BY c.semester, c.course_title, s.name
");
?>
<!DOCTYPE html>
<html>
<head>
<title>Enter Bulk Results - ScoreHive</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/layout.css?v=1.2">
<style>
body { font-family: 'Inter', sans-serif; background-color: #f4f7f6; }
.page-title { margin-bottom:25px; color:#1e293b; font-size:24px; font-weight:800; letter-spacing:-0.5px; }

.table-box { background:white; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,0.03); border:1px solid rgba(0,0,0,0.02); overflow:hidden; margin-bottom:30px; }
table { width:100%; border-collapse:collapse; }
th { padding:14px 20px; text-align:left; font-size:12px; color:#64748b; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid #e2e8f0; background:#f8fafc; }
td { padding:12px 20px; font-size:14px; font-weight:500; color:#334155; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
tr:last-child td { border-bottom:none; }
tr:hover td { background:#f8fafc; }

.mark-input {
    width: 80px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;
    font-size: 14px; font-weight: 600; text-align: center; font-family: 'Inter', sans-serif;
}
.mark-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }

.attempt-input {
    width: 60px; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;
    font-size: 13px; text-align: center; font-family: 'Inter', sans-serif; background:#f8fafc;
}

.global-settings {
    background: white; border-radius: 16px; padding: 25px; margin-bottom: 25px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02);
    display: flex; align-items: center; gap: 20px;
}
.global-settings label { font-weight: 700; color: #1e293b; font-size: 14px; }
.global-settings input { 
    padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-family: 'Inter', sans-serif;
    width: 200px;
}
.btn-success {
    background: linear-gradient(135deg, #10b981, #059669); color: white; border: none;
    padding: 12px 25px; border-radius: 8px; font-weight: 600; font-size: 15px; cursor: pointer;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2); transition: all 0.2s;
}
.btn-success:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3); }

.empty-state { padding: 40px; text-align: center; color: #64748b; font-weight: 500; }
</style>
</head>
<body>
<?php include("../../includes/header.php"); ?>
<div class="main">

<div class="page-title">Bulk Enter Results</div>

<?php if ($msg): list($type,$text) = explode(":",$msg,2); ?>
<div class="alert alert-<?= $type==='success'?'success':'danger' ?>">
    <?= htmlspecialchars($text) ?>
</div>
<?php endif; ?>

<form method="POST">

<div class="global-settings">
    <label>Global Exam Month / Year:</label>
    <input type="text" name="exam_month_year" placeholder="e.g. May 2024" required 
           value="<?= htmlspecialchars($_POST['exam_month_year'] ?? 'May 2026') ?>"
           style="width:200px; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-family: 'Inter', sans-serif;">
    <div style="font-size:13px; color:#64748b; margin-left:auto;">
        <b>Grade Logic:</b> ≥90(O), ≥75(A), ≥60(B), ≥50(C), &lt;50(F)
    </div>
</div>

<div class="table-box">
    <?php if ($pending->num_rows > 0): ?>
    <table>
        <tr>
            <th>#</th>
            <th>Student</th>
            <th>Course</th>
            <th>Semester</th>
            <th style="text-align:center;">Attempt</th>
            <th style="text-align:center;">Marks (0-100)</th>
        </tr>
        <?php $i=1; while ($p = $pending->fetch_assoc()): ?>
        <tr>
            <td style="color:#64748b;"><?= $i++ ?></td>
            <td>
                <div style="font-weight:700; color:#1e293b;"><?= htmlspecialchars($p['name']) ?></div>
                <div style="font-size:12px; color:#94a3b8;"><?= htmlspecialchars($p['register_number']) ?></div>
                <?php if ($p['malpractice_type']): ?>
                    <div style="margin-top:4px;">
                        <span class="badge" style="background:#fee2e2; color:#b91c1c; font-size:10px; padding:2px 6px;">
                            ⚠️ MALPRACTICE: <?= htmlspecialchars($p['action_taken']) ?>
                        </span>
                    </div>
                <?php endif; ?>
            </td>
            <td>
                <div style="font-weight:600; color:#334155;"><?= htmlspecialchars($p['course_title']) ?></div>
                <div style="font-size:12px; color:#94a3b8;"><?= htmlspecialchars($p['course_code']) ?></div>
            </td>
            <td style="font-weight:600; color:#475569;">Sem <?= $p['semester'] ?></td>
            <td style="text-align:center;">
                <input type="number" name="attempt_no[]" value="1" min="1" class="attempt-input" required>
            </td>
            <td style="text-align:center;">
                <input type="hidden" name="student_id[]" value="<?= $p['student_id'] ?>">
                <input type="hidden" name="course_id[]" value="<?= $p['course_id'] ?>">
                <input type="number" name="marks[]" min="0" max="100" step="0.01" class="mark-input" placeholder="—">
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <div style="padding:20px; background:#f8fafc; border-top:1px solid #e2e8f0; text-align:right;">
        <button type="submit" class="btn-success">✅ Submit All Entered Marks</button>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div style="font-size:40px; margin-bottom:10px;">🎉</div>
        <div style="font-size:18px; color:#1e293b; font-weight:700;">All Caught Up!</div>
        <div>There are no pending enrollments waiting for results.</div>
    </div>
    <?php endif; ?>
</div>

</form>

</div>
</body>
</html>



