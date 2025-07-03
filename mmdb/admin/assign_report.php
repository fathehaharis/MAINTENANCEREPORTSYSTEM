<?php
session_start();
require '../conn.php';

// Only allow admin
if (!isset($_SESSION['user_id']) || (int)$_SESSION['role'] !== 1) {
    header("Location: ../login.php");
    exit;
}

// Get list of unassigned reports
$reportSql = "SELECT report_id, title, description, location, status, date_reported FROM user_report WHERE assigned_to IS NULL OR assigned_to = 0 ORDER BY date_reported DESC";
$reportResult = $conn->query($reportSql);

// Get list of technicians by specialization
$techSql = "SELECT user_id, name, specialization FROM sys_user WHERE role_id=2 AND is_active=1 ORDER BY specialization, name";
$techResult = $conn->query($techSql);

$techniciansBySpec = [];
while ($row = $techResult->fetch_assoc()) {
    $techniciansBySpec[$row['specialization']][] = $row;
}

// Assign report handler
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id'], $_POST['technician_id'])) {
    $report_id = intval($_POST['report_id']);
    $technician_id = intval($_POST['technician_id']);

    // Assign technician and update status
    $update = $conn->prepare("UPDATE user_report SET assigned_to=?, status='Assigned' WHERE report_id=?");
    $update->bind_param("ii", $technician_id, $report_id);
    if ($update->execute()) {
        $message = "Report assigned successfully!";
    } else {
        $message = "Error assigning report!";
    }
    $update->close();
    header("Location: assign_report.php?msg=" . urlencode($message));
    exit;
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

