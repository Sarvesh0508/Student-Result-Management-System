<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScoreHive | Academic Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ff8c00;
            --primary-hover: #e67600;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --glass-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        body {
            margin: 0;
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(-45deg, #0f2027, #203a43, #2c5364, #0f2027);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            color: white;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Animated background shapes */
        .shape {
            position: absolute;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.6;
            animation: float 10s infinite ease-in-out alternate;
        }
        .shape-1 { width: 300px; height: 300px; background: #ff8c00; top: -100px; left: -100px; border-radius: 50%; }
        .shape-2 { width: 400px; height: 400px; background: #00d2ff; bottom: -150px; right: -100px; border-radius: 50%; animation-delay: -5s; }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(30px, 50px) scale(1.1); }
        }

        .container {
            perspective: 1000px;
        }

        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            padding: 50px 40px;
            width: 380px;
            border-radius: 24px;
            text-align: center;
            box-shadow: var(--glass-shadow);
            transform-style: preserve-3d;
            animation: slideUpFade 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
            transform: translateY(40px);
        }

        @keyframes slideUpFade {
            to { opacity: 1; transform: translateY(0); }
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 25px;
            transform: translateZ(30px);
        }

        .logo img {
            width: 70px;
            height: auto;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));
        }

        .logo h1 {
            margin: 0;
            font-size: 36px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .score { color: white; }
        .hive { color: var(--primary); }

        h2 {
            margin-bottom: 30px;
            font-size: 20px;
            font-weight: 400;
            color: rgba(255,255,255,0.8);
            transform: translateZ(20px);
        }

        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 16px;
            transform: translateZ(25px);
        }

        button {
            width: 100%;
            padding: 16px;
            background: rgba(255,255,255,0.05);
            color: white;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        button::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: all 0.5s ease;
        }

        button:hover::before {
            left: 100%;
        }

        button:hover {
            background: var(--primary);
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 140, 0, 0.3);
        }

        .icon { font-size: 20px; }

        .footer {
            margin-top: 35px;
            font-size: 13px;
            color: rgba(255,255,255,0.5);
            font-weight: 300;
            transform: translateZ(10px);
        }
    </style>
</head>

<body>
    <!-- Animated background elements -->
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>

    <div class="container">
        <div class="card" id="tilt-card">
            <div class="logo">
                <img src="assets/img/logo.png" alt="logo">
                <h1><span class="score">Score</span><span class="hive">Hive</span></h1>
            </div>

            <h2>Select your portal to login</h2>

            <div class="btn-group">
                <button onclick="location.href='student/login.php'">
                    Student Portal
                </button>
                <button onclick="location.href='teacher/login.php'">
                    Teacher Portal
                </button>
            </div>

            <div class="footer">
                Student Result Management System
            </div>
        </div>
    </div>

    <!-- 3D Tilt Effect Script -->
    <script>
        const card = document.getElementById('tilt-card');
        document.addEventListener('mousemove', (e) => {
            let xAxis = (window.innerWidth / 2 - e.pageX) / 25;
            let yAxis = (window.innerHeight / 2 - e.pageY) / 25;
            card.style.transform = `rotateY(${xAxis}deg) rotateX(${yAxis}deg)`;
        });
        document.addEventListener('mouseleave', () => {
            card.style.transform = `rotateY(0deg) rotateX(0deg)`;
        });
    </script>
</body>
</html>