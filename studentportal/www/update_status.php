<?php
header('Content-Type: application/json');

// DB connection
$host = "fdb1034.awardspace.net";
$user = "4669763_john";
$password = "plmun2023";
$database = "4669763_john";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit();
}

// Collect input
$status = $_POST['status'] ?? '';
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$scholarship_id = isset($_POST['scholarship_id']) ? (int)$_POST['scholarship_id'] : 0;

// Validate
$allowed = ['Pending', 'Approved', 'Rejected'];
if (!in_array($status, $allowed, true) || $user_id <= 0 || $scholarship_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

// Update
$stmt = $conn->prepare("UPDATE applications SET status = ? WHERE user_id = ? AND scholarship_id = ?");
$stmt->bind_param("sii", $status, $user_id, $scholarship_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}

$stmt->close();
$conn->close();
?>
