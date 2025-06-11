<?php
require 'conn.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: login.php");
    exit;
}

// Pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Count total reports
$count_sql = "SELECT COUNT(*) FROM user_report";
if ($status_filter) {
    $count_sql .= " WHERE status = ?";
}

if ($status_filter) {
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param("s", $status_filter);
    $stmt->execute();
    $stmt->bind_result($total_reports);
    $stmt->fetch();
    $stmt->close();
} else {
    $total_reports = $conn->query($count_sql)->fetch_row()[0];
}

$total_pages = ceil($total_reports / $perPage);

// Fetch reports with attachments (LEFT JOIN) -- media_url is not in your DDL, so use media_id and show via attachment.php
$sql = "SELECT r.report_id, r.title, r.status, r.date_reported, u.name as reporter, a.media_id
        FROM user_report r
        JOIN sys_user u ON r.submitted_by = u.user_id
        LEFT JOIN attachment a ON r.report_id = a.report_id";

if ($status_filter) {
    $sql .= " WHERE r.status = ?";
}
$sql .= " ORDER BY r.date_reported DESC, a.media_id ASC LIMIT ?, ?";

if ($status_filter) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $status_filter, $offset, $perPage);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $offset, $perPage);
}
$stmt->execute();
$result = $stmt->get_result();

// Collect reports and their attachments
$reports = [];
while ($row = $result->fetch_assoc()) {
    $rid = $row['report_id'];
    if (!isset($reports[$rid])) {
        $reports[$rid] = [
            'report_id' => $row['report_id'],
            'title' => $row['title'],
            'status' => $row['status'],
            'date_reported' => $row['date_reported'],
            'reporter' => $row['reporter'],
            'attachments' => []
        ];
    }
    if ($row['media_id']) {
        $reports[$rid]['attachments'][] = [
            'media_id' => $row['media_id']
        ];
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>View Reports - Admin</title>
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
        form {
            margin-bottom: 20px;
        }
        select {
            padding: 6px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f4f6f8;
        }
        a.btn {
            display: inline-block;
            background-color: #4285F4;
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            margin-right: 6px;
            transition: background-color 0.3s ease;
        }
        a.btn:hover {
            background-color: #3367D6;
        }
        .pagination {
            margin-top: 15px;
        }
        .pagination a {
            background-color: #4285F4;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            margin-right: 5px;
            text-decoration: none;
            font-weight: 600;
        }
        .pagination a:hover {
            background-color: #3367D6;
        }
        .success {
            color: green;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .attachments {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .attachment-thumb {
            display: inline-block;
            border: 1px solid #eee;
            padding: 2px;
            border-radius: 4px;
            background: #fff;
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
            font-size: 0.9em;
            word-break: break-all;
            color: #4285F4;
        }
    </style>
</head>
<body>

<header>
    Admin Dashboard - View Reports
</header>

<nav>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="logout.php">Logout</a>
</nav>

<main>
    <h1>All Reports</h1>

    <!-- Success message for deletion -->
    <?php if (isset($_GET['deleted'])): ?>
        <p class="success">Report deleted successfully!</p>
    <?php endif; ?>

    <form method="get" action="">
        <label>Filter by Status:
            <select name="status" onchange="this.form.submit()">
                <option value="">--All--</option>
                <?php
                $statuses = ['Submitted', 'In Progress', 'Resolved', 'Rejected'];
                foreach ($statuses as $status) {
                    $selected = ($status_filter == $status) ? 'selected' : '';
                    echo "<option value=\"$status\" $selected>$status</option>";
                }
                ?>
            </select>
        </label>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Status</th>
                <th>Date Reported</th>
                <th>Submitted By</th>
                <th>Media</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reports)): ?>
                <tr><td colspan="7" style="text-align:center;">No reports found.</td></tr>
            <?php else: ?>
                <?php foreach ($reports as $report): ?>
                <tr>
                    <td><?= $report['report_id'] ?></td>
                    <td><?= htmlspecialchars($report['title']) ?></td>
                    <td><?= htmlspecialchars($report['status']) ?></td>
                    <td><?= $report['date_reported'] ?></td>
                    <td><?= htmlspecialchars($report['reporter']) ?></td>
                    <td>
                        <div class="attachments">
                            <?php if (!empty($report['attachments'])): ?>
                                <?php foreach ($report['attachments'] as $att): ?>
                                    <a class="attachment-thumb" href="attachment.php?media_id=<?= $att['media_id'] ?>" target="_blank" title="Open attachment">
                                        <img src="attachment.php?media_id=<?= $att['media_id'] ?>" alt="Attachment" />
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="color:#888;">No media</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <a class="btn" href="report_details.php?id=<?= $report['report_id'] ?>">Update / Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination">
        <p>Page <?= $page ?> of <?= $total_pages ?></p>
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page-1 ?>&status=<?= urlencode($status_filter) ?>">Prev</a>
        <?php endif; ?>
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page+1 ?>&status=<?= urlencode($status_filter) ?>">Next</a>
        <?php endif; ?>
    </div>

    <p><a class="btn" href="admin_dashboard.php">Back to Dashboard</a></p>
</main>

</body>
</html>