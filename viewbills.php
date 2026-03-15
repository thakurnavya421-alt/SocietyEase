<?php
session_start();
include "config.php";

if (!isset($_SESSION['uid'])) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role'] ?? 'RESIDENT';
$is_admin = ($role === 'ADMIN');
$uid = (int) ($_SESSION['uid'] ?? 0);
$flash = '';
$flash_type = '';

$resident_name = '';
$resident_flat_no = '';
if (!$is_admin && $uid > 0) {
    $resident_q = mysqli_query($conn, "SELECT name, flat_no FROM residents WHERE id = $uid LIMIT 1");
    if ($resident_q && ($resident = mysqli_fetch_assoc($resident_q))) {
        $resident_name = trim($resident['name'] ?? '');
        $resident_flat_no = trim($resident['flat_no'] ?? '');
    }
}

function bill_num($val) {
    return is_numeric($val) ? (float) $val : 0;
}

function bill_total(array $row): float {
    return bill_num($row['society_charges'] ?? 0)
        + bill_num($row['repairs_maintenance'] ?? 0)
        + bill_num($row['sinking_fund'] ?? 0)
        + bill_num($row['water_charges'] ?? 0)
        + bill_num($row['insurance_charges'] ?? 0)
        + bill_num($row['parking_charges'] ?? 0)
        + bill_num($row['pending_due'] ?? 0)
        - bill_num($row['credit_balance'] ?? 0);
}

