
<?php
require_once("config.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Available Scholarships</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    :root {
        --bg-color: #e6e0e0ff;
        --text-color: #000;
        --sidebar-bg: #e6f0e6;
        --sidebar-border: #a0c0a0;
        --header-bg: #004225;
        --card-bg: #ffffff;
    }

    body.dark-mode {
        --bg-color: #121212;
        --text-color: #f0f0f0;
        --sidebar-bg: #1e2b1e;
        --sidebar-border: #2e472e;
        --header-bg: #1a1a1a;
        --card-bg: #1e1e1e;
    }

    body {
        background-color: var(--bg-color);
        color: var(--text-color);
        margin: 0;
        font-family: 'Segoe UI', sans-serif;
    }

    .sidebar {
        width: 200px;
        height: 100vh;
        background-color: var(--sidebar-bg);
        position: fixed;
        top: 0;
        left: 0;
        padding-top: 20px;
        border-right: 1px solid var(--sidebar-border);
    }

    .sidebar a {
        display: block;
        padding: 10px 20px;
        color: var(--text-color);
        text-decoration: none;
    }

    .sidebar a:hover,
    .sidebar a.active {
        background-color: var(--sidebar-border);
        color: white;
    }

    .header {
        margin-left: 200px;
        background-color: var(--header-bg);
        color: white;
        padding: 10px 20px;
        display: flex;
        align-items: center;
    }

    .main-content {
        margin-left: 200px;
        padding: 20px;
    }

    .card {
        background-color: var(--card-bg) !important;
        color: var(--text-color) !important;
        border: 1px solid var(--sidebar-border);
    }

    .card-title,
    .card-text {
        color: var(--text-color) !important;
    }
   /* Scholarship list container */
.scholarship-list {
    background-color: var(--card-bg);
    color: var(--text-color);
    border: 1px solid var(--sidebar-border);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
    cursor: pointer;
}

/* Hover effect for container */
.scholarship-list:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 12px 20px rgba(0,128,0,0.3); /* green shadow hover */
    background: #ffffff; /* light mode hover */
}

/* Individual card hover effect inside container */
.card {
    transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
    cursor: pointer;
}

.card:hover {
    transform: translateY(-6px) scale(1.02);
    box-shadow: 0 10px 18px rgba(0,128,0,0.3); /* green shadow */
    background-color: #f9fff9; /* subtle light green */
}

/* Dark mode hover */
body.dark-mode .scholarship-list:hover,
body.dark-mode .card:hover {
    background: #2a2a2a;
    box-shadow: 0 12px 20px rgba(0,255,0,0.3); /* bright green glow in dark mode */
}


  </style>
</head>
<body>

<div class="sidebar">
    <a href="home.php">Home</a>
    <a href="scholarships.php" class="active">Available Scholarships</a>
    <a href="view_applications.php">Status Applications</a>
    <a href="contact.php">Contact</a>
    <a href="FAQ.php">FAQ</a>
    <a href="logout.php">Logout</a>
</div>

<div class="header">
    <img src="tumblr_o4ti6j8k6r1unefbyo1_400.png" alt="Logo" style="height: 40px; margin-right: 10px;" />
    <h2>Pamantasan ng Lungsod ng Muntinlupa</h2>
</div>

<div class="main-content">
    <div class="container mt-4">
        <h2 class="mb-4 text-center">🎓 Available Scholarships</h2>

        <!-- Search Form -->
        <form method="GET" class="d-flex mb-4">
            <input type="text" name="search" class="form-control me-2" placeholder="Search scholarships..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" />
            <button type="submit" class="btn btn-success">Search</button>
        </form>

      <div class="row scholarship-list">
<?php
$search = $_GET['search'] ?? '';
$search = $conn->real_escape_string($search);

$query = "SELECT * FROM scholarships"; 
$result = $conn->query($query);

if (!$result) {
    echo "<div class='alert alert-danger'>Query Error: " . $conn->error . "</div>";
} elseif ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<div class='col-md-6 col-lg-4 mb-4'>";
        echo "  <div class='card h-100 shadow-sm'>";
        echo "    <div class='card-body'>";
        echo "      <h5 class='card-title'>" . htmlspecialchars($row['name']) . "</h5>";
        echo "      <p class='card-text'>" . htmlspecialchars($row['description']) . "</p>";
        echo "      <p class='card-text'><strong>Deadline:</strong> " . htmlspecialchars($row['deadline']) . "</p>";
        echo "      <a href='apply.php?scholarship_id=" . urlencode($row['scholarship_id']) . "' class='btn btn-success'>Apply Now</a>";
        echo "    </div>";
        echo "  </div>";
        echo "</div>";
    }
} else {
    echo "<p class='text-muted'>No scholarships found.</p>";
}
?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.onload = function () {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
        }
    };
</script>
</body>
</html>
