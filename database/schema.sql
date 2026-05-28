-- Student Result Management System Database Schema
-- Generated automatically on 2026-05-28 13:37:21

CREATE DATABASE IF NOT EXISTS `student_result_db`;
USE `student_result_db`;

DROP TABLE IF EXISTS `backlog_students`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `backlog_students` AS select `s`.`student_id` AS `student_id`,`s`.`name` AS `name`,`r`.`course_id` AS `course_id` from (`student` `s` join `result` `r` on((`s`.`student_id` = `r`.`student_id`))) where (`r`.`backlog_status` = 'ACTIVE');

-- Dumping data for table `backlog_students`
INSERT INTO `backlog_students` (`student_id`, `name`, `course_id`) VALUES ('5', 'Arjun Patel', '104');

DROP TABLE IF EXISTS `course`;
CREATE TABLE `course` (
  `course_id` int NOT NULL AUTO_INCREMENT,
  `course_title` varchar(100) DEFAULT NULL,
  `department` varchar(30) DEFAULT NULL,
  `year` int DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `credit` int DEFAULT NULL,
  `category` varchar(30) DEFAULT NULL,
  `semester` int DEFAULT NULL,
  PRIMARY KEY (`course_id`)
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `course`
INSERT INTO `course` (`course_id`, `course_title`, `department`, `year`, `course_code`, `credit`, `category`, `semester`) VALUES ('101', 'Database Management System', 'CSE', '1', 'DBMS101', '4', 'Core', '3');
INSERT INTO `course` (`course_id`, `course_title`, `department`, `year`, `course_code`, `credit`, `category`, `semester`) VALUES ('102', 'Data Structures', 'CSE', '1', 'DS102', '4', 'Core', '3');
INSERT INTO `course` (`course_id`, `course_title`, `department`, `year`, `course_code`, `credit`, `category`, `semester`) VALUES ('103', 'Operating Systems', 'CSE', '1', 'OS103', '4', 'Core', '4');
INSERT INTO `course` (`course_id`, `course_title`, `department`, `year`, `course_code`, `credit`, `category`, `semester`) VALUES ('104', 'Computer Networks', 'CSE', '1', 'CN104', '3', 'Core', '4');
INSERT INTO `course` (`course_id`, `course_title`, `department`, `year`, `course_code`, `credit`, `category`, `semester`) VALUES ('105', 'Software Engineering', 'CSE', '1', 'SE105', '3', 'Core', '5');
INSERT INTO `course` (`course_id`, `course_title`, `department`, `year`, `course_code`, `credit`, `category`, `semester`) VALUES ('106', 'Internet Of Things', 'CSE', '1', 'IOT121', '3', 'Core', '3');
INSERT INTO `course` (`course_id`, `course_title`, `department`, `year`, `course_code`, `credit`, `category`, `semester`) VALUES ('107', 'Engineering Mathematics I', 'CSE', '1', 'MA101', '4', 'Theory', '1');
INSERT INTO `course` (`course_id`, `course_title`, `department`, `year`, `course_code`, `credit`, `category`, `semester`) VALUES ('108', 'Engineering Physics', NULL, NULL, 'PH101', '4', 'Theory', '1');
INSERT INTO `course` (`course_id`, `course_title`, `department`, `year`, `course_code`, `credit`, `category`, `semester`) VALUES ('109', 'Problem Solving & C', NULL, NULL, 'CS101', '4', 'Theory', '1');
INSERT INTO `course` (`course_id`, `course_title`, `department`, `year`, `course_code`, `credit`, `category`, `semester`) VALUES ('110', 'Engineering Mathematics II', NULL, NULL, 'MA201', '4', 'Theory', '2');

DROP TABLE IF EXISTS `enrollment`;
CREATE TABLE `enrollment` (
  `enroll_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `course_id` int DEFAULT NULL,
  `faculty_id` int DEFAULT NULL,
  `enroll_status` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`enroll_id`),
  KEY `student_id` (`student_id`),
  KEY `course_id` (`course_id`),
  KEY `faculty_id` (`faculty_id`),
  CONSTRAINT `enrollment_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`),
  CONSTRAINT `enrollment_ibfk_3` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `enrollment`
INSERT INTO `enrollment` (`enroll_id`, `student_id`, `course_id`, `faculty_id`, `enroll_status`) VALUES ('1', '1', '101', '1', 'ENROLLED');
INSERT INTO `enrollment` (`enroll_id`, `student_id`, `course_id`, `faculty_id`, `enroll_status`) VALUES ('3', '3', '102', '2', 'ENROLLED');
INSERT INTO `enrollment` (`enroll_id`, `student_id`, `course_id`, `faculty_id`, `enroll_status`) VALUES ('4', '4', '103', '3', 'ENROLLED');
INSERT INTO `enrollment` (`enroll_id`, `student_id`, `course_id`, `faculty_id`, `enroll_status`) VALUES ('5', '5', '104', '4', 'ENROLLED');
INSERT INTO `enrollment` (`enroll_id`, `student_id`, `course_id`, `faculty_id`, `enroll_status`) VALUES ('7', '6', '101', '1', 'ENROLLED');
INSERT INTO `enrollment` (`enroll_id`, `student_id`, `course_id`, `faculty_id`, `enroll_status`) VALUES ('8', '2', '101', '3', 'ENROLLED');
INSERT INTO `enrollment` (`enroll_id`, `student_id`, `course_id`, `faculty_id`, `enroll_status`) VALUES ('10', '1', '102', '4', 'ENROLLED');
INSERT INTO `enrollment` (`enroll_id`, `student_id`, `course_id`, `faculty_id`, `enroll_status`) VALUES ('17', '1', '107', '1', 'ENROLLED');
INSERT INTO `enrollment` (`enroll_id`, `student_id`, `course_id`, `faculty_id`, `enroll_status`) VALUES ('18', '1', '108', '2', 'ENROLLED');
INSERT INTO `enrollment` (`enroll_id`, `student_id`, `course_id`, `faculty_id`, `enroll_status`) VALUES ('19', '1', '109', '5', 'ENROLLED');

DROP TABLE IF EXISTS `faculty`;
CREATE TABLE `faculty` (
  `faculty_id` int NOT NULL AUTO_INCREMENT,
  `faculty_name` varchar(50) DEFAULT NULL,
  `department` varchar(30) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`faculty_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `faculty`
INSERT INTO `faculty` (`faculty_id`, `faculty_name`, `department`, `email`) VALUES ('1', 'Dr. Kumar', 'CSE', 'kumar@college.edu');
INSERT INTO `faculty` (`faculty_id`, `faculty_name`, `department`, `email`) VALUES ('2', 'Dr. Mehta', 'CSE', 'mehta@college.edu');
INSERT INTO `faculty` (`faculty_id`, `faculty_name`, `department`, `email`) VALUES ('3', 'Dr. Rao', 'CSE', 'rao@college.edu');
INSERT INTO `faculty` (`faculty_id`, `faculty_name`, `department`, `email`) VALUES ('4', 'Dr. Sharma', 'CSE', 'sharma@college.edu');
INSERT INTO `faculty` (`faculty_id`, `faculty_name`, `department`, `email`) VALUES ('5', 'Dr.Suresh E', 'CSE', 'esuresh@gmail.com');

DROP TABLE IF EXISTS `malpractice`;
CREATE TABLE `malpractice` (
  `malpractice_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `course_id` int DEFAULT NULL,
  `semester` int DEFAULT NULL,
  `malpractice_type` varchar(50) DEFAULT NULL,
  `action_taken` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`malpractice_id`),
  KEY `student_id` (`student_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `malpractice_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`),
  CONSTRAINT `malpractice_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `malpractice`
INSERT INTO `malpractice` (`malpractice_id`, `student_id`, `course_id`, `semester`, `malpractice_type`, `action_taken`) VALUES ('1', '2', '101', '3', 'Copying in exam', 'Warning issued');
INSERT INTO `malpractice` (`malpractice_id`, `student_id`, `course_id`, `semester`, `malpractice_type`, `action_taken`) VALUES ('2', '5', '104', '3', 'Mobile phone usage', 'Result withheld');
INSERT INTO `malpractice` (`malpractice_id`, `student_id`, `course_id`, `semester`, `malpractice_type`, `action_taken`) VALUES ('3', '2', '102', '3', 'Copying in Exam', 'Warning Issued');
INSERT INTO `malpractice` (`malpractice_id`, `student_id`, `course_id`, `semester`, `malpractice_type`, `action_taken`) VALUES ('4', '3', '103', '3', 'Mobile Phone Usage', 'Result Withheld');

DROP TABLE IF EXISTS `result`;
CREATE TABLE `result` (
  `result_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `course_id` int DEFAULT NULL,
  `marks` decimal(5,2) DEFAULT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `attempt_no` int DEFAULT NULL,
  `result_status` varchar(20) DEFAULT NULL,
  `backlog_status` varchar(20) DEFAULT NULL,
  `exam_month_year` varchar(20) DEFAULT NULL,
  `faculty_id` int DEFAULT NULL,
  PRIMARY KEY (`result_id`),
  KEY `student_id` (`student_id`),
  KEY `subject_id` (`course_id`),
  KEY `fk_result_faculty` (`faculty_id`),
  CONSTRAINT `fk_result_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`),
  CONSTRAINT `result_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`),
  CONSTRAINT `result_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`),
  CONSTRAINT `check_marks` CHECK ((`marks` between 0 and 100))
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `result`
INSERT INTO `result` (`result_id`, `student_id`, `course_id`, `marks`, `grade`, `attempt_no`, `result_status`, `backlog_status`, `exam_month_year`, `faculty_id`) VALUES ('2', '2', '101', '70.00', 'B', '1', 'PASS', 'NONE', 'Nov 2025', '3');
INSERT INTO `result` (`result_id`, `student_id`, `course_id`, `marks`, `grade`, `attempt_no`, `result_status`, `backlog_status`, `exam_month_year`, `faculty_id`) VALUES ('3', '3', '102', '90.00', 'O', '1', 'PASS', 'NONE', 'Nov 2025', NULL);
INSERT INTO `result` (`result_id`, `student_id`, `course_id`, `marks`, `grade`, `attempt_no`, `result_status`, `backlog_status`, `exam_month_year`, `faculty_id`) VALUES ('4', '4', '103', '55.00', 'C', '1', 'PASS', 'NONE', 'May 2026', NULL);
INSERT INTO `result` (`result_id`, `student_id`, `course_id`, `marks`, `grade`, `attempt_no`, `result_status`, `backlog_status`, `exam_month_year`, `faculty_id`) VALUES ('5', '5', '104', '30.00', 'F', '1', 'FAIL', 'ACTIVE', 'May 2026', NULL);
INSERT INTO `result` (`result_id`, `student_id`, `course_id`, `marks`, `grade`, `attempt_no`, `result_status`, `backlog_status`, `exam_month_year`, `faculty_id`) VALUES ('10', '1', '101', '85.00', 'O', '1', 'PASS', 'NONE', 'Nov 2025', '3');
INSERT INTO `result` (`result_id`, `student_id`, `course_id`, `marks`, `grade`, `attempt_no`, `result_status`, `backlog_status`, `exam_month_year`, `faculty_id`) VALUES ('31', '6', '101', '92.50', 'O', '1', 'PASS', 'NONE', 'Nov 2025', NULL);
INSERT INTO `result` (`result_id`, `student_id`, `course_id`, `marks`, `grade`, `attempt_no`, `result_status`, `backlog_status`, `exam_month_year`, `faculty_id`) VALUES ('32', '2', '102', '40.00', 'F', '1', 'FAIL', 'CLEARED', 'Nov 2025', '1');
INSERT INTO `result` (`result_id`, `student_id`, `course_id`, `marks`, `grade`, `attempt_no`, `result_status`, `backlog_status`, `exam_month_year`, `faculty_id`) VALUES ('33', '2', '102', '65.00', 'B', '2', 'PASS', 'NONE', 'Nov 2025', '1');
INSERT INTO `result` (`result_id`, `student_id`, `course_id`, `marks`, `grade`, `attempt_no`, `result_status`, `backlog_status`, `exam_month_year`, `faculty_id`) VALUES ('35', '1', '102', '85.00', 'A', '1', 'PASS', 'NONE', 'Nov 2025', '4');
INSERT INTO `result` (`result_id`, `student_id`, `course_id`, `marks`, `grade`, `attempt_no`, `result_status`, `backlog_status`, `exam_month_year`, `faculty_id`) VALUES ('42', '2', '107', '78.00', 'A', '1', 'PASS', 'NONE', 'Nov 2024', '1');

