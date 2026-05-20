<?php
/**
 * Centralized SGPA/CGPA Calculation Logic
 * 
 * Logic:
 * - SGPA(Sem S) uses the LATEST attempt marks for each subject belonging to Semester S.
 * - CGPA(Sem S) uses the LATEST attempt marks for each subject belonging to Semester <= S.
 * - Backlogs are counted as subjects of Semester S where the latest attempt is a FAIL.
 */

function recalculate_all_semesters($conn, $student_id) {
    $grade_points = ['O' => 10, 'A' => 9, 'B' => 8, 'C' => 7, 'F' => 0];

    // Find all semesters the student has results for
    $sems_q = $conn->query("
        SELECT DISTINCT c.semester 
        FROM result r
        JOIN course c ON r.course_id = c.course_id
        WHERE r.student_id = $student_id
        ORDER BY c.semester ASC
    ");

    $semesters = [];
    while ($s = $sems_q->fetch_row()) $semesters[] = $s[0];

    if (empty($semesters)) {
        // Cleanup if no results left
        $conn->query("DELETE FROM semester_result WHERE student_id = $student_id");
        return;
    }

    foreach ($semesters as $sem) {
        // 1. SGPA for this specific semester
        $sgpa_q = $conn->query("
            SELECT r.grade, c.credit, r.result_status 
            FROM result r
            JOIN (
                SELECT course_id, MAX(attempt_no) as max_attempt 
                FROM result 
                WHERE student_id = $student_id 
                GROUP BY course_id
            ) latest ON r.course_id = latest.course_id AND r.attempt_no = latest.max_attempt
            JOIN course c ON r.course_id = c.course_id
            WHERE r.student_id = $student_id AND c.semester = $sem
        ");

        $s_points = 0; $s_credits = 0; $backlog_count = 0;
        while ($r = $sgpa_q->fetch_assoc()) {
            $gp = $grade_points[$r['grade']] ?? 0;
            $s_points += $gp * $r['credit'];
            $s_credits += $r['credit'];
            if ($r['result_status'] === 'FAIL') $backlog_count++;
        }
        $sgpa = ($s_credits > 0) ? round($s_points / $s_credits, 2) : 0;

        // 2. CGPA up to this semester
        $cgpa_q = $conn->query("
            SELECT r.grade, c.credit 
            FROM result r
            JOIN (
                SELECT course_id, MAX(attempt_no) as max_attempt 
                FROM result 
                WHERE student_id = $student_id 
                GROUP BY course_id
            ) latest ON r.course_id = latest.course_id AND r.attempt_no = latest.max_attempt
            JOIN course c ON r.course_id = c.course_id
            WHERE r.student_id = $student_id AND c.semester <= $sem
        ");

        $c_points = 0; $c_credits = 0;
        while ($r = $cgpa_q->fetch_assoc()) {
            $gp = $grade_points[$r['grade']] ?? 0;
            $c_points += $gp * $r['credit'];
            $c_credits += $r['credit'];
        }
        $cgpa = ($c_credits > 0) ? round($c_points / $c_credits, 2) : 0;

        // 3. Update or Insert into semester_result
        $check = $conn->query("SELECT sem_result_id FROM semester_result WHERE student_id=$student_id AND semester=$sem");
        if ($check->num_rows > 0) {
            $conn->query("
                UPDATE semester_result 
                SET sgpa = $sgpa, cgpa = $cgpa, backlog_count = $backlog_count
                WHERE student_id = $student_id AND semester = $sem
            ");
        } else {
            $conn->query("
                INSERT INTO semester_result (student_id, semester, sgpa, cgpa, backlog_count)
                VALUES ($student_id, $sem, $sgpa, $cgpa, $backlog_count)
            ");
        }
    }

    // 4. Cleanup: Remove any semester_result rows for semesters that no longer have results
    $sem_list = implode(',', $semesters);
    $conn->query("DELETE FROM semester_result WHERE student_id = $student_id AND semester NOT IN ($sem_list)");
}
?>
