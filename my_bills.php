<?php
session_start();
include "config.php";

if (!isset($_SESSION['uid'])) {
    header("Location: index.php");
    exit;
}

$uid = (int) ($_SESSION['uid'] ?? 0);
$role = $_SESSION['role'] ?? 'RESIDENT';
$is_admin = ($role === 'ADMIN');
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

/* 1️⃣ Check bill_no properly */
if (!isset($_GET['bill_no']) || empty($_GET['bill_no'])) {
    die("Bill number is missing.");
}

$bill_no = mysqli_real_escape_string($conn, $_GET['bill_no']);

if (isset($_POST['pay_now'])) {
    if ($is_admin) {
        $flash = "Dummy payment is available for residents only.";
        $flash_type = 'error';
    } else {
    $post_bill_no = mysqli_real_escape_string($conn, $_POST['bill_no'] ?? '');
    if ($post_bill_no !== '' && $post_bill_no === $bill_no) {
        $update_sql = "UPDATE bills SET payment_status='Paid' WHERE bill_no='$post_bill_no'";
        $conditions = [];
        if ($resident_flat_no !== '') {
            $conditions[] = "flat_no = '" . mysqli_real_escape_string($conn, $resident_flat_no) . "'";
        }
        if ($resident_name !== '') {
            $conditions[] = "resident_name = '" . mysqli_real_escape_string($conn, $resident_name) . "'";
        }
        if (count($conditions) === 0) {
            die("You are not allowed to pay this bill.");
        }
        $update_sql .= " AND (" . implode(" OR ", $conditions) . ")";

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

/* 2️⃣ Fetch bill using bill_no */
$sql = "SELECT * FROM bills WHERE bill_no = '$bill_no'";
if (!$is_admin) {
    $conditions = [];
    if ($resident_flat_no !== '') {
        $conditions[] = "flat_no = '" . mysqli_real_escape_string($conn, $resident_flat_no) . "'";
    }
    if ($resident_name !== '') {
        $conditions[] = "resident_name = '" . mysqli_real_escape_string($conn, $resident_name) . "'";
    }

    if (count($conditions) === 0) {
        die("You are not allowed to view this bill.");
    }

    $sql .= " AND (" . implode(" OR ", $conditions) . ")";
}
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) == 0) {
    die("Bill not found.");
}

$data = mysqli_fetch_assoc($result);

/* 3️⃣ Calculate Total */
$total =
    $data['society_charges'] +
    $data['repairs_maintenance'] +
    $data['sinking_fund'] +
    $data['water_charges'] +
    $data['insurance_charges'] +
    $data['parking_charges'] +
    $data['pending_due'] -
    $data['credit_balance'];
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="ui-enhance.css">
    <title>My Bill | SocietyEase</title>
    <style>
        body{
            font-family:'Segoe UI',sans-serif;
            background:linear-gradient(135deg,#eef2ff,#fdf2f8);
            padding:30px;
        }

        .bill-box{
            max-width:900px;
            margin:auto;
            background:white;
            padding:30px;
            border-radius:18px;
            box-shadow:0 20px 40px rgba(0,0,0,0.15);
            animation:fadeIn .6s ease;
        }

        h2{
            text-align:center;
            color:#4338ca;
            margin-bottom:5px;
        }

        .address{
            text-align:center;
            color:#555;
            margin-bottom:25px;
        }

        .info{
            display:flex;
            justify-content:space-between;
            margin-bottom:10px;
            font-size:14px;
        }

        table{
            width:100%;
            border-collapse:collapse;
            margin-top:20px;
        }

        th{
            background:#6366f1;
            color:white;
            padding:12px;
        }

        td{
            padding:10px;
            border-bottom:1px solid #eee;
            text-align:center;
        }

        .total{
            background:#6366f1;
            color:white;
            font-weight:bold;
        }

        .buttons{
            text-align:center;
            margin-bottom:20px;
        }

        .btn{
            padding:10px 18px;
            border:none;
            border-radius:25px;
            color:white;
            font-size:14px;
            cursor:pointer;
            margin:5px;
        }

        .pay{background:#16a34a;}
        .edit{background:#0ea5e9;}
        .flash{padding:10px 12px;border-radius:8px;margin:8px 0 14px;}
        .flash-success{background:#ecfdf3;color:#166534;border:1px solid #bbf7d0;}
        .flash-error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}

        @keyframes fadeIn{
            from{opacity:0; transform:translateY(20px);}
            to{opacity:1; transform:translateY(0);}
        }
    </style>
</head>

<body class="page-bill-detail">
<?php include_once "app_shell.php"; ?>
<?php se_render_shell_start('Bill Details', 'viewbills.php', $is_admin ? 'dashboard.php' : 'resident_dashboard.php', $is_admin); ?>

<div class="bill-box">

    <div class="buttons">
        <a href="viewbills.php" class="btn edit" style="text-decoration:none; display:inline-block;">BACK TO BILLS</a>
        <?php if (!$is_admin && ($data['payment_status'] ?? '') !== 'Paid'): ?>
        <form method="post" style="display:inline-block;">
            <input type="hidden" name="bill_no" value="<?= htmlspecialchars($data['bill_no']); ?>">
            <button class="btn pay" type="submit" name="pay_now">PAY NOW (DUMMY)</button>
        </form>
        <?php endif; ?>
    </div>

    <?php if ($flash): ?>
        <div class="flash <?= $flash_type === 'success' ? 'flash-success' : 'flash-error'; ?>">
            <?= htmlspecialchars($flash); ?>
        </div>
    <?php endif; ?>

    <h2>SOCIETYEASE</h2>
    <div class="address">
        Flat No: <?php echo $data['flat_no']; ?>, Pine Groove, Shimla (HP)
    </div>

    <div class="info">
        <div><b>Bill No:</b> <?php echo $data['bill_no']; ?></div>
        <div><b>Month:</b> <?php echo $data['bill_for_month']; ?></div>
        <div><b>Date:</b> <?php echo date("d-m-Y", strtotime($data['bill_date'])); ?></div>
    </div>

    <div class="info">
        <div><b>Name:</b> <?php echo $data['resident_name']; ?></div>
        <div><b>Status:</b> <?php echo $data['payment_status']; ?></div>
    </div>

    <table>
        <tr>
            <th>#</th>
            <th>Particular</th>
            <th>Amount (₹)</th>
        </tr>

        <tr><td>1</td><td>Society Charges</td><td><?php echo $data['society_charges']; ?></td></tr>
        <tr><td>2</td><td>Repairs & Maintenance</td><td><?php echo $data['repairs_maintenance']; ?></td></tr>
        <tr><td>3</td><td>Sinking Fund</td><td><?php echo $data['sinking_fund']; ?></td></tr>
        <tr><td>4</td><td>Water Charges</td><td><?php echo $data['water_charges']; ?></td></tr>
        <tr><td>5</td><td>Insurance Charges</td><td><?php echo $data['insurance_charges']; ?></td></tr>
        <tr><td>6</td><td>Parking Charges</td><td><?php echo $data['parking_charges']; ?></td></tr>
        <tr><td>7</td><td>Pending Due</td><td><?php echo $data['pending_due']; ?></td></tr>
        <tr><td>8</td><td>Credit Balance</td><td>-<?php echo $data['credit_balance']; ?></td></tr>

        <tr class="total">
            <td colspan="2">Total Payable</td>
            <td>₹ <?php echo $total; ?></td>
        </tr>
    </table>

</div>
<?php se_render_shell_end(); ?>

</body>
</html>