if ($is_admin) {
    if (isset($_POST['add_bill'])) {
        $bill_no = trim($_POST['bill_no'] ?? '');
        $resident_name = trim($_POST['resident_name'] ?? '');
        $flat_no = trim($_POST['flat_no'] ?? '');
        $bill_for_month = trim($_POST['bill_for_month'] ?? '');
        $bill_date = trim($_POST['bill_date'] ?? '');
        $payment_status = trim($_POST['payment_status'] ?? 'Unpaid');

        $society_charges = bill_num($_POST['society_charges'] ?? 0);
        $repairs_maintenance = bill_num($_POST['repairs_maintenance'] ?? 0);
        $sinking_fund = bill_num($_POST['sinking_fund'] ?? 0);
        $water_charges = bill_num($_POST['water_charges'] ?? 0);
        $insurance_charges = bill_num($_POST['insurance_charges'] ?? 0);
        $parking_charges = bill_num($_POST['parking_charges'] ?? 0);
        $pending_due = bill_num($_POST['pending_due'] ?? 0);
        $credit_balance = bill_num($_POST['credit_balance'] ?? 0);

        if ($bill_no === '' || $resident_name === '' || $flat_no === '' || $bill_for_month === '' || $bill_date === '') {
            $flash = "Please fill all required bill fields.";
            $flash_type = 'error';
        } else {
            $safe_bill_no = mysqli_real_escape_string($conn, $bill_no);
            $exists = mysqli_query($conn, "SELECT bill_no FROM bills WHERE bill_no='$safe_bill_no' LIMIT 1");
            if ($exists && mysqli_num_rows($exists) > 0) {
                $flash = "Bill number already exists.";
                $flash_type = 'error';
            } else {
                $insert_sql = "INSERT INTO bills
                    (bill_no, resident_name, flat_no, bill_for_month, bill_date, society_charges, repairs_maintenance, sinking_fund, water_charges, insurance_charges, parking_charges, pending_due, credit_balance, payment_status)
                    VALUES
                    ('" . mysqli_real_escape_string($conn, $bill_no) . "',
                     '" . mysqli_real_escape_string($conn, $resident_name) . "',
                     '" . mysqli_real_escape_string($conn, $flat_no) . "',
                     '" . mysqli_real_escape_string($conn, $bill_for_month) . "',
                     '" . mysqli_real_escape_string($conn, $bill_date) . "',
                     $society_charges, $repairs_maintenance, $sinking_fund, $water_charges, $insurance_charges, $parking_charges, $pending_due, $credit_balance,
                     '" . mysqli_real_escape_string($conn, $payment_status === 'Paid' ? 'Paid' : 'Unpaid') . "')";

                if (mysqli_query($conn, $insert_sql)) {
                    $flash = "Bill published successfully.";
                    $flash_type = 'success';
                } else {
                    $flash = "Unable to publish bill: " . mysqli_error($conn);
                    $flash_type = 'error';
                }
            }
        }
    }

    if (isset($_POST['update_bill'])) {
        $bill_no = trim($_POST['bill_no'] ?? '');
        $resident_name = trim($_POST['resident_name'] ?? '');
        $flat_no = trim($_POST['flat_no'] ?? '');
        $bill_for_month = trim($_POST['bill_for_month'] ?? '');
        $bill_date = trim($_POST['bill_date'] ?? '');
        $payment_status = trim($_POST['payment_status'] ?? 'Unpaid');

        $society_charges = bill_num($_POST['society_charges'] ?? 0);
        $repairs_maintenance = bill_num($_POST['repairs_maintenance'] ?? 0);
        $sinking_fund = bill_num($_POST['sinking_fund'] ?? 0);
        $water_charges = bill_num($_POST['water_charges'] ?? 0);
        $insurance_charges = bill_num($_POST['insurance_charges'] ?? 0);
        $parking_charges = bill_num($_POST['parking_charges'] ?? 0);
        $pending_due = bill_num($_POST['pending_due'] ?? 0);
        $credit_balance = bill_num($_POST['credit_balance'] ?? 0);

        if ($bill_no === '' || $resident_name === '' || $flat_no === '' || $bill_for_month === '' || $bill_date === '') {
            $flash = "Please fill all required fields to update bill.";
            $flash_type = 'error';
        } else {
            $update_sql = "UPDATE bills SET
                resident_name='" . mysqli_real_escape_string($conn, $resident_name) . "',
                flat_no='" . mysqli_real_escape_string($conn, $flat_no) . "',
                bill_for_month='" . mysqli_real_escape_string($conn, $bill_for_month) . "',
                bill_date='" . mysqli_real_escape_string($conn, $bill_date) . "',
                society_charges=$society_charges,
                repairs_maintenance=$repairs_maintenance,
                sinking_fund=$sinking_fund,
                water_charges=$water_charges,
                insurance_charges=$insurance_charges,
                parking_charges=$parking_charges,
                pending_due=$pending_due,
                credit_balance=$credit_balance,
                payment_status='" . mysqli_real_escape_string($conn, $payment_status === 'Paid' ? 'Paid' : 'Unpaid') . "'
                WHERE bill_no='" . mysqli_real_escape_string($conn, $bill_no) . "'";

            if (mysqli_query($conn, $update_sql)) {
                $flash = "Bill updated successfully.";
                $flash_type = 'success';
            } else {
                $flash = "Unable to update bill: " . mysqli_error($conn);
                $flash_type = 'error';
            }
        }
    }

    if (isset($_POST['set_status'])) {
        $bill_no = trim($_POST['bill_no'] ?? '');
        $payment_status = trim($_POST['payment_status'] ?? 'Unpaid');
        if ($bill_no === '') {
            $flash = "Invalid bill for status update.";
            $flash_type = 'error';
        } else {
            $status = $payment_status === 'Paid' ? 'Paid' : 'Unpaid';
            $ok = mysqli_query($conn, "UPDATE bills SET payment_status='" . mysqli_real_escape_string($conn, $status) . "' WHERE bill_no='" . mysqli_real_escape_string($conn, $bill_no) . "'");
            if ($ok) {
                $flash = "Payment status updated.";
                $flash_type = 'success';
            } else {
                $flash = "Unable to update status: " . mysqli_error($conn);
                $flash_type = 'error';
            }
        }
    }
} else {
    if (isset($_POST['pay_dummy'])) {
        $bill_no = trim($_POST['bill_no'] ?? '');
        if ($bill_no === '') {
            $flash = "Invalid bill selected for payment.";
            $flash_type = 'error';
        } else {
            $conditions = [];
            if ($resident_flat_no !== '') {
                $conditions[] = "flat_no = '" . mysqli_real_escape_string($conn, $resident_flat_no) . "'";
            }
            if ($resident_name !== '') {
                $conditions[] = "resident_name = '" . mysqli_real_escape_string($conn, $resident_name) . "'";
            }

            if (count($conditions) === 0) {
                $flash = "You are not allowed to pay this bill.";
                $flash_type = 'error';
            } else {
                $update_sql = "UPDATE bills
                    SET payment_status='Paid'
                    WHERE bill_no='" . mysqli_real_escape_string($conn, $bill_no) . "'
                    AND (" . implode(" OR ", $conditions) . ")
                    AND payment_status <> 'Paid'";

                if (mysqli_query($conn, $update_sql) && mysqli_affected_rows($conn) > 0) {
                    $flash = "Dummy payment successful. Bill marked as Paid.";
                    $flash_type = 'success';
                } else {
                    $flash = "Unable to process dummy payment.";
                    $flash_type = 'error';
                }
            }
        }
    }
}

