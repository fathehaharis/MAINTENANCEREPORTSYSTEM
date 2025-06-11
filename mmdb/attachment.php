<?php
require 'conn.php';

if (!isset($_GET['media_id']) || !is_numeric($_GET['media_id'])) {
    http_response_code(400);
    die('Invalid attachment ID.');
}

$media_id = (int)$_GET['media_id'];

// Fetch the media_data for the given media_id
$stmt = $conn->prepare("SELECT media_data FROM attachment WHERE media_id = ?");
$stmt->bind_param("i", $media_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    http_response_code(404);
    die('Attachment not found.');
}

$stmt->bind_result($media_data);
$stmt->fetch();

// Optional: try to detect content type. You could store the MIME type in the DB and use it here.
// For now, we'll default to image/jpeg. Adjust as needed.
header('Content-Type: image/jpeg');
header('Content-Disposition: inline; filename="attachment.jpg"');
echo $media_data;

$stmt->close();
?>