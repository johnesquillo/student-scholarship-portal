<?php
require_once __DIR__ . '/requirement_files.php';

function save_requirements($documents)
{
    if (!is_array($documents) || !isset($documents['tmp_name'], $documents['name'], $documents['error'], $documents['size'])
        || !is_array($documents['tmp_name']) || !is_array($documents['name']) || !is_array($documents['error']) || !is_array($documents['size'])
        || count($documents['tmp_name']) < 1 || count($documents['tmp_name']) > 10) {
        throw new RuntimeException('Upload between 1 and 10 PDF or JPG documents.');
    }
    $validated = [];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    foreach ($documents['tmp_name'] as $index => $tmpName) {
        $name = $documents['name'][$index] ?? null;
        $error = $documents['error'][$index] ?? null;
        $size = $documents['size'][$index] ?? null;
        if (!is_string($tmpName) || !is_string($name) || $error !== UPLOAD_ERR_OK || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('A document did not upload successfully. Select your files and try again.');
        }
        if (!is_int($size) || $size < 1 || $size > 5 * 1024 * 1024 || filesize($tmpName) > 5 * 1024 * 1024) {
            throw new RuntimeException('Each document must be between 1 byte and 5 MB.');
        }
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $mime = $finfo->file($tmpName);
        if (!(($extension === 'pdf' && $mime === 'application/pdf') || (in_array($extension, ['jpg', 'jpeg'], true) && $mime === 'image/jpeg'))) {
            throw new RuntimeException('Only valid PDF or JPG documents are allowed.');
        }
        $base = substr(preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($name, PATHINFO_FILENAME)), 0, 80);
        $validated[] = [$tmpName, $base . '_' . bin2hex(random_bytes(16)) . '.' . $extension];
    }
    $directory = requirement_upload_directory();
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Document storage is unavailable. Please contact the scholarship office.');
    }
    $saved = [];
    try {
        foreach ($validated as [$tmpName, $filename]) {
            if (!move_uploaded_file($tmpName, $directory . '/' . $filename)) {
                throw new RuntimeException('A document could not be saved. Please try again.');
            }
            $saved[] = $filename;
            chmod($directory . '/' . $filename, 0600);
        }
    } catch (Throwable $exception) {
        remove_new_requirements($saved);
        throw $exception;
    }
    return $saved;
}

function remove_new_requirements($filenames)
{
    // Only roll back files created by save_requirements in this request.
    foreach ($filenames as $filename) {
        $path = requirement_upload_directory() . '/' . $filename;
        if (is_file($path)) {
            unlink($path);
        }
    }
}
