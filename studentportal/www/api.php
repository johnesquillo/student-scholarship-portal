<?php
// JSON interface shared by the Expo browser and native clients.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = array_filter(array_map('trim', explode(',', getenv('PORTAL_ALLOWED_ORIGINS') ?: 'http://127.0.0.1:8081,http://localhost:8081,http://127.0.0.1:19006,http://localhost:19006')));
$sameOrigin = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '');
if ($origin !== '' && $origin !== $sameOrigin && !in_array($origin, $allowedOrigins, true)) {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['success' => false, 'message' => 'This website is not allowed to access the portal.']));
}
if ($origin !== '') {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, X-Portal-Native, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
$_SERVER['HTTP_ACCEPT'] = 'application/json';
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth_service.php';
require_once __DIR__ . '/application_service.php';
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === 0) {
    try {
        $body = json_decode(file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($body)) {
            throw new JsonException();
        }
        $_POST = $body;
    } catch (JsonException $exception) {
        json_response(['success' => false, 'message' => 'Invalid request data.'], 400);
    }
}
$action = input_value($_GET, 'action', 'session');
$native = $origin === '' && ($_SERVER['HTTP_X_PORTAL_NATIVE'] ?? '') === '1';
$token = '';
$authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/^Bearer ([a-f0-9]{64})$/', $authorization, $matches)) {
    $token = $matches[1];
} elseif ($authorization !== '') {
    json_response(['success' => false, 'message' => 'Please sign in again.'], 401);
}
function api_token_path($token)
{
    return dirname(__DIR__) . '/storage/api-sessions/' . hash('sha256', $token) . '.json';
}
function api_account($conn, $id)
{
    $stmt = $conn->prepare('SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($account) {
        $account['id'] = (int)$account['id'];
    }
    return $account;
}
$user = null;
if ($token !== '') {
    $path = api_token_path($token);
    $record = is_file($path) ? json_decode(file_get_contents($path), true) : null;
    if (!$record || ($record['expires'] ?? 0) < time()) {
        if (is_file($path)) {
            unlink($path);
        }
        json_response(['success' => false, 'message' => 'Your session expired. Please sign in again.'], 401);
    }
    $user = api_account($conn, (int)$record['user_id']);
    if (!$user) {
        json_response(['success' => false, 'message' => 'Please sign in again.'], 401);
    }
} elseif (!$native && !empty($_SESSION['user_id'])) {
    $user = api_account($conn, (int)$_SESSION['user_id']);
}
if ($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
} else {
    unset($_SESSION['user_id'], $_SESSION['name'], $_SESSION['email'], $_SESSION['role']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token === '' && !$native) {
    $_POST['csrf_token'] = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    verify_csrf(true);
}
$readActions = ['session', 'scholarships', 'applications', 'messages', 'requirements', 'document'];
if (in_array($action, $readActions, true)) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header('Allow: GET');
        json_response(['success' => false, 'message' => 'Use a GET request.'], 405);
    }
} else {
    require_post(true);
}
function api_success($data = [])
{
    json_response(array_merge(['success' => true], $data));
}
function api_id($source, $key)
{
    $value = $source[$key] ?? null;
    $id = (is_int($value) || is_string($value)) ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
    if (!$id) {
        json_response(['success' => false, 'message' => 'Invalid ' . str_replace('_', ' ', $key) . '.'], 400);
    }
    return $id;
}
function api_admin($user)
{
    if (!in_array($user['role'], ['admin', 'staff'], true)) {
        json_response(['success' => false, 'message' => 'Admin or staff access is required.'], 403);
    }
}
try {
    if ($action === 'session') {
        api_success(['user' => $user, 'csrf_token' => csrf_token()]);
    }
    if ($action === 'register') {
        $type = input_value($_POST, 'account_type', 'student');
        if (!in_array($type, ['student', 'admin'], true)) {
            throw new InvalidArgumentException('Please choose Student or Admin.');
        }
        register_portal_account($conn, input_value($_POST, 'name'), strtolower(input_value($_POST, 'email')), isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '');
        api_success(['login_type' => $type, 'message' => $type === 'admin' ? 'Account created. Contact the scholarship office to activate admin access.' : 'Account created. You can now sign in.']);
    }
    if ($action === 'login') {
        $user = authenticate_portal_account($conn, strtolower(input_value($_POST, 'email')), isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '', input_value($_POST, 'account_type') === 'admin');
        establish_portal_session($user);
        $result = ['user' => $user, 'csrf_token' => csrf_token()];
        if ($native) {
            $newToken = bin2hex(random_bytes(32));
            $directory = dirname(api_token_path($newToken));
            if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
                throw new RuntimeException('Session storage is unavailable.');
            }
            if (file_put_contents(api_token_path($newToken), json_encode(['user_id' => (int)$user['id'], 'expires' => time() + 604800]), LOCK_EX) === false) {
                throw new RuntimeException('Session storage is unavailable.');
            }
            chmod(api_token_path($newToken), 0600);
            $result['token'] = $newToken;
        }
        api_success($result);
    }
    if (!$user) {
        json_response(['success' => false, 'message' => 'Please sign in.'], 401);
    }
    if ($action === 'logout') {
        if ($token !== '' && is_file(api_token_path($token))) {
            unlink(api_token_path($token));
        }
        $_SESSION = [];
        session_regenerate_id(true);
        api_success(['csrf_token' => csrf_token()]);
    }
    if ($action === 'scholarships') {
        api_success(['scholarships' => $conn->query("SELECT scholarship_id, name, description, deadline FROM scholarships WHERE LOWER(status) = 'active' AND (deadline IS NULL OR deadline >= CURDATE()) ORDER BY deadline IS NULL, deadline, name")->fetch_all(MYSQLI_ASSOC)]);
    }
    if ($action === 'applications') {
        if (in_array($user['role'], ['admin', 'staff'], true)) {
            $result = $conn->query('SELECT a.*, s.name AS scholarship_name FROM applications a LEFT JOIN scholarships s ON s.scholarship_id = a.scholarship_id ORDER BY a.application_date DESC');
        } else {
            $stmt = $conn->prepare('SELECT a.*, s.name AS scholarship_name FROM applications a LEFT JOIN scholarships s ON s.scholarship_id = a.scholarship_id WHERE a.user_id = ? ORDER BY a.application_date DESC');
            $stmt->bind_param('i', $user['id']);
            $stmt->execute();
            $result = $stmt->get_result();
        }
        api_success(['applications' => $result->fetch_all(MYSQLI_ASSOC)]);
    }
    if ($action === 'apply') {
        $sid = api_id($_POST, 'scholarship_id');
        $values = [];
        foreach (['name', 'email', 'gwa', 'course', 'year'] as $key) {
            $values[$key] = input_value($_POST, $key);
        }
        submit_portal_application($conn, $user['id'], $sid, $values, $_FILES['documents'] ?? null);
        api_success(['message' => 'Application submitted successfully.']);
    }
    if ($action === 'contact') {
        $name = input_value($_POST, 'name');
        $email = input_value($_POST, 'email');
        $message = input_value($_POST, 'message');
        if ($name === '' || strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || $message === '' || strlen($message) > 5000) {
            throw new InvalidArgumentException('Enter a valid name, email, and message of up to 5,000 characters.');
        }
        $stmt = $conn->prepare('INSERT INTO contact_message (name, email, message) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $name, $email, $message);
        $stmt->execute();
        api_success(['message' => 'Your message has been sent.']);
    }
    api_admin($user);
    if ($action === 'messages') {
        api_success(['messages' => $conn->query('SELECT id, name, email, message, created_at FROM contact_message ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC)]);
    }
    if ($action === 'delete_message') {
        $id = api_id($_POST, 'id');
        $stmt = $conn->prepare('DELETE FROM contact_message WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        api_success();
    }
    if ($action === 'requirements') {
        $uid = api_id($_GET, 'user_id');
        $sid = api_id($_GET, 'scholarship_id');
        $stmt = $conn->prepare('SELECT a.*, s.name AS scholarship_name FROM applications a LEFT JOIN scholarships s ON s.scholarship_id = a.scholarship_id WHERE a.user_id = ? AND a.scholarship_id = ? LIMIT 1');
        $stmt->bind_param('ii', $uid, $sid);
        $stmt->execute();
        $application = $stmt->get_result()->fetch_assoc();
        if (!$application) {
            json_response(['success' => false, 'message' => 'Application not found.'], 404);
        }
        $files = [];
        foreach (requirement_files($application['uploaded_files']) as $index => $filename) {
            $files[] = ['index' => $index, 'name' => $filename, 'available' => requirement_file_path($filename) !== null];
        }
        api_success(['application' => $application, 'files' => $files]);
    }
    if ($action === 'document') {
        require __DIR__ . '/view_requirement.php';
        exit;
    }
    if ($action === 'application_action') {
        $uid = api_id($_POST, 'user_id');
        $sid = api_id($_POST, 'scholarship_id');
        $operation = input_value($_POST, 'operation');
        if ($operation === 'remove') {
            $stmt = $conn->prepare('DELETE FROM applications WHERE user_id = ? AND scholarship_id = ?');
            $stmt->bind_param('ii', $uid, $sid);
        } elseif ($operation === 'reapply') {
            $stmt = $conn->prepare("UPDATE applications SET status = 'Pending' WHERE user_id = ? AND scholarship_id = ? AND status = 'Rejected'");
            $stmt->bind_param('ii', $uid, $sid);
        } elseif (in_array($operation, ['Approved', 'Rejected'], true)) {
            $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE user_id = ? AND scholarship_id = ? AND status = 'Pending'");
            $stmt->bind_param('sii', $operation, $uid, $sid);
        } else {
            throw new InvalidArgumentException('Invalid application action.');
        }
        $stmt->execute();
        if (!$stmt->affected_rows) {
            json_response(['success' => false, 'message' => 'The application changed or no longer exists. Refresh and try again.'], 409);
        }
        api_success();
    }
    json_response(['success' => false, 'message' => 'Page not found.'], 404);
} catch (InvalidArgumentException $exception) {
    json_response(['success' => false, 'message' => $exception->getMessage()], 422);
} catch (RuntimeException $exception) {
    if ($exception instanceof mysqli_sql_exception) {
        throw $exception;
    }
    error_log((string)$exception);
    json_response(['success' => false, 'message' => $exception->getMessage()], 422);
}
