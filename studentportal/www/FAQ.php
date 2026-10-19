<?php
require_once __DIR__ . '/bootstrap.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <script src="js/preferences.js"></script>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PLMun Scholarship FAQs</title>

  <style>
    :root {
      --bg-color: #f4f4f4;
      --text-color: #000;
      --paragraph-color: #444;
      --card-bg: #f9d342;
      --header-bg: #004225;
      --sidebar-bg: #e6f0e6;
      --sidebar-border: #a0c0a0;

      /* Edge toggle (new) */
      --edge-toggle-bg: #ffffff;
      --edge-toggle-border: #cbd5e1;
      --edge-toggle-shadow: 0 2px 8px rgba(0,0,0,.15);
      --edge-toggle-hover: #f8fafc;
    }

    body.dark-mode {
      --bg-color: black;
      --text-color: #f0f0f0;
      --paragraph-color: #ccc;
      --card-bg: #333;
      --header-bg: #1a1a1a;
      --sidebar-bg: #1e2b1e;
      --sidebar-border: #2e472e;

      /* Edge toggle dark variant */
      --edge-toggle-bg: #1f2937;
      --edge-toggle-border: #374151;
      --edge-toggle-hover: #111827;
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
      overflow: visible; /* allow edge toggle to hang outside */
      z-index: 1000;
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
      z-index: 900;
      position: sticky;
      top: 0;
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

    .container {
      max-width: 1000px;
      margin: auto;
      padding: 0 20px;
    }

    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
    }

    /* Card Shadow Hover */
    .card {
      background: var(--card-bg);
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      cursor: pointer;
      transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
      color: var(--text-color);
    }

    .card:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 0 12px 20px rgba(0,0,0,0.2);
      background: #ffffff; /* Light mode hover */
    }

    body.dark-mode .card:hover { background: #2a2a2a; /* Dark mode hover */ }

    .card h3 { margin: 0; }

    .card p {
      display: none;
      font-size: 0.95rem;
      color: var(--paragraph-color);
      margin-top: 10px;
    }

    footer {
      text-align: center;
      padding: 20px;
      background-color: var(--header-bg);
      color: white;
      margin-top: 40px;
    }

    /* DARK MODE STYLING */
    body.dark-mode .card {
      background: #2b2b2b;
      color: #fff;
      transition: background 0.3s, box-shadow 0.3s;
    }

    body.dark-mode .card:hover {
      background: #3a3a3a;
      box-shadow: 0 12px 20px rgba(0, 255, 0, 0.5);
    }

    body.dark-mode table { background-color: #2b2b2b; }

    body.dark-mode table th,
    body.dark-mode table td {
      background-color: #3a3a3a;
      color: #fff;
      border-color: #555;
    }

    body.dark-mode table tbody tr:hover {
      background-color: #145a32;
      box-shadow: 0 8px 15px rgba(0, 255, 0, 0.5);
    }

    /* Light mode card hover for status dashboard */
    .card.pending:hover { background: #fff3cd; }
    .card.approved:hover { background: #d4edda; }
    .card.rejected:hover { background: #f8d7da; }

    /* Dark mode card hover for status dashboard */
    body.dark-mode .card.pending:hover { background: #665c00; }
    body.dark-mode .card.approved:hover { background: #145a32; }
    body.dark-mode .card.rejected:hover { background: #7b241c; }

    /* ===== Edge hide/show toggle button on sidebar (NEW) ===== */
    .sidebar-edge-toggle{
      position:absolute;
      top:50%;
      right:-18px;           /* hangs outside the edge */
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
      z-index: 1001;
    }
    .sidebar-edge-toggle:hover{
      background: var(--edge-toggle-hover);
      box-shadow: 0 4px 12px rgba(0,0,0,.2);
      transform: translateY(-50%) scale(1.05);
    }
    .sidebar-edge-toggle img{
      width:20px; height:20px; display:block; opacity:.9;
      transition: transform .2s ease;
    }
    .sidebar.hidden .sidebar-edge-toggle { display: none; } /* when sidebar hidden */
    /* ===== End edge toggle styles ===== */
  </style>
  <link rel="stylesheet" href="css/portal.css">
</head>
<body>
  <div class="sidebar" id="sidebar">
    <a href="home.php">Home</a>
    <a href="scholarships.php">Available Scholarships</a>
    <a href="view_applications.php">Status Applications</a>
    <a href="contact.php">Contact</a>
    <a href="FAQ.php" class="active">FAQ</a>
    <?= logout_form() ?>

    <!-- NEW: hide button pinned to middle-right of the sidebar -->
    <button
      class="sidebar-edge-toggle"
      id="edgeToggle"
      type="button"
      title="Hide sidebar"
      aria-label="Hide sidebar"
    >
      <!-- Use your uploaded icon; adjust path if needed -->
      <img src="img/hide.png" alt="Hide icon"
           onerror="this.style.display='none'; this.parentElement.textContent='⮜';" />
    </button>
  </div>

  <div class="header" id="header">
    <div style="display: flex; align-items: center;">
      <button class="toggle-btn" id="btnSidebar" type="button" title="Toggle Sidebar" aria-label="Toggle Sidebar">☰</button>
      <img src="img/tumblr_o4ti6j8k6r1unefbyo1_400.png" alt="Logo" style="height: 40px; margin: 0 10px;" />
      <h2>Pamantasan ng Lungsod ng Muntinlupa</h2>
    </div>
  </div>

  <div class="content" id="content">
    <main class="container">
      <h1 style="text-align:center; margin-bottom: 30px;">Scholarship FAQs</h1>
      <section class="cards">
        <!-- FAQs -->
        <div class="card" onclick="toggleAnswer(this)">
          <h3>Who can apply for a PLMun scholarship?</h3>
          <p>Scholarships are open to qualified residents of Muntinlupa who meet the academic, leadership, or special qualifications for each type of scholarship.</p>
        </div>
        <div class="card" onclick="toggleAnswer(this)">
          <h3>How do I apply for a scholarship?</h3>
          <p>Applications are usually submitted online or at the Office of Student Affairs. Watch for announcements on the PLMun website or social media.</p>
        </div>
        <div class="card" onclick="toggleAnswer(this)">
          <h3>Can I apply for more than one scholarship?</h3>
          <p>You may qualify for multiple programs, but only one scholarship may be awarded at a time unless otherwise stated.</p>
        </div>
        <div class="card" onclick="toggleAnswer(this)">
          <h3>What is the required GWA to qualify for the ACE scholarship?</h3>
          <p>You need a minimum GWA of 2.0 to qualify. Higher grades (1.75 and above) receive larger benefits.</p>
        </div>
        <div class="card" onclick="toggleAnswer(this)">
          <h3>Do I need to be a full-time student?</h3>
          <p>Yes, most scholarships require at least 21 enrolled units per semester to qualify.</p>
        </div>
        <div class="card" onclick="toggleAnswer(this)">
          <h3>What happens if I fail a subject?</h3>
          <p>Failing a subject may disqualify you from continuing or renewing your scholarship, depending on the scholarship's terms.</p>
        </div>
        <div class="card" onclick="toggleAnswer(this)">
          <h3>Is there a scholarship for graduate students?</h3>
          <p>Yes. The PLMun Masteral Scholarship is available to qualified Muntinlupa residents pursuing graduate degrees.</p>
        </div>
        <div class="card" onclick="toggleAnswer(this)">
          <h3>When is the application period?</h3>
          <p>The application period is announced before the start of each semester. Check the PLMun Facebook page or website for updates.</p>
        </div>
        <div class="card" onclick="toggleAnswer(this)">
          <h3>How can I maintain my scholarship?</h3>
          <p>Maintain your GPA and academic standing. Failing to meet requirements may result in disqualification.</p>
        </div>
        <div class="card" onclick="toggleAnswer(this)">
          <h3>Can my scholarship be transferred to another course or department?</h3>
          <p>Scholarships are generally tied to a course. Consult the Office of Student Affairs for exceptions.</p>
        </div>
        <div class="card" onclick="toggleAnswer(this)">
          <h3>Is there a deadline for scholarship renewal?</h3>
          <p>Yes, renewals must be submitted before each academic year begins. Late submissions won't be accepted.</p>
        </div>
        <div class="card" onclick="toggleAnswer(this)">
          <h3>What are the benefits of a PLMun scholarship?</h3>
          <p>Benefits vary per program and can include tuition coverage, stipends, and financial aid.</p>
        </div>
        <div class="card" onclick="toggleAnswer(this)">
          <h3>Are there scholarships for athletes or cultural achievers?</h3>
          <p>Yes, PLMun offers scholarships to athletes and cultural achievers who meet the criteria.</p>
        </div>
        <div class="card" onclick="toggleAnswer(this)">
          <h3>What should I do if I have more questions?</h3>
          <p>Visit the Office of Student Affairs or the Contact page for more info.</p>
        </div>
      </section>
    </main>
    <footer>
      &copy; 2025 Pamantasan ng Lungsod ng Muntinlupa — All Rights Reserved
    </footer>
  </div>

  <script>
    // Elements
    const sidebar = document.getElementById('sidebar');
    const header  = document.getElementById('header');
    const content = document.getElementById('content');
    const btnSidebar = document.getElementById('btnSidebar');
    const edgeToggle = document.getElementById('edgeToggle');

    function applySavedStates() {
      // Theme
      const savedTheme = portalStorage.getItem('theme');
      if (savedTheme === 'dark') document.body.classList.add('dark-mode');

      // Sidebar visibility
      const hidden = portalStorage.getItem('sidebarHidden') === '1';
      sidebar.classList.toggle('hidden', hidden);
      header.classList.toggle('shifted', hidden);
      content.classList.toggle('shifted', hidden);
    }

    function toggleSidebar() {
      const nowHidden = !sidebar.classList.contains('hidden');
      sidebar.classList.toggle('hidden', nowHidden);
      header.classList.toggle('shifted', nowHidden);
      content.classList.toggle('shifted', nowHidden);
      try { portalStorage.setItem('sidebarHidden', nowHidden ? '1' : '0'); } catch(e){}
    }

    function toggleAnswer(card) {
      const answer = card.querySelector("p");
      answer.style.display = answer.style.display === "block" ? "none" : "block";
    }

    // Expose for inline onclick in cards
    window.toggleAnswer = toggleAnswer;

    // Bind events
    if (btnSidebar) btnSidebar.addEventListener('click', toggleSidebar);
    if (edgeToggle) edgeToggle.addEventListener('click', toggleSidebar);

    // Init
    applySavedStates();

    // Optional: dark mode helpers (if you add a dark mode button somewhere)
    window.toggleDarkmode = function () {
      const isDark = !document.body.classList.contains("dark-mode");
      document.body.classList.toggle("dark-mode", isDark);
      try { portalStorage.setItem('theme', isDark ? 'dark' : 'light'); } catch(e){}
    };
  </script>
</body>
</html>
