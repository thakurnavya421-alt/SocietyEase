<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); } // Prevents session warning
include "config.php";

// Dynamic fetch based on logged-in user
if (!isset($_SESSION['uid'])) {
    header("Location: index.php");
    exit;
}
$user_id = (int) $_SESSION['uid'];

$res = $conn->query("SELECT * FROM residents WHERE id = '$user_id'");
$user = $res->fetch_assoc();
$is_aryan = isset($user['name']) && strcasecmp(trim($user['name']), 'Aryan') === 0;
$role = $_SESSION['role'] ?? 'RESIDENT';
$is_admin = ($role === 'ADMIN');
$dashboard_link = $is_admin ? 'dashboard.php' : 'resident_dashboard.php';
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="ui-enhance.css">
    <title>Profile - SOCIETYEASE</title>
    <style>
        /* General Reset */
        body { font-family: 'Segoe UI', Arial; margin: 0; background-color: #f0f2f5; color: #333; }

        /* Navigation Bar Styling */
        .navbar { 
            background-color: #212529; 
            display: flex; 
            align-items: center; 
            padding: 0 20px; 
            height: 60px; 
        }
        
        /* BRAND: SOCIETYEASE - Bold and sized as requested */
        .navbar-brand { 
            color: #ffffff; 
            font-size: 18px; 
            font-weight: bold; 
            text-decoration: none; 
            text-transform: uppercase;
            margin-right: 25px; 
        }

        .nav-links a { 
            color: #adb5bd; 
            text-decoration: none; 
            font-size: 14px; 
            margin-right: 15px; 
            transition: 0.3s; 
        }
        .nav-links a:hover { color: #fff; }

        /* Container & Card */
        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .profile-card { 
            background: white; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
            text-align: center; 
        }

        /* Profile Image - Fixed size to not be too big */
        .profile-pic { 
            width: 130px; 
            height: 130px; 
            border-radius: 50%; 
            border: 4px solid #007bff; 
            margin-bottom: 15px; 
            object-fit: cover;
        }

        .info-grid { text-align: left; max-width: 400px; margin: 20px auto; line-height: 2.5; }
        .info-item { border-bottom: 1px solid #eee; display: flex; justify-content: space-between; }
        .label { font-weight: bold; color: #555; }
    </style>
</head>
<body class="page-profile">
<?php include_once "app_shell.php"; ?>
<?php se_render_shell_start('Profile', 'profile.php', $dashboard_link, $is_admin); ?>

<div class="container">
    <div class="profile-card">
        <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" class="profile-pic">
        <h2 style="margin: 5px 0;"><?php echo $user['name']; ?></h2>
        <p style="color: #777;"><?= $is_aryan ? 'Secretary of Pine Groove Shimla' : 'Resident of Pine Groove Shimla'; ?></p>
        
        <div class="info-grid">
            <div class="info-item"><span class="label">Flat No:</span> <span><?php echo $user['flat_no']; ?></span></div>
            <div class="info-item"><span class="label">Email:</span> <span><?php echo $user['email']; ?></span></div>
            <div class="info-item"><span class="label">Phone:</span> <span><?php echo $user['phone']; ?></span></div>
            <div class="info-item"><span class="label">Status:</span> <span style="color: green; font-weight: bold;">Active</span></div>
        </div>
    </div>
</div>
<?php se_render_shell_end(); ?>

</body>
</html>