$sql = "SELECT * FROM bills ORDER BY bill_no DESC";
if (!$is_admin) {
    $conditions = [];
    if ($resident_flat_no !== '') {
        $conditions[] = "flat_no = '" . mysqli_real_escape_string($conn, $resident_flat_no) . "'";
    }
    if ($resident_name !== '') {
        $conditions[] = "resident_name = '" . mysqli_real_escape_string($conn, $resident_name) . "'";
    }

    if (count($conditions) > 0) {
        $sql = "SELECT * FROM bills WHERE (" . implode(" OR ", $conditions) . ") ORDER BY bill_no DESC";
    } else {
        $sql = "SELECT * FROM bills WHERE 1=0";
    }
}
$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

$bills = [];
$paid_count = 0;
$unpaid_count = 0;
$total_payable_sum = 0.0;

while ($row = mysqli_fetch_assoc($result)) {
    $row_total = bill_total($row);
    $row['row_total'] = $row_total;
    $bills[] = $row;
    $total_payable_sum += $row_total;
    if (($row['payment_status'] ?? '') === 'Paid') {
        $paid_count++;
    } else {
        $unpaid_count++;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="ui-enhance.css">
    <title>Bill Management | SocietyEase</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root{
            --bg-1:#fdf2f8;
            --bg-2:#eff6ff;
            --text:#3f3d56;
            --muted:#7c7a94;
            --primary:#8b5cf6;
            --primary-dark:#7c3aed;
            --accent:#fda4af;
            --success:#34d399;
            --danger:#fb7185;
            --card:#ffffffd6;
            --line:#ede9fe;
        }
        *{box-sizing:border-box;}
        body{
            margin:0;
            font-family:'Nunito',sans-serif;
            color:var(--text);
            background:
                radial-gradient(circle at 10% 15%, #fbcfe8 0%, transparent 28%),
                radial-gradient(circle at 90% 20%, #bfdbfe 0%, transparent 24%),
                linear-gradient(130deg,var(--bg-1),var(--bg-2));
            padding:28px;
        }
        .container{
            max-width:1220px;
            margin:auto;
            padding:22px;
            border-radius:22px;
            background:var(--card);
            border:1px solid #ffffff80;
            backdrop-filter:blur(12px);
            box-shadow:0 22px 48px rgba(15,23,42,.14);
            animation:rise .45s ease;
        }
        @keyframes rise{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
        h2{
            margin:0;
            font-size:28px;
            letter-spacing:.3px;
            font-family:'Playfair Display',serif;
            color:#5b4b8a;
        }
        .top{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:16px;
        }
        .btn{
            background:linear-gradient(135deg,var(--primary),var(--primary-dark));
            color:white;
            padding:9px 14px;
            border-radius:10px;
            text-decoration:none;
            border:none;
            cursor:pointer;
            transition:.2s ease;
        }
        .btn:hover{transform:translateY(-1px);filter:brightness(1.05);}
        .btn-secondary{background:#ede9fe;color:#4c1d95;}
        .btn-warning{background:linear-gradient(135deg,#fb7185,var(--accent));color:#fff;}
        .status-btn{
            padding:6px 12px;
            border-radius:8px;
            border:none;
            cursor:pointer;
            background:#7c3aed;
            color:#fff;
        }
        .flash{padding:11px 13px;border-radius:10px;margin-bottom:14px;}
        .flash-success{background:#ecfdf5;color:#166534;border:1px solid #a7f3d0;}
        .flash-error{background:#fff1f2;color:#9f1239;border:1px solid #fecdd3;}
        .stats{
            display:grid;
            grid-template-columns:repeat(4,minmax(0,1fr));
            gap:12px;
            margin:10px 0 18px 0;
        }
        .stat{
            padding:13px 14px;
            border-radius:14px;
            background:#faf5ff;
            border:1px solid var(--line);
            box-shadow:0 8px 18px rgba(15,23,42,.05);
        }
        .stat .k{font-size:12px;color:var(--muted);margin-bottom:4px;}
        .stat .v{font-size:20px;font-weight:700;}
        .toolbar{
            display:grid;
            grid-template-columns:2fr 1fr 1fr auto;
            gap:10px;
            margin-bottom:12px;
        }
        .toolbar input,.toolbar select{
            padding:10px 11px;
            border:1px solid #cbd5e1;
            border-radius:10px;
            background:#fdf4ff;
        }
        .table-wrap{
            border-radius:14px;
            overflow:auto;
            border:1px solid var(--line);
            background:#fffdfd;
        }
        table{width:100%;border-collapse:collapse;min-width:900px;}
        th{
            position:sticky;
            top:0;
            z-index:2;
            background:#7c3aed;
            color:white;
            padding:12px;
            text-align:left;
            font-size:13px;
            letter-spacing:.2px;
        }
        td{
            padding:10px;
            border-bottom:1px solid #eef2f7;
            font-size:13px;
            vertical-align:middle;
        }
        tbody tr{transition:background .18s ease;}
        tbody tr:hover{background:#f8fafc;}
        .paid{color:var(--success);font-weight:bold;}
        .unpaid{color:var(--danger);font-weight:bold;}
        .amount{font-weight:700;color:#6d28d9;}
        .empty,.hidden-empty{text-align:center;padding:22px;color:#64748b;}
        .hidden-empty{display:none;}
        .card{
            background:#faf5ff;
            border:1px solid var(--line);
            border-radius:14px;
            padding:16px;
            margin-bottom:18px;
        }
        .form-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;}
        .form-grid input,.form-grid select{
            padding:10px;
            border:1px solid #cbd5e1;
            border-radius:10px;
            width:100%;
        }
        .form-actions{margin-top:10px;}
        .actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
        .status-form{display:flex;gap:6px;align-items:center;}
        .status-form select{padding:6px;border:1px solid #d1d5db;border-radius:8px;}
        .modal{
            display:none;position:fixed;inset:0;background:rgba(2,6,23,.52);z-index:50;
            align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(3px);
        }
        .modal-content{
            background:#fdf4ff;width:100%;max-width:920px;border-radius:16px;padding:20px;
            box-shadow:0 24px 44px rgba(0,0,0,.26);animation:pop .2s ease;
        }
        .modal-head{display:flex;justify-content:space-between;align-items:center;}
        @keyframes pop{from{opacity:.7;transform:scale(.98)}to{opacity:1;transform:scale(1)}}
        @media (max-width: 980px){
            body{padding:14px;}
            .container{padding:14px;}
            .stats{grid-template-columns:repeat(2,minmax(0,1fr));}
            .toolbar{grid-template-columns:1fr;gap:8px;}
            .form-grid{grid-template-columns:repeat(2,minmax(0,1fr));}
        }
        @media (max-width: 560px){
            .stats,.form-grid{grid-template-columns:1fr;}
            .top{flex-direction:column;gap:10px;align-items:flex-start;}
            h2{font-size:24px;}
        }
    </style>
</head>
<body class="page-bills">
<?php include_once "app_shell.php"; ?>
<?php se_render_shell_start('Bill Management', 'viewbills.php', $is_admin ? 'dashboard.php' : 'resident_dashboard.php', $is_admin); ?>
<div class="container">
    <div class="top">
        <h2>Bill Management</h2>
        <a class="btn btn-secondary" href="<?= $is_admin ? 'dashboard.php' : 'resident_dashboard.php'; ?>">Dashboard</a>
    </div>

    <?php if ($flash): ?>
        <div class="flash <?= $flash_type === 'success' ? 'flash-success' : 'flash-error'; ?>">
            <?= htmlspecialchars($flash); ?>
        </div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat">
            <div class="k">Total Bills</div>
            <div class="v" id="statTotal"><?= count($bills); ?></div>
        </div>
        <div class="stat">
            <div class="k">Paid Bills</div>
            <div class="v" id="statPaid"><?= $paid_count; ?></div>
        </div>
        <div class="stat">
            <div class="k">Unpaid Bills</div>
            <div class="v" id="statUnpaid"><?= $unpaid_count; ?></div>
        </div>
        <div class="stat">
            <div class="k">Total Payable</div>
            <div class="v" id="statAmount"><?= number_format($total_payable_sum, 2); ?></div>
        </div>
    </div>

    <?php if ($is_admin): ?>
    <div class="card">
        <h3>Add / Publish Bill</h3>
        <p style="margin-top:-4px;color:#475569;font-size:13px;">
            Enter all bill particulars: Society Charges, Repairs &amp; Maintenance, Sinking Fund, Water Charges, Insurance Charges, Parking Charges, Pending Due, and Credit Balance.
        </p>
        <form method="post">
            <div class="form-grid">
                <input type="text" name="bill_no" placeholder="Bill No" required>
                <input type="text" name="resident_name" placeholder="Resident Name" required>
                <input type="text" name="flat_no" placeholder="Flat No" required>
                <input type="text" name="bill_for_month" placeholder="Bill Month (e.g. Feb 2026)" required>
                <input type="date" name="bill_date" required>
                <select name="payment_status">
                    <option value="Unpaid">Unpaid</option>
                    <option value="Paid">Paid</option>
                </select>
                <input type="number" step="0.01" name="society_charges" placeholder="Society Charges" value="<?= isset($_POST['society_charges']) ? htmlspecialchars((string) $_POST['society_charges']) : ''; ?>">
                <input type="number" step="0.01" name="repairs_maintenance" placeholder="Repairs & Maintenance" value="<?= isset($_POST['repairs_maintenance']) ? htmlspecialchars((string) $_POST['repairs_maintenance']) : ''; ?>">
                <input type="number" step="0.01" name="sinking_fund" placeholder="Sinking Fund" value="<?= isset($_POST['sinking_fund']) ? htmlspecialchars((string) $_POST['sinking_fund']) : ''; ?>">
                <input type="number" step="0.01" name="water_charges" placeholder="Water Charges" value="<?= isset($_POST['water_charges']) ? htmlspecialchars((string) $_POST['water_charges']) : ''; ?>">
                <input type="number" step="0.01" name="insurance_charges" placeholder="Insurance Charges" value="<?= isset($_POST['insurance_charges']) ? htmlspecialchars((string) $_POST['insurance_charges']) : ''; ?>">
                <input type="number" step="0.01" name="parking_charges" placeholder="Parking Charges" value="<?= isset($_POST['parking_charges']) ? htmlspecialchars((string) $_POST['parking_charges']) : ''; ?>">
                <input type="number" step="0.01" name="pending_due" placeholder="Pending Due" value="<?= isset($_POST['pending_due']) ? htmlspecialchars((string) $_POST['pending_due']) : ''; ?>">
                <input type="number" step="0.01" name="credit_balance" placeholder="Credit Balance" value="<?= isset($_POST['credit_balance']) ? htmlspecialchars((string) $_POST['credit_balance']) : ''; ?>">
            </div>
            <div class="form-actions">
                <button class="btn" type="submit" name="add_bill">Publish Bill</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="toolbar">
        <input type="text" id="searchBills" placeholder="Search by bill no, resident, or flat no">
        <select id="statusFilter">
            <option value="">All Status</option>
            <option value="Paid">Paid</option>
            <option value="Unpaid">Unpaid</option>
        </select>
        <input type="text" id="monthFilter" placeholder="Filter month (e.g. Feb 2026)">
        <button type="button" id="clearFilters" class="btn btn-secondary">Clear</button>
    </div>

    <div class="table-wrap">
    <table id="billsTable">
        <tr>
            <th>Bill No</th>
            <th>Resident Name</th>
            <th>Flat No</th>
            <th>Month</th>
            <th>Date</th>
            <th>Total Payable</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php if(count($bills) > 0): ?>
            <?php foreach($bills as $row): ?>
            <tr class="bill-row"
                data-bill-no="<?= htmlspecialchars($row['bill_no'], ENT_QUOTES); ?>"
                data-resident-name="<?= htmlspecialchars($row['resident_name'], ENT_QUOTES); ?>"
                data-flat-no="<?= htmlspecialchars($row['flat_no'], ENT_QUOTES); ?>"
                data-month="<?= htmlspecialchars($row['bill_for_month'], ENT_QUOTES); ?>"
                data-status="<?= htmlspecialchars($row['payment_status'], ENT_QUOTES); ?>"
                data-total="<?= htmlspecialchars((string)$row['row_total'], ENT_QUOTES); ?>">
                <td><?= htmlspecialchars($row['bill_no']); ?></td>
                <td><?= htmlspecialchars($row['resident_name']); ?></td>
                <td><?= htmlspecialchars($row['flat_no']); ?></td>
                <td><?= htmlspecialchars($row['bill_for_month']); ?></td>
                <td><?= htmlspecialchars($row['bill_date']); ?></td>
                <td class="amount"><?= number_format((float)$row['row_total'], 2); ?></td>
                <td class="<?= ($row['payment_status'] === 'Paid') ? 'paid' : 'unpaid'; ?>">
                    <?= htmlspecialchars($row['payment_status']); ?>
                </td>
                <td>
                    <div class="actions">
                        <a class="btn" href="my_bills.php?bill_no=<?= urlencode($row['bill_no']); ?>">View</a>
                        <?php if ($is_admin): ?>
                        <button type="button"
                            class="btn btn-warning edit-btn"
                            data-bill_no="<?= htmlspecialchars($row['bill_no'], ENT_QUOTES); ?>"
                            data-resident_name="<?= htmlspecialchars($row['resident_name'], ENT_QUOTES); ?>"
                            data-flat_no="<?= htmlspecialchars($row['flat_no'], ENT_QUOTES); ?>"
                            data-bill_for_month="<?= htmlspecialchars($row['bill_for_month'], ENT_QUOTES); ?>"
                            data-bill_date="<?= htmlspecialchars($row['bill_date'], ENT_QUOTES); ?>"
                            data-society_charges="<?= htmlspecialchars($row['society_charges'], ENT_QUOTES); ?>"
                            data-repairs_maintenance="<?= htmlspecialchars($row['repairs_maintenance'], ENT_QUOTES); ?>"
                            data-sinking_fund="<?= htmlspecialchars($row['sinking_fund'], ENT_QUOTES); ?>"
                            data-water_charges="<?= htmlspecialchars($row['water_charges'], ENT_QUOTES); ?>"
                            data-insurance_charges="<?= htmlspecialchars($row['insurance_charges'], ENT_QUOTES); ?>"
                            data-parking_charges="<?= htmlspecialchars($row['parking_charges'], ENT_QUOTES); ?>"
                            data-pending_due="<?= htmlspecialchars($row['pending_due'], ENT_QUOTES); ?>"
                            data-credit_balance="<?= htmlspecialchars($row['credit_balance'], ENT_QUOTES); ?>"
                            data-payment_status="<?= htmlspecialchars($row['payment_status'], ENT_QUOTES); ?>">
                            Edit
                        </button>
                        <form class="status-form" method="post">
                            <input type="hidden" name="bill_no" value="<?= htmlspecialchars($row['bill_no']); ?>">
                            <select name="payment_status">
                                <option value="Unpaid" <?= $row['payment_status'] === 'Unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                                <option value="Paid" <?= $row['payment_status'] === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                            </select>
                            <button class="status-btn" type="submit" name="set_status">Save</button>
                        </form>
                        <?php else: ?>
                            <?php if (($row['payment_status'] ?? '') !== 'Paid'): ?>
                                <form method="post">
                                    <input type="hidden" name="bill_no" value="<?= htmlspecialchars($row['bill_no']); ?>">
                                    <button class="status-btn" type="submit" name="pay_dummy">Pay Dummy</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8" class="empty">No bills found.</td></tr>
        <?php endif; ?>
    </table>
    </div>
    <div id="hiddenEmpty" class="hidden-empty">No bills match your filters.</div>
</div>
<?php se_render_shell_end(); ?>

<?php if ($is_admin): ?>
<div class="modal" id="editBillModal">
    <div class="modal-content">
        <div class="modal-head">
            <h3>Edit Bill</h3>
            <button class="btn btn-secondary" type="button" id="closeBillModal">Close</button>
        </div>
        <form method="post">
            <div class="form-grid">
                <input type="text" id="e_bill_no" name="bill_no" readonly>
                <input type="text" id="e_resident_name" name="resident_name" required>
                <input type="text" id="e_flat_no" name="flat_no" required>
                <input type="text" id="e_bill_for_month" name="bill_for_month" required>
                <input type="date" id="e_bill_date" name="bill_date" required>
                <select id="e_payment_status" name="payment_status">
                    <option value="Unpaid">Unpaid</option>
                    <option value="Paid">Paid</option>
                </select>
                <input type="number" step="0.01" id="e_society_charges" name="society_charges">
                <input type="number" step="0.01" id="e_repairs_maintenance" name="repairs_maintenance">
                <input type="number" step="0.01" id="e_sinking_fund" name="sinking_fund">
                <input type="number" step="0.01" id="e_water_charges" name="water_charges">
                <input type="number" step="0.01" id="e_insurance_charges" name="insurance_charges">
                <input type="number" step="0.01" id="e_parking_charges" name="parking_charges">
                <input type="number" step="0.01" id="e_pending_due" name="pending_due">
                <input type="number" step="0.01" id="e_credit_balance" name="credit_balance">
            </div>
            <div class="form-actions">
                <button class="btn" type="submit" name="update_bill">Save Bill Changes</button>
            </div>
        </form>
    </div>
</div>
<script>
const billModal = document.getElementById('editBillModal');
const closeBillModal = document.getElementById('closeBillModal');

document.querySelectorAll('.edit-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
        document.getElementById('e_bill_no').value = btn.dataset.bill_no || '';
        document.getElementById('e_resident_name').value = btn.dataset.resident_name || '';
        document.getElementById('e_flat_no').value = btn.dataset.flat_no || '';
        document.getElementById('e_bill_for_month').value = btn.dataset.bill_for_month || '';
        document.getElementById('e_bill_date').value = btn.dataset.bill_date || '';
        document.getElementById('e_society_charges').value = btn.dataset.society_charges || 0;
        document.getElementById('e_repairs_maintenance').value = btn.dataset.repairs_maintenance || 0;
        document.getElementById('e_sinking_fund').value = btn.dataset.sinking_fund || 0;
        document.getElementById('e_water_charges').value = btn.dataset.water_charges || 0;
        document.getElementById('e_insurance_charges').value = btn.dataset.insurance_charges || 0;
        document.getElementById('e_parking_charges').value = btn.dataset.parking_charges || 0;
        document.getElementById('e_pending_due').value = btn.dataset.pending_due || 0;
        document.getElementById('e_credit_balance').value = btn.dataset.credit_balance || 0;
        document.getElementById('e_payment_status').value = btn.dataset.payment_status || 'Unpaid';
        billModal.style.display = 'flex';
    });
});

closeBillModal.addEventListener('click', () => {
    billModal.style.display = 'none';
});

billModal.addEventListener('click', (e) => {
    if (e.target === billModal) {
        billModal.style.display = 'none';
    }
});
</script>
<?php endif; ?>

<script>
const searchBills = document.getElementById('searchBills');
const statusFilter = document.getElementById('statusFilter');
const monthFilter = document.getElementById('monthFilter');
const clearFilters = document.getElementById('clearFilters');
const rows = Array.from(document.querySelectorAll('.bill-row'));
const hiddenEmpty = document.getElementById('hiddenEmpty');

function toNumber(v) {
    const n = parseFloat(v);
    return Number.isFinite(n) ? n : 0;
}

function updateVisibleStats() {
    let total = 0;
    let paid = 0;
    let unpaid = 0;
    let amount = 0;
    rows.forEach((row) => {
        if (row.style.display !== 'none') {
            total++;
            const status = (row.dataset.status || '').toLowerCase();
            if (status === 'paid') {
                paid++;
            } else {
                unpaid++;
            }
            amount += toNumber(row.dataset.total || '0');
        }
    });

    const statTotal = document.getElementById('statTotal');
    const statPaid = document.getElementById('statPaid');
    const statUnpaid = document.getElementById('statUnpaid');
    const statAmount = document.getElementById('statAmount');
    if (statTotal) statTotal.textContent = total;
    if (statPaid) statPaid.textContent = paid;
    if (statUnpaid) statUnpaid.textContent = unpaid;
    if (statAmount) statAmount.textContent = amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function applyFilters() {
    const q = (searchBills?.value || '').trim().toLowerCase();
    const status = (statusFilter?.value || '').trim().toLowerCase();
    const month = (monthFilter?.value || '').trim().toLowerCase();
    let visible = 0;

    rows.forEach((row) => {
        const billNo = (row.dataset.billNo || '').toLowerCase();
        const residentName = (row.dataset.residentName || '').toLowerCase();
        const flatNo = (row.dataset.flatNo || '').toLowerCase();
        const billMonth = (row.dataset.month || '').toLowerCase();
        const billStatus = (row.dataset.status || '').toLowerCase();

        const matchesSearch = q === '' || billNo.includes(q) || residentName.includes(q) || flatNo.includes(q);
        const matchesStatus = status === '' || billStatus === status;
        const matchesMonth = month === '' || billMonth.includes(month);

        const show = matchesSearch && matchesStatus && matchesMonth;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    if (hiddenEmpty) {
        hiddenEmpty.style.display = visible > 0 ? 'none' : 'block';
    }
    updateVisibleStats();
}

if (searchBills) searchBills.addEventListener('input', applyFilters);
if (statusFilter) statusFilter.addEventListener('change', applyFilters);
if (monthFilter) monthFilter.addEventListener('input', applyFilters);
if (clearFilters) {
    clearFilters.addEventListener('click', () => {
        if (searchBills) searchBills.value = '';
        if (statusFilter) statusFilter.value = '';
        if (monthFilter) monthFilter.value = '';
        applyFilters();
    });
}
applyFilters();
</script>

</body>
</html>


