<?php
// One connection configuration for every page and compatibility wrapper.
$settings = [
    'host' => 'localhost',
    'port' => 3306,
    'user' => 'root',
    'password' => '',
    'database' => 'scholarship_system',
];
$localConfig = dirname(__DIR__) . '/config/local.php';
if (is_file($localConfig)) {
    $settings = array_replace($settings, require $localConfig);
}
foreach (['host' => 'DB_HOST', 'user' => 'DB_USER', 'password' => 'DB_PASSWORD', 'database' => 'DB_NAME', 'port' => 'DB_PORT'] as $key => $variable) {
    if (getenv($variable) !== false) {
        $settings[$key] = $key === 'port' ? (int)getenv($variable) : getenv($variable);
    }
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = mysqli_init();
    // Apply timeouts before connecting, including a server that never sends its handshake.
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    $conn->options(MYSQLI_OPT_READ_TIMEOUT, 5);
    $conn->real_connect($settings['host'], $settings['user'], $settings['password'], $settings['database'], $settings['port']);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    error_log('Portal database connection failed: ' . $exception->getMessage());
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Database unavailable. Check config/local.php or the DB_* environment variables.\n");
        exit(1);
    }
    http_response_code(503);
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'The database is temporarily unavailable.']);
    } else {
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'The database is temporarily unavailable. Please try again later.';
    }
    exit;
}
