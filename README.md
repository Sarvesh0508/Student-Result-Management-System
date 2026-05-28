# 🎓 ScoreHive | Student Result Management System

ScoreHive is a modern, premium, and feature-rich Academic Result Management Portal built with **PHP, MySQL, and CSS3 HSL glassmorphic design**. It is engineered to streamline academic administration by providing distinct portals for **Students** and **Faculty/Administrators** to view, enter, and analyze academic performance.

---

## ✨ Features

### 👤 Student Portal
*   **Aesthetic Performance Dashboard:** Visually track overall SGPA, CGPA, and backlog count using high-end glassmorphic UI.
*   **Result Explorer:** View detailed marksheets for any semester with credit and grade breakdowns.
*   **Backlog & History Tracking:** Monitor past backlog details and exam attempts.
*   **Malpractice Monitoring:** View status and actions taken regarding academic integrity.
*   **Account Security:** Change password and reset via security questions.

### 👨‍🏫 Faculty & Admin Portal
*   **Interactive Management Panel:** Manage students, courses, faculty, and marks input.
*   **Enrollment Tracking:** Enroll students in courses and assign faculty guides.
*   **Reports Generator:** Generate academic reports and performance analytics.
*   **Malpractice Registrar:** Log academic irregularities and record administrative actions.
*   **Password Management:** Security question setup for secure password resets.

---

## 🛠️ Technology Stack

*   **Backend:** PHP 8.x (using mysqli prepared statements for SQL injection prevention)
*   **Database:** MySQL / MariaDB (relational schema with query optimization)
*   **Frontend:** HTML5, CSS3 Custom Properties (`:root` variables), CSS animations, and Google Fonts (`Outfit`)
*   **Features:** Interactive 3D tilt effects, responsive grid styling, and animated gradient backdrops.

---

## 📁 Repository Directory Structure

```text
student_project/
├── assets/                 # Icons, logos, and global CSS
├── database/
│   └── schema.sql          # Preconfigured SQL database dump
├── includes/
│   ├── connect.php         # Secure MySQL database configuration
│   ├── calculations.php    # SGPA/CGPA calculator algorithms
│   └── header.php          # Reusable navigation components
├── student/                # Student portal pages (dashboard, marksheets, login)
├── teacher/                # Teacher portal pages (course management, entry, reports)
├── index.php               # Portal landing page with 3D card layout
└── README.md               # Project documentation
```

---

## 🚀 Local Installation & Setup

To run this project locally, you need a PHP environment and a MySQL database (XAMPP is recommended).

### Prerequisites
1.  Download and install [XAMPP](https://www.apachefriends.org/).
2.  Start **Apache** and **MySQL** from the XAMPP Control Panel.

### Installation Steps

1.  **Clone the Repository**
    Clone this repository into your XAMPP `htdocs` directory:
    ```bash
    cd C:\xampp\htdocs
    git clone https://github.com/Sarvesh0508/Student-Result-Management-System.git student_project
    ```

2.  **Import the Database**
    *   Open your browser and navigate to `http://localhost/phpmyadmin/`.
    *   Create a new database named `student_result_db`.
    *   Click on the **Import** tab.
    *   Choose the file located at `student_project/database/schema.sql` and click **Go**.

3.  **Configure Database Connection**
    Open `includes/connect.php` in your text editor and verify your database login credentials:
    ```php
    $conn = new mysqli("localhost", "root", "YOUR_MYSQL_PASSWORD", "student_result_db", 3306);
    ```

4.  **Run the Project**
    Open your browser and visit:
    `http://localhost/student_project/`

---

## 🔒 Security Best Practices
ScoreHive implements defensive coding principles to safeguard student records:
*   **SQL Injection Prevention:** All user inputs queried against the database are executed using **MySQLi Prepared Statements** (parameterized queries).
*   **Session Management:** State is verified on every page load to prevent unauthorized URL direct access.
