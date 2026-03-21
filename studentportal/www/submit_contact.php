<?php
// submit.php
// DB connection (AwardSpace)
$host = "fdb1034.awardspace.net";
$user = "4669763_john";
$password = "plmun2023";
$database = "4669763_john";

// Create connection (use correct variable names)
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Only accept POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: contact.php");
    exit();
}

// Validate required fields
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $message === '') {
    header("Location: contact.php?error=missing");
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: contact.php?error=invalid_email");
    exit();
}

// Insert into the correct table: contact_message (singular)
$sql  = "INSERT INTO contact_message (name, email, message) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    // Fallback if prepare fails
    $conn->close();
    header("Location: contact.php?error=server");
    exit();
}
$stmt->bind_param("sss", $name, $email, $message);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: contact.php?success=1");
    exit();
} else {
    $stmt->close();
    $conn->close();
    header("Location: contact.php?error=1");
    exit();
}
