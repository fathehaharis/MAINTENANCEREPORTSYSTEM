<?php
require 'conn.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 2) {
    header("Location: login.php");
    exit;
}

$tech_id = $_SESSION['user_id'];

// Fetch technician name
$stmt = $conn->prepare("SELECT name FROM SYS_USER WHERE user_id = ?");
$stmt->bind_param("i", $tech_id);
$stmt->execute();
$stmt->bind_result($full_name);
$stmt->fetch();
$stmt->close();

// Fetch report counts for each status
$statuses = ['In Progress', 'Completed', 'Escalated', 'Need More Info'];
$status_counts = [];

foreach ($statuses as $status) {
    $query = $conn->prepare("SELECT COUNT(*) FROM user_report WHERE assigned_to = ? AND status = ?");
    $query->bind_param("is", $tech_id, $status);
    $query->execute();
    $query->bind_result($count);
    $query->fetch();
    $status_counts[$status] = $count;
    $query->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Technician Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="navbar">
    <h1>Technician Dashboard</h1>
    <div class="nav-links">
        <a href="tech-ass.php">View Assignments</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>
</div>

<div class="container">
    <h2>Welcome, <?= htmlspecialchars($full_name) ?>!</h2>
    <p>This is your technician dashboard. From here, you can monitor your tasks and access tools quickly.</p>

    <!-- Status Cards -->
    <div class="status-cards">
        <div class="card in-progress">
            <h3><?= $status_counts['In Progress'] ?></h3>
            <p>In Progress</p>
        </div>
        <div class="card completed">
            <h3><?= $status_counts['Completed'] ?></h3>
            <p>Completed</p>
        </div>
        <div class="card escalated">
            <h3><?= $status_counts['Escalated'] ?></h3>
            <p>Escalated</p>
        </div>
        <div class="card need-info">
            <h3><?= $status_counts['Need More Info'] ?></h3>
            <p>Need More Info</p>
        </div>
    </div>

    <!-- Quick Tools -->
    <div class="quick-tools">
        <h3>Quick Tools</h3>
        <div class="tool-buttons">
            <a href="tech_ass.php">📋 View Assignments</a>
            <a href="tech-archive.php">🗂 Archived Reports</a>
            <a href="tech-help.php">❓ Maintenance Fundamentals</a>

        </div>
    </div>
</div>

</body>
</html>
