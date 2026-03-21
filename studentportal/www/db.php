<?php
// Database connection credentials
$host = "fdb1034.awardspace.net";  // or "localhost" if AwardSpace requires
$user = "4669763_john";            // your database username
$pass = "plmun2023";   // replace with your actual DB password
$dbname = "4669763_john";          // your database name

// Create a new connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
