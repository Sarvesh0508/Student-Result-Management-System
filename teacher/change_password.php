<?php
include("../includes/connect.php");
$root = "../";
include("../includes/header.php");
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    $uid     = $_SESSION['user_id'];

    // Get user record
    $user_q = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();

    if (!$user_q || $user_q['password'] !== $current) {
        $msg = "error:Current password is incorrect.";
    } elseif ($new !== $confirm) {
        $msg = "error:New passwords do not match.";
    } elseif (strlen($new) < 4) {
        $msg = "error:New password must be at least 4 characters.";
    } else {
        $new_esc = $conn->real_escape_string($new);
        $conn->query("UPDATE users SET password='$new_esc' WHERE id=$uid");
        $msg = "success:Password updated successfully! 🔑";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Change Password - ScoreHive</title>
<link rel="stylesheet" href="../assets/css/layout.css?v=1.2">
</head>
<body>
<div class="main">
<div class="page-title">🔑 Change Password</div>

<?php if ($msg): list($type,$text) = explode(":",$msg,2); ?>
<div class="alert alert-<?= $type==='success'?'success':'danger' ?>">
    <?= htmlspecialchars($text) ?>
</div>
<?php endif; ?>

<div class="form-box">
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

    <button type="submit" class="btn btn-primary"
            style="width:100%;padding:11px;font-size:15px;">
        🔑 Update Password
    </button>
</form>
</div>

</div>
</body>
</html>



