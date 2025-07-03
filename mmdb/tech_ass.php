<?php
require 'conn.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 2) {
    header("Location: login.php");
    exit;
}

$tech_id = $_SESSION['user_id'];

// Fetch assigned reports
$sql = "SELECT * FROM user_report WHERE assigned_to = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tech_id);
$stmt->execute();
$reports = $stmt->get_result();
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
    <a href="logout.php">Logout</a>
</div>

<div class="container">
    <h2>Assigned Maintenance Reports</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Status</th>
            <th>Priority</th>
            <th>Due Date</th>
            <th>Update</th>
            <th>Attachments</th>
            <th>Upload</th>
        </tr>
        <?php while($report = $reports->fetch_assoc()): ?>
            <tr>
                <td><?= $report['report_id'] ?></td>
                <td><?= htmlspecialchars($report['title']) ?></td>
                <td><?= htmlspecialchars($report['status']) ?></td>
                <td><?= htmlspecialchars($report['priority']) ?></td>
                <td><?= $report['due_date'] ?></td>
                <td>
                    <form method="post" action="update_status.php">
                        <input type="hidden" name="report_id" value="<?= $report['report_id'] ?>">
                        <select name="new_status">
                            <option value="In Progress">In Progress</option>
                            <option value="Resolved">Resolved</option>
                            <option value="Need More Info">Need More Info</option>
                            <option value="Completed">Completed</option>
                            <option value="Escalated">Escalated</option>
                        </select>
                        <input type="submit" value="Update">
                    </form>
                </td>
                <td>
                    <?php
                    $rid = $report['id'];
                    $aquery = $conn->prepare("SELECT media_id FROM attachment WHERE report_id = ?");
                    $aquery->bind_param("i", $rid);
                    $aquery->execute();
                    $attachments = $aquery->get_result();
                    while ($a = $attachments->fetch_assoc()) {
                        echo "<a class='attachment-link' href='view_attachment.php?id={$a['media_id']}' target='_blank'>View #{$a['media_id']}</a><br>";
                    }
                    ?>
                </td>
                <td>
                    <form action="upload_attachment.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                        <input type="file" name="attachment" required>
                        <input type="submit" value="Upload">
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
