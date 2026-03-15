<?php
session_start();
include "config.php";

if (!isset($_SESSION['uid'])) {
    header("Location: index.php");
    exit;
}

$uid = $_SESSION['uid'];
$role = $_SESSION['role'] ?? 'RESIDENT';
$is_admin = ($role === 'ADMIN');
$dashboard_link = $is_admin ? 'dashboard.php' : 'resident_dashboard.php';
$q = mysqli_query($conn, "SELECT * FROM residents WHERE id='$uid'");
$user = mysqli_fetch_assoc($q);
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="ui-enhance.css">
<title>Resident Dashboard</title>
<style>
body{
    margin:0;
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg,#fdfbfb,#ebedee);
}
.card{
    width:520px;
    margin:50px auto;
    background:#fff;
    padding:30px;
    border-radius:16px;
    box-shadow:0 10px 25px rgba(0,0,0,.1);
    animation: fadeIn .6s ease-in;
}
h2{
    text-align:center;
    color:#6b4eff;
}
.info{
    margin:15px 0;
    font-size:16px;
}
.info b{
    color:#555;
}
a{
    display:block;
    margin-top:12px;
    text-align:center;
    padding:12px;
    background:#6b4eff;
    color:#fff;
    border-radius:8px;
    text-decoration:none;
}
a:hover{opacity:.9;}
@keyframes fadeIn{
    from{opacity:0; transform:translateY(20px)}
    to{opacity:1; transform:translateY(0)}
}
</style>
</head>
<body class="page-resident-dashboard">
<?php include_once "app_shell.php"; ?>
<?php se_render_shell_start('Resident Dashboard', 'resident_dashboard.php', $dashboard_link, $is_admin); ?>

<div class="card">
    <h2>Welcome <?php echo $user['name']; ?></h2>

    <div class="info"><b>Email:</b> <?php echo $user['email']; ?></div>
    <div class="info"><b>Phone:</b> <?php echo $user['phone']; ?></div>
    <div class="info"><b>Flat No:</b> <?php echo $user['flat_no']; ?></div>

    <a href="profile.php">View Profile</a>
    <a href="viewbills.php" class="btn"> My Bills </a>
    <a href="residents.php"><?php echo $role === 'ADMIN' ? 'Add Residents' : 'Residents'; ?></a>
    <a href="notices.php">Notices</a>
    <a href="services.php">Services</a>
    <a href="logout.php">Logout</a>
</div>
<?php se_render_shell_end(); ?>

</body>
</html>



