<?php
require 'conn.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 2) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
        die("Error uploading file.");
    }

    $report_id = intval($_POST['report_id']);
    $fileTmpPath = $_FILES['attachment']['tmp_name'];
    $fileName = $_FILES['attachment']['name'];
    $fileType = mime_content_type($fileTmpPath); // detects correct MIME type
    $fileData = file_get_contents($fileTmpPath);

    $stmt = $conn->prepare("INSERT INTO attachment (report_id, file_name, file_type, media_data) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $report_id, $fileName, $fileType, $fileData);
    
    if ($stmt->execute()) {
        header("Location: tech_ass.php?upload=success");
        exit;
    } else {
        echo "Failed to store file.";
    }
} else {
    echo "Invalid request.";
}
?>
