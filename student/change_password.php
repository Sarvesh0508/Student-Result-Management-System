<?php
include("../includes/connect.php");
$student_root = "../";
include("nav.php");
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    // Get user record
    $user_q = $conn->query(
        "SELECT * FROM users WHERE role='student'
         AND email=(SELECT email FROM student WHERE student_id=$sid)"
    )->fetch_assoc();

    if (!$user_q || $user_q['password'] !== $current) {
        $msg = "error:Current password is incorrect.";
    } elseif ($new !== $confirm) {
        $msg = "error:New passwords do not match.";
    } elseif (strlen($new) < 4) {
        $msg = "error:New password must be at least 4 characters.";
    } else {
        $new_esc = $conn->real_escape_string($new);
        $email   = $user_q['email'];
        $conn->query(
            "UPDATE users SET password='$new_esc' WHERE email='$email'"
        );
        $msg = "success:Password updated successfully! 🔑";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Change Password - ScoreHive</title>
<link rel="stylesheet" href="../assets/css/student_layout.css?v=1.2">
</head>
<body>
<div class="main">
<div class="page-title">🔑 Change Password</div>

<?php if ($msg): list($type,$text) = explode(":",$msg,2); ?>
<div class="alert alert-<?= $type==='success'?'success':'danger' ?>">
    <?= htmlspecialchars($text) ?>
</div>
<?php endif; ?>

<div class="card form-box">
<form method="POST">
    <label>Current Password</label>
    <input type="password" name="current_password" required
           placeholder="Enter current password">

    <label>New Password</label>
    <input type="password" name="new_password" required
           placeholder="Enter new password">

    <label>Confirm New Password</label>
    <input type="password" name="confirm_password" required
           placeholder="Re-enter new password">

    <div style="background:#e8f4fd;border-radius:6px;padding:10px;
                font-size:13px;margin:12px 0;color:#0c5460;">
        &#9432; Choose a strong password. Minimum 4 characters.
    </div>

    <button type="submit" class="btn btn-orange"
            style="width:100%;padding:11px;font-size:15px;">
        🔑 Update Password
    </button>
</form>
</div>

</div>
</body>
</html>


