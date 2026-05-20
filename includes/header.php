<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: " . $root . "teacher/login.php");
    exit();
}
$name = $_SESSION['user_name'] ?? 'Teacher';
?>
<div class="topbar">
    <div style="display:flex;align-items:center;gap:15px;">
        <div id="mobile-toggle" style="display:none; cursor:pointer; font-size:24px;">☰</div>
        <img src="<?= $root ?>assets/img/logo.png" style="width:35px;">
        <h2 style="display:flex; align-items:center; margin:0;">
            <span style="font-weight:800; color:#1e293b; font-size:22px;">Score</span><span style="font-weight:800; color:#ff8c00; font-size:22px;">Hive</span>
            <span style="font-size:11px; color:#ff8c00; font-weight:800; background:rgba(255, 140, 0, 0.1); padding:5px 12px; border-radius:12px; margin-left:15px; text-transform:uppercase; letter-spacing:1px; border:1px solid rgba(255, 140, 0, 0.2);">TEACHER PORTAL</span>
        </h2>
    </div>
    <div style="display:flex;align-items:center;gap:15px;">
        <span style="font-size:14px; font-weight:600;">👤 ADMIN</span>
        <a href="<?= $root ?>logout.php"
           style="background:#dc3545;color:white;padding:7px 14px;
                  border-radius:6px;text-decoration:none;font-size:13px;">
            Logout
        </a>
    </div>
</div>

<?php $cp = $_SERVER['PHP_SELF']; ?>
<div class="sidebar">
    <a href="<?= $root ?>teacher/dashboard.php" class="<?= strpos($cp, 'dashboard.php')!==false ? 'active':'' ?>"><span class="icon">&#127968;</span> Dashboard</a>

    <span class="sidebar-section">Students</span>
    <a href="<?= $root ?>teacher/students/list.php" class="<?= strpos($cp, 'students/list.php')!==false ? 'active':'' ?>"><span class="icon">&#128101;</span> All Students</a>
    <a href="<?= $root ?>teacher/students/enrollment.php" class="<?= strpos($cp, 'students/enrollment.php')!==false ? 'active':'' ?>"><span class="icon">&#128203;</span> Enrollment</a>
    <a href="<?= $root ?>teacher/students/add.php" class="<?= strpos($cp, 'students/add.php')!==false ? 'active':'' ?>"><span class="icon">&#10133;</span> Add Student</a>

    <span class="sidebar-section">Courses</span>
    <a href="<?= $root ?>teacher/courses/list.php" class="<?= strpos($cp, 'courses/list.php')!==false ? 'active':'' ?>"><span class="icon">&#128218;</span> All Courses</a>
    <a href="<?= $root ?>teacher/courses/add.php" class="<?= strpos($cp, 'courses/add.php')!==false ? 'active':'' ?>"><span class="icon">&#10133;</span> Add Course</a>

    <span class="sidebar-section">Faculty</span>
    <a href="<?= $root ?>teacher/faculty/list.php" class="<?= strpos($cp, 'faculty/list.php')!==false ? 'active':'' ?>"><span class="icon">&#128104;</span> All Faculty</a>
    <a href="<?= $root ?>teacher/faculty/add.php" class="<?= strpos($cp, 'faculty/add.php')!==false ? 'active':'' ?>"><span class="icon">&#10133;</span> Add Faculty</a>

    <span class="sidebar-section">Results</span>
    <a href="<?= $root ?>teacher/results/enter.php" class="<?= strpos($cp, 'results/enter.php')!==false ? 'active':'' ?>"><span class="icon">&#128221;</span> Enter Result</a>
    <a href="<?= $root ?>teacher/results/view.php" class="<?= strpos($cp, 'results/view.php')!==false ? 'active':'' ?>"><span class="icon">&#128202;</span> View Results</a>
    <a href="<?= $root ?>teacher/results/edit_standalone.php" class="<?= strpos($cp, 'results/edit_standalone.php')!==false ? 'active':'' ?>"><span class="icon">📝</span> Edit Result</a>
    <a href="<?= $root ?>teacher/results/backlogs.php" class="<?= strpos($cp, 'results/backlogs.php')!==false ? 'active':'' ?>"><span class="icon">🚨</span> Backlogs</a>

    <span class="sidebar-section">Malpractice</span>
    <a href="<?= $root ?>teacher/malpractice/list.php" class="<?= strpos($cp, 'malpractice/list.php')!==false ? 'active':'' ?>"><span class="icon">🛑</span> Malpractice Cases</a>
    <a href="<?= $root ?>teacher/malpractice/add.php" class="<?= strpos($cp, 'malpractice/add.php')!==false ? 'active':'' ?>"><span class="icon">&#10133;</span> Add Case</a>

    <span class="sidebar-section">Other</span>
    <a href="<?= $root ?>teacher/reports/index.php" class="<?= strpos($cp, 'reports/index.php')!==false ? 'active':'' ?>"><span class="icon">&#128200;</span> Reports</a>
    <a href="<?= $root ?>teacher/change_password.php" class="<?= strpos($cp, 'change_password.php')!==false ? 'active':'' ?>"><span class="icon">&#128274;</span> Change Password</a>
</div>
<script>
document.getElementById('mobile-toggle').onclick = function() {
    document.querySelector('.sidebar').classList.toggle('show');
};
// Auto-detect mobile to show toggle
if (window.innerWidth <= 992) {
    document.getElementById('mobile-toggle').style.display = 'block';
}
window.onresize = function() {
    if (window.innerWidth <= 992) {
        document.getElementById('mobile-toggle').style.display = 'block';
    } else {
        document.getElementById('mobile-toggle').style.display = 'none';
        document.querySelector('.sidebar').classList.remove('show');
    }
};
</script>
