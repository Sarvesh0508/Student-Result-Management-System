<?php
include("../../includes/connect.php");
$root = "../../";
$id = intval($_GET['id'] ?? 0);
$msg = "";

if ($id === 0) { header("Location: list.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = $conn->real_escape_string(trim($_POST['faculty_name']));
    $dept  = $conn->real_escape_string(trim($_POST['department']));
    $email  = $conn->real_escape_string(trim($_POST['email']));
    $mobile = $conn->real_escape_string(trim($_POST['mobile_number']));

    $conn->query("UPDATE faculty SET faculty_name='$name', department='$dept', email='$email', mobile_number='$mobile'
                  WHERE faculty_id=$id");
    $msg = "success:Faculty updated successfully!";
}

$row = $conn->query("SELECT * FROM faculty WHERE faculty_id=$id")->fetch_assoc();
if (!$row) { echo "Faculty not found."; exit(); }
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Faculty - ScoreHive</title>
<link rel="stylesheet" href="../../assets/css/layout.css?v=1.2">
</head>
<body>
<?php include("../../includes/header.php"); ?>
<div class="main">

<div class="page-title">
    Edit Faculty
    <a href="list.php" class="btn btn-dark">&#8592; Back</a>
</div>

<?php if ($msg): list($type,$text) = explode(":",$msg,2); ?>
<div class="alert alert-<?= $type==='success'?'success':'danger' ?>">
    <?= htmlspecialchars($text) ?>
</div>
<?php endif; ?>

<div class="form-box">
<form method="POST">
    <label>Faculty Name</label>
    <input type="text" name="faculty_name"
           value="<?= htmlspecialchars($row['faculty_name']) ?>" required>

    <label>Department</label>
    <select name="department" required>
        <?php
        $depts = ['CSE','ECE','EEE','MECH','CIVIL','IT','AIDS','AIML','CSD'];
        foreach ($depts as $d):
            $sel = ($row['department']===$d)?'selected':'';
        ?>
        <option value="<?= $d ?>" <?= $sel ?>><?= $d ?></option>
        <?php endforeach; ?>
    </select>

    <label>Email</label>
    <input type="email" name="email"
           value="<?= htmlspecialchars($row['email']) ?>" required>

    <label>Mobile Number</label>
    <input type="text" name="mobile_number"
           value="<?= htmlspecialchars($row['mobile_number'] ?? '') ?>" required>

    <button type="submit" class="btn btn-primary"
            style="width:100%;padding:11px;font-size:15px;margin-top:12px;">
        &#128190; Update Faculty
    </button>
</form>
</div>

</div>
</body>
</html>



