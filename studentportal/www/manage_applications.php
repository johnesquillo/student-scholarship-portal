<?php
require_once __DIR__ . '/admin_auth.php';
require_admin_access();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = input_value($_POST, 'action');
    $uid = filter_var(input_value($_POST, 'user_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $sid = filter_var(input_value($_POST, 'scholarship_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$uid || !$sid || !in_array($action, ['remove', 'reapply'], true)) {
        http_response_code(400);
        exit('Invalid application action.');
    }
}

require __DIR__ . '/db.php';

// === HANDLE REMOVE ===
// Expect user_id + scholarship_id (composite key)
if ($_SERVER["REQUEST_METHOD"] === "POST" 
    && isset($_POST['action']) 
    && $_POST['action'] === 'remove' 
    && isset($_POST['user_id'], $_POST['scholarship_id'])) 
{
    $uid = (int)$_POST['user_id'];
    $sid = (int)$_POST['scholarship_id'];
    $stmt = $conn->prepare("DELETE FROM applications WHERE user_id = ? AND scholarship_id = ?");
    $stmt->bind_param("ii", $uid, $sid);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    if (!$affected) {
        http_response_code(404);
        exit('Application not found.');
    }
    redirect_to('manage_applications.php');
}

// === HANDLE RE-APPLY ===
// Expect user_id + scholarship_id (composite key)
if ($_SERVER["REQUEST_METHOD"] === "POST" 
    && isset($_POST['action']) 
    && $_POST['action'] === 'reapply' 
    && isset($_POST['user_id'], $_POST['scholarship_id'])) 
{
    $uid = (int)$_POST['user_id'];
    $sid = (int)$_POST['scholarship_id'];
    $stmt = $conn->prepare("UPDATE applications SET status = 'Pending' WHERE user_id = ? AND scholarship_id = ? AND status = 'Rejected'");
    $stmt->bind_param("ii", $uid, $sid);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    if (!$affected) {
        http_response_code(404);
        exit('Rejected application not found.');
    }
    redirect_to('manage_applications.php');
}

