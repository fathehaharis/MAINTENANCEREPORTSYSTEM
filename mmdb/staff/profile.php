<?php
session_start();
require '../conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 3) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$password_message = '';

// Fetch user info from DB
$stmt = $conn->prepare("SELECT name, email FROM sys_user WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $email);
$stmt->fetch();
$stmt->close();

// Update profile (name and email)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_name = trim($_POST['name']);
    $new_email = trim($_POST['email']);

    if (empty($new_name) || empty($new_email)) {
        $message = '<div class="message error">Name and Email cannot be empty.</div>';
    } else {
        $stmt = $conn->prepare("UPDATE sys_user SET name = ?, email = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $new_name, $new_email, $user_id);
        if ($stmt->execute()) {
            $_SESSION['name'] = $new_name;
            $message = '<div class="message success">Profile updated successfully.</div>';
        } else {
            $message = '<div class="message error">Error updating profile.</div>';
        }
    }
}

// Handle password change (plain text -- not recommended for production)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $stmt = $conn->prepare("SELECT password FROM sys_user WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($plain_password_db);
    $stmt->fetch();
    $stmt->close();

    if ($current_password !== $plain_password_db) {
        $password_message = '<div class="message error">Current password is incorrect.</div>';
    } elseif ($new_password !== $confirm_password) {
        $password_message = '<div class="message error">New passwords do not match.</div>';
    } else {
        $stmt = $conn->prepare("UPDATE sys_user SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $new_password, $user_id);
        if ($stmt->execute()) {
            $password_message = '<div class="message success">Password changed successfully.</div>';
        } else {
            $password_message = '<div class="message error">Failed to update password.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - Staff</title>
    <style>
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f7fafc; }
        header.staff-header { background: #4a90e2; color: white; padding: 1.3rem 0; font-size: 2rem; font-weight: 700; text-align: center; position: fixed; top: 0; width: 100%; z-index: 1000; }
        .sidebar { position: fixed; top: 0; left: 0; width: 220px; height: 100vh; background: #253444; color: #fff; display: flex; flex-direction: column; z-index: 1100; }
        .sidebar-header { padding: 2rem 1rem 1rem 2rem; font-size: 1.3rem; font-weight: bold; background: #1d2937; }
        .sidebar nav { flex: 1; display: flex; flex-direction: column; gap: 8px; padding: 1.5rem 0.5rem 1.5rem 2rem; }
        .sidebar-section-title { font-size: 1rem; margin-top: 1.5rem; margin-bottom: 0.7rem; font-weight: bold; color: #b8e0fc; }
        .sidebar nav a { color: #cdd9e5; text-decoration: none; font-size: 1.05rem; padding: 9px 16px; border-radius: 6px; transition: background 0.2s; font-weight: 500; display: block; }
        .sidebar nav a.active, .sidebar nav a:hover { background: #4285F4; color: #fff; }
        .sidebar .logout-link { margin-top: auto; margin-bottom: 2rem; padding-left: 2rem; }
        .sidebar .logout-link a { color: #ffbdbd; background: #a94442; font-weight: bold; text-decoration: none; padding: 10px 16px; border-radius: 6px; display: inline-block; }
        .main-content { margin-left: 220px; padding-top: 90px; padding-bottom: 2rem; min-height: 100vh; background: #f7fafc; }
        .container { max-width: 1100px; margin: 0 auto; padding: 0 20px; }
        .card-box { background: #ffffff; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .card-box h2 { font-size: 1.5rem; margin-bottom: 15px; color: #253444; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-weight: bold; display: block; margin-bottom: 6px; }
        .form-group input[type="text"], .form-group input[type="email"], .form-group input[type="password"] {
            width: 100%; padding: 10px; font-size: 1rem; border-radius: 6px; border: 1px solid #ccc;
        }
        .input-wrapper {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.15rem;
            color: #888;
            padding: 0 5px;
        }
        .toggle-password:active { color: #253444; }
        .btn { background: #4285F4; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-size: 15px; cursor: pointer; }
        .btn:hover { background: #3367D6; }
        .message { padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

<header class="staff-header">Maintenance Report System - Staff</header>

<aside class="sidebar">
    <div class="sidebar-header">MRS Staff</div>
    <nav>
        <a href="staff_dashboard.php" class="active">Dashboard</a>
        <div class="sidebar-section-title">My Profile</div>
        <a href="profile.php">Profile</a>
        <div class="sidebar-section-title">Report Management</div>
        <a href="submit_report.php">Submit Report</a>
        <a href="view_report.php">My Report</a>
    </nav>
    <div class="logout-link">
        <a href="../logout.php">Logout</a>
    </div>
</aside>

<div class="main-content">
    <div class="container">
        <div class="card-box">
            <h2>Update Profile</h2>
            <?= $message ?>
            <form method="POST">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                </div>
                <button type="submit" name="update_profile" class="btn">Update Profile</button>
            </form>
        </div>

        <div class="card-box">
            <h2>Change Password</h2>
            <?= $password_message ?>
            <form method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="current_password" name="current_password" required>
                        <button type="button" class="toggle-password" data-target="current_password" tabindex="-1">&#128065;</button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="new_password" name="new_password" required>
                        <button type="button" class="toggle-password" data-target="new_password" tabindex="-1">&#128065;</button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <button type="button" class="toggle-password" data-target="confirm_password" tabindex="-1">&#128065;</button>
                    </div>
                </div>
                <button type="submit" name="change_password" class="btn">Change Password</button>
            </form>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('.toggle-password').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var targetId = this.getAttribute('data-target');
        var input = document.getElementById(targetId);
        if (input.type === "password") {
            input.type = "text";
            this.innerHTML = "&#128586;"; // Hide icon
        } else {
            input.type = "password";
            this.innerHTML = "&#128065;"; // Eye icon
        }
    });
});
</script>
</body>
</html>