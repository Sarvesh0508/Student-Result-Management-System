<?php
include("../includes/connect.php");
$student_root = "../";
include("nav.php");

/*
 * FIX: Cleared backlogs table now shows:
 *   - The failed attempt's marks (original fail row, backlog_status=CLEARED)
 *   - The clearing attempt's marks (the PASS row for the same course)
 * This makes it clear WHY it's cleared and avoids confusion of showing
 * "40 marks — CLEARED" which looked like a contradiction.
 *
 * LOGIC: A backlog is CLEARED only when a later attempt has result_status=PASS
 * and marks >= 50. The original fail row gets backlog_status=CLEARED.
 */

$active = $conn->query("
    SELECT r.*, c.course_title, c.course_code, c.semester, c.credit, c.category
    FROM result r
    JOIN course c ON r.course_id = c.course_id
    INNER JOIN (
        SELECT course_id, MAX(attempt_no) AS max_attempt
        FROM result
        WHERE student_id = $sid
        GROUP BY course_id
    ) latest ON r.course_id = latest.course_id AND r.attempt_no = latest.max_attempt
    WHERE r.student_id = $sid AND r.result_status = 'FAIL'
    ORDER BY c.semester
");
$active_count = $active->num_rows;

// For cleared: find any courses where current status is PASS but attempt > 1
$cleared = $conn->query("
    SELECT
        p.result_id, p.student_id, p.course_id,
        c.course_title, c.course_code, c.semester, c.credit, c.category,
        f.marks             AS marks,
        f.attempt_no        AS attempt_no,
        p.marks             AS cleared_marks,
        p.attempt_no        AS cleared_attempt,
        p.exam_month_year   AS cleared_exam
    FROM result p
    JOIN course c ON p.course_id = c.course_id
    JOIN result f 
        ON f.student_id = p.student_id 
       AND f.course_id = p.course_id 
       AND f.attempt_no < p.attempt_no
       AND f.result_status = 'FAIL'
    WHERE p.student_id = $sid 
      AND p.result_status = 'PASS' 
      AND p.attempt_no > 1
    ORDER BY c.semester, p.attempt_no
");
$cleared_count = $cleared->num_rows;

$active_rows  = [];
$cleared_rows = [];
while ($r = $active->fetch_assoc())  $active_rows[]  = $r;
while ($r = $cleared->fetch_assoc()) $cleared_rows[] = $r;
?>
<!DOCTYPE html>
<html>
<head>
<title>Arrears - ScoreHive</title>
<link rel="stylesheet" href="../assets/css/student_layout.css?v=1.2">
<style>
.modal-overlay {
    display:none; position:fixed; top:0; left:0;
    width:100%; height:100%; background:rgba(0,0,0,0.5);
    z-index:9999; justify-content:center; align-items:center;
}
.modal-overlay.active { display:flex; }
.modal-box {
    background:white; border-radius:12px; padding:30px;
    max-width:500px; width:90%; box-shadow:0 8px 30px rgba(0,0,0,0.2);
    position:relative; animation:popIn 0.2s ease;
}
@keyframes popIn {
    from { transform:scale(0.9); opacity:0; }
    to   { transform:scale(1);   opacity:1; }
}
.modal-close {
    position:absolute; top:12px; right:16px;
    font-size:20px; cursor:pointer; color:#888;
    background:none; border:none;
}
.modal-close:hover { color:#333; }
.modal-title {
    font-size:18px; font-weight:bold; margin-bottom:16px;
    padding-bottom:10px; border-bottom:2px solid #f0f0f0;
}
.detail-grid {
    display:grid; grid-template-columns:1fr 1fr; gap:14px;
    margin-bottom:16px;
}
.detail-item label {
    font-size:11px; color:#888; display:block;
    text-transform:uppercase; margin-bottom:3px;
}
.detail-item span { font-size:14px; font-weight:bold; color:#333; }
.status-badge {
    display:inline-block; padding:5px 14px;
    border-radius:20px; font-size:13px; font-weight:bold;
}
.status-active  { background:#ffe0e0; color:#dc3545; }
.status-cleared { background:#d4edda; color:#155724; }
.subject-link {
    color:#dc3545; font-weight:bold; cursor:pointer;
    text-decoration:underline dotted;
}
.subject-link:hover { color:#a71d2a; }
.subject-link-cleared {
    color:#155724; font-weight:bold; cursor:pointer;
    text-decoration:underline dotted;
}
.subject-link-cleared:hover { color:#0a3d1a; }
.marks-journey {
    display:flex; align-items:center; gap:8px;
    font-size:13px;
}
.marks-journey .fail-m {
    color:#dc3545; font-weight:bold; font-size:15px;
}
.marks-journey .pass-m {
    color:#28a745; font-weight:bold; font-size:15px;
}
.arrow-right { color:#888; font-size:16px; }
</style>
</head>
<body>
<div class="main">
<div class="page-title">⚠ Arrears / Backlogs</div>

<div class="stat-grid stat-grid-2" style="margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-icon red">⚠</div>
        <div>
            <div class="stat-val" style="color:#dc3545;"><?= $active_count ?></div>
            <div class="stat-lbl">Active Backlogs</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div>
            <div class="stat-val" style="color:#28a745;"><?= $cleared_count ?></div>
            <div class="stat-lbl">Cleared Backlogs</div>
        </div>
    </div>
</div>

<!-- Active Backlogs -->
<div class="card">
    <h3>🔴 Active Backlogs (<?= $active_count ?>)</h3>
    <?php if ($active_count === 0): ?>
    <div class="alert alert-success">✅ Great! You have no active backlogs.</div>
    <?php else: ?>
    <div class="alert alert-danger">
        ⚠ You must clear these subjects. Register for re-exam at the earliest.
    </div>
    <div class="table-wrap">
    <table>
        <tr>
            <th>#</th><th>Subject</th><th>Code</th>
            <th>Semester</th><th>Marks Scored</th>
            <th>Grade</th><th>Attempt</th><th>Exam</th>
        </tr>
        <?php foreach ($active_rows as $i => $r): ?>
        <tr style="background:#fff5f5;">
            <td><?= $i + 1 ?></td>
            <td>
                <span class="subject-link" onclick="showDetail('active', <?= $i ?>)">
                    <?= htmlspecialchars($r['course_title']) ?>
                </span>
            </td>
            <td style="color:#888;font-size:12px;"><?= $r['course_code'] ?></td>
            <td>Semester <?= $r['semester'] ?></td>
            <td>
                <span style="color:#dc3545;font-weight:bold;font-size:18px;">
                    <?= $r['marks'] ?>
                </span>
                <span style="color:#888;font-size:12px;"> / 100</span>
            </td>
            <td><span class="badge badge-f">F</span></td>
            <td style="text-align:center;"><?= $r['attempt_no'] ?></td>
            <td style="font-size:12px;"><?= $r['exam_month_year'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- Cleared Backlogs -->
<?php if ($cleared_count > 0): ?>
<div class="card">
    <h3>✅ Cleared Backlogs (<?= $cleared_count ?>)</h3>
    <div class="alert alert-success" style="font-size:13px;">
        ✅ These backlogs have been cleared by passing in a later attempt.
        The table shows: <b>Failed Marks → Cleared Marks</b>.
    </div>
    <div class="table-wrap">
    <table>
        <tr>
            <th>#</th><th>Subject</th><th>Semester</th>
            <th>Fail Marks (Attempt <?php /* attempt shown in data */ ?>)</th>
            <th>Cleared By</th><th>Status</th>
        </tr>
        <?php foreach ($cleared_rows as $i => $r): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td>
                <span class="subject-link-cleared" onclick="showDetail('cleared', <?= $i ?>)">
                    <?= htmlspecialchars($r['course_title']) ?>
                </span>
            </td>
            <td>Semester <?= $r['semester'] ?></td>
            <td>
                <div class="marks-journey">
                    <?php if ($r['marks'] !== null): ?>
                    <span class="fail-m"><?= $r['marks'] ?></span>
                    <small style="color:#888;">(Attempt <?= $r['attempt_no'] ?>)</small>
                    <?php else: ?>
                    <span style="color:#888;font-size:12px;">N/A</span>
                    <?php endif; ?>
                </div>
            </td>
            <td>
                <?php if ($r['cleared_marks'] !== null): ?>
                <div class="marks-journey">
                    <span class="arrow-right">→</span>
                    <span class="pass-m"><?= $r['cleared_marks'] ?></span>
                    <small style="color:#888;">
                        (Attempt <?= $r['cleared_attempt'] ?>
                        <?= $r['cleared_exam'] ? ', ' . $r['cleared_exam'] : '' ?>)
                    </small>
                </div>
                <?php else: ?>
                <span style="color:#888;font-size:12px;">—</span>
                <?php endif; ?>
            </td>
            <td><span class="badge badge-cleared">CLEARED ✅</span></td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>
<?php endif; ?>

</div>

<!-- Detail Modal -->
<div class="modal-overlay" id="detailModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal()">✕</button>
        <div class="modal-title" id="modalTitle">Subject Details</div>
        <div class="detail-grid" id="modalBody"></div>
        <div id="modalStatus"></div>
    </div>
</div>

<script>
const activeData  = <?= json_encode($active_rows) ?>;
const clearedData = <?= json_encode($cleared_rows) ?>;

function showDetail(type, index) {
    const r        = type === 'active' ? activeData[index] : clearedData[index];
    const isActive = (type === 'active');

    document.getElementById('modalTitle').innerHTML = '📘 ' + r.course_title;

    let clearedInfo = '';
    if (!isActive && r.cleared_marks !== null) {
        clearedInfo = `
        <div class="detail-item" style="grid-column:1/-1;">
            <label>Cleared By (Passing Attempt)</label>
            <span style="color:#28a745;">
                ${r.cleared_marks} marks — Attempt #${r.cleared_attempt}
                ${r.cleared_exam ? ' (' + r.cleared_exam + ')' : ''}
            </span>
        </div>`;
    }

    document.getElementById('modalBody').innerHTML = `
        <div class="detail-item">
            <label>Subject Code</label>
            <span>${r.course_code}</span>
        </div>
        <div class="detail-item">
            <label>Semester</label>
            <span>Semester ${r.semester}</span>
        </div>
        <div class="detail-item">
            <label>Credits</label>
            <span>${r.credit}</span>
        </div>
        <div class="detail-item">
            <label>Category</label>
            <span>${r.category}</span>
        </div>
        <div class="detail-item">
            <label>Marks Scored (Failed Attempt)</label>
            <span style="color:#dc3545;font-size:20px;">
                ${r.marks} <small style="font-size:12px;color:#888;">/ 100</small>
            </span>
        </div>
        <div class="detail-item">
            <label>Grade</label>
            <span style="color:#dc3545;">${r.grade}</span>
        </div>
        <div class="detail-item">
            <label>Attempt No</label>
            <span>${r.attempt_no}</span>
        </div>
        <div class="detail-item">
            <label>Exam Month/Year</label>
            <span>${r.exam_month_year}</span>
        </div>
        ${clearedInfo}
    `;

    document.getElementById('modalStatus').innerHTML = `
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid #eee;">
            <label style="font-size:11px;color:#888;text-transform:uppercase;">Status</label><br>
            <span class="status-badge ${isActive ? 'status-active' : 'status-cleared'}">
                ${isActive ? '⚠ ACTIVE BACKLOG' : '✅ CLEARED'}
            </span>
            ${isActive ? `<p style="margin-top:10px;font-size:13px;color:#856404;
                background:#fff3cd;padding:8px;border-radius:6px;">
                ⚠ Please register for re-exam to clear this backlog.</p>` : ''}
        </div>
    `;

    document.getElementById('detailModal').classList.add('active');
}

function closeModal() {
    document.getElementById('detailModal').classList.remove('active');
}
document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>


