<?php
ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set(getenv('PORTAL_TIMEZONE') ?: 'Asia/Manila');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'use_strict_mode' => true,
        'cookie_httponly' => true,
        'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'cookie_samesite' => 'Lax',
    ]);
}
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
set_exception_handler(function ($exception) {
    error_log((string)$exception);
    http_response_code(500);
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
    } else {
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Something went wrong. Please try again or contact the scholarship office.';
    }
});

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function input_value($source, $key, $default = '')
{
    return isset($source[$key]) && is_string($source[$key]) ? trim($source[$key]) : $default;
}
function redirect_to($location)
{
    header('Location: ' . $location, true, 303);
    exit;
}
function json_response($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
function require_login()
{
    if (empty($_SESSION['user_id']) || empty($_SESSION['email'])) {
        redirect_to('index.php');
    }
}
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}
function require_post($json = false)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST');
        if ($json) {
            json_response(['success' => false, 'message' => 'Use a POST request.'], 405);
        }
        http_response_code(405);
        exit('Use a POST request.');
    }
}
function verify_csrf($json = false)
{
    $token = input_value($_POST, 'csrf_token');
    if ($token === '' || !hash_equals(csrf_token(), $token)) {
        if ($json) {
            json_response(['success' => false, 'message' => 'Your form expired. Refresh the page and try again.'], 403);
        }
        http_response_code(403);
        exit('Your form expired. Refresh the page and try again.');
    }
}
function logout_form($action = 'logout.php')
{
    return '<form class="logout-form" method="post" action="' . e($action) . '">' . csrf_field() . '<button type="submit">Logout</button></form>';
}
