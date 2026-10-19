<?php
function requirement_upload_directory()
{
    return dirname(__DIR__) . '/storage/requirements';
}
function requirement_files($uploadedFiles)
{
    return array_values(array_filter(array_map('trim', explode(',', (string)$uploadedFiles)), function ($name) {
        return $name !== '';
    }));
}

function requirement_file_path($filename)
{
    // Only filenames saved by apply.php are accepted, never paths from a URL.
    if ($filename === '' || basename($filename) !== $filename || strpbrk($filename, "\\:\0") !== false) {
        return null;
    }
    if (!in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg'], true)) {
        return null;
    }

    foreach ([requirement_upload_directory(), __DIR__ . '/uploads'] as $candidate) {
        $directory = realpath($candidate);
        $path = $directory === false ? false : realpath($directory . DIRECTORY_SEPARATOR . $filename);
        if ($path !== false && strpos($path, $directory . DIRECTORY_SEPARATOR) === 0 && is_file($path) && is_readable($path)) {
            return $path;
        }
    }
    return null;
}
