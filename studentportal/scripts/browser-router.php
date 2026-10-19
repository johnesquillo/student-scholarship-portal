<?php
// Serve the exported React Native web app and the PHP API on one origin.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if ($path === '/uploads' || strpos($path, '/uploads/') === 0) {
    http_response_code(403);
    exit('Use the authenticated document viewer.');
}
$build = dirname(__DIR__) . '/mobile/dist';
$index = $build . '/index.html';
$pages = [
    '/' => '', '/index.php' => 'login', '/admin_login.php' => 'admin-login',
    '/home.php' => 'home', '/scholarships.php' => 'scholarships', '/apply.php' => 'apply',
    '/view_applications.php' => 'applications', '/FAQ.php' => 'faq', '/contact.php' => 'contact',
    '/manage_applications.php' => 'admin', '/inbox.php' => 'inbox', '/application_requirements.php' => 'requirements',
];
if (is_file($index) && in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    $legacyPage = array_key_exists($path, $pages);
    $nativePage = in_array(ltrim($path, '/'), array_values($pages), true) || $path === '/register';
    if ($legacyPage || $nativePage) {
        if ($legacyPage && $path !== '/') {
            $page = $path === '/index.php' && ($_GET['form'] ?? '') === 'register' ? 'register' : $pages[$path];
            $params = [];
            if (isset($_GET['scholarship_id']) && ctype_digit((string)$_GET['scholarship_id'])) {
                $params['scholarship'] = $_GET['scholarship_id'];
            }
            if (isset($_GET['user_id']) && ctype_digit((string)$_GET['user_id'])) {
                $params['user'] = $_GET['user_id'];
            }
            header('Location: /' . $page . ($params ? '?' . http_build_query($params) : ''), true, 303);
            return true;
        }
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-cache');
        if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
            readfile($index);
        }
        return true;
    }
    $file = realpath($build . $path);
    $root = realpath($build);
    if ($file !== false && $root !== false && strpos($file, $root . DIRECTORY_SEPARATOR) === 0 && is_file($file)) {
        $types = ['js' => 'application/javascript', 'css' => 'text/css', 'html' => 'text/html', 'json' => 'application/json', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon', 'ttf' => 'font/ttf', 'woff' => 'font/woff', 'woff2' => 'font/woff2'];
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!isset($types[$extension])) {
            http_response_code(404);
            return true;
        }
        header('Content-Type: ' . $types[$extension]);
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . filesize($file));
        if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
            readfile($file);
        }
        return true;
    }
}
return false;
