<?php
include("../includes/connect.php");
$msg = "";

if (isset($_POST['email'])) {
    $email = $_POST['email'];
    $pass  = $_POST['password'];

    $stmt = $conn->prepare(
        "SELECT * FROM users WHERE email=? AND password=? AND role='teacher'"
    );
    $stmt->bind_param("ss", $email, $pass);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $_SESSION['user_id']   = $row['id'];
        $_SESSION['user_name'] = $row['username'] ?? $row['email'];
        $_SESSION['role']      = 'teacher';
        header("Location: dashboard.php");
        exit();
    } else {
        $msg = "Invalid login ❌";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ScoreHive - Teacher Login</title>
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

/* Background Shapes */
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
    width: 380px;
    border-radius: 20px; 
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
    margin-bottom: 30px;
    font-size: 24px;
    font-weight: 600;
}

.input-group {
    position: relative;
    margin-bottom: 20px;
}

.input-group input { 
    width: 100%; 
    padding: 15px; 
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 10px; 
    color: white;
    font-size: 15px;
    font-family: 'Outfit', sans-serif;
    outline: none;
    transition: all 0.3s ease;
}

.input-group input:focus {
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
.input-group input:valid ~ label {
    top: 0;
    font-size: 11px;
    background: #182e38; /* Fakes background cutout */
    padding: 0 5px;
    color: var(--primary);
    border-radius: 3px;
}

button { 
    width: 100%; 
    padding: 15px; 
    background: var(--primary); 
    color: white;
    border: none; 
    border-radius: 10px; 
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

.msg { 
    color: #ff6b6b; 
    text-align: center; 
    margin-top: 15px; 
    font-size: 14px;
    font-weight: 600;
    background: rgba(255, 107, 107, 0.1);
    padding: 8px;
    border-radius: 6px;
    display: <?= empty($msg) ? 'none' : 'block' ?>;
}

.footer-links {
    text-align: center;
    margin-top: 20px;
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
        <img src="../assets/img/logo.png" alt="logo">
        <h2><span class="score">Score</span><span class="hive">Hive</span></h2>
    </div>
    <a href="../index.php" class="nav-link">HOME</a>
</div>

<div class="container">
    <div class="card">
        <h2>Teacher Login</h2>
        <form method="POST">
            <div class="input-group">
                <input type="email" name="email" required>
                <label>Email Address</label>
            </div>
            <div class="input-group">
                <input type="password" name="password" required>
                <label>Password</label>
            </div>
            <button type="submit">Sign In</button>
        </form>
        <div class="msg"><?= $msg ?></div>
        
        <div class="footer-links">
            <a href="../forgot_password.php">Forgot Password?</a>
        </div>
    </div>
</div>
</body>
</html>



