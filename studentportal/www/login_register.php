<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth_service.php';
require_post();
verify_csrf();
$isRegistration = isset($_POST['register']);
$accountType = input_value($_POST, 'account_type', 'student');
if ($isRegistration) {
    if (!in_array($accountType, ['student', 'admin'], true)) {
        $_SESSION['register_error'] = 'Please choose Student or Admin.';
        $_SESSION['active_form'] = 'register';
        redirect_to('index.php');
    }
    $_SESSION['register_account_type'] = $accountType;
}
$adminLogin = input_value($_POST, 'login_context') === 'admin';
$destination = $adminLogin ? 'admin_login.php' : 'index.php';
$name = input_value($_POST, 'name');
$email = strtolower(input_value($_POST, 'email'));
// Whitespace can be part of a password.
$password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
function login_error($message, $registration, $destination)
{
    $_SESSION[$registration ? 'register_error' : 'login_error'] = $message;
    $_SESSION['active_form'] = $registration ? 'register' : 'login';
    redirect_to($destination);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || $password === '' || strlen($password) > 72 || strpos($password, "\0") !== false) {
    login_error('Enter a valid email address and password (up to 72 bytes).', $isRegistration, $destination);
}
if ($isRegistration && ($name === '' || strlen($name) > 100 || strlen($password) < 8)) {
    login_error('Enter your name (up to 100 bytes) and a password of at least 8 characters.', true, 'index.php');
}
require __DIR__ . '/db.php';
if ($isRegistration) {
    try {
        register_portal_account($conn, $name, $email, $password);
    } catch (InvalidArgumentException $exception) {
        login_error($exception->getMessage(), true, 'index.php');
    }
    $_SESSION['login_notice'] = $accountType === 'admin'
        ? 'Account created. Contact the scholarship office to activate admin access before using Admin / Staff Login.'
        : 'Account created. You can now sign in.';
    unset($_SESSION['register_account_type']);
    $_SESSION['active_form'] = 'login';
    redirect_to($accountType === 'admin' ? 'admin_login.php' : 'index.php');
}
try {
    $account = authenticate_portal_account($conn, $email, $password, $adminLogin);
} catch (InvalidArgumentException $exception) {
    login_error($exception->getMessage(), false, $destination);
}
establish_portal_session($account);
redirect_to(in_array($account['role'], ['admin', 'staff'], true) ? 'manage_applications.php' : 'home.php');
