<?php
require_once __DIR__ . '/admin_auth.php';
require_admin_access();
require_once __DIR__ . '/requirement_files.php';
header('Cache-Control: private, no-store');

$userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$scholarshipId = filter_input(INPUT_GET, 'scholarship_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$userId || !$scholarshipId) {
    http_response_code(400);
    exit('A valid applicant and scholarship are required.');
}

require __DIR__ . '/db.php';
$stmt = $conn->prepare('SELECT a.*, s.name AS scholarship_name FROM applications a LEFT JOIN scholarships s ON s.scholarship_id = a.scholarship_id WHERE a.user_id = ? AND a.scholarship_id = ? LIMIT 1');
$stmt->bind_param('ii', $userId, $scholarshipId);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
if (!$application) {
    http_response_code(404);
    exit('Application not found.');
}

$files = requirement_files($application['uploaded_files'] ?? '');
$baseQuery = http_build_query(['user_id' => $userId, 'scholarship_id' => $scholarshipId]);
function requirements_escape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submitted Requirements - PLMun</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; color: #24362c; background: #f4f4f4; }
        header { background: #004225; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap; }
        header h1 { margin: 0; font-size: 24px; }
        header a { color: white; }
        main { max-width: 1100px; margin: auto; padding: 24px 16px; }
        section, .requirement { background: white; border: 1px solid #dce5df; padding: 24px; border-radius: 10px; margin-bottom: 20px; }
        h2, h3 { color: #004225; margin-top: 0; }
        dl { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 0; }
        dt { font-weight: 600; color: #53665b; }
        dd { margin: 5px 0 0; overflow-wrap: anywhere; }
        .requirement summary { cursor: pointer; font-weight: 600; overflow-wrap: anywhere; }
        .actions { display: flex; gap: 12px; flex-wrap: wrap; margin: 20px 0; }
        .button { background: #004225; color: white; padding: 10px 16px; border-radius: 5px; text-decoration: none; display: inline-block; }
        .button:hover { background: #006438; }
        .button:focus-visible, summary:focus-visible { outline: 3px solid #bd8800; outline-offset: 3px; }
        iframe { width: 100%; height: 65vh; border: 1px solid #dce5df; }
        .preview-image { display: block; max-width: 100%; max-height: 70vh; height: auto; margin: auto; }
        .unavailable { color: #9c301b; }
        .empty { padding: 24px; background: #fff8df; border-radius: 8px; }
    </style>
  <link rel="stylesheet" href="css/portal.css">
</head>
<body>
<header>
    <h1>PLMun Scholarship Admin</h1>
    <nav aria-label="Admin navigation"><a href="manage_applications.php">Back to Applications</a></nav>
</header>
<main>
    <section aria-labelledby="applicant-heading">
        <h2 id="applicant-heading">Submitted Requirements</h2>
        <dl>
            <div><dt>Applicant</dt><dd><?= requirements_escape($application['name']) ?></dd></div>
            <div><dt>Scholarship</dt><dd><?= requirements_escape($application['scholarship_name'] ?? ('Scholarship #' . $scholarshipId)) ?></dd></div>
            <div><dt>User ID</dt><dd><?= (int)$userId ?></dd></div>
            <div><dt>Email</dt><dd><?= requirements_escape($application['email'] ?? '') ?></dd></div>
            <div><dt>Course / Year</dt><dd><?= requirements_escape($application['course'] . ' / ' . ($application['year_level'] ?? '')) ?></dd></div>
            <div><dt>GWA</dt><dd><?= requirements_escape($application['gwa'] ?? '') ?></dd></div>
            <div><dt>Application Date</dt><dd><?= requirements_escape($application['application_date']) ?></dd></div>
            <div><dt>Status</dt><dd><?= requirements_escape($application['status']) ?></dd></div>
        </dl>
    </section>

    <h2>Documents (<?= count($files) ?> submitted)</h2>
    <?php if (!$files): ?>
        <p class="empty">No requirements were uploaded with this application.</p>
    <?php endif; ?>
    <?php foreach ($files as $index => $filename): ?>
        <?php $fileUrl = 'view_requirement.php?' . $baseQuery . '&file=' . $index; ?>
        <details class="requirement">
            <summary><?= requirements_escape($filename) ?></summary>
            <?php if (requirement_file_path($filename) === null): ?>
                <p class="unavailable">This document is unavailable. The stored file may be missing or unsupported.</p>
            <?php else: ?>
                <div class="actions">
                    <a class="button" href="<?= requirements_escape($fileUrl) ?>" target="_blank" rel="noopener">Open document</a>
                    <a class="button" href="<?= requirements_escape($fileUrl . '&download=1') ?>">Download</a>
                </div>
                <?php if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'pdf'): ?>
                    <iframe loading="lazy" src="<?= requirements_escape($fileUrl) ?>" title="<?= requirements_escape('Preview of ' . $filename) ?>"></iframe>
                    <p>If the preview is unavailable, use Open document or Download.</p>
                <?php else: ?>
                    <img class="preview-image" loading="lazy" src="<?= requirements_escape($fileUrl) ?>" alt="<?= requirements_escape('Submitted requirement: ' . $filename) ?>">
                <?php endif; ?>
            <?php endif; ?>
        </details>
    <?php endforeach; ?>
</main>
</body>
</html>
