<?php
require 'conn.php';
session_start();

// Verify admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: login.php");
    exit;
}

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role_id = (int)$_POST['role_id'];

    if ($name && $email && $password && $role_id) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $stmt = $conn->prepare("INSERT INTO sys_user (name, email, password, role_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $name, $email, $hashed_password, $role_id);
        if ($stmt->execute()) {
            $message = "User created successfully.";
        } else {
            $message = "Error: " . $stmt->error;
        }
    } else {
        $message = "Please fill all fields.";
    }
}

// Fetch roles for the dropdown
$roles_result = $conn->query("SELECT role_id, role_name FROM user_role ORDER BY role_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Create New User - MRS</title>
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
            max-width: 600px;
            margin: auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgb(0 0 0 / 0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        form input[type="text"],
        form input[type="email"],
        form input[type="password"],
        form select {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
        }
        form button {
            background-color: #4285F4;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        form button:hover {
            background-color: #3367D6;
        }
        .message {
            margin-bottom: 20px;
            font-weight: 600;
            color: #e74c3c;
        }
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
    <h1>Create New User</h1>

    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="post" action="">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" required />

        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" required />

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required />

        <label for="role_id">Role</label>
        <select id="role_id" name="role_id" required>
            <option value="">-- Select Role --</option>
            <?php while ($role = $roles_result->fetch_assoc()): ?>
                <option value="<?= $role['role_id'] ?>"><?= htmlspecialchars($role['role_name']) ?></option>
            <?php endwhile; ?>
        </select>

        <button type="submit">Create User</button>
    </form>
</main>

</body>
</html>
