<?php
require 'conn.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 2) {
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

// Fetch reports
$sql = "SELECT r.report_id, r.title, r.status, r.date_reported, u.name as reporter
        FROM user_report r
        JOIN sys_user u ON r.submitted_by = u.user_id";

if ($status_filter) {
    $sql .= " WHERE r.status = ?";
}
$sql .= " ORDER BY r.date_reported DESC LIMIT ?, ?";

if ($status_filter) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $status_filter, $offset, $perPage);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $offset, $perPage);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>View Reports - Moderator</title>
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
    </style>
</head>
<body>

<header>
    Moderator Dashboard - View Reports
</header>

<nav>
    <a href="moderator_dashboard.php">Dashboard</a>
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
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['report_id'] ?></td>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= $row['date_reported'] ?></td>
                <td><?= htmlspecialchars($row['reporter']) ?></td>
                <td>
                    <a class="btn" href="mod_report_detail.php?id=<?= $row['report_id'] ?>">Update / Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
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

    <p><a class="btn" href="moderator_dashboard.php">Back to Dashboard</a></p>
</main>

</body>
</html>

<?php
$stmt->close();
?>
