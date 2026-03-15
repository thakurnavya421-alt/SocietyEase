<?php
session_start();
include "config.php";

if (!isset($_SESSION['uid'])) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role'] ?? 'RESIDENT';
$is_admin = ($role === 'ADMIN');

function residents_has_column($conn, $column) {
    $column = mysqli_real_escape_string($conn, $column);
    $check = mysqli_query($conn, "SHOW COLUMNS FROM residents LIKE '$column'");
    return $check && mysqli_num_rows($check) > 0;
}

$has_name = residents_has_column($conn, 'name');
$has_email = residents_has_column($conn, 'email');
$has_phone = residents_has_column($conn, 'phone');
$has_flat_no = residents_has_column($conn, 'flat_no');
$has_password = residents_has_column($conn, 'password');
$has_role = residents_has_column($conn, 'role');

$flash = '';
$flash_type = '';

if ($is_admin && isset($_POST['add_resident'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $flat_no = trim($_POST['flat_no'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || ($has_password && trim($password) === '')) {
        $flash = "Name, email and password are required.";
        $flash_type = 'error';
    } else {
        $safe_email = mysqli_real_escape_string($conn, $email);
        $exists_q = mysqli_query($conn, "SELECT id FROM residents WHERE email='$safe_email' LIMIT 1");
        if ($exists_q && mysqli_num_rows($exists_q) > 0) {
            $flash = "Email already exists.";
            $flash_type = 'error';
        } else {
            $cols = [];
            $vals = [];
            if ($has_name) {
                $cols[] = "name";
                $vals[] = "'" . mysqli_real_escape_string($conn, $name) . "'";
            }
            if ($has_email) {
                $cols[] = "email";
                $vals[] = "'" . $safe_email . "'";
            }
            if ($has_phone) {
                $cols[] = "phone";
                $vals[] = "'" . mysqli_real_escape_string($conn, $phone) . "'";
            }
            if ($has_flat_no) {
                $cols[] = "flat_no";
                $vals[] = "'" . mysqli_real_escape_string($conn, $flat_no) . "'";
            }
            if ($has_password) {
                $cols[] = "password";
                $vals[] = "'" . mysqli_real_escape_string($conn, password_hash($password, PASSWORD_DEFAULT)) . "'";
            }
            if ($has_role) {
                $cols[] = "role";
                $vals[] = "'RESIDENT'";
            }

            if (count($cols) < 2) {
                $flash = "Residents table format is not supported.";
                $flash_type = 'error';
            } else {
                $insert_sql = "INSERT INTO residents (" . implode(", ", $cols) . ") VALUES (" . implode(", ", $vals) . ")";
                if (mysqli_query($conn, $insert_sql)) {
                    $flash = "Resident added successfully.";
                    $flash_type = 'success';
                } else {
                    $flash = "Failed to add resident: " . mysqli_error($conn);
                    $flash_type = 'error';
                }
            }
        }
    }
}

$query = "SELECT id, name, email, phone, flat_no FROM residents";
if ($has_role) {
    $query .= " WHERE role='RESIDENT'";
}
$query .= " ORDER BY id DESC";
$result = mysqli_query($conn, $query);
$totalResidents = $result ? mysqli_num_rows($result) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="ui-enhance.css">
    <title>Residents Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f6f8fc; font-family: 'Segoe UI', sans-serif; }
        .dashboard-card { background: #ffffff; border-radius: 15px; padding: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
        .resident-card { background: #ffffff; border-radius: 15px; padding: 15px; box-shadow: 0 6px 18px rgba(0,0,0,0.08); transition: transform 0.3s; }
        .resident-card:hover { transform: translateY(-6px); }
        .profile-img { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; }
        .add-form { background: #fff; border-radius: 15px; padding: 18px; box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
        .top-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .flash-success { background:#ecfdf3; color:#166534; border:1px solid #bbf7d0; padding:10px 12px; border-radius:8px; margin-bottom:14px; }
        .flash-error { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; padding:10px 12px; border-radius:8px; margin-bottom:14px; }
    </style>
</head>
<body class="page-residents">
<?php include_once "app_shell.php"; ?>
<?php se_render_shell_start('Residents Dashboard', 'residents.php', $is_admin ? 'dashboard.php' : 'resident_dashboard.php', $is_admin); ?>
<div class="container my-4">
    <div class="top-actions">
        <h2 class="mb-0">Residents Dashboard</h2>
        <a class="btn btn-outline-primary" href="<?= $is_admin ? 'dashboard.php' : 'resident_dashboard.php'; ?>">Back to Dashboard</a>
    </div>

    <?php if ($flash): ?>
        <div class="<?= $flash_type === 'success' ? 'flash-success' : 'flash-error'; ?>">
            <?= htmlspecialchars($flash); ?>
        </div>
    <?php endif; ?>

    <?php if ($is_admin): ?>
    <div class="add-form mb-4">
        <h5 class="mb-3"><i class="bi bi-person-plus-fill"></i> Add Resident</h5>
        <form method="post" class="row g-2">
            <div class="col-md-3">
                <input class="form-control" type="text" name="name" placeholder="Full Name" required>
            </div>
            <div class="col-md-3">
                <input class="form-control" type="email" name="email" placeholder="Email" required>
            </div>
            <div class="col-md-2">
                <input class="form-control" type="text" name="phone" placeholder="Phone">
            </div>
            <div class="col-md-2">
                <input class="form-control" type="text" name="flat_no" placeholder="Flat No">
            </div>
            <div class="col-md-2">
                <input class="form-control" type="password" name="password" placeholder="Password" required>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit" name="add_resident">Add Resident</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="dashboard-card text-center">
                <div class="fs-3 text-primary"><i class="bi bi-people-fill"></i></div>
                <h5>Total Residents</h5>
                <h2><?= $totalResidents; ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card text-center">
                <div class="fs-3 text-success"><i class="bi bi-building"></i></div>
                <h5>Flats Occupied</h5>
                <h2><?= $totalResidents; ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card text-center">
                <div class="fs-3 text-danger"><i class="bi bi-person-badge"></i></div>
                <h5>Active Members</h5>
                <h2><?= $totalResidents; ?></h2>
            </div>
        </div>
    </div>

    <h4 class="mb-3">Residents List</h4>
    <div class="row">
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <div class="col-md-4 mb-4">
                    <div class="resident-card">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" class="profile-img me-3" alt="Resident">
                            <h5 class="mb-0"><?= htmlspecialchars($row['name'] ?? '-'); ?></h5>
                        </div>
                        <p><i class="bi bi-envelope-fill text-primary"></i> <?= htmlspecialchars($row['email'] ?? '-'); ?></p>
                        <p><i class="bi bi-telephone-fill text-success"></i> <?= htmlspecialchars($row['phone'] ?? '-'); ?></p>
                        <p><i class="bi bi-house-door-fill text-danger"></i> Flat No: <?= htmlspecialchars($row['flat_no'] ?? '-'); ?></p>
                    </div>
                </div>
            <?php } ?>
        <?php else: ?>
            <div class="col-12"><div class="alert alert-info">No residents found.</div></div>
        <?php endif; ?>
    </div>
</div>
<?php se_render_shell_end(); ?>
</body>
</html>




