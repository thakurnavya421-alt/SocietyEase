<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "config.php";

$error = "";
$success = "";

if (isset($_POST['signup'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $flat_no = trim($_POST['flat_no'] ?? '');
    $password = $_POST['password'] ?? '';
    $account_type = strtoupper(trim($_POST['account_type'] ?? 'RESIDENT'));

    $role = $account_type === 'SECRETARY' ? 'SECRETARY' : 'RESIDENT';

    if ($name === '' || $email === '' || $phone === '' || $flat_no === '' || $password === '') {
        $error = "Please fill all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $safe_email = mysqli_real_escape_string($conn, $email);
        $exists = mysqli_query($conn, "SELECT id FROM residents WHERE email='$safe_email' LIMIT 1");
        if ($exists && mysqli_num_rows($exists) > 0) {
            $error = "An account with this email already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO residents (name, email, phone, flat_no, password, role) VALUES ('"
                . mysqli_real_escape_string($conn, $name) . "', '"
                . $safe_email . "', '"
                . mysqli_real_escape_string($conn, $phone) . "', '"
                . mysqli_real_escape_string($conn, $flat_no) . "', '"
                . mysqli_real_escape_string($conn, $hash) . "', '"
                . mysqli_real_escape_string($conn, $role) . "')";

            if (mysqli_query($conn, $sql)) {
                $success = "Account created successfully. You can log in now.";
            } else {
                $error = "Unable to create account: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="ui-enhance.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | SocietyEase</title>
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #f1f5f9; }
        .wrap { max-width: 480px; margin: 40px auto; padding: 0 16px; }
        .card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 10px 28px rgba(0,0,0,0.08); }
        h2 { margin: 0 0 18px 0; color: #0f172a; }
        .group { margin-bottom: 14px; }
        label { display: block; margin-bottom: 6px; color: #334155; font-size: 13px; }
        input, select { width: 100%; box-sizing: border-box; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; }
        .btn { width: 100%; padding: 11px; border: none; border-radius: 8px; background: #2563eb; color: #fff; font-weight: 600; cursor: pointer; }
        .btn:hover { background: #1d4ed8; }
        .error { margin-bottom: 12px; color: #b91c1c; background: #fef2f2; border: 1px solid #fecaca; padding: 10px; border-radius: 8px; }
        .ok { margin-bottom: 12px; color: #166534; background: #ecfdf3; border: 1px solid #bbf7d0; padding: 10px; border-radius: 8px; }
        .links { margin-top: 14px; text-align: center; }
        .links a { color: #2563eb; text-decoration: none; }
    </style>
</head>
<body class="page-auth">
<div class="wrap">
    <div class="card">
        <h2>Create Account</h2>
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="ok"><?= htmlspecialchars($success); ?></div><?php endif; ?>
        <form method="post">
            <div class="group">
                <label>Full Name</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>
            <div class="group">
                <label>Email</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="group">
                <label>Phone</label>
                <input type="text" name="phone" required value="<?= htmlspecialchars($_POST['phone'] ?? ''); ?>">
            </div>
            <div class="group">
                <label>Flat No</label>
                <input type="text" name="flat_no" required value="<?= htmlspecialchars($_POST['flat_no'] ?? ''); ?>">
            </div>
            <div class="group">
                <label>Account Type</label>
                <select name="account_type">
                    <option value="RESIDENT" <?= (($_POST['account_type'] ?? 'RESIDENT') === 'RESIDENT') ? 'selected' : ''; ?>>Resident</option>
                    <option value="SECRETARY" <?= (($_POST['account_type'] ?? '') === 'SECRETARY') ? 'selected' : ''; ?>>Secretary</option>
                </select>
            </div>
            <div class="group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button class="btn" type="submit" name="signup">Sign Up</button>
        </form>
        <div class="links">
            <a href="index.php">Back to Login</a>
        </div>
    </div>
</div>
</body>
</html>




