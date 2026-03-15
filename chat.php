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
$name = trim((string)($_SESSION['name'] ?? 'User'));
$dashboard_link = $is_admin ? 'dashboard.php' : 'resident_dashboard.php';

function chat_has_column($conn, $column) {
    $column = mysqli_real_escape_string($conn, $column);
    $check = mysqli_query($conn, "SHOW COLUMNS FROM chat_messages LIKE '$column'");
    return $check && mysqli_num_rows($check) > 0;
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    sender_name VARCHAR(120) NOT NULL,
    sender_role VARCHAR(30) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

if (!chat_has_column($conn, 'sender_id')) {
    mysqli_query($conn, "ALTER TABLE chat_messages ADD COLUMN sender_id INT NOT NULL DEFAULT 0");
}
if (!chat_has_column($conn, 'sender_name')) {
    mysqli_query($conn, "ALTER TABLE chat_messages ADD COLUMN sender_name VARCHAR(120) NOT NULL DEFAULT 'User'");
}
if (!chat_has_column($conn, 'sender_role')) {
    mysqli_query($conn, "ALTER TABLE chat_messages ADD COLUMN sender_role VARCHAR(30) NOT NULL DEFAULT 'RESIDENT'");
}
if (!chat_has_column($conn, 'message')) {
    mysqli_query($conn, "ALTER TABLE chat_messages ADD COLUMN message TEXT NOT NULL");
}
if (!chat_has_column($conn, 'created_at')) {
    mysqli_query($conn, "ALTER TABLE chat_messages ADD COLUMN created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP");
}

$flash = '';
$flash_type = '';

if (isset($_POST['send_message'])) {
    $message = trim($_POST['message'] ?? '');
    if ($message === '') {
        $flash = "Message cannot be empty.";
        $flash_type = 'error';
    } else {
        $safe_message = mysqli_real_escape_string($conn, $message);
        $safe_name = mysqli_real_escape_string($conn, $name);
        $safe_role = mysqli_real_escape_string($conn, $is_admin ? 'SECRETARY' : 'RESIDENT');

        $insert_cols = ['sender_id', 'sender_name', 'message'];
        $insert_vals = [(string)$uid, "'$safe_name'", "'$safe_message'"];
        if (chat_has_column($conn, 'sender_role')) {
            $insert_cols[] = 'sender_role';
            $insert_vals[] = "'$safe_role'";
        }

        $insert_sql = "INSERT INTO chat_messages (" . implode(', ', $insert_cols) . ")
            VALUES (" . implode(', ', $insert_vals) . ")";

        $ok = mysqli_query($conn, $insert_sql);
        if ($ok) {
            header("Location: chat.php");
            exit;
        }
        $flash = "Unable to send message: " . mysqli_error($conn);
        $flash_type = 'error';
    }
}

$order_clause = chat_has_column($conn, 'created_at') ? "created_at DESC, id DESC" : "id DESC";
$messages = mysqli_query($conn, "SELECT * FROM chat_messages ORDER BY $order_clause LIMIT 100");
if (!$messages && $flash === '') {
    $flash = "Unable to load messages: " . mysqli_error($conn);
    $flash_type = 'error';
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="ui-enhance.css">
    <title>Society Chat</title>
    <meta http-equiv="refresh" content="20">
    <style>
        body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#ecfeff,#f8fafc);margin:0;padding:20px;}
        .wrap{max-width:980px;margin:auto;background:#fff;border-radius:16px;padding:20px;box-shadow:0 10px 28px rgba(0,0,0,.08);}
        .top{display:flex;justify-content:space-between;align-items:center;gap:10px;}
        h2{margin:0;color:#0f172a;}
        .btn{padding:9px 12px;border-radius:9px;border:none;text-decoration:none;cursor:pointer;background:#0284c7;color:#fff;}
        .btn-secondary{background:#e2e8f0;color:#0f172a;}
        .flash{margin:12px 0;padding:10px;border-radius:8px;}
        .flash-error{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;}
        .chat-box{margin-top:14px;border:1px solid #e2e8f0;border-radius:12px;height:430px;overflow:auto;padding:14px;background:#f8fafc;}
        .msg{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;margin-bottom:10px;}
        .self{border-color:#7dd3fc;background:#ecfeff;}
        .meta{font-size:12px;color:#64748b;margin-bottom:6px;}
        .text{white-space:pre-wrap;color:#0f172a;}
        .form{display:flex;gap:8px;margin-top:12px;}
        .form textarea{flex:1;min-height:56px;max-height:120px;padding:10px;border:1px solid #cbd5e1;border-radius:10px;resize:vertical;}
        @media (max-width:700px){.form{flex-direction:column;}}
    </style>
</head>
<body class="page-chat">
<?php include_once "app_shell.php"; ?>
<?php se_render_shell_start('Secretary and Residents Chat', 'chat.php', $dashboard_link, $is_admin); ?>
<div class="wrap">
    <div class="top">
        <h2>Secretary and Residents Chat</h2>
        <a class="btn btn-secondary" href="<?= $dashboard_link; ?>">Dashboard</a>
    </div>

    <?php if ($flash): ?>
        <div class="flash flash-error"><?= htmlspecialchars($flash); ?></div>
    <?php endif; ?>

    <div class="chat-box" id="chatBox">
        <?php
        $rows = [];
        if ($messages && mysqli_num_rows($messages) > 0) {
            while ($m = mysqli_fetch_assoc($messages)) {
                $rows[] = $m;
            }
            $rows = array_reverse($rows);
        }
        ?>
        <?php if (count($rows) > 0): ?>
            <?php foreach ($rows as $m): ?>
                <?php $is_self = ((int)($m['sender_id'] ?? 0) === $uid); ?>
                <div class="msg <?= $is_self ? 'self' : ''; ?>">
                    <div class="meta">
                        <?= htmlspecialchars($m['sender_name'] ?? 'User'); ?> (<?= htmlspecialchars($m['sender_role'] ?? 'RESIDENT'); ?>)
                        | <?= !empty($m['created_at']) ? date("d M Y, h:i A", strtotime($m['created_at'])) : '-'; ?>
                    </div>
                    <div class="text"><?= nl2br(htmlspecialchars($m['message'] ?? '')); ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="meta">No messages yet. Start the conversation.</div>
        <?php endif; ?>
    </div>

    <form method="post" class="form">
        <textarea name="message" placeholder="Type your message..." required></textarea>
        <button class="btn" type="submit" name="send_message">Send</button>
    </form>
</div>
<?php se_render_shell_end(); ?>
<script>
const chatBox = document.getElementById('chatBox');
if (chatBox) {
    chatBox.scrollTop = chatBox.scrollHeight;
}
</script>
</body>
</html>
