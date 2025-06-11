<?php
require 'conn.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: view_reports.php");
    exit;
}

$report_id = intval($_GET['id']);

// Handle delete
if (isset($_POST['delete'])) {
    // Also delete attachments (if ON DELETE CASCADE is not used)
    $stmt = $conn->prepare("DELETE FROM user_report WHERE report_id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $stmt->close();
    header("Location: view_reports.php?deleted=1");
    exit;
}

// Fetch report details
$stmt = $conn->prepare("SELECT r.*, u.name AS reporter_name FROM user_report r JOIN sys_user u ON r.submitted_by = u.user_id WHERE r.report_id = ?");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();
$stmt->close();

if (!$report) {
    echo "Report not found.";
    exit;
}

// Fetch report attachments (DDL: media_id, report_id, media_data, uploaded_at)
$stmt = $conn->prepare("SELECT media_id FROM attachment WHERE report_id = ?");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$attachments_result = $stmt->get_result();
$attachments = [];
while ($row = $attachments_result->fetch_assoc()) {
    $attachments[] = $row;
}
$stmt->close();

// Handle update
if (isset($_POST['update'])) {
    $new_title = $_POST['title'];
    $new_description = $_POST['description'];
    $new_status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE user_report SET title = ?, description = ?, status = ? WHERE report_id = ?");
    $stmt->bind_param("sssi", $new_title, $new_description, $new_status, $report_id);
    $stmt->execute();
    $stmt->close();

    // Add an entry in the report_hist for this status update
    $stmt = $conn->prepare("INSERT INTO report_hist (report_id, status, changed_by, notes) VALUES (?, ?, ?, ?)");
    $notes = "Report updated via admin panel.";
    $stmt->bind_param("isis", $report_id, $new_status, $_SESSION['user_id'], $notes);
    $stmt->execute();
    $stmt->close();

    header("Location: report_details.php?id=$report_id&success=1");
    exit;
}

// Fetch report history
$stmt = $conn->prepare("
    SELECT h.changed_at, h.status, h.notes, u.name AS changer_name
    FROM report_hist h
    JOIN sys_user u ON h.changed_by = u.user_id
    WHERE h.report_id = ?
    ORDER BY h.changed_at DESC
");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$history_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Report Details - Admin</title>
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
            text-align: center;
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
            padding: 40px 20px;
            display: flex;
            justify-content: center;
        }
        .container {
            background: white;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
        }
        h1, h2 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        .attachments-list {
            margin-bottom: 25px;
            text-align: left;
        }
        .attachment-thumb {
            display: inline-block;
            margin: 0 5px 10px 5px;
            border: 1px solid #eee;
            background: #fafbfc;
            border-radius: 4px;
            padding: 2px;
            max-width: 80px;
            max-height: 80px;
        }
        .attachment-thumb img {
            max-width: 75px;
            max-height: 75px;
            border-radius: 3px;
        }
        .attachment-link {
            display: block;
            font-size: 0.85em;
            word-break: break-all;
            color: #4285F4;
            margin-top: 3px;
        }
        form {
            margin-top: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #34495e;
        }
        input[type="text"], textarea, select {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            margin-bottom: 20px;
            width: 100%;
            font-size: 1rem;
            font-family: inherit;
            resize: vertical;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        input[type="text"]:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #4285F4;
            box-shadow: 0 0 5px rgba(66, 133, 244, 0.5);
        }
        input[type="submit"], .btn {
            background-color: #4285F4;
            color: white;
            font-weight: 600;
            border: none;
            cursor: pointer;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            font-size: 1rem;
            transition: background-color 0.3s ease;
            margin-right: 10px;
        }
        input[type="submit"]:hover, .btn:hover {
            background-color: #3367D6;
        }
        .danger {
            background-color: #e74c3c;
        }
        .danger:hover {
            background-color: #c0392b;
        }
        .success {
            color: green;
            font-weight: 600;
            margin-bottom: 25px;
            text-align: center;
        }
        .actions {
            display: flex;
            justify-content: center;
            margin-top: 10px;
        }
        .btn-back {
            margin-top: 30px;
            display: block;
            text-align: center;
        }
        /* History table */
        table.history {
            width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
        }
        table.history th, table.history td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        table.history th {
            background-color: #34495e;
            color: white;
        }
    </style>
</head>
<body>

<header>
    Admin Dashboard - Report Details
</header>

<nav>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="logout.php">Logout</a>
</nav>

<main>
    <div class="container">
        <h1>Report Details</h1>

        <?php if (isset($_GET['success'])): ?>
            <p class="success">Report updated successfully!</p>
        <?php endif; ?>

        <!-- Attachments -->
        <div class="attachments-list">
            <label>Media Attachments:</label>
            <?php if (empty($attachments)): ?>
                <span style="color: #888;">No media attached.</span>
            <?php else: ?>
                <?php foreach ($attachments as $att): ?>
                    <a class="attachment-thumb" href="attachment.php?media_id=<?= $att['media_id'] ?>" target="_blank" title="Open attachment">
                        <img src="attachment.php?media_id=<?= $att['media_id'] ?>" alt="Attachment" />
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form method="post">
            <label for="title">Title:</label>
            <input type="text" name="title" id="title" required value="<?= htmlspecialchars($report['title']) ?>" />

            <label for="description">Description:</label>
            <textarea name="description" id="description" rows="5" required><?= htmlspecialchars($report['description']) ?></textarea>

            <label for="status">Status:</label>
            <select name="status" id="status" required>
                <?php
                $statuses = ['Submitted', 'In Progress', 'Resolved', 'Rejected'];
                foreach ($statuses as $status) {
                    $selected = ($report['status'] == $status) ? 'selected' : '';
                    echo "<option value=\"$status\" $selected>$status</option>";
                }
                ?>
            </select>

            <div class="actions">
                <input type="submit" name="update" value="Update Report" />
                <input type="submit" name="delete" value="Delete Report" class="danger" onclick="return confirm('Are you sure you want to delete this report?');" />
            </div>
        </form>

        <h2>Change History</h2>
        <?php if ($history_result->num_rows > 0): ?>
        <table class="history">
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>Status</th>
                    <th>Changed By</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($hist = $history_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($hist['changed_at']) ?></td>
                        <td><?= htmlspecialchars($hist['status']) ?></td>
                        <td><?= htmlspecialchars($hist['changer_name']) ?></td>
                        <td><?= nl2br(htmlspecialchars($hist['notes'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p>No history available for this report.</p>
        <?php endif; ?>

        <a href="view_reports.php" class="btn btn-back">Back to Reports List</a>
    </div>
</main>

</body>
</html>