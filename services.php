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
$name = trim((string)($_SESSION['name'] ?? ''));
$dashboard_link = $is_admin ? 'dashboard.php' : 'resident_dashboard.php';
$resident_flat_no = '';

$rq = mysqli_query($conn, "SELECT flat_no FROM residents WHERE id=$uid LIMIT 1");
if ($rq && ($r = mysqli_fetch_assoc($rq))) {
    $resident_flat_no = trim((string)($r['flat_no'] ?? ''));
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS service_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,
    resident_name VARCHAR(140) NOT NULL,
    flat_no VARCHAR(40) NOT NULL,
    service_type VARCHAR(60) NOT NULL,
    description TEXT DEFAULT '',
    preferred_date DATE NULL,
    status VARCHAR(20) DEFAULT 'Open',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$flash = '';
$flash_type = '';

if (isset($_POST['add_request'])) {
    $service_type = trim($_POST['service_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $preferred_date = trim($_POST['preferred_date'] ?? '');
    $flat_no = trim($_POST['flat_no'] ?? $resident_flat_no);

    if ($service_type === '' || $flat_no === '') {
        $flash = "Service type and flat no are required.";
        $flash_type = 'error';
    } else {
        if (!$is_admin && $resident_flat_no !== '' && strcasecmp($resident_flat_no, $flat_no) !== 0) {
            $flash = "You can only raise service requests for your own flat.";
            $flash_type = 'error';
        } else {
            $safe_name = mysqli_real_escape_string($conn, $name);
            $safe_flat = mysqli_real_escape_string($conn, $flat_no);
            $safe_type = mysqli_real_escape_string($conn, $service_type);
            $safe_desc = mysqli_real_escape_string($conn, $description);
            $safe_date = $preferred_date !== '' ? ("'" . mysqli_real_escape_string($conn, $preferred_date) . "'") : "NULL";
            $ok = mysqli_query($conn, "INSERT INTO service_requests (resident_id, resident_name, flat_no, service_type, description, preferred_date, status)
                VALUES ($uid, '$safe_name', '$safe_flat', '$safe_type', '$safe_desc', $safe_date, 'Open')");
            if ($ok) {
                $flash = "Service request created.";
                $flash_type = 'success';
            } else {
                $flash = "Unable to create request: " . mysqli_error($conn);
                $flash_type = 'error';
            }
        }
    }
}

if ($is_admin && isset($_POST['update_status'])) {
    $rid = (int)($_POST['request_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'Open');
    $valid = ['Open', 'In Progress', 'Completed', 'Rejected'];
    if ($rid > 0 && in_array($status, $valid, true)) {
        $safe_status = mysqli_real_escape_string($conn, $status);
        mysqli_query($conn, "UPDATE service_requests SET status='$safe_status' WHERE id=$rid");
    }
}

$where = "";
if (!$is_admin) {
    $safe_flat = mysqli_real_escape_string($conn, $resident_flat_no);
    $where = $resident_flat_no !== '' ? "WHERE flat_no='$safe_flat'" : "WHERE resident_id=$uid";
}
$requests = mysqli_query($conn, "SELECT * FROM service_requests $where ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="ui-enhance.css">
    <title>Services</title>
    <style>
        body{font-family:'Segoe UI',sans-serif;background:linear-gradient(130deg,#f0fdf4,#f8fafc);margin:0;padding:20px;}
        .wrap{max-width:1120px;margin:auto;background:#fff;padding:20px;border-radius:16px;box-shadow:0 10px 26px rgba(0,0,0,.08);}
        .top{display:flex;justify-content:space-between;align-items:center;}
        .btn{padding:8px 12px;border:none;border-radius:8px;background:#16a34a;color:#fff;text-decoration:none;cursor:pointer;}
        .btn-secondary{background:#e2e8f0;color:#0f172a;}
        .flash{margin:12px 0;padding:10px;border-radius:8px;}
        .flash-success{background:#ecfdf3;border:1px solid #bbf7d0;color:#166534;}
        .flash-error{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;}
        .card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px;margin:14px 0;}
        .grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;}
        .grid input,.grid select,.grid textarea{padding:9px;border:1px solid #cbd5e1;border-radius:8px;width:100%;box-sizing:border-box;}
        .grid .full{grid-column:1/-1;}
        table{width:100%;border-collapse:collapse;}
        th,td{padding:10px;border-bottom:1px solid #e2e8f0;text-align:left;font-size:14px;}
        th{background:#14532d;color:#fff;}
        .status-open{color:#92400e;font-weight:700;}
        .status-in-progress{color:#1d4ed8;font-weight:700;}
        .status-completed{color:#166534;font-weight:700;}
        .status-rejected{color:#b91c1c;font-weight:700;}
        .status-form{display:flex;gap:6px;align-items:center;}
        @media (max-width:900px){.grid{grid-template-columns:1fr 1fr;}}
        @media (max-width:560px){.grid{grid-template-columns:1fr;}}
    </style>
</head>
<body class="page-services">
<?php include_once "app_shell.php"; ?>
<?php se_render_shell_start('Services and Requests', 'services.php', $dashboard_link, $is_admin); ?>
<div class="wrap">
    <div class="top">
        <h2>Services and Requests</h2>
        <a class="btn btn-secondary" href="<?= $dashboard_link; ?>">Dashboard</a>
    </div>

    <?php if ($flash): ?>
        <div class="flash <?= $flash_type === 'success' ? 'flash-success' : 'flash-error'; ?>"><?= htmlspecialchars($flash); ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 style="margin-top:0;">Raise Service Request</h3>
        <form method="post">
            <div class="grid">
                <select name="service_type" required>
                    <option value="">Select Service</option>
                    <option value="Plumbing">Plumbing</option>
                    <option value="Electrician">Electrician</option>
                    <option value="Cleaning">Cleaning</option>
                    <option value="Security">Security</option>
                    <option value="Carpentry">Carpentry</option>
                    <option value="Other">Other</option>
                </select>
                <input type="text" name="flat_no" placeholder="Flat No" value="<?= htmlspecialchars($resident_flat_no); ?>" <?= $is_admin ? '' : 'readonly'; ?> required>
                <input type="date" name="preferred_date">
                <input type="text" value="<?= htmlspecialchars($name); ?>" readonly>
                <textarea class="full" name="description" rows="3" placeholder="Describe the issue..."></textarea>
            </div>
            <div style="margin-top:10px;">
                <button class="btn" type="submit" name="add_request">Submit Request</button>
            </div>
        </form>
    </div>

    <table>
        <tr>
            <th>Resident</th>
            <th>Flat</th>
            <th>Service</th>
            <th>Description</th>
            <th>Preferred Date</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php if ($requests && mysqli_num_rows($requests) > 0): ?>
            <?php while ($s = mysqli_fetch_assoc($requests)): ?>
                <?php
                    $status = $s['status'] ?? 'Open';
                    $status_key = strtolower(str_replace(' ', '-', $status));
                    if (!in_array($status_key, ['open', 'in-progress', 'completed', 'rejected'], true)) {
                        $status_key = 'open';
                    }
                ?>
                <tr>
                    <td><?= htmlspecialchars($s['resident_name']); ?></td>
                    <td><?= htmlspecialchars($s['flat_no']); ?></td>
                    <td><?= htmlspecialchars($s['service_type']); ?></td>
                    <td><?= htmlspecialchars($s['description']); ?></td>
                    <td><?= htmlspecialchars($s['preferred_date'] ?? ''); ?></td>
                    <td class="status-<?= $status_key; ?>"><?= htmlspecialchars($status); ?></td>
                    <td>
                        <?php if ($is_admin): ?>
                            <form method="post" class="status-form">
                                <input type="hidden" name="request_id" value="<?= (int)$s['id']; ?>">
                                <select name="status">
                                    <option value="Open" <?= $status === 'Open' ? 'selected' : ''; ?>>Open</option>
                                    <option value="In Progress" <?= $status === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="Completed" <?= $status === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="Rejected" <?= $status === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                                <button class="btn" type="submit" name="update_status">Save</button>
                            </form>
                        <?php else: ?>
                            <span>-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">No service requests found.</td></tr>
        <?php endif; ?>
    </table>
</div>
<?php se_render_shell_end(); ?>
</body>
</html>




