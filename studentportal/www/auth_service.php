<?php
function register_portal_account($conn, $name, $email, $password)
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || strlen($password) > 72 || strpos($password, "\0") !== false) {
        throw new InvalidArgumentException('Enter a valid email address and password (up to 72 bytes).');
    }
    if ($name === '' || strlen($name) > 100 || strlen($password) < 8) {
        throw new InvalidArgumentException('Enter your name (up to 100 bytes) and a password of at least 8 characters.');
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
    $stmt->bind_param('sss', $name, $email, $hash);
    try {
        $stmt->execute();
    } catch (mysqli_sql_exception $exception) {
        if ((int)$exception->getCode() === 1062) {
            throw new InvalidArgumentException('This email is already registered. Please sign in.');
        }
        throw $exception;
    } finally {
        $stmt->close();
    }
}

function authenticate_portal_account($conn, $email, $password, $adminOnly = false)
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || $password === '' || strlen($password) > 72 || strpos($password, "\0") !== false) {
        throw new InvalidArgumentException('Enter a valid email address and password (up to 72 bytes).');
    }
    $stmt = $conn->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $hash = $account['password'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
    $valid = password_verify($password, $hash);
    if (!$account || !$valid || !in_array($account['role'], ['user', 'student', 'admin', 'staff'], true)) {
        throw new InvalidArgumentException('Email or password is incorrect.');
    }
    if ($adminOnly && !in_array($account['role'], ['admin', 'staff'], true)) {
        throw new InvalidArgumentException('An admin or staff account is required.');
    }
    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->bind_param('si', $newHash, $account['id']);
        $stmt->execute();
        $stmt->close();
    }
    unset($account['password']);
    return $account;
}

function establish_portal_session($account)
{
    session_regenerate_id(true);
    $_SESSION = ['user_id' => (int)$account['id'], 'name' => $account['name'], 'email' => $account['email'], 'role' => $account['role']];
}
