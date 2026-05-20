<?php
include("includes/connect.php");
$res = $conn->query("SELECT * FROM users");
echo "<h2>All User Credentials</h2>";
echo "<table border='1'><tr><th>ID</th><th>Email</th><th>Password</th><th>Role</th></tr>";
while($r = $res->fetch_assoc()) {
    echo "<tr><td>".$r['id']."</td><td>".$r['email']."</td><td>".$r['password']."</td><td>".$r['role']."</td></tr>";
}
echo "</table>";
?>
