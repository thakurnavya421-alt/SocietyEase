<?php
session_start();
include "config.php";

if (!isset($_SESSION['uid'])) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role'] ?? 'RESIDENT';
$is_admin = ($role === 'ADMIN');
$dashboard_link = $is_admin ? 'dashboard.php' : 'resident_dashboard.php';

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS staff_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    role VARCHAR(80) NOT NULL,
    phone VARCHAR(30) DEFAULT '',
    shift_time VARCHAR(80) DEFAULT '',
    notes VARCHAR(255) DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$flash = '';
$flash_type = '';

if ($is_admin && isset($_POST['add_staff'])) {
    $name = trim($_POST['name'] ?? '');
    $staff_role = trim($_POST['staff_role'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $shift_time = trim($_POST['shift_time'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($name === '' || $staff_role === '') {
        $flash = "Staff name and role are required.";
        $flash_type = 'error';
    } else {
        $safe_name = mysqli_real_escape_string($conn, $name);
        $safe_role = mysqli_real_escape_string($conn, $staff_role);
        $safe_phone = mysqli_real_escape_string($conn, $phone);
        $safe_shift = mysqli_real_escape_string($conn, $shift_time);
        $safe_notes = mysqli_real_escape_string($conn, $notes);
        $ok = mysqli_query($conn, "INSERT INTO staff_members (name, role, phone, shift_time, notes)
            VALUES ('$safe_name', '$safe_role', '$safe_phone', '$safe_shift', '$safe_notes')");
        if ($ok) {
            $flash = "Staff member added.";
            $flash_type = 'success';
        } else {
            $flash = "Unable to add staff member: " . mysqli_error($conn);
            $flash_type = 'error';
        }
    }
}

if ($is_admin && isset($_POST['delete_staff'])) {
    $id = (int)($_POST['staff_id'] ?? 0);
    if ($id > 0) {
        mysqli_query($conn, "DELETE FROM staff_members WHERE id=$id");
    }
}

$staff = mysqli_query($conn, "SELECT * FROM staff_members ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="ui-enhance.css">
    <title>Staff Management</title>
    <style>
        body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#fff7ed,#f8fafc);margin:0;padding:20px;}
        .wrap{max-width:1080px;margin:auto;background:#fff;padding:20px;border-radius:16px;box-shadow:0 10px 26px rgba(0,0,0,.08);}
        .top{display:flex;justify-content:space-between;align-items:center;}
        .btn{padding:8px 12px;border:none;border-radius:8px;background:#ea580c;color:#fff;text-decoration:none;cursor:pointer;}
        .btn-danger{background:#b91c1c;}
        .btn-secondary{background:#e2e8f0;color:#0f172a;}
        .flash{margin:12px 0;padding:10px;border-radius:8px;}
        .flash-success{background:#ecfdf3;border:1px solid #bbf7d0;color:#166534;}
        .flash-error{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;}
        .card{background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:14px;margin:14px 0;}
        .grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;}
        .grid input{padding:9px;border:1px solid #cbd5e1;border-radius:8px;}
        table{width:100%;border-collapse:collapse;}
        th,td{padding:10px;border-bottom:1px solid #e2e8f0;text-align:left;font-size:14px;}
        th{background:#9a3412;color:#fff;}
        @media (max-width:900px){.grid{grid-template-columns:1fr 1fr;}}
        @media (max-width:560px){.grid{grid-template-columns:1fr;}}
    </style>
</head>
<body class="page-staff">
<?php include_once "app_shell.php"; ?>
<?php se_render_shell_start($is_admin ? 'Staff Management' : 'Staff Directory', 'staff.php', $dashboard_link, $is_admin); ?>
<div class="wrap">
    <div class="top">
        <h2><?= $is_admin ? 'Staff Management' : 'Staff Directory'; ?></h2>
        <a class="btn btn-secondary" href="<?= $dashboard_link; ?>">Dashboard</a>
    </div>

    <?php if ($flash): ?>
        <div class="flash <?= $flash_type === 'success' ? 'flash-success' : 'flash-error'; ?>"><?= htmlspecialchars($flash); ?></div>
    <?php endif; ?>

    <?php if ($is_admin): ?>
        <div class="card">
            <h3 style="margin-top:0;">Add Staff Member</h3>
            <form method="post">
                <div class="grid">
                    <input type="text" name="name" placeholder="Staff Name" required>
                    <input type="text" name="staff_role" placeholder="Role (Guard, Cleaner, etc.)" required>
                    <input type="text" name="phone" placeholder="Phone">
                    <input type="text" name="shift_time" placeholder="Shift (9 AM - 6 PM)">
                    <input type="text" name="notes" placeholder="Notes">
                </div>
                <div style="margin-top:10px;">
                    <button class="btn" type="submit" name="add_staff">Add Staff</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <table>
        <tr>
            <th>Name</th>
            <th>Role</th>
            <th>Phone</th>
            <th>Shift</th>
            <th>Notes</th>
            <th>Action</th>
        </tr>
        <?php if ($staff && mysqli_num_rows($staff) > 0): ?>
            <?php while ($s = mysqli_fetch_assoc($staff)): ?>
                <tr>
                    <td><?= htmlspecialchars($s['name']); ?></td>
                    <td><?= htmlspecialchars($s['role']); ?></td>
                    <td><?= htmlspecialchars($s['phone']); ?></td>
                    <td><?= htmlspecialchars($s['shift_time']); ?></td>
                    <td><?= htmlspecialchars($s['notes']); ?></td>
                    <td>
                        <?php if ($is_admin): ?>
                            <form method="post" onsubmit="return confirm('Delete this staff member?');">
                                <input type="hidden" name="staff_id" value="<?= (int)$s['id']; ?>">
                                <button class="btn btn-danger" type="submit" name="delete_staff">Delete</button>
                            </form>
                        <?php else: ?>
                            <span>-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No staff records found.</td></tr>
        <?php endif; ?>
    </table>
</div>
<?php se_render_shell_end(); ?>
</body>
</html>




