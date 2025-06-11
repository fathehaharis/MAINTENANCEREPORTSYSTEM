<?php
require 'conn.php';
session_start();

// Security Check: Ensure user is logged in and is a Staff member
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 3) {
    header("Location: login.php");
    exit;
}

// Get the report ID from the URL and validate it
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Error: Invalid report ID.");
}

$report_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// --- 1. Fetch the main report details ---
$stmt = $conn->prepare("SELECT title, description, location, status, date_reported 
                        FROM user_report 
                        WHERE report_id = ? AND submitted_by = ?");
$stmt->bind_param("ii", $report_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();

if (!$report) {
    die("Error: Report not found or you do not have permission to view it.");
}

// --- 2. Fetch associated attachments (media_data as LONGBLOB) ---
$stmt_attach = $conn->prepare("SELECT media_id FROM attachment WHERE report_id = ?");
$stmt_attach->bind_param("i", $report_id);
$stmt_attach->execute();
$attachments = $stmt_attach->get_result()->fetch_all(MYSQLI_ASSOC);

// --- 3. Fetch report history ---
$stmt_hist = $conn->prepare("SELECT h.status, h.notes, h.changed_at, u.name as changed_by_name
                             FROM report_hist h
                             JOIN sys_user u ON h.changed_by = u.user_id
                             WHERE h.report_id = ?
                             ORDER BY h.changed_at ASC");
$stmt_hist->bind_param("i", $report_id);
$stmt_hist->execute();
$history = $stmt_hist->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$stmt_attach->close();
$stmt_hist->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Report #<?= htmlspecialchars($report_id) ?> - MRS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f4f7f6; color: #333; }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
        header {
            background-color: #2c3e50; color: #fff; padding: 1rem 2rem;
            display: flex; justify-content: space-between; align-items: center;
        }
        header h1 { font-size: 1.5rem; }
        header a { color: #fff; text-decoration: none; font-weight: bold; }
        .report-section {
            background-color: #ffffff; padding: 25px; border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); margin-bottom: 25px;
        }
        h2 { margin-bottom: 20px; color: #2c3e50; border-bottom: 2px solid #f1f1f1; padding-bottom: 10px; }
        .detail-item { margin-bottom: 15px; }
        .detail-item strong { display: block; color: #555; margin-bottom: 5px; }
        .detail-item span, .detail-item p { font-size: 1rem; line-height: 1.6; }
        .attachments-list img { max-width: 200px; border-radius: 5px; margin-right: 10px; border: 1px solid #ddd; }
        .history-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .history-table th, .history-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .history-table th { background-color: #f2f2f2; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #4285F4; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<header>
    <h1>View Report Details</h1>
    <a href="logout.php">Logout</a>
</header>

<div class="container">
    <a href="staff_dashboard.php" class="back-link">← Back to Dashboard</a>

    <!-- Main Report Details -->
    <section class="report-section">
        <h2>Report #<?= htmlspecialchars($report_id) ?>: <?= htmlspecialchars($report['title']) ?></h2>
        <div class="detail-item"><strong>Status:</strong> <span><?= htmlspecialchars($report['status']) ?></span></div>
        <div class="detail-item"><strong>Date Reported:</strong> <span><?= date('F j, Y, g:i a', strtotime($report['date_reported'])) ?></span></div>
        <div class="detail-item"><strong>Location:</strong> <span><?= htmlspecialchars($report['location']) ?></span></div>
        <div class="detail-item"><strong>Description:</strong> <p><?= nl2br(htmlspecialchars($report['description'])) ?></p></div>
    </section>

    <!-- Attachments Section -->
    <section class="report-section">
        <h2>Attachments</h2>
        <div class="attachments-list">
            <?php if (empty($attachments)): ?>
                <p>No attachments found.</p>
            <?php else: ?>
                <?php foreach ($attachments as $attachment): ?>
                    <a href="attachment.php?media_id=<?= $attachment['media_id'] ?>" target="_blank">
                        <img src="attachment.php?media_id=<?= $attachment['media_id'] ?>" alt="Attachment">
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- History Section -->
    <section class="report-section">
        <h2>Report History & Updates</h2>
        <?php if (empty($history)): ?>
            <p>No status updates have been recorded yet.</p>
        <?php else: ?>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date Changed</th>
                        <th>New Status</th>
                        <th>Changed By</th>
                        <th>Notes/Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $hist_item): ?>
                        <tr>
                            <td><?= date('F j, Y, g:i a', strtotime($hist_item['changed_at'])) ?></td>
                            <td><?= htmlspecialchars($hist_item['status']) ?></td>
                            <td><?= htmlspecialchars($hist_item['changed_by_name']) ?></td>
                            <td><?= htmlspecialchars($hist_item['notes']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>

</body>
</html>