DROP TABLE IF EXISTS `semester_result`;
CREATE TABLE `semester_result` (
  `sem_result_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `semester` int DEFAULT NULL,
  `sgpa` decimal(4,2) DEFAULT NULL,
  `cgpa` decimal(4,2) DEFAULT NULL,
  `backlog_count` int DEFAULT NULL,
  PRIMARY KEY (`sem_result_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `semester_result_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `semester_result`
INSERT INTO `semester_result` (`sem_result_id`, `student_id`, `semester`, `sgpa`, `cgpa`, `backlog_count`) VALUES ('1', '1', '3', '9.50', '9.00', '0');
INSERT INTO `semester_result` (`sem_result_id`, `student_id`, `semester`, `sgpa`, `cgpa`, `backlog_count`) VALUES ('2', '2', '3', '8.00', '8.25', '0');
INSERT INTO `semester_result` (`sem_result_id`, `student_id`, `semester`, `sgpa`, `cgpa`, `backlog_count`) VALUES ('3', '3', '3', '10.00', '10.00', '0');
INSERT INTO `semester_result` (`sem_result_id`, `student_id`, `semester`, `sgpa`, `cgpa`, `backlog_count`) VALUES ('6', '5', '4', '0.00', '0.00', '1');
INSERT INTO `semester_result` (`sem_result_id`, `student_id`, `semester`, `sgpa`, `cgpa`, `backlog_count`) VALUES ('7', '6', '3', '10.00', '10.00', '0');
INSERT INTO `semester_result` (`sem_result_id`, `student_id`, `semester`, `sgpa`, `cgpa`, `backlog_count`) VALUES ('8', '4', '4', '7.00', '7.00', '0');
INSERT INTO `semester_result` (`sem_result_id`, `student_id`, `semester`, `sgpa`, `cgpa`, `backlog_count`) VALUES ('9', '1', '1', '8.67', '8.67', '0');
INSERT INTO `semester_result` (`sem_result_id`, `student_id`, `semester`, `sgpa`, `cgpa`, `backlog_count`) VALUES ('10', '2', '1', '8.33', '8.33', '0');
INSERT INTO `semester_result` (`sem_result_id`, `student_id`, `semester`, `sgpa`, `cgpa`, `backlog_count`) VALUES ('16', '2', '2', '8.33', '8.33', '0');

DROP TABLE IF EXISTS `student`;
CREATE TABLE `student` (
  `student_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `department` varchar(30) DEFAULT NULL,
  `year` int DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `mobile_number` varchar(15) NOT NULL,
  `batch` int DEFAULT NULL,
  `register_number` varchar(20) DEFAULT NULL,
  `is_detained` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `student`
INSERT INTO `student` (`student_id`, `name`, `department`, `year`, `email`, `mobile_number`, `batch`, `register_number`, `is_detained`) VALUES ('1', 'Rahul Sharma', 'CSE', '1', 'rahul@gmail.com', '9876543210', '2024', 'RA2411003012161', '0');
INSERT INTO `student` (`student_id`, `name`, `department`, `year`, `email`, `mobile_number`, `batch`, `register_number`, `is_detained`) VALUES ('2', 'Anita Verma', 'CSE', '1', 'anita@gmail.com', '9876543211', '2024', 'RA2411003012162', '0');
INSERT INTO `student` (`student_id`, `name`, `department`, `year`, `email`, `mobile_number`, `batch`, `register_number`, `is_detained`) VALUES ('3', 'Rohit Kumar', 'CSE', '1', 'rohit@gmail.com', '9876543212', '2024', 'RA2411003012163', '0');
INSERT INTO `student` (`student_id`, `name`, `department`, `year`, `email`, `mobile_number`, `batch`, `register_number`, `is_detained`) VALUES ('4', 'Priya Singh', 'CSE', '1', 'priya@gmail.com', '9876543213', '2024', 'RA2411003012164', '0');
INSERT INTO `student` (`student_id`, `name`, `department`, `year`, `email`, `mobile_number`, `batch`, `register_number`, `is_detained`) VALUES ('5', 'Arjun Patel', 'CSE', '1', 'arjun@gmail.com', '9876543214', '2024', 'RA2411003012165', '0');
INSERT INTO `student` (`student_id`, `name`, `department`, `year`, `email`, `mobile_number`, `batch`, `register_number`, `is_detained`) VALUES ('6', 'Kiran Reddy', 'CSE', '1', 'kiran@gmail.com', '9876543220', '2024', 'RA2411003012166', '0');
INSERT INTO `student` (`student_id`, `name`, `department`, `year`, `email`, `mobile_number`, `batch`, `register_number`, `is_detained`) VALUES ('7', 'Nithin Reddy', 'CSE', '1', 'nithin@gmail.com', '9876543216', '2024', 'RA2411003012167', '0');

DROP TABLE IF EXISTS `student_result_view`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_result_view` AS select `s`.`student_id` AS `student_id`,`s`.`name` AS `name`,`c`.`course_title` AS `course_title`,`r`.`marks` AS `marks`,`r`.`grade` AS `grade` from ((`student` `s` join `result` `r` on((`s`.`student_id` = `r`.`student_id`))) join `course` `c` on((`r`.`course_id` = `c`.`course_id`)));

-- Dumping data for table `student_result_view`
INSERT INTO `student_result_view` (`student_id`, `name`, `course_title`, `marks`, `grade`) VALUES ('1', 'Rahul Sharma', 'Database Management System', '85.00', 'O');
INSERT INTO `student_result_view` (`student_id`, `name`, `course_title`, `marks`, `grade`) VALUES ('1', 'Rahul Sharma', 'Data Structures', '85.00', 'A');
INSERT INTO `student_result_view` (`student_id`, `name`, `course_title`, `marks`, `grade`) VALUES ('1', 'Rahul Sharma', 'Engineering Mathematics I', '80.00', 'A');
INSERT INTO `student_result_view` (`student_id`, `name`, `course_title`, `marks`, `grade`) VALUES ('1', 'Rahul Sharma', 'Engineering Physics', '60.00', 'B');
INSERT INTO `student_result_view` (`student_id`, `name`, `course_title`, `marks`, `grade`) VALUES ('1', 'Rahul Sharma', 'Problem Solving & C', '77.00', 'A');
INSERT INTO `student_result_view` (`student_id`, `name`, `course_title`, `marks`, `grade`) VALUES ('2', 'Anita Verma', 'Database Management System', '70.00', 'B');
INSERT INTO `student_result_view` (`student_id`, `name`, `course_title`, `marks`, `grade`) VALUES ('2', 'Anita Verma', 'Data Structures', '40.00', 'F');
INSERT INTO `student_result_view` (`student_id`, `name`, `course_title`, `marks`, `grade`) VALUES ('2', 'Anita Verma', 'Data Structures', '65.00', 'B');
INSERT INTO `student_result_view` (`student_id`, `name`, `course_title`, `marks`, `grade`) VALUES ('2', 'Anita Verma', 'Engineering Mathematics I', '78.00', 'A');
INSERT INTO `student_result_view` (`student_id`, `name`, `course_title`, `marks`, `grade`) VALUES ('2', 'Anita Verma', 'Engineering Physics', '65.00', 'B');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(50) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `role` varchar(20) DEFAULT NULL,
  `security_question` varchar(100) DEFAULT NULL,
  `answer` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `users`
INSERT INTO `users` (`id`, `email`, `password`, `role`, `security_question`, `answer`) VALUES ('1', 'student@gmail.com', '1234', 'student', 'Your pet name?', 'tom');
INSERT INTO `users` (`id`, `email`, `password`, `role`, `security_question`, `answer`) VALUES ('3', 'rahul@gmail.com', '1234', 'student', 'Your pet name?', 'tom');
INSERT INTO `users` (`id`, `email`, `password`, `role`, `security_question`, `answer`) VALUES ('4', 'anita@gmail.com', '1234', 'student', 'Your pet name?', 'tom');
INSERT INTO `users` (`id`, `email`, `password`, `role`, `security_question`, `answer`) VALUES ('5', 'rohit@gmail.com', '1234', 'student', 'Your pet name?', 'tom');
INSERT INTO `users` (`id`, `email`, `password`, `role`, `security_question`, `answer`) VALUES ('6', 'priya@gmail.com', '1234', 'student', 'Your pet name?', 'tom');
INSERT INTO `users` (`id`, `email`, `password`, `role`, `security_question`, `answer`) VALUES ('7', 'arjun@gmail.com', '1234', 'student', 'Your pet name?', 'tom');
INSERT INTO `users` (`id`, `email`, `password`, `role`, `security_question`, `answer`) VALUES ('8', 'kiran@gmail.com', '1234', 'student', 'Your pet name?', 'tom');
INSERT INTO `users` (`id`, `email`, `password`, `role`, `security_question`, `answer`) VALUES ('10', 'nithin@gmail.com', '1234', 'student', 'Your pet name?', 'tom');
INSERT INTO `users` (`id`, `email`, `password`, `role`, `security_question`, `answer`) VALUES ('14', 'teacher@gmail.com', 'admin', 'teacher', NULL, NULL);

