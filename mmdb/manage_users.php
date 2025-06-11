<?php
require 'conn.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: login.php");
    exit;
}

// Handle user deletion
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM sys_user WHERE user_id = $delete_id");
    header("Location: manage_users.php");
    exit;
}

// Fetch users
$users_result = $conn->query("
    SELECT u.user_id, u.name, u.email, u.is_active, u.date_created, r.role_name
    FROM sys_user u
    JOIN user_role r ON u.role_id = r.role_id
    ORDER BY u.user_id ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Users - MRS</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #2c3e50;
            color: white;
            padding: 15px 30px;
            font-size: 1.5rem;
            font-weight: bold;
        }
        nav {
            background: #34495e;
            padding: 10px 30px;
            display: flex;
            justify-content: flex-end;
        }
        nav a {
            color: #ecf0f1;
            text-decoration: none;
            margin-left: 20px;
            font-weight: 600;
        }
        nav a:hover {
            text-decoration: underline;
        }
        main {
            padding: 30px;
        }
        h1 {
            color: #2c3e50;
        }
        .btn {
            display: inline-block;
            background-color: #4285F4;
            color: white;
            padding: 8px 14px;
            margin: 4px;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        .btn:hover {
            background-color: #3367D6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        table th, table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        table th {
            background-color: #34495e;
            color: white;
        }
        .actions a {
            margin-right: 10px;
            color: #2980b9;
            text-decoration: none;
        }
        .actions a:hover {
            text-decoration: underline;
        }
        .status-active { color: green; font-weight: 600; }
        .status-inactive { color: red; font-weight: 600; }
    </style>
</head>
<body>

<header>Maintenance Report System - Admin</header>
<nav>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="logout.php">Logout</a>
</nav>

<main>
    <h1>Manage Users</h1>

    <a href="create_user.php" class="btn">+ Create New User</a>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($user = $users_result->fetch_assoc()): ?>
                <tr>
                    <td><?= $user['user_id'] ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['role_name']) ?></td>
                    <td class="<?= $user['is_active'] ? 'status-active' : 'status-inactive' ?>">
                        <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                    </td>
                    <td><?= $user['date_created'] ?></td>
                    <td class="actions">
                        <a href="edit_user.php?id=<?= $user['user_id'] ?>">Edit</a>
                        <a href="manage_users.php?delete_id=<?= $user['user_id'] ?>" onclick="return confirm('Are you sure to delete?');">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</main>

</body>
</html>
