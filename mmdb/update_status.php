<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_id = $_POST['report_id'];
    $new_status = $_POST['new_status'];

    $stmt = $conn->prepare("UPDATE report SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $report_id);
    $stmt->execute();
}

header("Location: dashboard.php");
exit();
