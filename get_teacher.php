<?php
include("includes/connect.php");
$res = $conn->query("SELECT email, password FROM users WHERE role='teacher'");
echo "<h2>Teacher Credentials</h2>";
while($r = $res->fetch_assoc()) {
    echo "Email: " . $r['email'] . " | Password: " . $r['password'] . "<br>";
}
?>
