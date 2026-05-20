<?php
include("includes/connect.php");

// Check if any teacher exists
$check = $conn->query("SELECT * FROM users WHERE role='teacher'");

if ($check->num_rows == 0) {
    // Create default teacher
    $sql = "INSERT INTO users (email, password, role) VALUES ('teacher@gmail.com', 'password123', 'teacher')";
    if ($conn->query($sql)) {
        echo "SUCCESS: Teacher account created. Email: teacher@gmail.com | Pass: password123";
    } else {
        echo "ERROR: " . $conn->error;
    }
} else {
    $row = $check->fetch_assoc();
    echo "EXISTS: Teacher account already exists. Email: " . $row['email'] . " | Pass: " . $row['password'];
}
?>
