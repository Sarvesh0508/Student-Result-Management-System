<?php
include("../../includes/connect.php");

if (isset($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);
    
    // Fetch courses NOT yet enrolled by this student
    $query = "
        SELECT course_id, course_title, course_code 
        FROM course 
        WHERE course_id NOT IN (
            SELECT course_id FROM enrollment WHERE student_id = $student_id
        )
        ORDER BY course_title
    ";
    
    $result = $conn->query($query);
    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($courses);
}
?>



