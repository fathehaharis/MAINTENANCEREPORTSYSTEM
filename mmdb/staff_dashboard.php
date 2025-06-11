<?php
require 'conn.php';
session_start();

// --- 1. Security Check: Ensure user is logged in and is a Staff member ---
// Role ID 3 = Staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 3) {
    // Redirect to login page if not logged in or not a staff member
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$message = ''; // To store success/error messages

// --- 2. Handle New Report Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);

    if (empty($title) || empty($description) || empty($location)) {
        $message = '<div class="message error">All fields are required.</div>';
    } else {
        // Use a transaction to ensure both report and attachment are saved, or neither.
        $conn->begin_transaction();

        try {
            // Insert into user_report table
            $stmt = $conn->prepare("INSERT INTO user_report (title, description, location, submitted_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $title, $description, $location, $user_id);
            $stmt->execute();
            $report_id = $conn->insert_id; // Get the ID of the new report

            // Handle file upload and save to DB as longblob
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
                $file_tmp = $_FILES['attachment']['tmp_name'];
                $file_data = file_get_contents($file_tmp);

                // Insert file data as longblob into attachment table
                $stmt_attach = $conn->prepare("INSERT INTO attachment (report_id, media_data) VALUES (?, ?)");
                $null = NULL; // for longblob
                $stmt_attach->bind_param("ib", $report_id, $null);
                $stmt_attach->send_long_data(1, $file_data);
                $stmt_attach->execute();
            }

            // If everything is fine, commit the transaction
            $conn->commit();
            $message = '<div class="message success">Report submitted successfully!</div>';

        } catch (Exception $e) {
            // If anything goes wrong, roll back the transaction
            $conn->rollback();
            $message = '<div class="message error">Error: ' . $e->getMessage() . '</div>';
        }
    }
}

// --- 3. Fetch Reports Submitted by This Staff Member ---
$reports = [];
$stmt = $conn->prepare("SELECT report_id, title, status, date_reported FROM user_report WHERE submitted_by = ? ORDER BY date_reported DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard - MRS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f4f7f6; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        header {
            background-color: #2c3e50;
            color: #fff;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header h1 { font-size: 1.5rem; }
        header a { color: #fff; text-decoration: none; font-weight: bold; }
        header a:hover { text-decoration: underline; }
        main { margin-top: 20px; }
        .dashboard-section {
            background-color: #ffffff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }
        h2 { margin-bottom: 20px; color: #2c3e50; border-bottom: 2px solid #f1f1f1; padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .button {
            padding: 10px 20px;
            background-color: #4285F4;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .button:hover { background-color: #3367D6; }
        .reports-table { width: 100%; border-collapse: collapse; }
        .reports-table th, .reports-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .reports-table th { background-color: #f2f2f2; }
        .reports-table tr:hover { background-color: #f9f9f9; }
        .action-links a { margin-right: 10px; text-decoration: none; color: #4285F4; }
        .action-links a.disabled { color: #999; pointer-events: none; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

<header>
    <h1>Staff Dashboard</h1>
    <div>
        <span>Welcome, <?= htmlspecialchars($user_name) ?>!</span> |
        <a href="logout.php">Logout</a>
    </div>
</header>

<div class="container">
    <main>
        <?= $message ?>

        <!-- Section to Submit a New Report -->
        <section class="dashboard-section">
            <h2>Submit a New Report</h2>
            <form action="staff_dashboard.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" required>
                </div>
                <div class="form-group">
                    <label for="attachment">Upload Attachment (e.g., photo)</label>
                    <input type="file" id="attachment" name="attachment">
                </div>
                <button type="submit" name="submit_report" class="button">Submit Report</button>
            </form>
        </section>

        <!-- Section to View Own Reports -->
        <section class="dashboard-section">
            <h2>My Submitted Reports</h2>
            <?php if (empty($reports)): ?>
                <p>You have not submitted any reports yet.</p>
            <?php else: ?>
                <table class="reports-table">
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Date Reported</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?= htmlspecialchars($report['report_id']) ?></td>
                                <td><?= htmlspecialchars($report['title']) ?></td>
                                <td><?= htmlspecialchars($report['status']) ?></td>
                                <td><?= htmlspecialchars(date('F j, Y, g:i a', strtotime($report['date_reported']))) ?></td>
                                <td class="action-links">
                                    <a href="view_report.php?id=<?= $report['report_id'] ?>">View Details</a>
                                    <?php if ($report['status'] === 'Submitted'): ?>
                                        <a href="edit_report.php?id=<?= $report['report_id'] ?>">Edit</a>
                                    <?php else: ?>
                                        <a href="#" class="disabled">Edit</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</div>

</body>
</html>