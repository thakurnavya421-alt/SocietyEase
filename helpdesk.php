<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "config.php";

if (!isset($_SESSION['uid'])) {
    header("Location: index.php");
    exit;
}

function table_has_column($conn, $table, $column) {
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $check && mysqli_num_rows($check) > 0;
}

$uid = (int) $_SESSION['uid'];
$resident_name = $_SESSION['name'] ?? '';
$role = $_SESSION['role'] ?? 'RESIDENT';
$dashboard_link = $role === 'ADMIN' ? 'dashboard.php' : 'resident_dashboard.php';

$resident_q = mysqli_query($conn, "SELECT name FROM residents WHERE id = $uid LIMIT 1");
if ($resident_q && ($row = mysqli_fetch_assoc($resident_q))) {
    $resident_name = $row['name'];
}

$submit_error = '';
$submit_success = '';

$has_resident_id = table_has_column($conn, 'complaints', 'resident_id');
$has_resident_name = table_has_column($conn, 'complaints', 'resident_name');
$has_subject = table_has_column($conn, 'complaints', 'subject');
$has_description = table_has_column($conn, 'complaints', 'description');
$has_status = table_has_column($conn, 'complaints', 'status');
$has_date_submitted = table_has_column($conn, 'complaints', 'date_submitted');
$has_created_at = table_has_column($conn, 'complaints', 'created_at');

