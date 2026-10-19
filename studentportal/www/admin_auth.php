<?php
require_once __DIR__ . '/bootstrap.php';
// The login handler must set this role from the authenticated account record.
function require_admin_access($json = false)
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['email'])) {
        if ($json) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please sign in.']);
        } else {
            header('Location: admin_login.php');
        }
        exit;
    }

    if (!in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
        http_response_code(403);
        if ($json) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Admin or staff access is required.']);
        } else {
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Admin or staff access is required.';
        }
        exit;
    }
}
