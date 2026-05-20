<?php
include("../../includes/connect.php");
$root = "../../";
$id = intval($_GET['id'] ?? 0);
$msg = "";

if ($id === 0) { header("Location: list.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = $conn->real_escape_string(trim($_POST['name']));
    $mobile = $conn->real_escape_string(trim($_POST['mobile_number']));
    $dept   = $conn->real_escape_string(trim($_POST['department']));
    $reg_no = $conn->real_escape_string(trim($_POST['register_number']));
    $batch  = $conn->real_escape_string(trim($_POST['batch']));
    $email  = $conn->real_escape_string(trim($_POST['email']));

    // Check duplicate email (excluding current student)
    $dup = $conn->query("SELECT student_id FROM student WHERE email='$email' AND student_id != $id");
    if ($dup->num_rows > 0) {
        $msg = "error:Another student with this email already exists!";
    } else {
        // Update student table
        $conn->query("
            UPDATE student SET name='$name', register_number='$reg_no',
            mobile_number='$mobile', department='$dept', batch='$batch', email='$email'
            WHERE student_id=$id
        ");

        // Update users table email too
        $old_email = $conn->query("SELECT email FROM student WHERE student_id=$id")->fetch_assoc()['email'] ?? '';
        if ($old_email && $old_email !== $email) {
            $conn->query("UPDATE users SET email='$email' WHERE email='$old_email'");
        }

        $msg = "success:Student updated successfully!";
    }
}

$row = $conn->query("SELECT * FROM student WHERE student_id=$id")->fetch_assoc();
if (!$row) { echo "Student not found."; exit(); }
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Student - ScoreHive</title>
<link rel="stylesheet" href="../../assets/css/layout.css?v=1.2">
</head>
<body>
<?php include("../../includes/header.php"); ?>
<div class="main">

<div class="page-title">
    Edit Student
    <a href="list.php" class="btn btn-dark">&#8592; Back to Students</a>
</div>

<?php if ($msg): list($type,$text) = explode(":",$msg,2); ?>
<div class="alert alert-<?= $type==='success'?'success':'danger' ?>">
    <?= htmlspecialchars($text) ?>
</div>
<?php endif; ?>

<div class="form-box">
<form method="POST">

    <label>Full Name</label>
    <input type="text" name="name"
           value="<?= htmlspecialchars($row['name']) ?>"
           placeholder="e.g. Rahul Kumar" required>

    <label>Department</label>
    <select name="department" required>
        <?php
        $depts = ['CSE','ECE','EEE','MECH','CIVIL','IT','AIDS','AIML','CSD'];
        foreach ($depts as $d):
            $sel = ($row['department'] === $d) ? 'selected' : '';
        ?>
        <option value="<?= $d ?>" <?= $sel ?>><?= $d ?></option>
        <?php endforeach; ?>
    </select>

    <label>Register Number</label>
    <input type="text" name="register_number"
           value="<?= htmlspecialchars($row['register_number'] ?? '') ?>"
           placeholder="e.g. RA24..." required>

    <label>Batch (Year of Joining)</label>
    <input type="text" name="batch"
           value="<?= htmlspecialchars($row['batch'] ?? '') ?>"
           placeholder="e.g. 2021" required>

    <label>Mobile Number</label>
    <input type="text" name="mobile_number"
           value="<?= htmlspecialchars($row['mobile_number']) ?>"
           placeholder="10-digit mobile" maxlength="15" required>

    <label>Email</label>
    <input type="email" name="email"
           value="<?= htmlspecialchars($row['email']) ?>"
           placeholder="student@email.com" required>

    <div style="background:#fff3cd;border-radius:6px;padding:10px;
                font-size:13px;margin:10px 0;color:#856404;">
        &#9432; Note: Login email will also be updated automatically.
    </div>

    <button type="submit" class="btn btn-primary"
            style="width:100%;padding:11px;font-size:15px;margin-top:8px;">
        &#128190; Update Student
    </button>
</form>
</div>

</div>
</body>
</html>



