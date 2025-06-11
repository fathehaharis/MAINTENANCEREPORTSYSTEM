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
$message = '';
$can_edit = false;

// --- 1. Fetch report data to check for ownership and status ---
$stmt = $conn->prepare("SELECT title, description, location, status FROM user_report WHERE report_id = ? AND submitted_by = ?");
$stmt->bind_param("ii", $report_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();
$stmt->close();

if (!$report) {
    die("Error: Report not found or you do not have permission to edit it.");
}

// Check if the report is in "Submitted" status
if ($report['status'] === 'Submitted') {
    $can_edit = true;
} else {
    $message = '<div class="message error">This report cannot be edited because it is already under review.</div>';
}

// --- 1b. Fetch the current attachment (if any) ---
$stmt_attach = $conn->prepare("SELECT media_id FROM attachment WHERE report_id = ? ORDER BY uploaded_at DESC LIMIT 1");
$stmt_attach->bind_param("i", $report_id);
$stmt_attach->execute();
$result_attach = $stmt_attach->get_result();
$current_attachment = $result_attach->fetch_assoc();
$stmt_attach->close();

// --- 2. Handle Form Submission to Update the Report ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_edit) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);

    // File upload (optional)
    $new_file_uploaded = (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK);

    if (empty($title) || empty($description) || empty($location)) {
        $message = '<div class="message error">All fields are required.</div>';
    } else {
        // Start transaction for safety
        $conn->begin_transaction();
        try {
            // Update the report in the database
            $stmt_update = $conn->prepare("UPDATE user_report SET title = ?, description = ?, location = ? WHERE report_id = ? AND submitted_by = ?");
            $stmt_update->bind_param("sssii", $title, $description, $location, $report_id, $user_id);
            $stmt_update->execute();
            $stmt_update->close();

            // If new file uploaded, update or insert attachment
            if ($new_file_uploaded) {
                $file_data = file_get_contents($_FILES['attachment']['tmp_name']);

                if ($current_attachment) {
                    // Update the existing attachment
                    $stmt_img = $conn->prepare("UPDATE attachment SET media_data = ?, uploaded_at = NOW() WHERE media_id = ?");
                    $stmt_img->bind_param("bi", $null, $current_attachment['media_id']);
                    $null = NULL;
                    $stmt_img->send_long_data(0, $file_data);
                    $stmt_img->execute();
                    $stmt_img->close();
                } else {
                    // Insert a new attachment
                    $stmt_img = $conn->prepare("INSERT INTO attachment (report_id, media_data) VALUES (?, ?)");
                    $stmt_img->bind_param("ib", $report_id, $null);
                    $null = NULL;
                    $stmt_img->send_long_data(1, $file_data);
                    $stmt_img->execute();
                    $stmt_img->close();
                }
            }
            $conn->commit();
            // Redirect to the view page to see the changes
            header("Location: view_report.php?id=" . $report_id);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $message = '<div class="message error">An error occurred while updating the report: ' . $e->getMessage() . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Report #<?= htmlspecialchars($report_id) ?> - MRS</title>
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
        .back-link { display: inline-block; margin-bottom: 20px; color: #4285F4; text-decoration: none; font-weight: bold; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"], .form-group textarea {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;
        }
        .form-group textarea { resize: vertical; min-height: 120px; }
        .button {
            padding: 12px 25px; background-color: #4285F4; color: white; border: none;
            border-radius: 4px; cursor: pointer; font-size: 16px;
        }
        .button:hover { background-color: #3367D6; }
        .button:disabled { background-color: #aaa; cursor: not-allowed; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .current-image { margin-bottom: 10px; }
        .current-image img { max-width: 200px; border-radius: 5px; border: 1px solid #ddd; display: block; margin-bottom: 5px; }
    </style>
</head>
<body>

<header>
    <h1>Edit Report</h1>
    <a href="logout.php">Logout</a>
</header>

<div class="container">
    <a href="staff_dashboard.php" class="back-link">← Back to Dashboard</a>

    <section class="report-section">
        <h2>Edit Report #<?= htmlspecialchars($report_id) ?></h2>
        
        <?= $message ?>

        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($report['title']) ?>" <?= !$can_edit ? 'disabled' : '' ?> required>
            </div>
            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" value="<?= htmlspecialchars($report['location']) ?>" <?= !$can_edit ? 'disabled' : '' ?> required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" <?= !$can_edit ? 'disabled' : '' ?> required><?= htmlspecialchars($report['description']) ?></textarea>
            </div>
            <div class="form-group">
                <label>Current Attachment</label>
                <div class="current-image">
                    <?php if ($current_attachment): ?>
                        <img src="attachment.php?media_id=<?= $current_attachment['media_id'] ?>" alt="Current Attachment">
                    <?php else: ?>
                        <span>No attachment found.</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-group">
                <label for="attachment">Replace Attachment (leave blank to keep existing)</label>
                <input type="file" id="attachment" name="attachment" <?= !$can_edit ? 'disabled' : '' ?>>
            </div>
            <button type="submit" class="button" <?= !$can_edit ? 'disabled' : '' ?>>Save Changes</button>
        </form>
    </section>
</div>

</body>
</html>