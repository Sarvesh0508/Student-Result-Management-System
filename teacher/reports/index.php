<?php
include("../../includes/connect.php");
$root = "../../";

// ── Data for charts ──────────────────────────────────────────────

// 1. Pass vs Fail
$pass = $conn->query("SELECT COUNT(*) c FROM result WHERE result_status='PASS'")->fetch_assoc()['c'];
$fail = $conn->query("SELECT COUNT(*) c FROM result WHERE result_status='FAIL'")->fetch_assoc()['c'];

// 2. Grade distribution
$grades = [];
$gq = $conn->query("SELECT grade, COUNT(*) c FROM result GROUP BY grade ORDER BY grade");
while ($g = $gq->fetch_assoc()) $grades[$g['grade']] = $g['c'];
$grade_labels = json_encode(array_keys($grades));
$grade_data   = json_encode(array_values($grades));

// 3. Avg marks per course (top 8)
$course_labels = []; $course_avgs = [];
$cq = $conn->query("
    SELECT c.course_title, ROUND(AVG(r.marks),1) avg_marks
    FROM result r JOIN course c ON r.course_id=c.course_id
    GROUP BY r.course_id ORDER BY avg_marks DESC LIMIT 8
");
while ($c = $cq->fetch_assoc()) {
    $course_labels[] = $c['course_title'];
    $course_avgs[]   = $c['avg_marks'];
}
$course_labels_json = json_encode($course_labels);
$course_avgs_json   = json_encode($course_avgs);

// 4. Marks distribution buckets
$buckets = [
    '90-100' => $conn->query("SELECT COUNT(*) c FROM result WHERE marks>=90")->fetch_assoc()['c'],
    '75-89'  => $conn->query("SELECT COUNT(*) c FROM result WHERE marks>=75 AND marks<90")->fetch_assoc()['c'],
    '60-74'  => $conn->query("SELECT COUNT(*) c FROM result WHERE marks>=60 AND marks<75")->fetch_assoc()['c'],
    '50-59'  => $conn->query("SELECT COUNT(*) c FROM result WHERE marks>=50 AND marks<60")->fetch_assoc()['c'],
    '0-49'   => $conn->query("SELECT COUNT(*) c FROM result WHERE marks<50")->fetch_assoc()['c'],
];
$bucket_labels = json_encode(array_keys($buckets));
$bucket_data   = json_encode(array_values($buckets));

// 5. Summary stats
$total_students  = $conn->query("SELECT COUNT(*) c FROM student")->fetch_assoc()['c'];
$total_results   = $conn->query("SELECT COUNT(*) c FROM result")->fetch_assoc()['c'];
$avg_marks       = $conn->query("SELECT ROUND(AVG(marks),2) c FROM result")->fetch_assoc()['c'];
$top_student_q   = $conn->query("
    SELECT s.name, ROUND(AVG(r.marks),1) avg
    FROM result r JOIN student s ON r.student_id=s.student_id
    GROUP BY r.student_id ORDER BY avg DESC LIMIT 1
")->fetch_assoc();
$pass_pct = $total_results > 0 ? round(($pass / $total_results) * 100, 1) : 0;

// 6. Department-wise pass rate
$dept_labels = []; $dept_pass = []; $dept_fail = [];
$dq = $conn->query("
    SELECT s.department,
           SUM(r.result_status='PASS') AS passed,
           SUM(r.result_status='FAIL') AS failed
    FROM result r JOIN student s ON r.student_id=s.student_id
    GROUP BY s.department
");
while ($d = $dq->fetch_assoc()) {
    $dept_labels[] = $d['department'];
    $dept_pass[]   = (int)$d['passed'];
    $dept_fail[]   = (int)$d['failed'];
}
$dept_labels_json = json_encode($dept_labels);
$dept_pass_json   = json_encode($dept_pass);
$dept_fail_json   = json_encode($dept_fail);
?>
<!DOCTYPE html>
<html>
<head>
<title>Reports - ScoreHive</title>
<link rel="stylesheet" href="../../assets/css/layout.css?v=1.2">
<style>
.stat-grid {
    display:grid; grid-template-columns:repeat(5,1fr);
    gap:15px; margin-bottom:25px;
}
.stat { background:white; border-radius:10px; padding:16px 18px;
        box-shadow:0 1px 4px rgba(0,0,0,0.08); text-align:center; }
.stat .val { font-size:26px; font-weight:bold; color:#0f2027; }
.stat .lbl { font-size:12px; color:#888; margin-top:3px; }

.chart-grid {
    display:grid; grid-template-columns:1fr 1fr;
    gap:20px; margin-bottom:20px;
}
.chart-box {
    background:white; border-radius:10px; padding:20px;
    box-shadow:0 1px 4px rgba(0,0,0,0.08);
}
.chart-box h3 { margin:0 0 15px; font-size:15px; color:#0f2027; }
.chart-full {
    background:white; border-radius:10px; padding:20px;
    box-shadow:0 1px 4px rgba(0,0,0,0.08); margin-bottom:20px;
}
.chart-full h3 { margin:0 0 15px; font-size:15px; color:#0f2027; }
</style>
</head>
<body>
<?php include("../../includes/header.php"); ?>
<div class="main">

<div class="page-title">Reports & Analytics</div>

<!-- Summary Stats -->
<div class="stat-grid">
    <div class="stat">
        <div class="val"><?= $total_students ?></div>
        <div class="lbl">Total Students</div>
    </div>
    <div class="stat">
        <div class="val"><?= $total_results ?></div>
        <div class="lbl">Total Results</div>
    </div>
    <div class="stat">
        <div class="val" style="color:#28a745;"><?= $pass_pct ?>%</div>
        <div class="lbl">Pass Rate</div>
    </div>
    <div class="stat">
        <div class="val" style="color:#007bff;"><?= $avg_marks ?></div>
        <div class="lbl">Avg Marks</div>
    </div>
    <div class="stat">
        <div class="val" style="font-size:16px;padding-top:5px;">
            <?= htmlspecialchars($top_student_q['name'] ?? 'N/A') ?>
        </div>
        <div class="lbl">Top Performer (<?= $top_student_q['avg'] ?? '-' ?>)</div>
    </div>
</div>

<!-- Row 1: Doughnut + Grade Bar -->
<div class="chart-grid">
    <div class="chart-box">
        <h3>&#128200; Pass vs Fail</h3>
        <canvas id="passFailChart" height="220"></canvas>
    </div>
    <div class="chart-box">
        <h3>&#127891; Grade Distribution</h3>
        <canvas id="gradeChart" height="220"></canvas>
    </div>
</div>

<!-- Row 2: Marks Distribution -->
<div class="chart-full">
    <h3>&#128202; Marks Distribution (Buckets)</h3>
    <canvas id="bucketChart" height="100"></canvas>
</div>

<!-- Row 3: Avg per course -->
<div class="chart-full">
    <h3>&#128218; Average Marks per Course</h3>
    <canvas id="courseChart" height="100"></canvas>
</div>

<!-- Row 4: Dept pass/fail (only if multiple depts) -->
<?php if (count($dept_labels) > 1): ?>
<div class="chart-full">
    <h3>&#127979; Department-wise Pass / Fail</h3>
    <canvas id="deptChart" height="100"></canvas>
</div>
<?php endif; ?>

<!-- Top Students Table -->
<div class="chart-full">
    <h3>&#127942; Top 10 Students by Average Marks</h3>
    <div class="table-box">
    <table>
        <tr>
            <th>Rank</th><th>Student</th><th>Department</th>
            <th>Avg Marks</th><th>Results</th>
        </tr>
        <?php
        $top = $conn->query("
            SELECT s.name, s.department,
                   ROUND(AVG(r.marks),2) avg_m,
                   COUNT(r.result_id) total_r
            FROM result r
            JOIN student s ON r.student_id = s.student_id
            GROUP BY r.student_id
            ORDER BY avg_m DESC
            LIMIT 10
        ");
        $rank = 1;
        while ($t = $top->fetch_assoc()):
        ?>
        <tr>
            <td>
                <?php if ($rank===1) echo '&#127945;';
                      elseif ($rank===2) echo '&#129352;';
                      elseif ($rank===3) echo '&#129353;';
                      else echo $rank; ?>
            </td>
            <td><b><?= htmlspecialchars($t['name']) ?></b></td>
            <td><?= $t['department'] ?></td>
            <td>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="background:#e8f4fd;border-radius:4px;height:8px;
                                width:<?= min(100, $t['avg_m']) ?>px;
                                background:linear-gradient(to right,#007bff,#28a745);">
                    </div>
                    <b><?= $t['avg_m'] ?></b>
                </div>
            </td>
            <td><?= $t['total_r'] ?></td>
        </tr>
        <?php $rank++; endwhile; ?>
    </table>
    </div>
</div>

</div>

<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const COLORS = ['#28a745','#dc3545','#007bff','#ffc107','#343a40','#17a2b8','#6f42c1','#fd7e14'];

// 1. Pass vs Fail Doughnut
new Chart(document.getElementById('passFailChart'), {
    type: 'doughnut',
    data: {
        labels: ['Pass','Fail'],
        datasets:[{ data:[<?=$pass?>,<?=$fail?>],
            backgroundColor:['#28a745','#dc3545'], borderWidth:2 }]
    },
    options:{ plugins:{ legend:{ position:'bottom' } }, cutout:'65%' }
});

// 2. Grade Bar
new Chart(document.getElementById('gradeChart'), {
    type: 'bar',
    data: {
        labels: <?= $grade_labels ?>,
        datasets:[{ label:'Students', data:<?= $grade_data ?>,
            backgroundColor:COLORS, borderRadius:6 }]
    },
    options:{
        plugins:{ legend:{ display:false } },
        scales:{ y:{ beginAtZero:true, ticks:{ stepSize:1 } } }
    }
});

// 3. Marks Bucket Bar
new Chart(document.getElementById('bucketChart'), {
    type: 'bar',
    data: {
        labels: <?= $bucket_labels ?>,
        datasets:[{ label:'Number of Students',
            data:<?= $bucket_data ?>,
            backgroundColor:['#28a745','#17a2b8','#007bff','#ffc107','#dc3545'],
            borderRadius:6 }]
    },
    options:{
        plugins:{ legend:{ display:false } },
        scales:{ y:{ beginAtZero:true, ticks:{ stepSize:1 } } }
    }
});

// 4. Avg per course horizontal bar
new Chart(document.getElementById('courseChart'), {
    type: 'bar',
    data: {
        labels: <?= $course_labels_json ?>,
        datasets:[{ label:'Average Marks',
            data:<?= $course_avgs_json ?>,
            backgroundColor:'#007bff', borderRadius:6 }]
    },
    options:{
        indexAxis:'y',
        plugins:{ legend:{ display:false } },
        scales:{ x:{ beginAtZero:true, max:100 } }
    }
});

// 5. Dept stacked bar
<?php if (count($dept_labels) > 1): ?>
new Chart(document.getElementById('deptChart'), {
    type: 'bar',
    data: {
        labels: <?= $dept_labels_json ?>,
        datasets:[
            { label:'Pass', data:<?= $dept_pass_json ?>,
              backgroundColor:'#28a745', borderRadius:4 },
            { label:'Fail', data:<?= $dept_fail_json ?>,
              backgroundColor:'#dc3545', borderRadius:4 }
        ]
    },
    options:{
        plugins:{ legend:{ position:'bottom' } },
        scales:{ x:{ stacked:true }, y:{ stacked:true, beginAtZero:true } }
    }
});
<?php endif; ?>
</script>

</body>
</html>



