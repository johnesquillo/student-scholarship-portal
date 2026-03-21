<?php
$host = "fdb1034.awardspace.net";             // usually localhost on AwardSpace
$user = "4669763_john";          // your DB username
$password = "plmun2023";  // your DB password
$database = "4669763_john";      // your DB name

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if an ID is passed in the URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    // Delete the message from the database
    $sql = "DELETE FROM contact_messages WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        // Redirect to inbox with success message
        header("Location: inbox.php?status=deleted");
        exit();
    } else {
        // Redirect to inbox with error message
        header("Location: inbox.php?status=error");
        exit();
    }
} else {
    // If no ID is provided
    header("Location: inbox.php?status=error");
    exit();
}

$conn->close();
?>
