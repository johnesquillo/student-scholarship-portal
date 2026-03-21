<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

// (Optional) show PHP errors while debugging — remove/comment in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// === DB CONNECTION ===
$host = "fdb1034.awardspace.net";
$user = "4669763_john";
$password = "plmun2023";
$database = "4669763_john";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// === FETCH STATS FOR DASHBOARD ===
$counts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
$total = 0;

$status_query = $conn->query("SELECT status, COUNT(*) as count FROM applications GROUP BY status");
while ($row = $status_query->fetch_assoc()) {
    $counts[$row['status']] = (int)$row['count'];
    $total += (int)$row['count'];
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
  <title>PLMun Scholarship Portal</title>

  <!-- Pre-paint: apply saved theme & sidebar state BEFORE the page draws -->
  <script>
    (function () {
      try {
        var theme = localStorage.getItem('theme') || 'light';
        if (theme === 'dark') document.documentElement.classList.add('pre-dark');
        var sb = localStorage.getItem('sidebarHidden');
        if (sb === '1') document.documentElement.classList.add('pre-sidebar-hidden');
      } catch (e) { /* ignore */ }
    })();
  </script>

  <style>
    /* ================= THEME VARIABLES ================= */
    :root{
      --bg-color: #cbd1ceff;
      --text-color: #000;
      --muted-text: #444;
      --card-bg: #f9d342;
      --header-bg: #004225;
      --sidebar-bg: #e6f0e6;
      --sidebar-border: #a0c0a0;

      --row-bg: #ffffff;
      --row-hover-bg: #e6ffe6;
      --row-border: #e5e7eb;
      --row-shadow: 0 12px 20px rgba(0, 128, 0, 0.4);
      /* NEW: sidebar toggle colors */
      --edge-toggle-bg: #ffffff;
      --edge-toggle-border: #cbd5e1;
      --edge-toggle-shadow: 0 2px 8px rgba(0,0,0,.15);
      --edge-toggle-hover: #f8fafc;
    }
    body.dark-mode{
      --bg-color: #000;
      --text-color: #f0f0f0;
      --muted-text: #c7c7c7;
      --card-bg: #333;
      --header-bg: #1a1a1a;
      --sidebar-bg: #1e2b1e;
      --sidebar-border: #2e472e;
      --row-bg: #2b2b2b;
      --row-hover-bg: #145a32;
      --row-border: #555;
      --row-shadow: 0 12px 20px rgba(0, 255, 0, 0.5);
      /* NEW: dark variant for the edge toggle */
      --edge-toggle-bg: #1f2937;
      --edge-toggle-border: #374151;
      --edge-toggle-hover: #111827;
    }

    /* Pre-paint classes (avoid flash) */
    html.pre-dark body{ background:#000; color:#f0f0f0; }
    html.pre-sidebar-hidden #sidebar{ transform: translateX(-100%); }
    html.pre-sidebar-hidden #header,
    html.pre-sidebar-hidden #content,
    html.pre-sidebar-hidden #footer{ margin-left:0 !important; }

    /* ================= LAYOUT ================= */
    html, body { height: 100%; }
    body{
      margin:0;
      font-family:'Segoe UI', sans-serif;
      background-color: var(--bg-color);
      color: var(--text-color);
      display:flex;
      flex-direction:column;
      min-height:100vh;
    }

    .sidebar{
      width:200px;
      height:100vh;
      background-color: var(--sidebar-bg);
      position: fixed;
      top:0; left:0;
      padding-top:20px;
      border-right:1px solid var(--sidebar-border);
      transition: transform 0.3s ease;
      z-index:1000;
      overflow: visible; /* NEW: so the edge toggle can hang outside */
    }
    .sidebar.hidden{ transform: translateX(-100%); }

    .sidebar a{
      display:block;
      padding:10px 20px;
      color: var(--text-color);
      text-decoration:none;
    }
    .sidebar a:hover, .sidebar a.active{
      background-color: var(--sidebar-border);
      color:white;
    }

    .header{
      margin-left:200px;
      background-color: var(--header-bg);
      color:white;
      padding:10px 20px;
      display:flex;
      justify-content:space-between;
      align-items:center;
      transition: margin-left 0.3s ease;
      z-index:900;
    }
    .header.shifted{ margin-left:0; }

    .toggle-btn{
      background:none;
      border:none;
      color:white;
      font-size:24px;
      cursor:pointer;
    }
    .toggle-btn:hover{ color:#ccc; }

    .darkmode-btn{
      background:#7c9562;
      border:none;
      color:white;
      padding:5px 15px;
      border-radius:5px;
      cursor:pointer;
      font-size:16px;
      margin:10px 20px;
      width: calc(100% - 40px);
    }
    .darkmode-btn:hover{ background:#66824e; }

    .content{
      margin-left:200px;
      padding:20px;
      transition: margin-left 0.3s ease;
      flex:1;
    }
    .content.shifted{ margin-left:0; }

    .dashboard{
      display:flex;
      justify-content:space-around;
      margin-bottom:20px;
      text-align:center;
      flex-wrap:wrap;
      gap:10px;
    }

    .card{
      background: var(--card-bg);
      padding:20px;
      border-radius:10px;
      width:30%;
      min-width:220px;
      box-shadow:0 2px 5px rgba(0,0,0,0.1);
      color: var(--text-color);
      cursor:pointer;
      transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
    }
    .card h3{ margin:0; font-size:18px; color:var(--muted-text); }
    .card p{ margin:10px 0 0; font-size:24px; font-weight:bold; color:var(--text-color); }
    .card:hover{
      transform: translateY(-8px) scale(1.03);
      box-shadow: var(--row-shadow);
      background:#fff;
    }
    .card.pending:hover  { background:#fff3cd; }
    .card.approved:hover { background:#d4edda; }
    .card.rejected:hover { background:#f8d7da; }

    h1{ text-align:center; }

    .info-card{
      background:#e0ddddff;
      border-radius:12px;
      padding:20px;
      text-align:left;
      box-shadow:0 4px 6px rgba(0,0,0,0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
      cursor:pointer;
      margin: 0 auto 25px auto;
      max-width: 1000px;
    }
    .info-card:hover{
      transform: translateY(-8px) scale(1.03);
      box-shadow: var(--row-shadow);
      background: #ffffff;
    }
    body.dark-mode .info-card{ background:#2b2b2b; color:#fff; }
    body.dark-mode .info-card:hover{ background:#3a3a3a; }

    .img-fluid{ width:100%; height:auto; margin-top:20px; border-radius:12px; }

    footer{
      background: var(--header-bg);
      color:#fff;
      text-align:center;
      padding:10px 16px;
      margin-left:200px;
      transition: margin-left 0.3s ease;
    }
    footer.shifted{ margin-left:0; }

    @media (max-width: 800px){
      .card{ width:100%; }
      .header h2{ display:none; }
    }

    /* ================= EDGE TOGGLE (NEW) ================= */
    .sidebar-edge-toggle{
      position:absolute;
      top:50%;
      right:-18px; /* hangs outside the sidebar edge */
      transform: translateY(-50%);
      width:36px;
      height:36px;
      display:flex;
      align-items:center;
      justify-content:center;
      border-radius:9999px;
      border:1px solid var(--edge-toggle-border);
      background: var(--edge-toggle-bg);
      box-shadow: var(--edge-toggle-shadow);
      cursor:pointer;
      transition: background .2s ease, box-shadow .2s ease, transform .2s ease;
      z-index: 1001; /* above sidebar */
    }
    .sidebar-edge-toggle:hover{
      background: var(--edge-toggle-hover);
      box-shadow: 0 4px 12px rgba(0,0,0,.2);
      transform: translateY(-50%) scale(1.05);
    }
    .sidebar-edge-toggle img{
      width:20px; height:20px; display:block;
      opacity:.85;
    }
    .sidebar.hidden .sidebar-edge-toggle{ display:none; } /* hide when sidebar hidden (header button still available) */
  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <div class="sidebar" id="sidebar">
    <a href="home.php" class="active">Home</a>
    <a href="scholarships.php">Available Scholarships</a>
    <a href="view_applications.php">Status Applications</a>
    <a href="contact.php">Contact</a>
    <a href="FAQ.php">FAQ</a>
    <a href="logout.php">Logout</a>
    <button class="darkmode-btn" id="modeToggle" type="button">🌙</button>

    <!-- NEW: Hide/Show toggle pinned to middle-right of the sidebar -->
    <button
      class="sidebar-edge-toggle"
      id="edgeToggle"
      type="button"
      title="Hide sidebar"
      aria-label="Hide sidebar"
    >
      <!-- Replace src with your actual icon location -->
      <img src="assets/hide_12252279.png" alt="Hide icon" onerror="this.style.display='none'; this.parentElement.textContent='⮜';" />
    </button>
  </div>

  <!-- HEADER -->
  <div class="header" id="header">
    <div style="display:flex; align-items:center; gap:10px;">
      <button class="toggle-btn" id="btnSidebar" type="button" title="Toggle Sidebar" aria-label="Toggle Sidebar">☰</button>
      <img src="tumblr_o4ti6j8k6r1unefbyo1_400.png" alt="Logo" style="height: 40px;" />
      <h2>Pamantasan ng Lungsod ng Muntinlupa</h2>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="content" id="content">
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

    <h1>Scholarship and Financial Assistance</h1>

    <div class="info-card">
      <h3>PLMun Educational Assistance Programs and Other Incentives</h3>
      <p>Ordinance No. 18-183 (2018) providing educational assistance and incentives.</p>
      <ul>
        <li>Academic Commitment for Excellence Program (ACE)</li>
        <li>International Student Training Exchange Program (I-STEP)</li>
        <li>Board Delivery for Outstanding Academic Results and Delivery (BOARD)</li>
        <li>Cultural, Athletics, ROTC Incentives (CARRI)</li>
        <li>Research and Excellence Award Program (CREATE)</li>
        <li>Scholars' Empowerment Assistance for Leadership (SEAL)</li>
        <li>PLMun Masteral Scholarship</li>
      </ul>
    </div>

    <div class="info-card">
      <h3>Partnership with Local Private Companies</h3>
      <p>Industry collaboration for financial support:</p>
      <ul>
        <li>Luis Co Chi Kiat (LCCK) Foundation</li>
        <li>Real Life Foundation Inc.</li>
        <li>Green Cross Foundation</li>
        <li>Lingap Adhikain Foundation</li>
        <li>Charity First Foundation</li>
        <li>How Good Foundation Inc.</li>
      </ul>
    </div>

    <div class="info-card">
      <h3>Student Assistance (SA) Program</h3>
      <p>Supports students via financial aid aligned with Labor Code standards.</p>
      <h4>Objectives</h4>
      <ol>
        <li>Support academic requirements aligned with PLMun’s vision.</li>
        <li>Provide financial help to parents of deserving students.</li>
        <li>Offer real-world office experience to students.</li>
      </ol>
      <img class="img-fluid" src="494357771_1889081168545182_276995763447389085_n.jpg" alt="Student Assistant">
      <h4>Application Process:</h4>
      <ul>
        <li>Initial screening by OSA-CSFA</li>
        <li>Endorsement to HRDMO</li>
        <li>Approval by VPSLD and HRDMO</li>
        <li>Deployment to offices</li>
      </ul>
    </div>
  </div>

  <!-- FOOTER -->
  <footer id="footer">
    2025 Pamantasan ng Lungsod ng Muntinlupa — All Rights Reserved
  </footer>

  <script>
    // Grab elements (null-safe)
    const sidebar    = document.getElementById('sidebar');
    const header     = document.getElementById('header');
    const content    = document.getElementById('content');
    const footer     = document.getElementById('footer');
    const btnSidebar = document.getElementById('btnSidebar');
    const modeToggle = document.getElementById('modeToggle');
    const edgeToggle = document.getElementById('edgeToggle'); // NEW

    function setEdgeToggleLabel(hidden){
      if (!edgeToggle) return;
      const label = hidden ? 'Show sidebar' : 'Hide sidebar';
      edgeToggle.setAttribute('title', label);
      edgeToggle.setAttribute('aria-label', label);
    }

    function applySavedStates() {
      try {
        // Theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        const isDark = savedTheme === 'dark';
        document.body.classList.toggle('dark-mode', isDark);
        if (modeToggle) modeToggle.textContent = isDark ? '☀️' : '🌙 ';

        // Sidebar
        const hidden = localStorage.getItem('sidebarHidden') === '1';
        if (sidebar) sidebar.classList.toggle('hidden', hidden);
        if (header)  header.classList.toggle('shifted', hidden);
        if (content) content.classList.toggle('shifted', hidden);
        if (footer)  footer.classList.toggle('shifted', hidden);
        setEdgeToggleLabel(hidden); // NEW
      } catch (e) {}
    }

    function toggleSidebar(){
      if (!sidebar) return;
      const nowHidden = !sidebar.classList.contains('hidden');
      sidebar.classList.toggle('hidden', nowHidden);
      if (header)  header.classList.toggle('shifted', nowHidden);
      if (content) content.classList.toggle('shifted', nowHidden);
      if (footer)  footer.classList.toggle('shifted', nowHidden);
      setEdgeToggleLabel(nowHidden); // NEW
      try { localStorage.setItem('sidebarHidden', nowHidden ? '1' : '0'); } catch(e){}
    }

    function toggleDarkMode(){
      const isDark = !document.body.classList.contains('dark-mode');
      document.body.classList.toggle('dark-mode', isDark);
      if (modeToggle) modeToggle.textContent = isDark ? '☀️' : '🌙';
      try { localStorage.setItem('theme', isDark ? 'dark' : 'light'); } catch(e){}
    }

    // Bind events (guard if elements are missing)
    if (btnSidebar) btnSidebar.addEventListener('click', toggleSidebar);
    if (edgeToggle) edgeToggle.addEventListener('click', toggleSidebar); // NEW
    if (modeToggle) modeToggle.addEventListener('click', toggleDarkMode);

    // Apply saved states once DOM is ready
    (function init(){
      applySavedStates();
      // Remove pre-paint classes (no longer needed after we hydrate)
      document.documentElement.classList.remove('pre-dark', 'pre-sidebar-hidden');
    })();
  </script>
</body>
</html>
<?php $conn->close(); ?>