// Helper function to get attachments for a report
function get_attachments($conn, $report_id) {
    $stmt = $conn->prepare("SELECT media_id, file_name, file_type FROM attachment WHERE report_id=?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $attachments = [];
    while ($row = $result->fetch_assoc()) {
        $attachments[] = $row;
    }
    $stmt->close();
    return $attachments;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Report - MRS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f7fafc; min-height: 100vh;}
        header.admin-header { width: 100%; background: #5481a7; color: white; padding: 1.3rem 0; font-size: 2rem; font-weight: 700; text-align: center; letter-spacing: 1px; position: fixed; top: 0; left: 0; z-index: 1000;}
        .sidebar { position: fixed; top: 0; left: 0; width: 220px; height: 100vh; background: #253444; color: #fff; display: flex; flex-direction: column; z-index: 1100;}
        .sidebar-header { padding: 2rem 1rem 1rem 2rem; font-size: 1.3rem; font-weight: bold; letter-spacing: 1px; background: #1d2937;}
        .sidebar nav { flex: 1; display: flex; flex-direction: column; gap: 8px; padding: 1.5rem 0.5rem 1.5rem 2rem;}
        .sidebar-section-title { font-size: 1rem; margin-top: 1.5rem; margin-bottom: 0.7rem; font-weight: bold; color: #b8e0fc; letter-spacing: 0.5px;}
        .sidebar nav a { color: #cdd9e5; text-decoration: none; font-size: 1.05rem; padding: 9px 16px; border-radius: 6px; transition: background 0.2s, color 0.2s; font-weight: 500; display: block;}
        .sidebar nav a.active, .sidebar nav a:hover { background: #4285F4; color: #fff;}
        .sidebar .logout-link { margin-top: auto; margin-bottom: 2rem; padding-left: 2rem;}
        .sidebar .logout-link a { color: #ffbdbd; font-weight: bold; text-decoration: none; padding: 10px 16px; border-radius: 6px; background: #a94442; display: inline-block;}
        .main-content { margin-left: 220px; padding-top: 70px; padding-bottom: 2rem; min-height: 100vh; background: #f7fafc;}
        .container { max-width: 1000px; margin: 0 auto; padding: 0 20px;}
        h2 { color: #253444; margin: 2rem 0 1rem 0; font-size: 1.7rem; font-weight: bold;}
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); overflow: hidden;}
        th, td { padding: 0.8rem 1rem; border-bottom: 1px solid #eaeaea; text-align: left; vertical-align: top;}
        th { background: #e8f0fe; color: #253444; font-size: 1.07rem; font-weight: bold;}
        tr:last-child td { border-bottom: none;}
        .msg { color: #205e10; margin-bottom: 1rem; font-weight: bold; text-align: center;}
        .assign-form { margin: 0;}
        select { padding: 6px 10px; border-radius: 5px; border: 1px solid #aaa;}
        button { background: #4285F4; color: #fff; padding: 6px 18px; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem;}
        button:hover { background: #306ac3;}
        .no-reports { color: #888; text-align: center; }
        .attachments-list { list-style: none; padding-left: 0; margin: 0; }
        .attachments-list li { margin-bottom: 3px; font-size: 0.96rem;}
        .attachment-link { color: #4285F4; text-decoration: underline; }
        .attachment-link:hover { color: #205e10; }
        .attachment-preview-img { max-width: 80px; max-height: 80px; border: 1px solid #ccc; margin-top: 3px; display: block; }
        @media (max-width: 1100px) { .container { padding: 0 8px; } }
        @media (max-width: 900px) { .main-content { margin-left: 0; padding-top: 70px; }
            .sidebar { position: static; width: 100%; min-height: auto; flex-direction: row; }
            .sidebar-header, .sidebar nav, .sidebar .logout-link { padding-left: 1rem; }
            .container { padding: 0 8px; }
            header.admin-header { font-size: 1.2rem; }
        }
        @media (max-width: 600px) { .container { padding: 0 2px; } }
    </style>
</head>
<body>
<header class="admin-header">
    Maintenance Report System - Admin Dashboard
</header>
<aside class="sidebar">
    <div class="sidebar-header">MRS Admin</div>
    <nav>
        <a href="admin_dashboard.php">Dashboard</a>
        <div class="sidebar-section-title">User Management</div>
        <a href="manage_staff.php">Staff</a>
        <a href="manage_technician.php">Technician</a>
        <div class="sidebar-section-title">Report Management</div>
        <a href="assign_report.php" class="active">Assign Report</a>
    </nav>
    <div class="logout-link">
        <a href="../logout.php">Logout</a>
    </div>
</aside>
<div class="main-content">
    <div class="container">
        <h2>Assign Maintenance Reports to Technicians</h2>
        <?php if ($message): ?>
            <div class="msg"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <table>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Location</th>
                <th>Attachments</th>
                <th>Status</th>
                <th>Date Reported</th>
                <th>Assign Technician</th>
            </tr>
            <?php if ($reportResult->num_rows > 0): ?>
                <?php while ($row = $reportResult->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                    <td><?= htmlspecialchars($row['location']) ?></td>
                <td>
                    <?php
                    $attachments = get_attachments($conn, $row['report_id']);
                    if (count($attachments) > 0): ?>
                        <ul class="attachments-list">
                            <?php foreach ($attachments as $att):
                                $label = $att['file_name'] ? htmlspecialchars($att['file_name']) : 'Attachment '.$att['media_id'];
                                $file_type = $att['file_type'] ?? '';
                                $is_image = false;
                                if ($file_type && strpos($file_type, 'image/') === 0) {
                                    $is_image = true;
                                } elseif (!$file_type && isset($att['file_name'])) {
                                    $ext = strtolower(pathinfo($att['file_name'], PATHINFO_EXTENSION));
                                    if (in_array($ext, ['jpg','jpeg','png','gif','bmp','webp'])) $is_image = true;
                                }
                            ?>
                                <li>
                                    <a class="attachment-link" href="../attachment.php?media_id=<?= $att['media_id'] ?>" target="_blank">
                                        <?= $label ?>
                                    </a>
                                    <?php if ($is_image): ?>
                                        <br>
                                        <img src="../attachment.php?media_id=<?= $att['media_id'] ?>"
                                            alt="<?= $label ?>"
                                            class="attachment-preview-img"
                                            style="max-width:80px;max-height:80px;border:1px solid #ccc;margin-top:3px;">
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <span style="color:#a00;">No attachment</span>
                    <?php endif; ?>
                </td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td><?= htmlspecialchars($row['date_reported']) ?></td>
                    <td>
                        <form method="post" class="assign-form">
                            <input type="hidden" name="report_id" value="<?= $row['report_id'] ?>">
                            <select name="technician_id" required>
                                <option value="">Select technician</option>
                                <?php
                                foreach ($techniciansBySpec as $spec => $techs) {
                                    echo "<optgroup label='" . htmlspecialchars($spec) . "'>";
                                    foreach ($techs as $tech) {
                                        echo "<option value='{$tech['user_id']}'>" . htmlspecialchars($tech['name']) . "</option>";
                                    }
                                    echo "</optgroup>";
                                }
                                ?>
                            </select>
                            <button type="submit">Assign</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" class="no-reports">No unassigned reports at the moment.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
</body>
</html>