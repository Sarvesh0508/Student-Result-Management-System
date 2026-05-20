<?php
include("../includes/connect.php");
$student_root = "../";
include("nav.php");

/*
 * FIX: All stats use latest attempt per course only.
 * Low Marks Alert excludes subjects with backlog_status='CLEARED'
 * (i.e. old failed attempts that have been retaken and passed).
 * Grade distribution uses latest attempt only.
 * Rank total_students fixed with num_rows before loop.
 */

// Derived table: latest attempt per course for this student
$latest_subq = "
    SELECT r.*
    FROM result r
    INNER JOIN (
        SELECT course_id, MAX(attempt_no) AS max_attempt
        FROM result
        WHERE student_id = $sid
        GROUP BY course_id
    ) la ON r.course_id = la.course_id
        AND r.attempt_no = la.max_attempt
    WHERE r.student_id = $sid
";

// Full stats — latest attempts only
$stats = $conn->query("
    SELECT
        COUNT(*)                        AS total,
        SUM(result_status = 'PASS')     AS passed,
        SUM(result_status = 'FAIL')     AS failed,
        ROUND(AVG(marks), 1)            AS avg_marks,
        MAX(marks)                      AS highest,
        MIN(marks)                      AS lowest,
        SUM(backlog_status = 'ACTIVE')  AS active_backlogs
    FROM ($latest_subq) AS latest
")->fetch_assoc();

// Semester SGPA/CGPA trend
$sgpa_q = $conn->query(
    "SELECT semester, sgpa, cgpa FROM semester_result
     WHERE student_id=$sid ORDER BY semester"
);
$sem_labels=[]; $sgpa_data=[]; $cgpa_data=[];
while ($s = $sgpa_q->fetch_assoc()) {
    $sem_labels[] = "Sem " . $s['semester'];
    $sgpa_data[]  = floatval($s['sgpa']);
    $cgpa_data[]  = floatval($s['cgpa']);
}
$sem_labels_json = json_encode($sem_labels);
$sgpa_json       = json_encode($sgpa_data);
$cgpa_json       = json_encode($cgpa_data);

// Grade distribution — latest attempt per subject only
$grade_q = $conn->query("
    SELECT grade, COUNT(*) c
    FROM ($latest_subq) AS latest
    GROUP BY grade ORDER BY grade
");
$g_labels=[]; $g_data=[];
while ($g = $grade_q->fetch_assoc()) {
    $g_labels[] = $g['grade'];
    $g_data[]   = $g['c'];
}
$g_labels_json = json_encode($g_labels);
$g_data_json   = json_encode($g_data);

// Subject-wise marks chart — latest attempt per subject
$subj_q = $conn->query("
    SELECT c.course_title, r.marks, r.grade, r.result_status
    FROM result r JOIN course c ON r.course_id = c.course_id
    WHERE r.student_id = $sid
      AND r.attempt_no = (
          SELECT MAX(r2.attempt_no)
          FROM result r2
          WHERE r2.student_id = r.student_id
            AND r2.course_id  = r.course_id
      )
    ORDER BY r.marks DESC
");
$s_labels=[]; $s_marks=[]; $s_colors=[];
while ($s = $subj_q->fetch_assoc()) {
    $short      = strlen($s['course_title']) > 15
                ? substr($s['course_title'], 0, 15) . '...'
                : $s['course_title'];
    $s_labels[] = $short;
    $s_marks[]  = $s['marks'];
    $s_colors[] = $s['marks'] >= 75 ? '#28a745'
                : ($s['marks'] >= 50 ? '#ffc107' : '#dc3545');
}
$s_labels_json = json_encode($s_labels);
$s_marks_json  = json_encode($s_marks);
$s_colors_json = json_encode($s_colors);

// Rank — latest-attempt average, num_rows before loop (fixes "4 of 4" bug)
$rank_q = $conn->query("
    SELECT student_id, ROUND(AVG(marks), 2) avg_m
    FROM (
        SELECT r.*
        FROM result r
        INNER JOIN (
            SELECT student_id, course_id, MAX(attempt_no) AS max_attempt
            FROM result
            GROUP BY student_id, course_id
        ) la ON r.student_id = la.student_id
            AND r.course_id  = la.course_id
            AND r.attempt_no = la.max_attempt
    ) AS all_latest
    GROUP BY student_id
    ORDER BY avg_m DESC
");
$total_st = $rank_q->num_rows; // BEFORE loop
$rank = 1; $my_avg = 0;
while ($rr = $rank_q->fetch_assoc()) {
    if ($rr['student_id'] == $sid) { $my_avg = $rr['avg_m']; break; }
    $rank++;
}

$pass_pct = $stats['total'] > 0
    ? round(($stats['passed'] / $stats['total']) * 100, 1)
    : 0;

// Low marks alert — latest attempt only AND not a cleared backlog
$low_q = $conn->query("
    SELECT c.course_title, r.marks, r.grade
    FROM result r JOIN course c ON r.course_id = c.course_id
    WHERE r.student_id = $sid
      AND r.marks < 50
      AND r.result_status = 'FAIL'
      AND r.backlog_status != 'CLEARED'
      AND r.attempt_no = (
          SELECT MAX(r2.attempt_no)
          FROM result r2
          WHERE r2.student_id = r.student_id
            AND r2.course_id  = r.course_id
      )
    ORDER BY r.marks
");
?>
<!DOCTYPE html>
<html>
<head>
<title>Performance - ScoreHive</title>
<link rel="stylesheet" href="../assets/css/student_layout.css?v=1.2">
</head>
<body>
<div class="main">
<div class="page-title">📊 Performance Analysis</div>

<!-- Summary Stats -->
<div class="stat-grid stat-grid-4" style="margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div>
            <div class="stat-val" style="color:#28a745;"><?= $pass_pct ?>%</div>
            <div class="stat-lbl">Pass Rate</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">📈</div>
        <div>
            <div class="stat-val"><?= $stats['avg_marks'] ?></div>
            <div class="stat-lbl">Average Marks</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">⬆</div>
        <div>
            <div class="stat-val" style="color:#28a745;"><?= $stats['highest'] ?></div>
            <div class="stat-lbl">Highest Marks</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">⬇</div>
        <div>
            <div class="stat-val" style="color:#dc3545;"><?= $stats['lowest'] ?></div>
            <div class="stat-lbl">Lowest Marks</div>
        </div>
    </div>
</div>

<!-- Charts Row 1 -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
    <div class="card">
        <h3>📊 Subject-wise Marks (Latest Attempt)</h3>
        <canvas id="subjectChart" height="200"></canvas>
    </div>
    <div class="card">
        <h3>🎓 Grade Distribution (Latest Attempt)</h3>
        <canvas id="gradeChart" height="200"></canvas>
    </div>
</div>

<!-- SGPA/CGPA Trend -->
<?php if (count($sem_labels) > 0): ?>
<div class="card">
    <h3>📉 SGPA / CGPA Trend</h3>
    <canvas id="trendChart" height="100"></canvas>
</div>
<?php endif; ?>

<!-- Low Marks Alert — only active fails, not cleared backlogs -->
<?php if ($low_q->num_rows > 0): ?>
<div class="card">
    <h3>📉 Low Marks Alert (Below 50)</h3>
    <div class="alert alert-danger">
        ⚠ Focus on these subjects — you need to clear them.
    </div>
    <div class="table-wrap">
    <table>
        <tr><th>Subject</th><th>Marks</th><th>Grade</th></tr>
        <?php while ($lw = $low_q->fetch_assoc()): ?>
        <tr style="background:#fff5f5;">
            <td><b style="color:#dc3545;"><?= htmlspecialchars($lw['course_title']) ?></b></td>
            <td>
                <div class="marks-bar-wrap">
                    <span style="color:#dc3545;font-weight:bold;"><?= $lw['marks'] ?></span>
                    <div class="marks-bar">
                        <div class="marks-bar-fill"
                             style="width:<?= $lw['marks'] ?>%;background:#dc3545;">
                        </div>
                    </div>
                </div>
            </td>
            <td><span class="badge badge-f">F</span></td>
        </tr>
        <?php endwhile; ?>
    </table>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="alert alert-success">
        ✅ Great! No subjects with marks below 50 in your latest attempts.
    </div>
</div>
<?php endif; ?>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('subjectChart'), {
    type:'bar',
    data:{
        labels:<?= $s_labels_json ?>,
        datasets:[{
            label:'Marks', data:<?= $s_marks_json ?>,
            backgroundColor:<?= $s_colors_json ?>, borderRadius:6
        }]
    },
    options:{
        indexAxis:'y',
        plugins:{legend:{display:false}},
        scales:{x:{beginAtZero:true,max:100}}
    }
});

new Chart(document.getElementById('gradeChart'), {
    type:'pie',
    data:{
        labels:<?= $g_labels_json ?>,
        datasets:[{
            data:<?= $g_data_json ?>,
            backgroundColor:['#28a745','#007bff','#17a2b8','#ffc107','#dc3545'],
            borderWidth:2
        }]
    },
    options:{
        plugins:{
            legend:{
                position:'right',
                labels:{ padding:20, font:{family:"'Outfit', sans-serif", weight:600} }
            }
        }
    }
});

<?php if (count($sem_labels) > 0): ?>
new Chart(document.getElementById('trendChart'), {
    type:'line',
    data:{
        labels:<?= $sem_labels_json ?>,
        datasets:[
            {
                label:'SGPA', data:<?= $sgpa_json ?>,
                borderColor:'#ff8c00', backgroundColor:'rgba(255,140,0,0.1)',
                tension:0.4, fill:true, pointRadius:5, clip: false
            },
            {
                label:'CGPA', data:<?= $cgpa_json ?>,
                borderColor:'#007bff', backgroundColor:'rgba(0,123,255,0.1)',
                tension:0.4, fill:true, pointRadius:5, clip: false
            }
        ]
    },
    options:{
        layout: { padding: { top: 10 } },
        plugins:{
            legend:{
                position:'bottom',
                labels:{ padding:25, font:{family:"'Outfit', sans-serif", weight:600} }
            }
        },
        scales:{
            y:{beginAtZero:false, min:0, max:10, ticks:{stepSize:1}},
            x:{grid:{display:false}}
        }
    }
});
<?php endif; ?>
</script>
</body>
</html>


