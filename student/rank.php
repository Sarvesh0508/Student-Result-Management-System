<?php
include("../includes/connect.php");
$student_root = "../";
include("nav.php");

/*
 * FIX 1: Ranking uses latest attempt per course for every student,
 *         so retake attempts don't drag down (or inflate) averages.
 * FIX 2: total_r and passed count DISTINCT subjects (latest attempt),
 *         not raw result rows. Anita shows 2/2, not 2/3.
 * FIX 3: total_students from num_rows — never stops early.
 */

$rank_q = $conn->query("
    SELECT
        s.name,
        s.department,
        s.student_id,
        ROUND(AVG(lat.marks), 2)                AS avg_m,
        COUNT(lat.course_id)                    AS total_r,
        SUM(lat.result_status = 'PASS')         AS passed
    FROM student s
    JOIN (
        SELECT r.*
        FROM result r
        INNER JOIN (
            SELECT student_id, course_id, MAX(attempt_no) AS max_attempt
            FROM result
            GROUP BY student_id, course_id
        ) la ON r.student_id = la.student_id
            AND r.course_id  = la.course_id
            AND r.attempt_no = la.max_attempt
    ) lat ON s.student_id = lat.student_id
    GROUP BY s.student_id, s.name, s.department
    ORDER BY avg_m DESC
");

$rank           = 1;
$my_rank        = 0;
$total_students = $rank_q->num_rows; // BEFORE loop
$all_ranks      = [];

while ($rr = $rank_q->fetch_assoc()) {
    $rr['rank'] = $rank;
    if ($rr['student_id'] == $sid) $my_rank = $rank;
    $all_ranks[] = $rr;
    $rank++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Rank - ScoreHive</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/student_layout.css?v=1.2">
<style>
/* ── Rank Specific Styles ── */
.rank-card {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    color: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    text-align: center;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.rank-card::before {
    content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px;
    background: linear-gradient(to right, #ff8c00, #facc15);
}
.rank-num {
    font-size: 72px; font-weight: 800; line-height: 1; margin: 10px 0;
    background: linear-gradient(135deg, #ff8c00, #facc15);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.rank-lbl { font-size: 15px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }

.marks-bar-wrap { display: flex; align-items: center; gap: 10px; }
.marks-bar { width: 60px; height: 6px; border-radius: 4px; background: #e2e8f0; overflow: hidden; flex-shrink: 0; }
.marks-bar-fill { height: 100%; border-radius: 4px; }

/* ── Animations ── */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-up {
    animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    opacity: 0;
}
.delay-1 { animation-delay: 0.1s; }
.delay-2 { animation-delay: 0.2s; }
</style>
</head>
<body>
<div class="main">
<div class="page-title animate-fade-up">🏆 Class Ranking</div>

<!-- My Rank Card -->
<div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;margin-bottom:25px;" class="animate-fade-up delay-1">
    <div class="rank-card">
        <div style="font-size:14px;opacity:0.9;margin-bottom:5px;">Your Class Rank</div>
        <div class="rank-num"><?= $my_rank ?></div>
        <div class="rank-lbl">out of <?= $total_students ?> students</div>
        <?php if ($my_rank === 1): ?>
        <div style="margin-top:15px;font-size:22px;font-weight:800;">🥇 Topper!</div>
        <?php elseif ($my_rank === 2): ?>
        <div style="margin-top:15px;font-size:22px;font-weight:800;">🥈 2nd Place!</div>
        <?php elseif ($my_rank === 3): ?>
        <div style="margin-top:15px;font-size:22px;font-weight:800;">🥉 3rd Place!</div>
        <?php elseif ($my_rank <= round($total_students * 0.25)): ?>
        <div style="margin-top:15px;font-size:16px;font-weight:700;color:#facc15;">⭐ Top 25%</div>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-bottom:0;">
        <h3>📊 Your Position</h3>
        <?php
        $my_row = null;
        foreach ($all_ranks as $ar) {
            if ($ar['student_id'] == $sid) { $my_row = $ar; break; }
        }
        if ($my_row):
        ?>
        <div style="display:flex;gap:20px;flex-wrap:wrap;">
            <div>
                <div style="font-size:28px;font-weight:bold;color:#ff8c00;">
                    <?= $my_row['avg_m'] ?>
                </div>
                <div style="font-size:12px;color:#888;">Average Marks</div>
            </div>
            <div>
                <div style="font-size:28px;font-weight:bold;color:#28a745;">
                    <?= $my_row['passed'] ?>
                </div>
                <div style="font-size:12px;color:#888;">Subjects Passed</div>
            </div>
            <div>
                <div style="font-size:28px;font-weight:bold;color:#007bff;">
                    <?= $my_row['total_r'] ?>
                </div>
                <div style="font-size:12px;color:#888;">Total Subjects</div>
            </div>
        </div>
        <?php
        $pct = $total_students > 1
             ? round((($total_students - $my_rank) / ($total_students - 1)) * 100)
             : 100;
        ?>
        <div style="margin-top:25px;">
            <div style="font-size:14px;color:#64748b;font-weight:700;margin-bottom:8px;">
                Better than <?= $pct ?>% of class
            </div>
            <div style="background:#e9ecef;border-radius:4px;height:12px;">
                <div style="width:<?= $pct ?>%;
                            background:linear-gradient(to right,#ff8c00,#28a745);
                            height:100%;border-radius:4px;"></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Full Leaderboard -->
<div class="card animate-fade-up delay-2">
    <h3>🏆 Class Leaderboard</h3>
    <div class="table-wrap">
    <table>
        <tr>
            <th>Rank</th><th>Name</th><th>Department</th>
            <th>Avg Marks</th><th>Passed / Total</th>
        </tr>
        <?php foreach ($all_ranks as $r):
            $is_me = ($r['student_id'] == $sid);
        ?>
        <tr style="<?= $is_me ? 'background:#fff8e1;border-left:3px solid #ff8c00;' : '' ?>">
            <td>
                <b style="font-size:15px;">
                    <?php
                    if     ($r['rank'] === 1) echo '🥇';
                    elseif ($r['rank'] === 2) echo '🥈';
                    elseif ($r['rank'] === 3) echo '🥉';
                    else echo '#' . $r['rank'];
                    ?>
                </b>
            </td>
            <td>
                <b><?= htmlspecialchars($r['name']) ?></b>
                <?= $is_me
                    ? ' <span style="background:#ff8c00;color:white;padding:2px 8px;
                                     border-radius:10px;font-size:11px;">You</span>'
                    : '' ?>
            </td>
            <td><?= htmlspecialchars($r['department']) ?></td>
            <td>
                <div class="marks-bar-wrap">
                    <b style="color:<?= $r['avg_m']>=75?'#10b981':($r['avg_m']>=50?'#f59e0b':'#ef4444') ?>;">
                        <?= $r['avg_m'] ?>
                    </b>
                    <div class="marks-bar">
                        <div class="marks-bar-fill"
                             style="width:<?= min($r['avg_m'],100) ?>%;
                             background:<?= $r['avg_m']>=75?'#10b981':($r['avg_m']>=50?'#f59e0b':'#ef4444') ?>;">
                        </div>
                    </div>
                </div>
            </td>
            <td><?= $r['passed'] ?> / <?= $r['total_r'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>

</div>
</body>
</html>


