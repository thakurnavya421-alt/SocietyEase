<?php
session_start();
include "config.php";

if (!isset($_SESSION['uid'])) {
    header("Location: index.php");
    exit;
}

$uid = (int) $_SESSION['uid'];
$role = $_SESSION['role'] ?? 'RESIDENT';
$is_admin = ($role === 'ADMIN');
$dashboard_link = $is_admin ? 'dashboard.php' : 'resident_dashboard.php';
$resident_name = trim((string)($_SESSION['name'] ?? ''));
$resident_flat_no = '';

$rq = mysqli_query($conn, "SELECT flat_no FROM residents WHERE id=$uid LIMIT 1");
if ($rq && ($r = mysqli_fetch_assoc($rq))) {
    $resident_flat_no = trim((string)($r['flat_no'] ?? ''));
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_name VARCHAR(140) NOT NULL,
    phone VARCHAR(30) DEFAULT '',
    purpose VARCHAR(255) DEFAULT '',
    flat_no VARCHAR(40) NOT NULL,
    visit_date DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'Pending',
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$flash = '';
$flash_type = '';

if (isset($_POST['add_visitor'])) {
    $visitor_name = trim($_POST['visitor_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $flat_no = trim($_POST['flat_no'] ?? '');
    $visit_date = trim($_POST['visit_date'] ?? '');

    if ($visitor_name === '' || $flat_no === '' || $visit_date === '') {
        $flash = "Visitor name, flat no and visit date are required.";
        $flash_type = 'error';
    } else {
        if (!$is_admin && $resident_flat_no !== '' && strcasecmp($resident_flat_no, $flat_no) !== 0) {
            $flash = "You can only create visitor entries for your own flat.";
            $flash_type = 'error';
        } else {
            $safe_name = mysqli_real_escape_string($conn, $visitor_name);
            $safe_phone = mysqli_real_escape_string($conn, $phone);
            $safe_purpose = mysqli_real_escape_string($conn, $purpose);
            $safe_flat = mysqli_real_escape_string($conn, $flat_no);
            $safe_date = mysqli_real_escape_string($conn, $visit_date);
            $ok = mysqli_query($conn, "INSERT INTO visitors (visitor_name, phone, purpose, flat_no, visit_date, status, created_by)
                VALUES ('$safe_name', '$safe_phone', '$safe_purpose', '$safe_flat', '$safe_date', 'Pending', $uid)");
            if ($ok) {
                $flash = "Visitor entry added.";
                $flash_type = 'success';
            } else {
                $flash = "Unable to add visitor: " . mysqli_error($conn);
                $flash_type = 'error';
            }
        }
    }
}

if ($is_admin && isset($_POST['set_status'])) {
    $vid = (int)($_POST['visitor_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'Pending');
    if ($vid > 0) {
        $status = ($status === 'Approved' || $status === 'Rejected') ? $status : 'Pending';
        $safe_status = mysqli_real_escape_string($conn, $status);
        mysqli_query($conn, "UPDATE visitors SET status='$safe_status' WHERE id=$vid");
    }
}

$where = "";
if (!$is_admin) {
    if ($resident_flat_no !== '') {
        $safe_flat = mysqli_real_escape_string($conn, $resident_flat_no);
        $where = "WHERE flat_no='$safe_flat'";
    } else {
        $where = "WHERE created_by=$uid";
    }
}
$visitors = mysqli_query($conn, "SELECT * FROM visitors $where ORDER BY visit_date DESC, id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="ui-enhance.css">
    <title>Visitor Management</title>
    <style>
        body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#f0f9ff,#f8fafc);margin:0;padding:20px;}
        .wrap{max-width:1100px;margin:auto;background:#fff;padding:20px;border-radius:16px;box-shadow:0 10px 26px rgba(0,0,0,.08);}
        .top{display:flex;justify-content:space-between;align-items:center;gap:10px;}
        h2{margin:0;}
        .btn{padding:8px 12px;border:none;border-radius:8px;background:#0ea5e9;color:#fff;text-decoration:none;cursor:pointer;}
        .btn-secondary{background:#e2e8f0;color:#0f172a;}
        .flash{margin:12px 0;padding:10px;border-radius:8px;}
        .flash-success{background:#ecfdf3;border:1px solid #bbf7d0;color:#166534;}
        .flash-error{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;}
        .card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px;margin:14px 0;}
        .grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;}
        .grid input{padding:9px;border:1px solid #cbd5e1;border-radius:8px;}
        table{width:100%;border-collapse:collapse;}
        th,td{padding:10px;border-bottom:1px solid #e2e8f0;text-align:left;font-size:14px;}
        th{background:#0c4a6e;color:#fff;}
        .approved{color:#166534;font-weight:700;}
        .rejected{color:#b91c1c;font-weight:700;}
        .pending{color:#92400e;font-weight:700;}
        .status-form{display:flex;gap:6px;align-items:center;}
        @media (max-width:860px){.grid{grid-template-columns:1fr 1fr;}}
        @media (max-width:560px){.grid{grid-template-columns:1fr;}}
    </style>
</head>
<body class="page-visitors">
<?php include_once "app_shell.php"; ?>
<?php se_render_shell_start('Visitor Management', 'visitors.php', $dashboard_link, $is_admin); ?>
<div class="wrap">
    <div class="top">
        <h2>Visitor Management</h2>
        <a class="btn btn-secondary" href="<?= $dashboard_link; ?>">Dashboard</a>
    </div>

    <?php if ($flash): ?>
        <div class="flash <?= $flash_type === 'success' ? 'flash-success' : 'flash-error'; ?>"><?= htmlspecialchars($flash); ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 style="margin-top:0;">Add Visitor Entry</h3>
        <form method="post">
            <div class="grid">
                <input type="text" name="visitor_name" placeholder="Visitor Name" required>
                <input type="text" name="phone" placeholder="Phone">
                <input type="text" name="purpose" placeholder="Purpose">
                <input type="text" name="flat_no" placeholder="Flat No" value="<?= htmlspecialchars($resident_flat_no); ?>" <?= $is_admin ? '' : 'readonly'; ?> required>
                <input type="date" name="visit_date" required>
            </div>
            <div style="margin-top:10px;">
                <button class="btn" type="submit" name="add_visitor">Save Visitor</button>
            </div>
        </form>
    </div>

    <table>
        <tr>
            <th>Name</th>
            <th>Phone</th>
            <th>Purpose</th>
            <th>Flat No</th>
            <th>Visit Date</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php if ($visitors && mysqli_num_rows($visitors) > 0): ?>
            <?php while ($v = mysqli_fetch_assoc($visitors)): ?>
                <?php
                    $status = $v['status'] ?? 'Pending';
                    $status_class = strtolower($status);
                    if (!in_array($status_class, ['approved', 'rejected', 'pending'])) {
                        $status_class = 'pending';
                    }
                ?>
                <tr>
                    <td><?= htmlspecialchars($v['visitor_name']); ?></td>
                    <td><?= htmlspecialchars($v['phone']); ?></td>
                    <td><?= htmlspecialchars($v['purpose']); ?></td>
                    <td><?= htmlspecialchars($v['flat_no']); ?></td>
                    <td><?= htmlspecialchars($v['visit_date']); ?></td>
                    <td class="<?= $status_class; ?>"><?= htmlspecialchars($status); ?></td>
                    <td>
                        <?php if ($is_admin): ?>
                            <form method="post" class="status-form">
                                <input type="hidden" name="visitor_id" value="<?= (int)$v['id']; ?>">
                                <select name="status">
                                    <option value="Pending" <?= $status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Approved" <?= $status === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="Rejected" <?= $status === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                                <button class="btn" name="set_status" type="submit">Save</button>
                            </form>
                        <?php else: ?>
                            <span>-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">No visitors found.</td></tr>
        <?php endif; ?>
    </table>
</div>
<?php se_render_shell_end(); ?>
</body>
</html>




