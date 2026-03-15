<?php
// Establish Connection
include 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = $_SESSION['role'] ?? 'RESIDENT';
$is_admin = ($role === 'ADMIN');
$dashboard_link = $is_admin ? 'dashboard.php' : 'resident_dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="ui-enhance.css">
    <title>Emergency Numbers - SOCIETYEASE</title>
    <style>
        .emergency-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .emergency-card { background: white; padding: 20px; border-radius: 8px; border-left: 5px solid #d9534f; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .emergency-card h3 { margin: 0 0 10px 0; color: #d9534f; }
        .emergency-card p { margin: 5px 0; font-size: 16px; font-weight: bold; }
    </style>
</head>
<body class="page-emergency">
    <?php include_once "app_shell.php"; ?>
    <?php se_render_shell_start('Emergency Contacts', 'emergency.php', $dashboard_link, $is_admin); ?>

    <div class="container">
        <div class="section-header">Emergency Contacts</div>
        
        <div class="emergency-grid">
            <div class="emergency-card">
                <h3>🚑 Ambulance  </h3>
                <p>Main Hospital: 102 </p>
                <p>Nearby Clinic: +91 98765 43210</p>
            </div>
            <div class="emergency-card">
                <h3>🚒 Fire Brigade</h3>
                <p>Central Station: 101</p>
                <p>Local Station: 0177-2652101</p>
            </div>
            <div class="emergency-card">
                <h3>🚓 Police</h3>
                <p>Emergency: 100</p>
                <p>Shimla Police HQ: 0177-2651484</p>
            </div>
            <div class="emergency-card">
                <h3>🛠️ Maintenance Team</h3>
                <p>Electrician (Rohan): +91 91234 56789</p>
                <p>Plumber (Suresh): +91 92345 67890</p>
            </div>
        </div>
    </div>
    <?php se_render_shell_end(); ?>
</body>
</html>




