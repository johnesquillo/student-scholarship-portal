<?php
require_once __DIR__ . '/bootstrap.php';
require_login();
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $showModal = true;
} else {
    $showModal = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <script src="js/preferences.js"></script>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PLMun Scholarship Contact</title>
  <style>
    :root {
        --bg-color: #dfd9d9ff;
        --text-color: #000;
        --sidebar-bg: #e6f0e6;
        --sidebar-border: #a0c0a0;
        --header-bg: #004225;
        --card-bg: #ffffff;
        --modal-bg: #fff;
        --modal-text: #000;

        /* Toggle buttons */
        --edge-toggle-bg: #ffffff;
        --edge-toggle-border: #cbd5e1;
        --edge-toggle-shadow: 0 2px 8px rgba(0,0,0,.15);
        --edge-toggle-hover: #f8fafc;
    }

    body.dark-mode {
        --bg-color: #000;
        --text-color: #f0f0f0;
        --sidebar-bg: #1e2b1e;
        --sidebar-border: #2e472e;
        --header-bg: #000;
        --card-bg: #1a1a1a;
        --modal-bg: #1e1e1e;
        --modal-text: #f0f0f0;

        --edge-toggle-bg: #1f2937;
        --edge-toggle-border: #374151;
        --edge-toggle-hover: #111827;
    }

    body {
        background-color: var(--bg-color);
        color: var(--text-color);
        margin: 0;
        font-family: 'Segoe UI', sans-serif;
    }

    /* Sidebar */
    .sidebar {
        width: 200px;
        height: 100vh;
        background-color: var(--sidebar-bg);
        position: fixed;
        inset: 0 auto 0 0;
        padding-top: 20px;
        border-right: 1px solid var(--sidebar-border);
        transition: transform 0.3s ease;
        overflow: visible;
        z-index: 1000;
    }
    .sidebar.hidden { transform: translateX(-100%); }

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

    /* Hide button inside sidebar */
    .sidebar-edge-toggle{
      position:absolute;
      top:50%;
      right:-18px;
      transform: translateY(-50%);
      width:36px;
      height:36px;
      display:flex;
      align-items:center;
      justify-content:center;
      border-radius:50%;
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
    }
    .sidebar.hidden .sidebar-edge-toggle { display:none; }

    /* Show button */
    .show-sidebar-btn{
      position: fixed;
      top: 50%;
      left: 12px;
      transform: translateY(-50%);
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border:1px solid var(--edge-toggle-border);
      background: var(--edge-toggle-bg);
      box-shadow: var(--edge-toggle-shadow);
      cursor: pointer;
      display: none;
      z-index: 1200;
      align-items:center;
      justify-content:center;
      padding:0;
      transition: background .2s ease, box-shadow .2s ease, transform .2s ease;
    }
    .show-sidebar-btn:hover{
      background: var(--edge-toggle-hover);
      box-shadow: 0 4px 12px rgba(0,0,0,.2);
      transform: translateY(-50%) scale(1.05);
    }
    .show-sidebar-btn img{ width:22px; height:22px; display:block; transform: rotate(180deg); }

    /* Main content */
    .main-content {
        margin-left: 200px;
        padding: 20px;
        transition: margin-left 0.3s ease;
    }
    .main-content.shifted { margin-left: 0; }

    header {
        background: var(--header-bg);
        color: white;
        text-align: center;
        padding: 30px 20px;
    }
    header img { width: 80px; height: 80px; margin-bottom: 10px; }
    header h1 { margin: 0; font-size: 1.8rem; }

    /* Contact info */
    .contact-info {
        background: var(--card-bg);
        border: 2px solid var(--sidebar-border);
        margin: 30px auto 20px;
        padding: 25px;
        max-width: 800px;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        text-align: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
    }
    .contact-info h2 { color: #006d38; margin-bottom: 15px; }
    .contact-info p { font-size: 1rem; margin: 6px 0; color: var(--text-color); }
    .contact-info:hover{
      transform: translateY(-6px) scale(1.01);
      box-shadow: 0 12px 20px rgba(0, 128, 0, 0.3);
    }

    /* Form container */
    .container {
        max-width: 600px;
        background: var(--card-bg);
        padding: 30px;
        margin: 30px auto;
        border-radius: 12px;
        box-shadow: 0 0 15px rgba(0,0,0,0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
    }
    .container h2 { margin-bottom: 20px; color: #006d38; text-align: center; }
    .container:hover{
      transform: translateY(-6px) scale(1.01);
      box-shadow: 0 12px 20px rgba(0, 255, 0, 0.3);
      /* background removed */
    }

    label { display: block; margin-top: 15px; font-weight: 600; color: var(--text-color); }
    input, textarea {
        width: 100%;
        padding: 12px;
        margin-top: 5px; margin-bottom: 20px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 1rem;
        box-sizing: border-box;
        background-color: #fff;
        color: #000;
    }
    body.dark-mode input, body.dark-mode textarea {
        background-color: #1e1e1e; color: #f0f0f0; border: 1px solid #444;
    }

    button {
        margin-top: 20px;
        background: #006d38;
        color: white;
        border: none;
        padding: 12px 20px;
        font-size: 1rem;
        border-radius: 8px;
        cursor: pointer;
        width: 100%;
        transition: background 0.3s;
    }
    button:hover { background: #004d2a; }

    /* Modal */
    #successModal {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
        z-index: 2000;
    }
    #successModal .modal-content {
        background: var(--modal-bg);
        color: var(--modal-text);
        padding: 30px;
        border-radius: 12px;
        max-width: 400px;
        text-align: center;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }
    #successModal .modal-content h2 { color: #00b350; margin-bottom: 10px; }
    #successModal .modal-content button {
        margin-top: 20px;
        padding: 10px 20px;
        background-color: #006d38;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        cursor: pointer;
    }
    #successModal .modal-content button:hover { background-color: #004d2a; }
  </style>
  <link rel="stylesheet" href="css/portal.css">
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <a href="home.php">Home</a>
    <a href="scholarships.php">Available Scholarships</a>
    <a href="view_applications.php">Status Applications</a>
    <a href="contact.php" class="active">Contact</a>
    <a href="FAQ.php">FAQ</a>
    <?= logout_form() ?>

    <button class="sidebar-edge-toggle" id="hideBtn" type="button" title="Hide sidebar" aria-label="Hide sidebar">
      <img src="img/hide.png" alt="Hide">
    </button>
</div>

<button class="show-sidebar-btn" id="showBtn" type="button" title="Show sidebar" aria-label="Show sidebar">
  <img src="img/hide.png" alt="Show">
</button>

<div class="main-content" id="mainContent">
    <header>
        <img src="img/tumblr_o4ti6j8k6r1unefbyo1_400.png" alt="PLMun Logo">
        <h1>Pamantasan ng Lungsod ng Muntinlupa</h1>
        <p>Scholarship Portal</p>
    </header>

    <div class="contact-info">
        <h2>Contact Information</h2>
        <p><strong>Email:</strong> plmuncommunications@gmail.com</p>
        <p><strong>Phone:</strong> (02) 8864 8654</p>
        <p><strong>Address:</strong> PLMun Main Campus, NBP Reservation, Poblacion, Muntinlupa City</p>
    </div>

    <div class="container">
        <h2>Send Us a Message</h2>
        <form action="submit_contact.php" method="POST">
            <?= csrf_field() ?>
            <?php if (isset($_GET['error'])): ?><p class="form-feedback" role="alert">Your message was not sent. Check the fields and try again.</p><?php endif; ?>
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" maxlength="100" required>

            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required>

            <label for="message">Your Message</label>
            <textarea id="message" name="message" rows="5" maxlength="5000" required></textarea>

            <button type="submit">Send Message</button>
        </form>
    </div>
</div>

<div id="successModal">
    <div class="modal-content">
        <h2>✅ Message Sent!</h2>
        <p>Your message has been successfully sent. Thank you for contacting us!</p>
        <button onclick="document.getElementById('successModal').style.display='none'">Close</button>
    </div>
</div>

<script>
    const sidebar     = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const hideBtn     = document.getElementById('hideBtn');
    const showBtn     = document.getElementById('showBtn');

    function syncShowButton() {
        showBtn.style.display = sidebar.classList.contains('hidden') ? 'flex' : 'none';
    }

    function applySavedStates() {
        const savedTheme = portalStorage.getItem('theme');
        if (savedTheme === 'dark') document.body.classList.add('dark-mode');

        const saved = portalStorage.getItem('sidebarHidden');
        if (saved === null) {
            const onMobile = window.matchMedia('(max-width: 820px)').matches;
            sidebar.classList.toggle('hidden', onMobile);
            mainContent.classList.toggle('shifted', onMobile);
            try { portalStorage.setItem('sidebarHidden', onMobile ? '1' : '0'); } catch(e){}
        } else {
            const hidden = saved === '1';
            sidebar.classList.toggle('hidden', hidden);
            mainContent.classList.toggle('shifted', hidden);
        }
        syncShowButton();
    }

    function hideSidebar() {
        sidebar.classList.add('hidden');
        mainContent.classList.add('shifted');
        try { portalStorage.setItem('sidebarHidden', '1'); } catch(e){}
        syncShowButton();
    }

    function showSidebar() {
        sidebar.classList.remove('hidden');
        mainContent.classList.remove('shifted');
        try { portalStorage.setItem('sidebarHidden', '0'); } catch(e){}
        syncShowButton();
    }

    hideBtn.addEventListener('click', hideSidebar, { passive: true });
    showBtn.addEventListener('click', showSidebar, { passive: true });

    window.addEventListener('resize', syncShowButton, { passive: true });

    window.addEventListener('DOMContentLoaded', () => {
        applySavedStates();

        <?php if ($showModal): ?>
            document.getElementById("successModal").style.display = "flex";
            window.history.replaceState({}, document.title, window.location.pathname);
        <?php endif; ?>
    });
</script>

</body>
</html>
