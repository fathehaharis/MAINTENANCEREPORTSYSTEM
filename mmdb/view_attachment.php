<?php
require 'conn.php';

$id = $_GET['report_id'] ?? 0;

$stmt = $conn->prepare("SELECT media_url FROM attachment WHERE media_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($data);
$stmt->fetch();

header("Content-Type: image/png"); // Adjust MIME type if needed
echo $data;
