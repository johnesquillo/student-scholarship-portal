<?php
require_once __DIR__ . '/admin_auth.php';
require_admin_access();
require_once __DIR__ . '/requirement_files.php';
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');

$userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$scholarshipId = filter_input(INPUT_GET, 'scholarship_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$fileIndex = filter_input(INPUT_GET, 'file', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if (!$userId || !$scholarshipId || $fileIndex === false || $fileIndex === null) {
    http_response_code(400);
    exit('Invalid requirement request.');
}

require_once __DIR__ . '/db.php';
$stmt = $conn->prepare('SELECT uploaded_files FROM applications WHERE user_id = ? AND scholarship_id = ? LIMIT 1');
$stmt->bind_param('ii', $userId, $scholarshipId);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
$files = requirement_files($application['uploaded_files'] ?? '');
$filename = $files[$fileIndex] ?? '';
$path = requirement_file_path($filename);
if (!$application || $path === null) {
    http_response_code(404);
    exit('Document not found.');
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
$expectedMime = strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'pdf' ? 'application/pdf' : 'image/jpeg';
if ($mime !== $expectedMime) {
    http_response_code(415);
    exit('This document is not a valid PDF or JPG file.');
}

$disposition = ($_GET['download'] ?? '') === '1' ? 'attachment' : 'inline';
$safeName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);
header('Content-Type: ' . $mime);
header('Content-Disposition: ' . $disposition . '; filename="' . $safeName . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
header('Content-Length: ' . filesize($path));
session_write_close();
readfile($path);
