<?php
session_start();
include "config.php";

if (!isset($_SESSION['uid'])) {
    header("Location: index.php");
    exit;
}

$uid = (int) $_SESSION['uid'];
$role = $_SESSION['role'] ?? 'RESIDENT';
$dashboard_link = $role === 'ADMIN' ? 'dashboard.php' : 'resident_dashboard.php';

function notices_has_column($conn, $column) {
    $column = mysqli_real_escape_string($conn, $column);
    $check = mysqli_query($conn, "SHOW COLUMNS FROM notices LIKE '$column'");
    return $check && mysqli_num_rows($check) > 0;
}

$has_message = notices_has_column($conn, 'message');
$has_description = notices_has_column($conn, 'description');
$has_created_at = notices_has_column($conn, 'created_at');

$flash = '';
$flash_type = '';

if ($role === 'ADMIN') {
    if (isset($_POST['add_notice'])) {
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($title === '' || $message === '') {
            $flash = "Title and message are required.";
            $flash_type = 'error';
        } else {
            $safe_title = mysqli_real_escape_string($conn, $title);
            $safe_message = mysqli_real_escape_string($conn, $message);

            if ($has_message) {
                $insert_sql = "INSERT INTO notices (title, message, created_by) VALUES ('$safe_title', '$safe_message', '$uid')";
            } elseif ($has_description) {
                $insert_sql = "INSERT INTO notices (title, description, created_by) VALUES ('$safe_title', '$safe_message', '$uid')";
            } else {
                $insert_sql = "INSERT INTO notices (title, created_by) VALUES ('$safe_title', '$uid')";
            }

            if (mysqli_query($conn, $insert_sql)) {
                $flash = "Notice published successfully.";
                $flash_type = 'success';
            } else {
                $flash = "Failed to publish notice: " . mysqli_error($conn);
                $flash_type = 'error';
            }
        }
    }

    if (isset($_POST['update_notice'])) {
        $notice_id = (int) ($_POST['notice_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($notice_id <= 0 || $title === '' || $message === '') {
            $flash = "Please fill all fields to update notice.";
            $flash_type = 'error';
        } else {
            $safe_title = mysqli_real_escape_string($conn, $title);
            $safe_message = mysqli_real_escape_string($conn, $message);

            if ($has_message) {
                $update_sql = "UPDATE notices SET title='$safe_title', message='$safe_message' WHERE id=$notice_id";
            } elseif ($has_description) {
                $update_sql = "UPDATE notices SET title='$safe_title', description='$safe_message' WHERE id=$notice_id";
            } else {
                $update_sql = "UPDATE notices SET title='$safe_title' WHERE id=$notice_id";
            }

            if (mysqli_query($conn, $update_sql)) {
                $flash = "Notice updated successfully.";
                $flash_type = 'success';
            } else {
                $flash = "Failed to update notice: " . mysqli_error($conn);
                $flash_type = 'error';
            }
        }
    }

    if (isset($_POST['delete_notice'])) {
        $notice_id = (int) ($_POST['notice_id'] ?? 0);
        if ($notice_id <= 0) {
            $flash = "Invalid notice selected.";
            $flash_type = 'error';
        } else {
            if (mysqli_query($conn, "DELETE FROM notices WHERE id=$notice_id")) {
                $flash = "Notice deleted successfully.";
                $flash_type = 'success';
            } else {
                $flash = "Failed to delete notice: " . mysqli_error($conn);
                $flash_type = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="ui-enhance.css">
<title>Notices</title>
<style>
body{
    font-family:'Segoe UI',sans-serif;
    background:linear-gradient(135deg,#fce7f3,#e0f2fe);
    margin:0;
    padding:20px;
}
.container{max-width:1100px;margin:auto;}
h2{text-align:center;color:#374151;}
.top-actions{display:flex;justify-content:flex-end;gap:10px;margin-bottom:15px;}
.btn{text-decoration:none;padding:10px 14px;border-radius:10px;font-size:13px;font-weight:600;border:none;cursor:pointer;}
.btn-secondary{background:#e5e7eb;color:#111827;}
.btn-primary{background:#2563eb;color:#fff;}
.btn-danger{background:#dc2626;color:#fff;}
.btn-warning{background:#f59e0b;color:#111827;}
.notice-form{background:#ffffff;padding:20px;border-radius:14px;margin-bottom:30px;box-shadow:0 10px 25px rgba(0,0,0,0.08);}
.notice-form input,.notice-form textarea{width:100%;padding:12px;margin:8px 0;border-radius:10px;border:1px solid #ddd;font-size:14px;}
.notice-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;}
.card{background:#ffffff;padding:20px;border-radius:16px;box-shadow:0 12px 30px rgba(0,0,0,0.1);position:relative;}
.card h3{color:#6b21a8;margin-bottom:8px;margin-top:0;}
.card p{color:#374151;font-size:14px;min-height:70px;}
.card span{font-size:12px;color:#6b7280;}
.card-actions{display:flex;gap:8px;margin-top:12px;}
.flash{margin:10px 0 18px;padding:10px 12px;border-radius:8px;font-size:14px;}
.flash-success{background:#ecfdf3;color:#166534;border:1px solid #bbf7d0;}
.flash-error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:50;align-items:center;justify-content:center;padding:16px;}
.modal-content{background:#fff;width:100%;max-width:520px;border-radius:14px;padding:20px;box-shadow:0 20px 40px rgba(0,0,0,.25);}
.modal-content h3{margin-top:0;}
.modal-content input,.modal-content textarea{width:100%;padding:10px;margin:8px 0;border-radius:8px;border:1px solid #ddd;box-sizing:border-box;}
.modal-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:10px;}
</style>
</head>
<body class="page-notices">
<?php include_once "app_shell.php"; ?>
<?php se_render_shell_start('Society Notices', 'notices.php', $dashboard_link, $role === 'ADMIN'); ?>
<div class="container">
<h2>Society Notices</h2>

<div class="top-actions">
    <a class="btn btn-secondary" href="<?= $dashboard_link; ?>">Dashboard</a>
    <a class="btn btn-primary" href="notices.php">Refresh</a>
</div>

<?php if ($flash): ?>
    <div class="flash <?= $flash_type === 'success' ? 'flash-success' : 'flash-error'; ?>">
        <?= htmlspecialchars($flash); ?>
    </div>
<?php endif; ?>

<?php if ($role === 'ADMIN') { ?>
<div class="notice-form">
    <h3>Add New Notice</h3>
    <form method="post">
        <input type="text" name="title" placeholder="Notice Title" required>
        <textarea name="message" rows="4" placeholder="Notice Message" required></textarea>
        <button class="btn btn-primary" type="submit" name="add_notice">Publish Notice</button>
    </form>
</div>
<?php } ?>

<div class="notice-grid">
<?php
$message_select = $has_message ? "n.message" : ($has_description ? "n.description" : "''");
$order_field = $has_created_at ? "n.created_at" : "n.id";
$q = mysqli_query($conn, "SELECT n.*, $message_select AS notice_text, r.name
    FROM notices n
    LEFT JOIN residents r ON n.created_by = r.id
    ORDER BY $order_field DESC");

if (!$q) {
    echo "<div class='flash flash-error'>Unable to load notices: " . htmlspecialchars(mysqli_error($conn)) . "</div>";
}

while ($q && ($n = mysqli_fetch_assoc($q))) {
    $notice_id = (int) ($n['id'] ?? 0);
    $title = $n['title'] ?? '';
    $text = $n['notice_text'] ?? '';
    $time_text = '-';
    if (!empty($n['created_at'])) {
        $time_text = date("d M Y, h:i A", strtotime($n['created_at']));
    }
?>
    <div class="card">
        <h3><?= htmlspecialchars($title); ?></h3>
        <p><?= nl2br(htmlspecialchars($text)); ?></p>
        <span>
            Posted by <?= htmlspecialchars($n['name'] ?? 'Admin'); ?><br>
            <?= htmlspecialchars($time_text); ?>
        </span>
        <?php if ($role === 'ADMIN' && $notice_id > 0) { ?>
            <div class="card-actions">
                <button class="btn btn-warning edit-btn" type="button"
                    data-id="<?= $notice_id; ?>"
                    data-title="<?= htmlspecialchars($title, ENT_QUOTES); ?>"
                    data-message="<?= htmlspecialchars($text, ENT_QUOTES); ?>">
                    Edit
                </button>
                <form method="post" onsubmit="return confirm('Delete this notice?');">
                    <input type="hidden" name="notice_id" value="<?= $notice_id; ?>">
                    <button class="btn btn-danger" type="submit" name="delete_notice">Delete</button>
                </form>
            </div>
        <?php } ?>
    </div>
<?php } ?>
</div>
</div>
<?php se_render_shell_end(); ?>

<?php if ($role === 'ADMIN') { ?>
<div class="modal" id="editModal">
    <div class="modal-content">
        <h3>Edit Notice</h3>
        <form method="post">
            <input type="hidden" name="notice_id" id="editNoticeId">
            <input type="text" name="title" id="editTitle" required>
            <textarea name="message" id="editMessage" rows="5" required></textarea>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="closeModal">Cancel</button>
                <button type="submit" name="update_notice" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
<script>
const modal = document.getElementById('editModal');
const closeBtn = document.getElementById('closeModal');
const editNoticeId = document.getElementById('editNoticeId');
const editTitle = document.getElementById('editTitle');
const editMessage = document.getElementById('editMessage');

document.querySelectorAll('.edit-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
        editNoticeId.value = btn.dataset.id || '';
        editTitle.value = btn.dataset.title || '';
        editMessage.value = btn.dataset.message || '';
        modal.style.display = 'flex';
    });
});

closeBtn.addEventListener('click', () => {
    modal.style.display = 'none';
});

modal.addEventListener('click', (e) => {
    if (e.target === modal) {
        modal.style.display = 'none';
    }
});
</script>
<?php } ?>
</body>
</html>




