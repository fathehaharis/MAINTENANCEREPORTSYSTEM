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
    <title>Technician - Assigned Reports</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="navbar">
    <h1>Assigned Reports</h1>
    <div class="nav-links">
        <a href="tech_dashboard.php" class="btn-return">⬅ Dashboard</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>
</div>

<div class="container">

    <h2>My Assigned Maintenance Tasks</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Status</th>
                <th>Report Date</th>
                <th>Update Status</th>
                <th>Attachments</th>
                <th>Upload</th>
            </tr>
        </thead>
        <tbody>
        <?php while($report = $reports->fetch_assoc()): ?>
            <tr>
                <td><?= $report['report_id'] ?></td>
                <td><?= htmlspecialchars($report['title']) ?></td>
                <td><?= htmlspecialchars($report['status']) ?></td>
                <td><?= $report['date_reported'] ?></td>
                <td>
                    <form method="post" action="update_status.php" class="inline-form">
                        <input type="hidden" name="report_id" value="<?= $report['report_id'] ?>">
                        <select name="new_status" required>
                            <option value="">--Select--</option>
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
                    $rid = $report['report_id'];
                    $aquery = $conn->prepare("SELECT media_id FROM attachment WHERE report_id = ?");
                    $aquery->bind_param("i", $rid);
                    $aquery->execute();
                    $attachments = $aquery->get_result();
                    while ($a = $attachments->fetch_assoc()) {
                        echo "<a class='attachment-link' href='view_attachment.php?id={$a['media_id']}' target='_blank'>View Attachment #{$a['media_id']}</a><br>";
                    }
                    ?>
                </td>
                <td>
                    <form action="upload_attachment.php" method="post" enctype="multipart/form-data" class="inline-form">
                        <input type="hidden" name="report_id" value="<?= $report['report_id'] ?>">
                        <input type="file" name="attachment" required>
                        <input type="submit" value="Upload">
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

</div>

</body>
</html>
