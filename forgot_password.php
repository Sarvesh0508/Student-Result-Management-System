<?php
include("includes/connect.php");

$msg = "";

if(isset($_POST['email'])){
    $email = $_POST['email'];
    $q = $_POST['question'];
    $ans = $_POST['answer'];
    $new = $_POST['newpass'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND security_question=? AND answer=?");
    $stmt->bind_param("sss", $email, $q, $ans);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res->num_rows > 0){
        $stmt2 = $conn->prepare("UPDATE users SET password=? WHERE email=?");
        $stmt2->bind_param("ss", $new, $email);
        $stmt2->execute();

        $msg = "✅ Password updated successfully";
    } else {
        $msg = "❌ Wrong answer! Cannot reset password";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ScoreHive - Forgot Password</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #ff8c00;
    --primary-hover: #e67600;
    --glass-bg: rgba(255, 255, 255, 0.1);
    --glass-border: rgba(255, 255, 255, 0.2);
    --glass-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
}

* { box-sizing: border-box; }

body { 
    margin: 0; 
    font-family: 'Outfit', sans-serif;
    background: linear-gradient(-45deg, #0f2027, #203a43, #2c5364, #0f2027);
    background-size: 400% 400%;
    animation: gradientBG 15s ease infinite;
    height: 100vh;
    color: white;
    overflow: hidden;
}

@keyframes gradientBG {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.shape { position: absolute; filter: blur(80px); z-index: -1; opacity: 0.6; animation: float 10s infinite ease-in-out alternate; }
.shape-1 { width: 300px; height: 300px; background: #ff8c00; top: -50px; left: -50px; border-radius: 50%; }
.shape-2 { width: 400px; height: 400px; background: #00d2ff; bottom: -100px; right: -50px; border-radius: 50%; animation-delay: -5s; }

@keyframes float {
    0% { transform: translate(0, 0) scale(1); }
    100% { transform: translate(30px, 50px) scale(1.1); }
}

.navbar { 
    background: rgba(15, 32, 39, 0.6); 
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255,255,255,0.1);
    color: white; 
    padding: 15px 40px;
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 10;
}
.logo { display: flex; align-items: center; gap: 12px; }
.logo img { width: 40px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3)); }
.logo h2 { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px; }
.score { color: white; } .hive { color: var(--primary); }

.nav-link {
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: color 0.3s;
}
.nav-link:hover { color: white; }

.container { 
    display: flex; 
    justify-content: center; 
    align-items: center;
    height: 100vh;
    padding-top: 60px;
}

.card { 
    background: var(--glass-bg);
    backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    padding: 40px; 
    width: 400px;
    border-radius: 24px; 
    box-shadow: var(--glass-shadow); 
    animation: slideUpFade 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    opacity: 0;
    transform: translateY(30px);
}

@keyframes slideUpFade {
    to { opacity: 1; transform: translateY(0); }
}

.card h2 { 
    text-align: center; 
    margin-top: 0;
    margin-bottom: 25px;
    font-size: 24px;
    font-weight: 600;
}

.input-group {
    position: relative;
    margin-bottom: 20px;
}

.input-group input, .input-group select { 
    width: 100%; 
    padding: 15px; 
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 12px; 
    color: white;
    font-size: 15px;
    font-family: 'Outfit', sans-serif;
    outline: none;
    transition: all 0.3s ease;
    appearance: none;
}

.input-group select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 15px center;
    background-size: 15px;
}

.input-group select option {
    background: #203a43;
    color: white;
}

.input-group input:focus, .input-group select:focus {
    background: rgba(255,255,255,0.1);
    border-color: var(--primary);
    box-shadow: 0 0 10px rgba(255, 140, 0, 0.2);
}

.input-group label {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 14px;
    color: rgba(255,255,255,0.6);
    pointer-events: none;
    transition: all 0.3s ease;
}

.input-group input:focus ~ label,
.input-group input:valid ~ label,
.input-group select:focus ~ label,
.input-group select:valid ~ label {
    top: 0;
    font-size: 11px;
    background: #182e38;
    padding: 0 5px;
    color: var(--primary);
    border-radius: 3px;
}

button { 
    width: 100%; 
    padding: 16px; 
    background: var(--primary); 
    color: white;
    border: none; 
    border-radius: 12px; 
    font-size: 16px; 
    font-weight: 600;
    font-family: 'Outfit', sans-serif;
    cursor: pointer; 
    margin-top: 10px; 
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(255, 140, 0, 0.2);
}

button:hover { 
    background: var(--primary-hover); 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 140, 0, 0.4);
}

.msg-box {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
    font-weight: 600;
    padding: 10px;
    border-radius: 8px;
}
.success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.error { background: rgba(239, 68, 68, 0.1); color: #ff6b6b; }

.footer-links {
    text-align: center;
    margin-top: 25px;
}
.footer-links a { 
    color: rgba(255,255,255,0.7); 
    font-size: 13px; 
    text-decoration: none;
    transition: color 0.3s;
}
.footer-links a:hover { color: var(--primary); }
</style>
</head>
<body>
<div class="shape shape-1"></div>
<div class="shape shape-2"></div>

<div class="navbar">
    <div class="logo">
        <img src="assets/img/logo.png" alt="logo">
        <h2><span class="score">Score</span><span class="hive">Hive</span></h2>
    </div>
    <a href="index.php" class="nav-link">HOME</a>
</div>

<div class="container">
    <div class="card">
        <h2>Reset Password</h2>
        <form method="post">
            <div class="input-group">
                <input type="email" name="email" required>
                <label>Email Address</label>
            </div>
            
            <div class="input-group">
                <select name="question" required>
                    <option value="" disabled selected></option>
                    <option>Your pet name?</option>
                    <option>Your birth place?</option>
                </select>
                <label>Security Question</label>
            </div>

            <div class="input-group">
                <input type="text" name="answer" required>
                <label>Answer</label>
            </div>

            <div class="input-group">
                <input type="password" name="newpass" required>
                <label>New Password</label>
            </div>

            <button type="submit">Update Password</button>
        </form>

        <?php if($msg != ""): ?>
        <div class="msg-box <?= (strpos($msg, '✅') !== false) ? 'success' : 'error' ?>">
            <?= $msg ?>
        </div>
        <?php endif; ?>

        <div class="footer-links">
            <a href="student/login.php">Back to Login</a>
        </div>
    </div>
</div>
</body>
</html>