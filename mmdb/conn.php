<?php
$host = "localhost";    // Change to your MySQL server host
$user = "root";     // Change to your MySQL username
$pass = "";     // Change to your MySQL password
$dbname = "complain";   // Change to your MySQL database name

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: set charset
$conn->set_charset("utf8");

?>
