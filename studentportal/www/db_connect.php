<?php
$host = "fdb1034.awardspace.net";             // usually localhost on AwardSpace
$user = "4669763_john";          // your DB username
$password = "plmun2023";  // your DB password
$database = "4669763_john";      // your DB name

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
