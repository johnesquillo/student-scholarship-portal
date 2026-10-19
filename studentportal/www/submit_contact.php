<?php
require_once __DIR__ . '/bootstrap.php';
require_login();
require_post();
verify_csrf();
$name = input_value($_POST, 'name');
$email = input_value($_POST, 'email');
$message = input_value($_POST, 'message');
if ($name === '' || strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || $message === '' || strlen($message) > 5000) {
    redirect_to('contact.php?error=invalid');
}
require __DIR__ . '/db.php';
$stmt = $conn->prepare('INSERT INTO contact_message (name, email, message) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $name, $email, $message);
$stmt->execute();
$stmt->close();
$conn->close();
redirect_to('contact.php?success=1');
