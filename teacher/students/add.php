<?php
include("../../includes/connect.php");
$root = "../../";
$msg  = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = $conn->real_escape_string(trim($_POST['name']));
    $mobile = $conn->real_escape_string(trim($_POST['mobile_number']));
    $dept   = $conn->real_escape_string(trim($_POST['department']));
    $batch  = $conn->real_escape_string(trim($_POST['batch'] ?? ''));
    $email  = $conn->real_escape_string(trim($_POST['email']));
    $pass   = trim($_POST['password']);

    // Check duplicate email
    $dup = $conn->query("SELECT student_id FROM student WHERE email='$email'");
    if ($dup->num_rows > 0) {
        $msg = "error:A student with this email already exists.";
    } else {
        $reg_no = $conn->real_escape_string(trim($_POST['register_number']));

        // Check duplicate Register Number
        $dup_reg = $conn->query("SELECT student_id FROM student WHERE register_number='$reg_no'");
        
        if ($dup_reg->num_rows > 0) {
            $msg = "error:This Register Number is already assigned to another student.";
        } else {
            // Insert into student table
            $conn->query("
                INSERT INTO student (name, register_number, mobile_number, department, batch, email)
                VALUES ('$name', '$reg_no', '$mobile', '$dept', '$batch', '$email')
            ");
            $new_id = $conn->insert_id;

            // Also create login in users table
            $conn->query("
                INSERT INTO users (email, password, role)
                VALUES ('$email', '$pass', 'student')
            ");

            // Redirect to list to prevent double-submission
            header("Location: list.php?added=1");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Add Student - ScoreHive</title>
<link rel="stylesheet" href="../../assets/css/layout.css?v=1.2">
</head>
<body>
<?php include("../../includes/header.php"); ?>
<div class="main">

<div class="page-title">
    Add New Student
    <a href="list.php" class="btn btn-dark">&#8592; Back to List</a>
</div>

<?php if ($msg): list($type,$text) = explode(":",$msg,2); ?>
<div class="alert alert-<?= $type==='success'?'success':'danger' ?>">
    <?= htmlspecialchars($text) ?>
    <?php if ($type==='success'): ?>
        &nbsp; <a href="list.php">View all students &rarr;</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="form-box">
<form method="POST">

    <label>Full Name</label>
    <input type="text" name="name"
           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
           placeholder="e.g. Rahul Kumar" required>

    <label>Register Number (e.g. RA24...)</label>
    <input type="text" name="register_number"
           value="<?= htmlspecialchars($_POST['register_number'] ?? '') ?>"
           placeholder="e.g. RA2411003012162" required>

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

    <label>Batch (Year of Joining)</label>
    <input type="text" name="batch"
           value="<?= htmlspecialchars($_POST['batch'] ?? '') ?>"
           placeholder="e.g. 2021" required>

    <label>Mobile Number</label>
    <input type="text" name="mobile_number"
           value="<?= htmlspecialchars($_POST['mobile_number'] ?? '') ?>"
           placeholder="10-digit mobile" maxlength="15" required>

    <label>Email</label>
    <input type="email" name="email"
           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
           placeholder="student@email.com" required>

    <label>Login Password</label>
    <input type="password" name="password"
           placeholder="Set login password for student" required>

    <div style="background:#e8f4fd;border-radius:6px;padding:10px;
                font-size:13px;margin:10px 0;color:#0c5460;">
        &#9432; This will create a login account for the student automatically.
    </div>

    <button type="submit" class="btn btn-success"
            style="width:100%;padding:11px;font-size:15px;margin-top:8px;">
        &#10003; Add Student
    </button>
</form>
</div>

</div>
</body>
</html>



