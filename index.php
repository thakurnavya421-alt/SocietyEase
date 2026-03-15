<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "config.php";

$error = "";

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass = $_POST['password'];

    $q = mysqli_query($conn, "SELECT * FROM residents WHERE email='$email'");

    if ($u = mysqli_fetch_assoc($q)) {
        if (password_verify($pass, $u['password'])) {
            $raw_role = strtoupper(trim((string)($u['role'] ?? 'RESIDENT')));
            $session_role = $raw_role === 'SECRETARY' ? 'ADMIN' : $raw_role;

            $_SESSION['uid'] = $u['id'];
            $_SESSION['name'] = $u['name'];
            $_SESSION['role'] = $session_role;

            if ($session_role === 'ADMIN') {
                header("Location: dashboard.php");
            } else {
                header("Location: resident_dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid Password";
        }
    } else {
        $error = "User not found";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="ui-enhance.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOCIETYEASE - Welcome</title>
    <style>
        body, html { height: 100%; margin: 0; font-family: 'Segoe UI', sans-serif; }
        
        /* Background with Overlay */
        .hero-bg {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                        url('https://images.unsplash.com/photo-1449824913935-59a10b8d2000?auto=format&fit=crop&w=1920&q=80');
            min-height: 100%;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            display: flex;
            flex-direction: column;
        }

        /* Navbar Styling */
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 20px 50px; }
        .brand { color: white; font-size: 24px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }
        .nav-links a { color: #ccc; text-decoration: none; margin-left: 20px; font-size: 14px; transition: 0.3s; }
        .nav-links a:hover { color: white; }

        /* Main Content Area */
        .main-content { flex: 1; display: flex; align-items: center; justify-content: space-around; padding: 0 50px; }
        
        /* Left Side: Welcome Text */
        .welcome-text { color: white; max-width: 500px; }
        .welcome-text h1 { font-size: 60px; margin: 0; }
        .welcome-text p { font-size: 18px; line-height: 1.6; color: #ddd; }

        /* Right Side: Login Box */
        .login-card { 
            background: rgba(255, 255, 255, 0.1); 
            backdrop-filter: blur(10px); 
            padding: 40px; 
            border-radius: 15px; 
            width: 350px; 
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
        }
        .login-card h2 { color: white; text-align: center; margin-bottom: 30px; }
        
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; color: #bbb; margin-bottom: 5px; font-size: 13px; }
        .input-group input { 
            width: 100%; padding: 12px; border: none; border-radius: 5px; 
            background: rgba(255,255,255,0.9); box-sizing: border-box;
        }

        .btn-login { 
            width: 100%; padding: 12px; border: none; border-radius: 5px; 
            background: #28a745; color: white; font-weight: bold; cursor: pointer; transition: 0.3s;
        }
        .btn-login:hover { background: #218838; transform: translateY(-2px); }
        
        .error-msg { color: #ff6b6b; text-align: center; font-size: 14px; margin-bottom: 15px; }
        .sub-pages { background: #f8fafc; padding: 40px 50px; }
        .section-card { max-width: 1000px; margin: 0 auto 20px auto; background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        .features-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
        .feature { background: #f1f5f9; border-radius: 10px; padding: 14px; font-size: 14px; color: #334155; }
        .cta-btn { display: inline-block; padding: 10px 16px; background: #2563eb; color: #fff; border-radius: 8px; text-decoration: none; }
        .cta-btn:hover { background: #1d4ed8; }
        @media (max-width: 900px) {
            .main-content { flex-direction: column; gap: 24px; padding: 20px; }
            .welcome-text h1 { font-size: 42px; }
            .login-card { width: 100%; max-width: 360px; }
            .features-grid { grid-template-columns: 1fr; }
            .navbar { padding: 16px 20px; }
        }
    </style>
</head>
<body class="page-auth">

<div class="hero-bg">
    <nav class="navbar">
        <div class="brand">SOCIETYEASE</div>
        <div class="nav-links">
            <a href="#features">Features</a>
            <a href="#">Contact</a>
            <a href="signup.php">Sign Up</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="welcome-text">
            <h1> PINE GROVE </h1>
            <p>Simplify everyday living for your housing society. Manage payments, resolve complaints, and stay updated with your community.</p>
        </div>

        <div class="login-card">
            <h2>Resident / Secretary Login</h2>
            
            <?php if($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="shreya@gmail.com" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" name="login" class="btn-login">LOGIN</button>
            </form>
            
            <p style="color: #bbb; text-align: center; font-size: 13px; margin-top: 20px;">
                New user? <a href="signup.php" style="color: #007bff; text-decoration: none;">Create Account</a>
            </p>
        </div>
    </div>
</div>

<div class="sub-pages">
    <section id="features" class="section-card">
        <h3 style="margin-top:0;">Features</h3>
        <div class="features-grid">
            <div class="feature"><b>Bill Management</b><br>Track monthly maintenance bills and payment status.</div>
            <div class="feature"><b>Notice Board</b><br>Share society announcements instantly.</div>
            <div class="feature"><b>Helpdesk</b><br>Raise and monitor complaints in one place.</div>
        </div>
    </section>
    <section id="signup" class="section-card">
        <h3 style="margin-top:0;">Sign Up</h3>
        <p style="color:#475569;">Create your Resident or Secretary account to start using SocietyEase.</p>
        <a href="signup.php" class="cta-btn">Go to Sign Up</a>
    </section>
</div>

</body>
</html>




