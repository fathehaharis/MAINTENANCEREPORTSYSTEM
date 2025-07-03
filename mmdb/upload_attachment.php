<?php
require 'conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['attachment'])) {
    $report_id = $_POST['report_id'];
    $media = file_get_contents($_FILES['attachment']['tmp_name']);

    $stmt = $conn->prepare("INSERT INTO attachment (report_id, media_data) VALUES (?, ?)");
    $stmt->bind_param("ib", $report_id, $media);
    $stmt->send_long_data(1, $media);
    $stmt->execute();
}

header("Location: staffmaintenancedashboard.php");
exit();
