<?php
require_once __DIR__ . '/bootstrap.php';
require_login();
require_once __DIR__ . '/application_service.php';
require __DIR__ . '/db.php';
$user_id = (int)$_SESSION['user_id'];
$scholarship_error = '';
$form_error = '';
$selectedScholarship = filter_var(input_value($_POST, 'scholarship_id', input_value($_GET, 'scholarship_id')), FILTER_VALIDATE_INT);
$values = [
    'name' => input_value($_POST, 'name', $_SESSION['name'] ?? ''),
    'email' => input_value($_POST, 'email', $_SESSION['email']),
    'gwa' => input_value($_POST, 'gwa'),
    'course' => input_value($_POST, 'course'),
    'year' => input_value($_POST, 'year'),
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        http_response_code(413);
        $form_error = 'The upload exceeds the server limit. Select fewer or smaller files.';
    } else {
        verify_csrf();
        try {
            submit_portal_application($conn, $user_id, $selectedScholarship, $values, $_FILES['documents'] ?? null);
            redirect_to('apply.php?success=1');
        } catch (InvalidArgumentException $exception) {
            $form_error = $exception->getMessage();
        } catch (RuntimeException $exception) {
            if ($exception instanceof mysqli_sql_exception) {
                throw $exception;
            }
            $form_error = $exception->getMessage();
        }
    }
}
$scholarships = [];
$res = $conn->query("SELECT scholarship_id, name, deadline FROM scholarships WHERE LOWER(status) = 'active' AND (deadline IS NULL OR deadline >= CURDATE()) ORDER BY deadline IS NULL, deadline, name");
while ($row = $res->fetch_assoc()) {
    $scholarships[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
  <script src="js/preferences.js"></script>
    <title>Scholarship Application</title>
    <style>
        :root {
            --bg-color: #f4f4f4; --text-color: #000; --sidebar-bg: #e6f0e6;
            --sidebar-border: #a0c0a0; --header-bg: #004225;
        }
        body.dark-mode {
            --bg-color: #000; --text-color: #f0f0f0; --sidebar-bg: #1e2b1e;
            --sidebar-border: #2e472e; --header-bg: #1a1a1a;
        }
        body { background-color: var(--bg-color); color: var(--text-color); margin: 0; font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 200px; height: 100vh; background-color: var(--sidebar-bg); position: fixed; top: 0; left: 0; padding-top: 20px; border-right: 1px solid var(--sidebar-border); display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar nav { flex-grow: 1; }
        .sidebar a { display: block; padding: 10px 20px; color: var(--text-color); text-decoration: none; }
        .sidebar a:hover, .sidebar a.active { background-color: var(--sidebar-border); color: white; }
        .header { margin-left: 200px; background-color: var(--header-bg); color: white; padding: 10px 20px; display: flex; align-items: center; }
        .main-content { margin-left: 200px; padding: 20px; }
        .container { width: 100%; padding: 20px; }
        .form-section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        body.dark-mode .form-section { background: #222; }
        input, button, select { padding: 10px; margin: 5px 0; width: 100%; box-sizing: border-box; }
        button { background-color: #004225; color: white; border: none; cursor: pointer; border-radius: 5px; }
        button:hover { background-color: #006400; }
        h2 { color: #ffffff; }
        .darkmode-btn { background: transparent; color: var(--text-color); border: none; padding: 15px 20px; cursor: pointer; text-align: left; font-size: 16px; width: 100%; }
        .darkmode-btn:hover { background-color: var(--sidebar-border); color: white; }
        #successModal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 9999; justify-content: center; align-items: center; }
        #successModal .modal-content { background: white; padding: 30px; border-radius: 10px; max-width: 400px; text-align: center; box-shadow: 0 0 10px rgba(0,0,0,0.3); }
        #successModal .modal-content h2 { color: #004225; margin-bottom: 10px; }
        #successModal .modal-content button { background-color: #004225; color: white; padding: 10px 20px; border: none; margin-top: 15px; border-radius: 5px; cursor: pointer; }
        #successModal .modal-content button:hover { background-color: #006400; }
        body.dark-mode #successModal .modal-content { background: #222; color: #f0f0f0; }
        .error-box { color: #b00020; background: #ffe6e6; border: 1px solid #b00020; padding: 10px; margin-bottom: 15px; border-radius: 6px; }
    </style>
  <link rel="stylesheet" href="css/portal.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<div class="sidebar">
    <nav>
        <a href="home.php">Home</a>
        <a href="scholarships.php">Available Scholarships</a>
        <a href="view_applications.php">Status Applications</a>
        <a href="contact.php">Contact</a>
        <a href="FAQ.php">FAQ</a>
        <?= logout_form() ?>
    </nav>
    <button class="darkmode-btn" id="toggleMode">🌙 Toggle Dark Mode</button>
</div>

<div class="header">
    <img src="img/tumblr_o4ti6j8k6r1unefbyo1_400.png" alt="Logo" style="height: 40px; margin-right: 10px;" />
    <h2>PLMun Scholarship</h2>
</div>

<div class="main-content">
    <div class="container">
        <div class="form-section">
            <h2 style="color: var(--text-color);">Scholarship Application Form</h2>

            <?php if (!empty($scholarship_error)): ?>
                <div class="error-box"><?php echo htmlspecialchars($scholarship_error); ?></div>
            <?php endif; ?>
            <?php if (!empty($form_error)): ?>
                <div class="error-box"><?php echo htmlspecialchars($form_error); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <h3>Select Scholarship</h3>
                <?php if (!$scholarships): ?><p class="form-feedback">No scholarships are currently accepting applications.</p><?php endif; ?>
                <select name="scholarship_id" required>
                    <option value="">-- Choose Scholarship --</option>
                    <?php foreach ($scholarships as $s): ?>
                        <option value="<?= (int)$s['scholarship_id'] ?>" <?= $selectedScholarship === (int)$s['scholarship_id'] ? 'selected' : '' ?>>
                            <?php echo htmlspecialchars($s['name']); ?> 
                            (<?= $s['deadline'] === null ? 'No deadline announced' : 'Deadline: ' . e($s['deadline']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <h3>Personal Information</h3>
                <label>Full Name</label>
                <input type="text" name="name" maxlength="100" value="<?= e($values['name']) ?>" required />

                <label>Email Address</label>
                <input type="email" name="email" maxlength="254" value="<?= e($values['email']) ?>" required />

                <h3>Academic Info</h3>
                <label>GWA</label>
                <input type="text" name="gwa" maxlength="6" value="<?= e($values['gwa']) ?>" required />

                <label>Course</label>
                <input type="text" name="course" maxlength="150" value="<?= e($values['course']) ?>" required />

                <label>Year Level</label>
                <select name="year" required>
                    <option value="">Select Year</option>
                    <option <?= $values['year'] === '1st Year' ? 'selected' : '' ?>>1st Year</option>
                    <option <?= $values['year'] === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                    <option <?= $values['year'] === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                    <option <?= $values['year'] === '4th Year' ? 'selected' : '' ?>>4th Year</option>
                </select>

                <h3>📎 Required Documents</h3>
                <label>Upload 1–10 Files (PDF or JPG, up to 5 MB each)</label>
                <input type="file" name="documents[]" accept=".pdf,.jpg,.jpeg" multiple required />

                <br>
                <button type="submit" <?= !$scholarships ? 'disabled' : '' ?>>Submit Application</button>
            </form>
        </div>
    </div>
</div>

<div id="successModal">
    <div class="modal-content">
        <h2>🎉 Successfully Submitted!</h2>
        <p>Your details have been successfully submitted.<br>Thank you for applying!</p>
        <button onclick="document.getElementById('successModal').style.display='none'">Close</button>
    </div>
</div>

<script>
    // Dark mode
    const savedTheme = portalStorage.getItem("theme") || "light";
    document.body.classList.add(savedTheme + "-mode");

    document.getElementById("toggleMode").addEventListener("click", () => {
        const isDark = document.body.classList.contains("dark-mode");
        document.body.classList.toggle("dark-mode");
        document.body.classList.toggle("light-mode");
        portalStorage.setItem("theme", isDark ? "light" : "dark");
        document.getElementById("toggleMode").textContent = isDark ? "🌙 Toggle Dark Mode" : "☀️ Toggle Light Mode";
    });

    window.onload = function () {
        const isDark = document.body.classList.contains("dark-mode");
        document.getElementById("toggleMode").textContent = isDark ? "☀️ Toggle Light Mode" : "🌙 Toggle Dark Mode";
        if (window.location.search.includes("success=1")) {
            document.getElementById("successModal").style.display = "flex";
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    };
</script>
</body>
</html>
