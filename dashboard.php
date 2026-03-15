<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    ?>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="ui-enhance.css">
    <title>Dashboard - E-Society</title>
</head>
<body class="page-dashboard">
    <?php include_once "app_shell.php"; ?>
    <?php se_render_shell_start('Admin Dashboard', 'dashboard.php', 'dashboard.php', true); ?>

    <div class="dashboard-grid">
        <a href="residents.php" class="menu-card">
            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Residents">
            <h3>Residents</h3>
        </a>
        <a href="notices.php" class="menu-card">
            <img src="https://cdn-icons-png.flaticon.com/512/2666/2666505.png" alt="Noticeboard">
            <h3>Noticeboard</h3>
        </a>
        <a href="viewbills.php" class="menu-card">
            <img src="https://cdn-icons-png.flaticon.com/512/1611/1611154.png" alt="Bill">
            <h3>Bill</h3>
        </a>
        <a href="emergency.php" class="menu-card">
            <img src="https://cdn-icons-png.flaticon.com/512/564/564619.png" alt="Emergency">
            <h3>Emergency No's</h3>
        </a>
        <a href="profile.php" class="menu-card">
            <img src="https://cdn-icons-png.flaticon.com/512/1144/1144760.png" alt="Profile">
            <h3>Profile</h3>
        </a>
        <a href="visitors.php" class="menu-card">
            <img src="https://cdn-icons-png.flaticon.com/512/9836/9836349.png" alt="Visitors">
            <h3>Visitors</h3>
        </a>
        <a href="services.php" class="menu-card">
            <img src="https://cdn-icons-png.flaticon.com/512/1256/1256650.png" alt="Services">
            <h3>Services</h3>
        </a>
        <a href="staff.php" class="menu-card">
            <img src="https://cdn-icons-png.flaticon.com/512/921/921347.png" alt="Staff">
            <h3>Staff</h3>
        </a>
    </div>
    <?php se_render_shell_end(); ?>
</body>
</html>



