<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "scholarship_system"; // 🔁 Replace with your real DB name

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
