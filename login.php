<?php
session_start();
require_once 'db.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: calendar.php');
    exit();
}

// Check for registration success message
if (isset($_GET['registered']) && $_GET['registered'] == 'true') {
    $success = 'Registration successful! Please login with your credentials.';
} else {
    $success = '';
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Check user credentials
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: calendar.php');
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CHRONOMUSE</title>
    <link rel="stylesheet" href="style.css">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('cm_theme');
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <style>
        body {
            margin: 0;
        }
        .auth-page {
            min-height: calc(100vh - 90px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .auth-container {
            max-width: 340px;
            margin: 0 auto;
            padding: 28px;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 18px;
            box-shadow: 0 10px 24px rgba(255, 20, 147, 0.18), inset 0 1px 0 rgba(255,255,255,0.6);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            text-align: center;
        }
        
        .auth-container:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(255, 20, 147, 0.18);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 64px;
        }
        .auth-header h1 {
            margin: 0 0 8px;
            color: #ff4fae;
            transition: transform 0.2s ease, color 0.2s ease;
            animation: brand-float-login 4s ease-in-out infinite;
        }
        .auth-header p {
            margin: 0;
            color: #ff4fae;
            transition: transform 0.2s ease, color 0.2s ease;
            animation: brand-float-login 4s ease-in-out infinite;
            font-size: 13px;
        }
        .auth-header:hover h1,
        .auth-header:hover p {
            transform: translateY(-2px);
        }
        .auth-header:hover h1 {
            color: #b11663;
        }
        
        .auth-form input {
            width: 85%;
            padding: 9px 12px;
            margin: 0 auto 18px;
            border: 1px solid #ddd;
            border-radius: 12px;
            font-size: 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, transform 0.2s ease;
            text-align: center;
            background: rgba(255, 225, 240, 0.9);
            color: #b11663;
        }
        
        .auth-form input:hover {
            border-color: #ff69b4;
            box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.15);
            transform: scale(1.02);
        }
        
        .auth-form button {
            width: 85%;
            padding: 12px;
            background: rgba(255, 105, 180, 0.85);
            color: white;
            border: 1px solid rgba(255, 105, 180, 0.7);
            border-radius: 12px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s ease;
            backdrop-filter: blur(6px);
            margin: 0 auto;
        }
        
        .auth-form button:hover {
            background: linear-gradient(135deg, rgba(255, 105, 180, 0.9), rgba(255, 20, 147, 0.85));
            transform: scale(1.03);
        }
        .auth-credits {
            text-align: center;
            font-size: 12px;
            color: #b11663;
            margin: -52px 0 6px;
        }
        
        .auth-links {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
        }
        
        .auth-links a {
            color: #b11663;
            text-decoration: none;
            transition: color 0.2s ease, transform 0.2s ease;
        }
        .auth-links a:hover {
            color: #8f0f50;
            text-decoration: underline;
            transform: translateY(-1px);
        }

        @keyframes brand-float-login {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }
        
        .error {
            color: #dc3545;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .success {
            color: #28a745;
            text-align: center;
            margin-bottom: 20px;
        }
        .about-btn {
            padding: 8px 16px;
            border-radius: 999px;
            border: 1px solid rgba(255, 105, 180, 0.6);
            background: rgba(255, 105, 180, 0.25);
            color: #ff4fae;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
            backdrop-filter: blur(6px);
        }
        .about-btn.is-active {
            background: linear-gradient(135deg, rgba(255, 105, 180, 0.6), rgba(255, 20, 147, 0.6));
            color: #ffd1ea;
            border-color: rgba(255, 79, 174, 0.8);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.25), 0 8px 16px rgba(255, 20, 147, 0.25);
        }
        .about-btn:hover {
            background: linear-gradient(135deg, rgba(255, 105, 180, 0.55), rgba(255, 20, 147, 0.55));
            transform: translateY(-1px);
            color: #ffd1ea;
        }
        .about-section,
        .contact-section {
            max-width: 720px;
            padding: 24px 28px;
            background: rgba(255, 255, 255, 0.4);
            border: 2px solid rgba(255, 105, 180, 0.6);
            border-radius: 12px;
            box-shadow: 0 10px 24px rgba(255, 20, 147, 0.18), inset 0 1px 0 rgba(255,255,255,0.6);
            backdrop-filter: blur(12px);
            text-align: center;
        }
        .about-section {
            margin: 120px auto 24px;
        }
        .contact-section {
            margin: 40vh auto 220px;
        }
        .reveal-card {
            opacity: 0;
            transform: scale(0.96) translateY(16px);
            transition: transform 0.45s ease, opacity 0.45s ease;
            will-change: transform, opacity;
        }
        .reveal-card.is-visible {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
        .auth-container.reveal-card:hover {
            transform: translateY(-3px) scale(1.01);
            box-shadow: 0 10px 24px rgba(255, 20, 147, 0.18);
        }
        .about-section.reveal-card:hover,
        .contact-section.reveal-card:hover {
            transform: translateY(-3px) scale(1.01);
            box-shadow: 0 10px 24px rgba(255, 20, 147, 0.18);
        }
        .about-section h2 {
            margin: 0 0 8px;
            color: #ff4fae;
        }
        .about-section p {
            margin: 0;
            color: #b11663;
            font-size: 13px;
            line-height: 1.6;
        }
        .about-section p + p {
            margin-top: 12px;
        }
        .contact-section {
            margin: 120px auto 80px;
        }
        .contact-section h2 {
            margin: 0 0 8px;
            color: #ff4fae;
        }
        .contact-section p {
            margin: 0;
            color: #b11663;
            font-size: 13px;
            line-height: 1.6;
        }
        .contact-section p:nth-of-type(2) {
            margin-bottom: 10px;
        }
        .contact-section a {
            color: #b11663;
            text-decoration: none;
            text-shadow: 0 0 6px rgba(255, 20, 147, 0.35);
        }
        .contact-section a:hover {
            color: #8f0f50;
            text-decoration: underline;
            text-shadow: 0 0 10px rgba(255, 20, 147, 0.6);
        }
    </style>
</head>
<body>
    <header class="top-header">
        <div class="topbar-stars" aria-hidden="true"></div>
        <div class="brand">
            <div class="brand-logo">
                <img src="logo.png" alt="CHRONOMUSE logo">
            </div>
            <div class="brand-text">
                <div class="brand-title">CHRONOMUSE</div>
                <div class="brand-subtitle">Remember your days</div>
            </div>
        </div>
        <div class="header-actions">
            <button type="button" class="about-btn" data-target="home" onclick="showInfoSection('home')">Home</button>
            <button type="button" class="about-btn" data-target="about" onclick="showInfoSection('about')">About</button>
            <button type="button" class="about-btn" data-target="contact" onclick="showInfoSection('contact')">Contact</button>
        </div>
    </header>
    <div id="floatingSparkLayer" aria-hidden="true"></div>
    <div id="cursorSparkleLayer" aria-hidden="true"></div>
    <button type="button" id="themeToggle" class="theme-toggle" aria-pressed="false" aria-label="Switch to dark mode">
        <span class="theme-icon theme-icon-light" aria-hidden="true">☀︎</span>
        <span class="theme-icon theme-icon-dark" aria-hidden="true">🌙</span>
    </button>
    <div class="auth-page">
        <div class="auth-container reveal-card" id="authCard">
        <div class="auth-header">
            <h1>CHRONOMUSE</h1>
            <p>Remember your days</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form class="auth-form" method="POST" action="">
            <input type="text" name="username" placeholder="Username or Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        
        <div class="auth-links">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
        </div>
    </div>
    <div class="auth-credits">© 2026 CHRONOMUSE. All rights reserved. Developed by Merlye Deyto</div>
    <section id="aboutSection" class="about-section reveal-card">
        <h2>About CHRONOMUSE</h2>
        <p>CHRONOMUSE is a personal memory system designed to help you remember and reflect on your daily life. It allows you to save memories, reactions, and meaningful moments in a beautiful and organized calendar.</p>
        <p>With CHRONOMUSE, users can easily look back on past experiences, track emotions over time, and preserve special events whether big milestones or simple everyday moments. It provides a personal digital space where time, memories, and reflection come together.</p>
    </section>
    <section id="contactSection" class="contact-section reveal-card">
        <h2>Contact</h2>
        <p>CHRONOMUSE is a personally developed system by Merlye Deyto.</p>
        <p>For inquiries, feedback, or system-related concerns, you may contact the developer through the following channels:</p>
        <p>Email: <a href="mailto:merlyedeyto@email.com">merlyedeyto@email.com</a><br>Facebook: <a href="https://www.facebook.com/share/1CEdsrYWof/" target="_blank" rel="noopener">facebook.com/share/1CEdsrYWof</a><br>Instagram: <a href="https://www.instagram.com/anzu_lye?igsh=dnluNHRtNTA2YXpn" target="_blank" rel="noopener">instagram.com/anzu_lye</a></p>
    </section>
    <script>
        function showInfoSection(which) {
            const buttons = document.querySelectorAll(".about-btn");
            buttons.forEach(btn => {
                btn.classList.toggle("is-active", btn.dataset.target === which);
            });
            const about = document.getElementById("aboutSection");
            const contact = document.getElementById("contactSection");
            const auth = document.getElementById("authCard");
            if (which === "about" && about) {
                about.scrollIntoView({ behavior: "smooth", block: "center" });
            }
            if (which === "contact" && contact) {
                contact.scrollIntoView({ behavior: "smooth", block: "center" });
            }
            if (which === "home" && auth) {
                auth.scrollIntoView({ behavior: "smooth", block: "center" });
            }
        }
        // default highlight
        showInfoSection("home");

        // Scroll reveal for cards
        (() => {
            const cards = document.querySelectorAll(".reveal-card");
            if (!("IntersectionObserver" in window)) {
                cards.forEach(c => c.classList.add("is-visible"));
                return;
            }
            const observer = new IntersectionObserver(
                entries => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add("is-visible");
                        } else {
                            entry.target.classList.remove("is-visible");
                        }
                    });
                },
                { threshold: 0.2 }
            );
            cards.forEach(c => observer.observe(c));
        })();
    </script>
    <script>
        const themeToggle = document.getElementById("themeToggle");
        function applyTheme(theme) {
            const isDark = theme === "dark";
            document.documentElement.classList.toggle("dark", isDark);
            if (themeToggle) {
                themeToggle.classList.toggle("is-dark", isDark);
                themeToggle.setAttribute("aria-pressed", isDark ? "true" : "false");
                themeToggle.setAttribute("aria-label", isDark ? "Switch to light mode" : "Switch to dark mode");
                themeToggle.setAttribute("title", isDark ? "Light mode" : "Dark mode");
            }
        }
        const savedTheme = localStorage.getItem("cm_theme");
        applyTheme(savedTheme === "dark" ? "dark" : "light");
        if (themeToggle) {
            themeToggle.addEventListener("click", () => {
                const isDark = document.documentElement.classList.contains("dark");
                const nextTheme = isDark ? "light" : "dark";
                localStorage.setItem("cm_theme", nextTheme);
                applyTheme(nextTheme);
            });
        }
        // Floating background stars (same as main page)
        (() => {
            const layer = document.getElementById("floatingSparkLayer");
            if (!layer) return;
            const prefersReducedMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
            if (prefersReducedMotion) return;
            const starCount = 60;
            const hues = [330, 315, 200, 140, 30, 350];
            for (let i = 0; i < starCount; i++) {
                const star = document.createElement("span");
                star.className = "floating-spark star-spark";
                const size = 2 + Math.random() * 4;
                const left = Math.random() * 100;
                const top = Math.random() * 100;
                const delay = Math.random() * 8;
                const duration = 4 + Math.random() * 6;
                const hue = hues[Math.floor(Math.random() * hues.length)];
                star.style.width = `${size}px`;
                star.style.height = `${size}px`;
                star.style.left = `${left}%`;
                star.style.top = `${top}%`;
                star.style.setProperty("--spark-delay", `${delay}s`);
                star.style.setProperty("--spark-duration", `${duration}s`);
                star.style.setProperty("--spark-hue", `${hue}`);
                layer.appendChild(star);
            }
        })();
        // Top bar stars
        (() => {
            const layer = document.querySelector(".topbar-stars");
            if (!layer) return;
            const count = 16;
            for (let i = 0; i < count; i++) {
                const star = document.createElement("span");
                star.className = "topbar-star";
                const size = 2 + Math.random() * 3;
                star.style.width = `${size}px`;
                star.style.height = `${size}px`;
                star.style.left = `${Math.random() * 100}%`;
                star.style.top = `${Math.random() * 100}%`;
                star.style.animationDelay = `${Math.random() * 4}s`;
                star.style.animationDuration = `${6 + Math.random() * 6}s`;
                layer.appendChild(star);
            }
        })();
    </script>
    <script>
        // Cursor sparkle effect (same as calendar.php)
        (() => {
            const layer = document.getElementById("cursorSparkleLayer");
            if (!layer) return;
            const prefersReducedMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
            const canHover = window.matchMedia && window.matchMedia("(hover: hover)").matches;
            if (prefersReducedMotion || !canHover) return;

            let lastTime = 0;
            const cooldownMs = 22;
            const maxSparks = 80;
            let activeSparks = 0;

            function spawnSpark(x, y) {
                if (activeSparks >= maxSparks) return;
                activeSparks++;

                const spark = document.createElement("span");
                spark.className = "cursor-sparkle";
                const size = 4 + Math.random() * 6;
                const rotate = Math.random() * 360;
                const driftX = (Math.random() * 2 - 1) * 14;
                const driftY = (Math.random() * 2 - 1) * 14 - 6;
                spark.style.width = `${size}px`;
                spark.style.height = `${size}px`;
                spark.style.transform = `translate(-50%, -50%) rotate(${rotate}deg)`;
                spark.style.left = `${x}px`;
                spark.style.top = `${y}px`;
                spark.style.setProperty("--spark-dx", `${driftX}px`);
                spark.style.setProperty("--spark-dy", `${driftY}px`);

                spark.addEventListener("animationend", () => {
                    spark.remove();
                    activeSparks = Math.max(0, activeSparks - 1);
                });

                layer.appendChild(spark);
            }

            window.addEventListener("pointermove", (e) => {
                if (e.pointerType && e.pointerType !== "mouse") return;
                const now = performance.now();
                if (now - lastTime < cooldownMs) return;
                lastTime = now;
                spawnSpark(e.clientX, e.clientY);
            });
        })();
    </script>
</body>
</html>
