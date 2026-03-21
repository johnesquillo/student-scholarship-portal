<?php
session_start();
require_once("db.php");

// Show errors while testing (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?msg=please_login");
    exit();
}
$user_id = (int)$_SESSION['user_id'];

$scholarship_error = '';
$form_error = '';

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scholarship_id = (int)($_POST['scholarship_id'] ?? 0);
    $name   = trim($_POST['name'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $gwa    = trim($_POST['gwa'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year   = trim($_POST['year'] ?? '');

    // Validate scholarship
    if ($scholarship_id > 0) {
        $stmt_check = $conn->prepare("SELECT scholarship_id FROM scholarships WHERE scholarship_id = ? AND LOWER(status)='active'");
        $stmt_check->bind_param("i", $scholarship_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows === 0) {
            $scholarship_error = "❌ Invalid or inactive scholarship selected.";
        }
        $stmt_check->close();
    } else {
        $scholarship_error = "❌ Please choose a scholarship.";
    }

    // Check required fields
    if (empty($scholarship_error)) {
        if ($name === '' || $email === '' || $gwa === '' || $course === '' || $year === '') {
            $form_error = "⚠️ Please fill in all required fields.";
        }
    }

    // File uploads
    $uploadedNames = [];
    if (empty($form_error) && empty($scholarship_error)) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        if (isset($_FILES['documents']) && is_array($_FILES['documents']['tmp_name'])) {
            foreach ($_FILES['documents']['tmp_name'] as $key => $tmp_name) {
                if (!is_uploaded_file($tmp_name)) continue;

                $original = $_FILES['documents']['name'][$key];
                $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
                if (!in_array($ext, ['pdf','jpg','jpeg'])) {
                    $form_error = "⚠️ Only PDF or JPG files are allowed.";
                    break;
                }

                $safeBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($original, PATHINFO_FILENAME));
                $fileName = $safeBase . '_' . uniqid() . '.' . $ext;

                $targetPath = $uploadDir . $fileName;
                if (move_uploaded_file($tmp_name, $targetPath)) {
                    $uploadedNames[] = $fileName;
                }
            }
        }
    }

    // Save application
    if (empty($form_error) && empty($scholarship_error)) {
        $uploaded_files = implode(",", $uploadedNames);

        $stmt = $conn->prepare("INSERT INTO applications 
            (user_id, scholarship_id, name, course, email, gwa, year_level, uploaded_files) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssss", 
            $user_id, $scholarship_id, $name, $course, $email, $gwa, $year, $uploaded_files
        );

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: apply.php?success=1");
            exit();
        } else {
            $form_error = "❌ Something went wrong saving your application. Please try again.";
        }
        $stmt->close();
    }
}

// Fetch active scholarships for dropdown
$scholarships = [];
$res = $conn->query("SELECT scholarship_id, name, deadline FROM scholarships WHERE LOWER(status)='active'");
while ($row = $res->fetch_assoc()) {
    $scholarships[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
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
</head>
<body>

<div class="sidebar">
    <nav>
        <a href="home.php">Home</a>
        <a href="scholarships.php">Available Scholarships</a>
        <a href="view_applications.php">Status Applications</a>
        <a href="contact.php">Contact</a>
        <a href="FAQ.php">FAQ</a>
        <a href="logout.php">Logout</a>
    </nav>
    <button class="darkmode-btn" id="toggleMode">🌙 Toggle Dark Mode</button>
</div>

<div class="header">
    <img src="tumblr_o4ti6j8k6r1unefbyo1_400.png" alt="Logo" style="height: 40px; margin-right: 10px;" />
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
                <h3>Select Scholarship</h3>
                <select name="scholarship_id" required>
                    <option value="">-- Choose Scholarship --</option>
                    <?php foreach ($scholarships as $s): ?>
                        <option value="<?php echo $s['scholarship_id']; ?>">
                            <?php echo htmlspecialchars($s['name']); ?> 
                            (Deadline: <?php echo htmlspecialchars($s['deadline']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <h3>Personal Information</h3>
                <label>Full Name</label>
                <input type="text" name="name" required />

                <label>Email Address</label>
                <input type="email" name="email" required />

                <h3>Academic Info</h3>
                <label>GWA</label>
                <input type="text" name="gwa" required />

                <label>Course</label>
                <input type="text" name="course" required />

                <label>Year Level</label>
                <select name="year" required>
                    <option value="">Select Year</option>
                    <option>1st Year</option>
                    <option>2nd Year</option>
                    <option>3rd Year</option>
                    <option>4th Year</option>
                </select>

                <h3>📎 Required Documents</h3>
                <label>Upload Files (PDF or JPG only)</label>
                <input type="file" name="documents[]" accept=".pdf,.jpg,.jpeg" multiple required />

                <br>
                <button type="submit">Submit Application</button>
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
    const savedTheme = localStorage.getItem("theme") || "light";
    document.body.classList.add(savedTheme + "-mode");

    document.getElementById("toggleMode").addEventListener("click", () => {
        const isDark = document.body.classList.contains("dark-mode");
        document.body.classList.toggle("dark-mode");
        document.body.classList.toggle("light-mode");
        localStorage.setItem("theme", isDark ? "light" : "dark");
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
