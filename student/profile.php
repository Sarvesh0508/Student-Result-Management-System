<?php
include("../includes/connect.php");
$student_root = "../";
include("nav.php");
$student = $conn->query("SELECT * FROM student WHERE student_id=$sid")->fetch_assoc();

// Get current semester
$cgpa_row = $conn->query("SELECT semester FROM semester_result WHERE student_id=$sid ORDER BY semester DESC LIMIT 1")->fetch_assoc();
$current_sem = $cgpa_row['semester'] ?? 1;

// Calculate Batch Year based on EARLIEST result
$earliest_exam = $conn->query("
    SELECT exam_month_year FROM result 
    WHERE student_id = $sid 
    ORDER BY exam_month_year ASC LIMIT 1
")->fetch_assoc()['exam_month_year'] ?? '';

$batch_year = $student['batch'] ?? 'Unknown';
if (!$batch_year || $batch_year === 'Unknown') {
    if ($earliest_exam) {
        $parts = explode('-', $earliest_exam);
        $batch_year = end($parts);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>My Profile - ScoreHive</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/student_layout.css?v=1.2">
<style>
    .profile-header {
        background: linear-gradient(135deg, #0f2027, #203a43);
        border-radius: 16px; padding: 40px; color: white; display:flex; align-items:center; gap:30px; margin-bottom: 30px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1); position:relative; overflow:hidden;
    }
    .profile-header::after {
        content:'🎓'; position:absolute; right:-20px; bottom:-20px; font-size:150px; opacity:0.05; transform: rotate(-15deg);
    }
    .avatar-large {
        width:120px; height:120px; border-radius:50%; background:#ff8c00;
        display:flex; align-items:center; justify-content:center;
        font-size:48px; font-weight:800; border:6px solid rgba(255,255,255,0.1);
        box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }
    .profile-main-info h1 { margin:0; font-size:32px; font-weight:800; letter-spacing:-1px; }
    .profile-main-info p { margin:8px 0 0; opacity:0.8; font-size:16px; display:flex; align-items:center; gap:10px; }

    .info-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:25px; }
    .info-card { background:white; border-radius:16px; padding:25px; box-shadow:0 4px 20px rgba(0,0,0,0.03); border:1px solid rgba(0,0,0,0.02); }
    .info-card h3 { margin:0 0 20px; font-size:16px; font-weight:800; color:#1e293b; border-bottom:1px solid #f1f5f9; padding-bottom:15px; }
    
    .detail-row { display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid #f8fafc; }
    .detail-row:last-child { border-bottom:none; }
    .detail-label { font-size:13px; color:#64748b; font-weight:600; }
    .detail-value { font-size:14px; color:#1e293b; font-weight:700; }
    
    .contact-badge { display:inline-flex; align-items:center; gap:8px; background:#f1f5f9; padding:8px 16px; border-radius:10px; font-size:13px; color:#475569; font-weight:600; }
</style>
</head>
<body>
<div class="main">

    <div class="page-title">
        <div>My Profile</div>
        <a href="dashboard.php" class="btn btn-dark" style="font-size:12px; padding:6px 15px;">&larr; Back to Dashboard</a>
    </div>

    <div class="profile-header">
        <div class="avatar-large"><?= $initials ?></div>
        <div class="profile-main-info">
            <h1><?= htmlspecialchars($sname) ?></h1>
            <p>
                <span>🏢 <?= $student['department'] ?> Department</span>
                <span>•</span>
                <span>🎓 <?= $batch_year ?></span>
            </p>
            <div style="margin-top:20px; display:flex; gap:12px;">
                <div class="contact-badge">📞 <?= $student['mobile_number'] ?></div>
                <div class="contact-badge">✉️ <?= $student['email'] ?></div>
            </div>
        </div>
    </div>

    <div class="info-grid">
        <!-- Academic Info -->
        <div class="info-card">
            <h3>📜 Academic Information</h3>
            <div class="detail-row">
                <span class="detail-label">Register Number</span>
                <span class="detail-value"><?= $student['register_number'] ?? 'RA2411003012' . str_pad($sid, 3, '0', STR_PAD_LEFT) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Department</span>
                <span class="detail-value"><?= $student['department'] ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Batch Year</span>
                <span class="detail-value"><?= $batch_year ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Current Status</span>
                <?php if ($student['is_detained']): ?>
                    <span class="detail-value" style="color:#ef4444;">DETAINED</span>
                <?php else: ?>
                    <span class="detail-value" style="color:#10b981;">Active Student</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Personal Info -->
        <div class="info-card">
            <h3>👤 Personal Details</h3>
            <div class="detail-row">
                <span class="detail-label">Full Name</span>
                <span class="detail-value"><?= htmlspecialchars($student['name']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email Address</span>
                <span class="detail-value"><?= $student['email'] ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Mobile Number</span>
                <span class="detail-value"><?= $student['mobile_number'] ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Account Type</span>
                <span class="detail-value">Student Portal Access</span>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:25px; background:#f8fafc; border:1px dashed #cbd5e1; text-align:center; padding:30px;">
        <p style="margin:0; font-size:13px; color:#64748b; font-weight:500;">
            If any of the above information is incorrect, please contact the <b>Administrative Office</b> for updates.
        </p>
    </div>

</div>
</body>
</html>


