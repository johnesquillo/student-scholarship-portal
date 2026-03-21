<?php
$host = "fdb1034.awardspace.net";             // usually localhost on AwardSpace
$user = "4669763_john";          // your DB username
$password = "plmun2023";  // your DB password
$database = "4669763_john";      // your DB name

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// === FETCH APPLICATIONS ===
$result = $conn->query("SELECT * FROM applications ORDER BY application_date DESC");

// === FETCH STATS FOR DASHBOARD ===
$counts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
$total = 0;

$status_query = $conn->query("SELECT status, COUNT(*) as count FROM applications GROUP BY status");
while ($row = $status_query->fetch_assoc()) {
    $status = $row['status'];
    $counts[$status] = $row['count'];
    $total += $row['count'];
}

$percentages = [];
foreach ($counts as $status => $count) {
    $percentages[$status] = $total > 0 ? round(($count / $total) * 100, 1) : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>View Applications</title>
  <style>
    :root {
      --bg-color: #dbd4d4ff;
      --text-color: #000;
      --card-bg: #f9d342;
      --header-bg: #004225;
      --sidebar-bg: #e6f0e6;
      --sidebar-border: #a0c0a0;
      --row-bg: #ffffff;
      --row-hover-bg: #e6ffe6;            /* light green */
      --row-border: #e5e7eb;              /* light gray */
      --row-shadow: 0 8px 15px rgba(0, 128, 0, 0.3);  /* green shadow */
    }

    body {
      background-color: #f4f4f4;
      color: #000;
    }

    body.dark-mode {
      background-color: #333;
      color: #f0f0f0;
      --bg-color: #000;
      --text-color: #f0f0f0;
      --card-bg: #333;
      --header-bg: #1a1a1a;
      --sidebar-bg: #1e2b1e;
      --sidebar-border: #2e472e;
      --row-bg: #2b2b2b;
      --row-hover-bg: #145a32;                               /* muted green */
      --row-border: #555;
      --row-shadow: 0 8px 15px rgba(0, 255, 0, 0.5);
    }

    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: var(--bg-color);
      color: var(--text-color);
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
      transition: transform 0.3s ease;
    }
    .sidebar.hidden { transform: translateX(-100%); }

    .sidebar a {
      display: block;
      padding: 10px 20px;
      color: var(--text-color);
      text-decoration: none;
    }
    .sidebar a:hover, .sidebar a.active {
      background-color: var(--sidebar-border);
      color: white;
    }

    .header {
      margin-left: 200px;
      background-color: var(--header-bg);
      color: white;
      padding: 10px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: margin-left 0.3s ease;
    }
    .header.shifted { margin-left: 0; }

    .toggle-btn {
      background: none;
      border: none;
      color: white;
      font-size: 24px;
      cursor: pointer;
    }
    .toggle-btn:hover { color: #ccc; }

    .content {
      margin-left: 200px;
      padding: 20px;
      transition: margin-left 0.3s ease;
    }
    .content.shifted { margin-left: 0; }

    .dashboard {
      display: flex;
      justify-content: space-around;
      margin-bottom: 20px;
      text-align: center;
      flex-wrap: wrap;
      gap: 10px;
    }

    .card {
      background: var(--card-bg);
      padding: 20px;
      border-radius: 10px;
      width: 30%;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      color: var(--text-color);
      cursor: pointer;
      transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
    }
    .card h3 { margin: 0; font-size: 18px; }
    .card p { margin: 10px 0 0; font-size: 24px; font-weight: bold; }

    .card:hover {
      transform: translateY(-8px) scale(1.03);
      box-shadow: 0 12px 20px rgba(0, 128, 0, 0.4); /* green glow */
      background: #ffffff;
    }
    .card.pending:hover  { background: #fff3cd; }
    .card.approved:hover { background: #d4edda; }
    .card.rejected:hover { background: #f8d7da; }

    .pending { color: orange; }
    .approved { color: green; }
    .rejected { color: red; }

    /* ===== TABLE AS ROW CARDS ===== */
    table {
      width: 100%;
      border-collapse: separate;      /* IMPORTANT: allow spacing so shadow shows */
      border-spacing: 0 8px;         /* gap between "row cards" */
      margin-top: 20px;
      background-color: transparent;  /* let rows render as cards */
    }

    thead th {
      background-color: var(--header-bg);
      color: #fff;
      padding: 10px;
      text-align: center;
      border: none;
    }

    /* Base row cells look like a single card */
    tbody td {
      background: var(--row-bg);
      border: 1px solid var(--row-border);
      padding: 12px;
      text-align: center;
      color: var(--text-color);
      transition: background 0.25s ease, box-shadow 0.25s ease, transform 0.25s ease;
    }
    /* Rounded corners for the "card" */
    tbody tr td:first-child { border-radius: 10px 0 0 10px; }
    tbody tr td:last-child  { border-radius: 0 10px 10px 0; }

    /* Hover effect: green glow + lift (applied to all cells so it looks like one card) */
    tbody tr:hover td {
      background-color: var(--row-hover-bg);
      box-shadow: var(--row-shadow);
    }
    tbody tr:hover { transform: translateY(-4px); }

    h2 { text-align: center; color: #ffffff; }
  </style>
</head>
<body>
  <div class="sidebar" id="sidebar">
    <a href="home.php">Home</a>
    <a href="scholarships.php">Available Scholarships</a>
    <a href="view_applications.php" class="active">Status Applications</a>
    <a href="contact.php">Contact</a>
    <a href="FAQ.php">FAQ</a>
    <a href="logout.php">Logout</a>
  </div>

  <div class="header" id="header">
    <div style="display: flex; align-items: center;">
      <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
      <img src="tumblr_o4ti6j8k6r1unefbyo1_400.png" alt="Logo" style="height: 40px; margin: 0 10px;" />
      <h2>Pamantasan ng Lungsod ng Muntinlupa</h2>
    </div>
  </div>

  <div class="content" id="content">
    <!-- DASHBOARD SUMMARY -->
    <div class="dashboard">
      <div class="card pending">
        <h3>Pending Applications</h3>
        <p><?= $percentages['Pending'] ?>%</p>
      </div>
      <div class="card approved">
        <h3>Approved Applications</h3>
        <p><?= $percentages['Approved'] ?>%</p>
      </div>
      <div class="card rejected">
        <h3>Rejected Applications</h3>
        <p><?= $percentages['Rejected'] ?>%</p>
      </div>
    </div>

    <!-- APPLICATION TABLE -->
    <h2 style="color: black;">Manage Student Applications</h2>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Student</th>
          <th>Course</th>
          <th>Application Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['scholarship_id']) ?></td>
          <td><?= htmlspecialchars($row['name']) ?></td>
          <td><?= htmlspecialchars($row['course']) ?></td>
          <td><?= htmlspecialchars($row['application_date']) ?></td>
          <td class="<?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('hidden');
      document.getElementById('header').classList.toggle('shifted');
      document.getElementById('content').classList.toggle('shifted');
    }

    function toggleDarkMode() {
      document.body.classList.toggle("dark-mode");
    }

    window.onload = function() {
      const savedTheme = localStorage.getItem('theme');
      if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
      } else {
        document.body.classList.remove('dark-mode');
      }
    };
  </script>
</body>
</html>

<?php $conn->close(); ?>
