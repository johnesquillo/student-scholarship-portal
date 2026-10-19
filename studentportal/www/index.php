<?php

    require_once __DIR__ . '/bootstrap.php';
    if (!empty($_SESSION['user_id'])) {
        redirect_to(in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true) ? 'manage_applications.php' : 'home.php');
    }

    $errors = [
        'login' => $_SESSION['login_error'] ?? '',
        'register' => $_SESSION['register_error'] ?? ''
    ];

    $activeForm = $_SESSION['active_form'] ?? (input_value($_GET, 'form') === 'register' ? 'register' : 'login');
    $registrationType = $_SESSION['register_account_type'] ?? 'student';

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
    <title>Student Scholarship Portal</title>
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
                <h2>Student Login</h2>
                <?= showError($errors['login']); ?>
                <?php if ($notice): ?><p class="form-feedback"><?= e($notice) ?></p><?php endif; ?>
                <p>Student Scholarship Portal</p>
                <p>For students and scholarship applicants</p>
                <p><a href="admin_login.php">Admin / Staff Login</a></p>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" maxlength="72" required>
                <button type="submit" name="login">Login</button>
                <p>Do you have an account? <a href="#" onclick="showForm('register-form')">Register</a></p>
            </form>
        </div>
         <div class="container">
        <div class="form-box <?= isActiveForm('register', $activeForm); ?>" id="register-form">
            <div class="header-logo">
                <img src="img/tumblr_o4ti6j8k6r1unefbyo1_400.png" alt="PLMun Logo">
            </div>

            <form action="login_register.php" method="post">
                <?= csrf_field() ?>
                <h2>Create an Account</h2>
                <?= showError($errors['register']); ?>
                <p>Student Scholarship Portal</p>
                <label class="field-label" for="register-account-type">Registering as</label>
                <select id="register-account-type" name="account_type" aria-describedby="account-type-help" required>
                    <option value="student" <?= $registrationType === 'student' ? 'selected' : '' ?>>Student</option>
                    <option value="admin" <?= $registrationType === 'admin' ? 'selected' : '' ?>>Admin (activation required)</option>
                </select>
                <p class="account-type-help" id="account-type-help">Admin access must be activated by the scholarship office after registration.</p>

                <label class="field-label" for="register-name">Full Name</label>
                <input id="register-name" type="text" name="name" placeholder="Name" maxlength="100" autocomplete="name" required>
                <label class="field-label" for="register-email">Email Address</label>
                <input id="register-email" type="email" name="email" placeholder="Email" autocomplete="email" required>
                <label class="field-label" for="register-password">Password</label>
                <input id="register-password" type="password" name="password" placeholder="Password" minlength="8" maxlength="72" autocomplete="new-password" required>

                <p>Use a password of 8 to 72 bytes.</p>
                <button type="submit" name="register">Register</button>
                <p>Already have an account? <a href="#" onclick="showForm('login-form')">Login</a></p>
            </form>
        </div>
       </div>
        <script src="js/script.js"></script>
</body>
</html>
