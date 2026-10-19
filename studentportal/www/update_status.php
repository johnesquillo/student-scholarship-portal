<?php
require_once __DIR__ . '/admin_auth.php';
require_admin_access(true);
require_post(true);
verify_csrf(true);
$status = input_value($_POST, 'status');
$userId = filter_var(input_value($_POST, 'user_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$scholarshipId = filter_var(input_value($_POST, 'scholarship_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!in_array($status, ['Pending', 'Approved', 'Rejected'], true) || !$userId || !$scholarshipId) {
    json_response(['success' => false, 'message' => 'Invalid application or status.'], 400);
}
require __DIR__ . '/db.php';
$stmt = $conn->prepare('SELECT status FROM applications WHERE user_id = ? AND scholarship_id = ?');
$stmt->bind_param('ii', $userId, $scholarshipId);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$application) {
    json_response(['success' => false, 'message' => 'Application not found.'], 404);
}
$stmt = $conn->prepare('UPDATE applications SET status = ? WHERE user_id = ? AND scholarship_id = ?');
$stmt->bind_param('sii', $status, $userId, $scholarshipId);
$stmt->execute();
$stmt->close();
$conn->close();
json_response(['success' => true]);
