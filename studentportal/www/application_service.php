<?php
require_once __DIR__ . '/upload_requirements.php';

function submit_portal_application($conn, $userId, $scholarshipId, $values, $documents)
{
    if ($values['name'] === '' || strlen($values['name']) > 100 || strlen($values['email']) > 254
        || !filter_var($values['email'], FILTER_VALIDATE_EMAIL) || $values['course'] === '' || strlen($values['course']) > 150
        || !is_numeric($values['gwa']) || (float)$values['gwa'] < 0 || (float)$values['gwa'] > 100
        || !in_array($values['year'], ['1st Year', '2nd Year', '3rd Year', '4th Year'], true)) {
        throw new InvalidArgumentException('Enter valid personal and academic details in every required field.');
    }
    if (!$scholarshipId || $scholarshipId < 1) {
        throw new InvalidArgumentException('Please choose a scholarship.');
    }
    $saved = [];
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT scholarship_id FROM scholarships WHERE scholarship_id = ? AND LOWER(status) = 'active' AND (deadline IS NULL OR deadline >= CURDATE()) FOR UPDATE");
        $stmt->bind_param('i', $scholarshipId);
        $stmt->execute();
        $scholarship = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$scholarship) {
            throw new InvalidArgumentException('This scholarship is inactive or its deadline has passed.');
        }
        $stmt = $conn->prepare('SELECT status FROM applications WHERE user_id = ? AND scholarship_id = ?');
        $stmt->bind_param('ii', $userId, $scholarshipId);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($existing) {
            throw new InvalidArgumentException('You have already applied for this scholarship. Check My Applications or contact the scholarship office.');
        }
        $saved = save_requirements($documents);
        $files = implode(',', $saved);
        $stmt = $conn->prepare('INSERT INTO applications (user_id, scholarship_id, name, course, email, gwa, year_level, uploaded_files) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('iissssss', $userId, $scholarshipId, $values['name'], $values['course'], $values['email'], $values['gwa'], $values['year'], $files);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
    } catch (Throwable $exception) {
        $conn->rollback();
        remove_new_requirements($saved);
        if ($exception instanceof mysqli_sql_exception) {
            error_log((string)$exception);
            throw new InvalidArgumentException((int)$exception->getCode() === 1062 ? 'You have already applied for this scholarship.' : 'Your application could not be saved. Please try again.');
        }
        if ($exception instanceof RuntimeException || $exception instanceof InvalidArgumentException) {
            throw $exception;
        }
        error_log((string)$exception);
        throw new RuntimeException('Your application could not be saved. Please try again.');
    }
}
