<?php
include("../../includes/connect.php");
$root = "../../";
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = $conn->real_escape_string(trim($_POST['faculty_name']));
    $dept   = $conn->real_escape_string(trim($_POST['department']));
    $email  = $conn->real_escape_string(trim($_POST['email']));
    $mobile = $conn->real_escape_string(trim($_POST['mobile_number']));

    $dup = $conn->query("SELECT faculty_id FROM faculty WHERE email='$email'");
    if ($dup->num_rows > 0) {
        $msg = "error:Faculty with this email already exists!";
    } else {
        $conn->query("INSERT INTO faculty (faculty_name, department, email, mobile_number)
                      VALUES ('$name','$dept','$email','$mobile')");
        $msg = "success:Faculty '$name' added successfully!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Add Faculty - ScoreHive</title>
<link rel="stylesheet" href="../../assets/css/layout.css?v=1.2">
</head>
<body>
<?php include("../../includes/header.php"); ?>
<div class="main">

<div class="page-title">
    Add Faculty
    <a href="list.php" class="btn btn-dark">&#8592; Back to List</a>
</div>

<?php if ($msg): list($type,$text) = explode(":",$msg,2); ?>
<div class="alert alert-<?= $type==='success'?'success':'danger' ?>">
    <?= htmlspecialchars($text) ?>
    <?php if ($type==='success'): ?>
        &nbsp;<a href="list.php">View all faculty &rarr;</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="form-box">
<form method="POST">
    <label>Faculty Name</label>
    <input type="text" name="faculty_name"
           value="<?= htmlspecialchars($_POST['faculty_name'] ?? '') ?>"
           placeholder="e.g. Dr. Kumar" required>

    <label>Department</label>
    <select name="department" required>
        <option value="">-- Select Department --</option>
        <?php
        $depts = ['CSE','ECE','EEE','MECH','CIVIL','IT','AIDS','AIML','CSD'];
        foreach ($depts as $d):
            $sel = (($_POST['department'] ?? '') === $d) ? 'selected' : '';
        ?>
        <option value="<?= $d ?>" <?= $sel ?>><?= $d ?></option>
        <?php endforeach; ?>
    </select>

    <label>Email</label>
    <input type="email" name="email"
           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
           placeholder="faculty@college.edu" required>

    <label>Mobile Number</label>
    <input type="text" name="mobile_number"
           value="<?= htmlspecialchars($_POST['mobile_number'] ?? '') ?>"
           placeholder="e.g. 9876543210" required>

    <button type="submit" class="btn btn-success"
            style="width:100%;padding:11px;font-size:15px;margin-top:12px;">
        &#10003; Add Faculty
    </button>
</form>
</div>

</div>
</body>
</html>



