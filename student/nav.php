<?php
// Protect all student pages
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}
$sid       = $_SESSION['student_id'];
$sname     = $_SESSION['user_name'];
$initials  = strtoupper(substr($sname, 0, 1));

// Get department
$info = $conn->query("SELECT * FROM student WHERE student_id=$sid")->fetch_assoc();
$dept = $info['department'] ?? 'Student';

// Notifications count - Robust logic
$backlog_count_row = $conn->query("
    SELECT COUNT(*) c FROM (
        SELECT r.course_id
        FROM result r
        INNER JOIN (
            SELECT course_id, MAX(attempt_no) AS max_attempt
            FROM result
            WHERE student_id = $sid
            GROUP BY course_id
        ) la ON r.course_id = la.course_id AND r.attempt_no = la.max_attempt
        WHERE r.student_id = $sid AND r.result_status = 'FAIL'
    ) t
")->fetch_assoc();
$backlog_count = $backlog_count_row['c'] ?? 0;

$mal_count = $conn->query(
    "SELECT COUNT(*) c FROM malpractice WHERE student_id=$sid"
)->fetch_assoc()['c'];
?>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-logo" style="display:flex; align-items:center; gap:15px;">
        <div id="mobile-toggle" style="display:none; cursor:pointer; font-size:24px; color:#1e293b;">☰</div>
        <img src="<?= $student_root ?>assets/img/logo.png" alt="logo" style="width:35px;">
        <h2 style="display:flex; align-items:center; margin:0;">
            <span class="score" style="font-weight:800; color:#1e293b; font-size:22px;">Score</span><span class="hive" style="font-weight:800; color:#ff8c00; font-size:22px;">Hive</span>
            <span style="font-size:11px; color:#ff8c00; font-weight:800; background:rgba(255,140,0,0.1); padding:5px 10px; border-radius:12px; margin-left:12px; text-transform:uppercase; letter-spacing:1px; border:1px solid rgba(255,140,0,0.2);">Student Portal</span>
        </h2>
    </div>
    <div class="topbar-right">
        <?php if ($backlog_count > 0): ?>
        <span style="background:#dc3545;color:white;padding:4px 10px;
                     border-radius:20px;font-size:12px;">
            ⚠ <?= $backlog_count ?> Backlog<?= $backlog_count>1?'s':'' ?>
        </span>
        <?php endif; ?>
        <span>👤 <?= htmlspecialchars($sname) ?></span>
        <a href="<?= $student_root ?>logout.php"
           style="background:#dc3545;color:white;padding:6px 13px;
                  border-radius:6px;text-decoration:none;font-size:12px;">
            Logout
        </a>
    </div>
</div>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-profile">
        <div class="sidebar-avatar"><?= $initials ?></div>
        <div class="sidebar-name"><?= htmlspecialchars($sname) ?></div>
        <div class="sidebar-dept"><?= $dept ?></div>
    </div>

    <div class="sidebar-section">STUDENT HUB</div>
    <a href="dashboard.php"
       class="<?= basename($_SERVER['PHP_SELF'])==='dashboard.php'?'active':'' ?>">
        <span class="icon">🏠</span> Dashboard
    </a>
    <a href="profile.php"
       class="<?= basename($_SERVER['PHP_SELF'])==='profile.php'?'active':'' ?>">
        <span class="icon">👤</span> My Profile
    </a>

    <div class="sidebar-section">Results</div>
    <a href="results.php"
       class="<?= basename($_SERVER['PHP_SELF'])==='results.php'?'active':'' ?>">
        <span class="icon">📄</span> View Results
    </a>
    <a href="semester.php"
       class="<?= basename($_SERVER['PHP_SELF'])==='semester.php'?'active':'' ?>">
        <span class="icon">📚</span> Semester-wise
    </a>
    <a href="backlogs.php"
       class="<?= basename($_SERVER['PHP_SELF'])==='backlogs.php'?'active':'' ?>">
        <span class="icon">🚨</span> Arrears / Backlogs
        <?php if ($backlog_count > 0): ?>
        <span style="background:#dc3545;color:white;padding:2px 7px;
                     border-radius:10px;font-size:11px;margin-left:auto;">
            <?= $backlog_count ?>
        </span>
        <?php endif; ?>
    </a>
    <a href="marksheet.php"
       class="<?= basename($_SERVER['PHP_SELF'])==='marksheet.php'?'active':'' ?>">
        <span class="icon">📜</span> Print Marksheet
    </a>

    <div class="sidebar-section">Analysis</div>
    <a href="performance.php"
       class="<?= basename($_SERVER['PHP_SELF'])==='performance.php'?'active':'' ?>">
        <span class="icon">📊</span> Performance
    </a>
    <a href="rank.php"
       class="<?= basename($_SERVER['PHP_SELF'])==='rank.php'?'active':'' ?>">
        <span class="icon">🏆</span> My Rank
    </a>

    <div class="sidebar-section">Other</div>
    <?php if ($mal_count > 0): ?>
    <a href="malpractice.php"
       class="<?= basename($_SERVER['PHP_SELF'])==='malpractice.php'?'active':'' ?>">
        <span class="icon">🛑</span> Malpractice
        <span style="background:#dc3545;color:white;padding:2px 7px;
                     border-radius:10px;font-size:11px;margin-left:auto;">
            <?= $mal_count ?>
        </span>
    </a>
    <?php endif; ?>
    <a href="change_password.php"
       class="<?= basename($_SERVER['PHP_SELF'])==='change_password.php'?'active':'' ?>">
        <span class="icon">🔑</span> Change Password
    </a>
</div>

<script>
document.getElementById('mobile-toggle').onclick = function() {
    document.querySelector('.sidebar').classList.toggle('show');
};
// Auto-detect mobile to show toggle
function checkMobile() {
    if (window.innerWidth <= 992) {
        document.getElementById('mobile-toggle').style.display = 'block';
    } else {
        document.getElementById('mobile-toggle').style.display = 'none';
        document.querySelector('.sidebar').classList.remove('show');
    }
}
window.onresize = checkMobile;
checkMobile();
</script>


