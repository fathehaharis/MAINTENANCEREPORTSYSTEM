<?php
require 'conn.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit;
}

$user_id = (int)$_GET['id'];
$message = '';

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM sys_user WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: manage_users.php");
    exit;
}

// Handle form update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role_id = (int)$_POST['role_id'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($name && $email && $role_id) {
        $stmt = $conn->prepare("UPDATE sys_user SET name=?, email=?, role_id=?, is_active=? WHERE user_id=?");
        $stmt->bind_param("ssiii", $name, $email, $role_id, $is_active, $user_id);
        if ($stmt->execute()) {
            $message = "User updated successfully.";
            header("Location: manage_user.php");
            exit;
        } else {
            $message = "Error: " . $stmt->error;
        }
    } else {
        $message = "Please fill all fields.";
    }
}

// Fetch roles
$roles_result = $conn->query("SELECT role_id, role_name FROM user_role");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Edit User - MRS</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f8; margin: 0; padding: 0; }
        header { background-color: #2c3e50; color: white; padding: 15px 30px; font-size: 1.5rem; font-weight: bold; }
        nav { background: #34495e; padding: 10px 30px; display: flex; justify-content: flex-end; }
        nav a { color: #ecf0f1; text-decoration: none; margin-left: 20px; font-weight: 600; }
        nav a:hover { text-decoration: underline; }
        main { padding: 30px; max-width: 600px; margin: auto; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgb(0 0 0 / 0.1); }
        h1 { color: #2c3e50; margin-bottom: 20px; }
        form label { display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50; }
        form input[type="text"], form input[type="email"], form select { width: 100%; padding: 10px 12px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 6px; font-size: 1rem; }
        form input[type="checkbox"] { margin-right: 10px; }
        form button { background-color: #4285F4; color: white; padding: 12px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background-color 0.3s ease; }
        form button:hover { background-color: #3367D6; }
        .message { margin-bottom: 20px; font-weight: 600; color: #e74c3c; }
    </style>
</head>
<body>

<header>Maintenance Report System - Admin</header>
<nav>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="manage_users.php">Back</a>
    <a href="logout.php">Logout</a>
</nav>

<main>
    <h1>Edit User</h1>

    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="post">
        <label>Full Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required />

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required />

        <label>Role</label>
        <select name="role_id" required>
            <?php while ($role = $roles_result->fetch_assoc()): ?>
                <option value="<?= $role['role_id'] ?>" <?= $user['role_id'] == $role['role_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($role['role_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>
            <input type="checkbox" name="is_active" <?= $user['is_active'] ? 'checked' : '' ?> /> Active
        </label>

        <button type="submit">Update User</button>
    </form>
</main>

</body>
</html>
