<?php

    require_once __DIR__ . '/bootstrap.php';
    if (!empty($_SESSION['user_id'])) {
        redirect_to(in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true) ? 'manage_applications.php' : 'home.php');
    }

    $errors = [
        'login' => $_SESSION['login_error'] ?? '',
        'register' => $_SESSION['register_error'] ?? ''
    ];

    $activeForm = $_SESSION['active_form'] ?? 'login';

    $notice = $_SESSION['login_notice'] ?? '';
    unset($_SESSION['login_error'], $_SESSION['register_error'], $_SESSION['active_form'], $_SESSION['login_notice']);

    function showError($error) {
        return !empty($error) ? "<p class='error-message'>" . e($error) . "</p>" : '';
    }

    function isActiveForm($formName, $activeForm) {
        return $formName === $activeForm ? 'active' : '';
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin / Staff Login</title>
    <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/portal.css">
</head>
<body>
   <div class="container">
        <div class="form-box <?= isActiveForm('login', $activeForm); ?>" id="login-form">
            <div class="header-logo">
                <img src="img/tumblr_o4ti6j8k6r1unefbyo1_400.png" alt="PLMun Logo">
            </div>

            <form action="login_register.php" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="login_context" value="admin">
                <h2>Admin / Staff Login</h2>
                <?= showError($errors['login']); ?>
                <?php if ($notice): ?><p class="form-feedback"><?= e($notice) ?></p><?php endif; ?>
                <p>Admin / Staff</p>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" maxlength="72" required>
                <button type="submit" name="login">Login</button>

            </form>
        </div>
        <p><a href="index.php">Student Login</a></p>
   </div>
        <script src="js/script.js"></script>
</body>
</html>
