<?php
require_once __DIR__ . '/admin_auth.php';
require_admin_access();
require_post();
verify_csrf();
$id = filter_var(input_value($_POST, 'id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    http_response_code(400);
    exit('Invalid message ID.');
}
require __DIR__ . '/db.php';
$stmt = $conn->prepare('DELETE FROM contact_message WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$deleted = $stmt->affected_rows;
$stmt->close();
$conn->close();
redirect_to('inbox.php?status=' . ($deleted ? 'deleted' : 'missing'));