if (isset($_POST['submit_complaint'])) {
    $subject = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($subject === '' || $description === '') {
        $submit_error = "Please fill in both subject and description.";
    } else {
        $columns = [];
        $values = [];

        if ($has_resident_id) {
            $columns[] = "resident_id";
            $values[] = $uid;
        }
        if ($has_resident_name) {
            $columns[] = "resident_name";
            $values[] = "'" . mysqli_real_escape_string($conn, $resident_name) . "'";
        }
        if ($has_subject) {
            $columns[] = "subject";
            $values[] = "'" . mysqli_real_escape_string($conn, $subject) . "'";
        }
        if ($has_description) {
            $columns[] = "description";
            $values[] = "'" . mysqli_real_escape_string($conn, $description) . "'";
        }
        if ($has_status) {
            $columns[] = "status";
            $values[] = "'Pending'";
        }
        if ($has_date_submitted) {
            $columns[] = "date_submitted";
            $values[] = "'" . date("Y-m-d") . "'";
        } elseif ($has_created_at) {
            $columns[] = "created_at";
            $values[] = "NOW()";
        }

        if (count($columns) < 2) {
            $submit_error = "Complaint table format is not supported. Please contact admin.";
        } else {
            $insert_sql = "INSERT INTO complaints (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $values) . ")";
            if (mysqli_query($conn, $insert_sql)) {
                $submit_success = "Complaint submitted successfully.";
            } else {
                $submit_error = "Unable to submit complaint: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="ui-enhance.css">
    <title>Helpdesk - SOCIETYEASE</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; background-color: #f4f7f6; color: #333; }
        
        /* Navigation (Bold Branding) */
        .navbar { background-color: #212529; display: flex; align-items: center; padding: 0 20px; height: 60px; }
        
        /* Brand: SOCIETYEASE - Bold and sized */
        .navbar-brand { 
            color: #fff; 
            font-size: 18px; 
            font-weight: bold; 
            text-decoration: none; 
            text-transform: uppercase; 
            margin-right: 25px; 
        }
        
        .nav-links a { color: #adb5bd; text-decoration: none; margin-right: 15px; font-size: 14px; }
        .nav-links a:hover { color: #fff; }

        .container { max-width: 900px; margin: 30px auto; padding: 20px; }
        
        /* Complaint Form Card */
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .card h2 { margin-top: 0; color: #333; border-bottom: 2px solid #007bff; display: inline-block; padding-bottom: 5px; font-size: 22px; }
        
        input, textarea { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; font-size: 14px; }
        .btn-submit { background: #007bff; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; font-size: 16px; transition: 0.2s ease; }
        .btn-submit:hover { background: #0056b3; transform: translateY(-1px); }
        .btn-submit:disabled { background: #7baee6; cursor: not-allowed; transform: none; }
        .feedback { padding: 10px 12px; border-radius: 8px; margin-bottom: 12px; font-size: 14px; }
        .feedback-success { background: #e8f8ef; color: #116530; border: 1px solid #b7ebcc; }
        .feedback-error { background: #fdecec; color: #a61b1b; border: 1px solid #f6caca; }

        /* Status Table */
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #495057; color: white; font-weight: bold; }
        .status-pending { color: #f39c12; font-weight: bold; }
        .status-resolved { color: #1a7f37; font-weight: bold; }
        .status-progress { color: #0b66c3; font-weight: bold; }
    </style>
</head>
<body class="page-helpdesk">
<?php include_once "app_shell.php"; ?>
<?php se_render_shell_start('Helpdesk', 'helpdesk.php', $dashboard_link, $role === 'ADMIN'); ?>

<div class="container">
    <div class="card">
        <h2>Submit a Complaint</h2>
        <?php if ($submit_success): ?>
            <div class="feedback feedback-success"><?= htmlspecialchars($submit_success); ?></div>
        <?php endif; ?>
        <?php if ($submit_error): ?>
            <div class="feedback feedback-error"><?= htmlspecialchars($submit_error); ?></div>
        <?php endif; ?>
        <form method="POST" id="complaintForm">
            <input type="text" name="subject" placeholder="Subject (e.g., Plumbing, Electricity)" required>
            <textarea name="description" rows="4" placeholder="Describe your issue here..." required></textarea>
            <button type="submit" name="submit_complaint" id="submitComplaintBtn" class="btn-submit">Submit Complaint</button>
        </form>
    </div>

    <div class="card">
        <h2>Your Complaint History</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Subject</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $select_subject = $has_subject ? "subject" : "''";
                $select_status = $has_status ? "status" : "'Pending'";
                if ($has_date_submitted) {
                    $select_date = "date_submitted";
                } elseif ($has_created_at) {
                    $select_date = "created_at";
                } else {
                    $select_date = "NULL";
                }

                $conditions = [];
                if ($has_resident_id) {
                    $conditions[] = "resident_id = $uid";
                }
                if ($has_resident_name && $resident_name !== '') {
                    $safe_name = mysqli_real_escape_string($conn, $resident_name);
                    $conditions[] = "resident_name = '$safe_name'";
                }
                $where_clause = count($conditions) > 0 ? (" WHERE " . implode(" OR ", $conditions)) : "";
                $order_clause = table_has_column($conn, 'complaints', 'id') ? " ORDER BY id DESC" : "";

                $query = "SELECT $select_date AS submitted_on, $select_subject AS subject, $select_status AS status FROM complaints" . $where_clause . $order_clause;
                $history = mysqli_query($conn, $query);

                if ($history && $history->num_rows > 0) {
                    while($row = $history->fetch_assoc()) {
                        $status = strtolower(trim($row['status']));
                        $status_class = "status-pending";
                        if ($status === "resolved") {
                            $status_class = "status-resolved";
                        } elseif ($status === "in progress" || $status === "processing") {
                            $status_class = "status-progress";
                        }
                        echo "<tr>
                                <td>" . htmlspecialchars($row['submitted_on'] ?? '-') . "</td>
                                <td>" . htmlspecialchars($row['subject']) . "</td>
                                <td class='" . $status_class . "'>" . htmlspecialchars($row['status']) . "</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' style='text-align:center; padding: 20px;'>No complaints submitted yet.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
<?php se_render_shell_end(); ?>

<script>
document.getElementById('complaintForm').addEventListener('submit', function () {
    const btn = document.getElementById('submitComplaintBtn');
    btn.disabled = true;
    btn.textContent = 'Submitting...';
});
</script>

</body>
</html>




