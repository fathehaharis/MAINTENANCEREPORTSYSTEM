<?php
session_start();
require 'conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_id = $_POST['report_id'];
    $new_status = $_POST['new_status'];

    $stmt = $conn->prepare("UPDATE user_report SET status = ? WHERE report_id = ?");
    $stmt->bind_param("si", $new_status, $report_id);
    $stmt->execute();
}

header("Location: tech_dashboard.php");
exit();
