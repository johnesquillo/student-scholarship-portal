<?php
require_once __DIR__ . '/admin_auth.php';
require_admin_access();

require __DIR__ . '/db.php';

// ✅ Use the correct table name: contact_message
$sql = "SELECT id, name, email, message, created_at
        FROM contact_message
        ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inbox</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f4f4f4;
      margin: 0;
      padding: 0;
    }
    header {
      background-color: #004225;
      color: white;
      padding: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    header h1 {
      margin: 0;
      display: flex;
      align-items: center;
      font-family: 'Libre Baskerville', serif;
      font-size: 24px;
      letter-spacing: 1px;
    }
    header h1 img {
      height: 50px;
      margin-right: 15px;
    }
    nav a {
      color: white;
      margin: 0 15px;
      text-decoration: none;
      font-weight: bold;
    }
    .container {
      padding: 20px;
    }
    .table-section {
      background: white;
      padding: 20px;
      margin-bottom: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 12px;
      text-align: left;
      vertical-align: top;
    }
    th {
      background-color: #004225;
      color: white;
    }
    .actions a {
      margin-right: 6px;
      padding: 5px 10px;
      background-color: #007bff;
      color: white;
      text-decoration: none;
      border-radius: 4px;
    }
    .actions a.delete {
      background-color: #dc3545;
    }
    .actions a:hover {
      opacity: 0.9;
    }
    .error-message {
      padding: 10px;
      color: white;
      text-align: center;
      margin-bottom: 20px;
      background-color: #dc3545;
      border-radius: 6px;
    }
    h2 {
      color: #004225;
      margin-top: 0;
    }
  </style>
  <link rel="stylesheet" href="css/portal.css">
</head>
<body>
  <!-- Header Section -->
  <header>
    <h1>
      <img src="img/tumblr_o4ti6j8k6r1unefbyo1_400.png" alt="PLMun Logo">
      PLMun Scholarship Admin
    </h1>
    <nav>
      <a href="manage_applications.php">Home</a>
      <?= logout_form('admin_logout.php') ?>
    </nav>
  </header>

  <!-- Main Content -->
  <div class="container">
    <?php if (isset($_GET['status']) && $_GET['status'] === 'error'): ?>
      <div class="error-message">There was an error deleting the message.</div>
    <?php endif; ?>

    <div class="table-section">
      <h2>📬 Inbox - Contact Messages</h2>
      <table>
        <thead>
          <tr>
            <th style="width:60px;">ID</th>
            <th style="width:180px;">Name</th>
            <th style="width:220px;">Email</th>
            <th>Message</th>
            <th style="width:180px;">Submitted At</th>
            <th style="width:100px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= (int)$row['id']; ?></td>
                <td><?= htmlspecialchars($row['name']); ?></td>
                <td><?= htmlspecialchars($row['email']); ?></td>
                <td><?= nl2br(htmlspecialchars($row['message'])); ?></td>
                <td><?= htmlspecialchars($row['created_at']); ?></td>
                <td class="actions">
                  <form action="delete_message.php" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <button type="submit" onclick="return confirm('Delete this message?');">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="6">No messages found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>

<?php $conn->close(); ?>
