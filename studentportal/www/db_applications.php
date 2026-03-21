<?php
$host = "fdb1034.awardspace.net";            
$user = "4669763_john";          
$password = "plmun2023";  
$database = "4669763_john";      

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
