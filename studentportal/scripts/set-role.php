<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$email = strtolower(trim($argv[1] ?? ''));
$role = $argv[2] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['user', 'admin', 'staff'], true)) {
    fwrite(STDERR, "Usage: php scripts/set-role.php EMAIL user|admin|staff\n");
    exit(1);
}
require dirname(__DIR__) . '/www/db.php';
$stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$account) {
    fwrite(STDERR, "Account not found. Register it through the portal first.\n");
    exit(1);
}
$stmt = $conn->prepare('UPDATE users SET role = ? WHERE id = ?');
$stmt->bind_param('si', $role, $account['id']);
$stmt->execute();
echo "Role updated. Sign out and sign in again to use the new role.\n";