// === FETCH APPLICATIONS SEPARATELY ===
$all_result      = $conn->query("SELECT * FROM applications ORDER BY application_date DESC");
$approved_result = $conn->query("SELECT * FROM applications WHERE status = 'Approved' ORDER BY application_date DESC");
$rejected_result = $conn->query("SELECT * FROM applications WHERE status = 'Rejected' ORDER BY application_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Applications - PLMun</title>
  <style>
    body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
    header { background-color: #004225; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
    header h1 { margin: 0; display: flex; align-items: center; font-family: 'Libre Baskerville', serif; font-size: 24px; letter-spacing: 1px; }
    header h1 img { height: 50px; margin-right: 15px; }
    nav a { color: white; margin: 0 15px; text-decoration: none; font-weight: bold; }
    .container { padding: 20px; }
    .table-section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 12px; text-align: center; }
    th { background-color: #004225; color: white; }
    .btn { padding: 6px 12px; margin: 2px; border: none; cursor: pointer; border-radius: 4px; }
    .approve { background-color: #28a745; color: white; }
    .reject  { background-color: #dc3545; color: white; }
    .remove  { background-color: #6c757d; color: white; }
    .reapply { background-color: #ffc107; color: black; }
    .requirements { background-color: #004225; color: white; display: inline-block; text-decoration: none; }
    .table-section { overflow-x: auto; }
    .pending { color: orange; } .approved { color: green; } .rejected { color: red; }
    h2 { color: #004225; }
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
      <a href="#all">Applications</a>
      <a href="#approved">Approved</a>
      <a href="#rejected">Rejected</a>
      <a href="inbox.php">Messages</a>
      <?= logout_form('admin_logout.php') ?>
    </nav>
  </header>

  <div class="container">
    <!-- All Applications -->
    <div class="table-section" id="all">
      <h2>📄 Manage Student Applications (All)</h2>
      <table>
        <thead>
          <tr>
            <th>Scholarship ID</th>
            <th>User ID</th>
            <th>Student</th>
            <th>Course</th>
            <th>Application Date</th>
            <th>Status</th>
            <th>Requirements</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php while($row = $all_result->fetch_assoc()): ?>
          <tr>
            <td><?= (int)$row['scholarship_id'] ?></td>
            <td><?= (int)$row['user_id'] ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['course']) ?></td>
            <td><?= htmlspecialchars($row['application_date']) ?></td>
            <td class="<?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></td>
            <td><a class="btn requirements" href="application_requirements.php?user_id=<?= (int)$row['user_id'] ?>&amp;scholarship_id=<?= (int)$row['scholarship_id'] ?>">View Requirements</a></td>
            <td>
              <?php if ($row['status'] === 'Pending'): ?>
                <button class="btn approve" onclick="updateStatus(<?= (int)$row['user_id'] ?>, <?= (int)$row['scholarship_id'] ?>, 'Approved')">Approve</button>
                <button class="btn reject"  onclick="updateStatus(<?= (int)$row['user_id'] ?>, <?= (int)$row['scholarship_id'] ?>, 'Rejected')">Reject</button>
              <?php endif; ?>

              <form method="POST" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= (int)$row['user_id'] ?>">
                <input type="hidden" name="scholarship_id" value="<?= (int)$row['scholarship_id'] ?>">
                <button type="submit" name="action" value="remove" class="btn remove" onclick="return confirm('Remove this application?');">Remove</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- Approved Applications -->
    <div class="table-section" id="approved">
      <h2>✅ Approved Applications</h2>
      <table>
        <thead>
          <tr>
            <th>Scholarship ID</th>
            <th>User ID</th>
            <th>Student</th>
            <th>Course</th>
            <th>Application Date</th>
            <th>Requirements</th>
          </tr>
        </thead>
        <tbody>
        <?php while($row = $approved_result->fetch_assoc()): ?>
          <tr>
            <td><?= (int)$row['scholarship_id'] ?></td>
            <td><?= (int)$row['user_id'] ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['course']) ?></td>
            <td><?= htmlspecialchars($row['application_date']) ?></td>
            <td><a class="btn requirements" href="application_requirements.php?user_id=<?= (int)$row['user_id'] ?>&amp;scholarship_id=<?= (int)$row['scholarship_id'] ?>">View Requirements</a></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- Rejected Applications -->
    <div class="table-section" id="rejected">
      <h2>❌ Rejected Applications</h2>
      <table>
        <thead>
          <tr>
            <th>Scholarship ID</th>
            <th>User ID</th>
            <th>Student</th>
            <th>Course</th>
            <th>Application Date</th>
            <th>Requirements</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php while($row = $rejected_result->fetch_assoc()): ?>
          <tr>
            <td><?= (int)$row['scholarship_id'] ?></td>
            <td><?= (int)$row['user_id'] ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['course']) ?></td>
            <td><?= htmlspecialchars($row['application_date']) ?></td>
            <td><a class="btn requirements" href="application_requirements.php?user_id=<?= (int)$row['user_id'] ?>&amp;scholarship_id=<?= (int)$row['scholarship_id'] ?>">View Requirements</a></td>
            <td>
              <form method="POST" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= (int)$row['user_id'] ?>">
                <input type="hidden" name="scholarship_id" value="<?= (int)$row['scholarship_id'] ?>">
                <button type="submit" name="action" value="reapply" class="btn reapply" onclick="return confirm('Return this application to pending review?');">Return to Pending</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

<script>
function updateStatus(user_id, scholarship_id, status) {
  if (!confirm(`Are you sure you want to ${status.toLowerCase()} this application?`)) return;

  fetch('update_status.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
    body: new URLSearchParams({user_id, scholarship_id, status, csrf_token: <?= json_encode(csrf_token()) ?>})
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      alert(`Application ${status.toLowerCase()} successfully.`);
      location.reload();
    } else {
      alert(`Failed to update status: ${data.message || 'Unknown error'}`);
    }
  })
  .catch(err => {
    console.error('Error:', err);
    alert('An error occurred while updating the status.');
  });
}
</script>

</body>
</html>

<?php $conn->close(); ?>
