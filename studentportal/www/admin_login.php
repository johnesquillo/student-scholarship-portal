<?php

    session_start();

    $errors = [
        'login' => $_SESSION['login_error'] ?? '',
        'register' => $_SESSION['register_error'] ?? ''
    ];

    $activeForm = $_SESSION['active_form'] ?? 'login';

    session_unset();

    function showError($error) {
        return !empty($error) ? "<p class='error-message'>$error</p>" : '';
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
    <title>Admin Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
   <div class="container">
        <div class="form-box <?= isActiveForm('login', $activeForm); ?>" id="login-form">
            <div class="header-logo">
                <img src="tumblr_o4ti6j8k6r1unefbyo1_400.png" alt="PLMun Logo">
            </div>

            <form action="login_register.php" method="post">
                <h2>Admin Dashboard</h2>
                <?= showError($errors['login']); ?>
                <p>Admin</p>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
                
            </form>
        </div>
         <div class="container">
        <div class="form-box <?= isActiveForm('register', $activeForm); ?>" id="register-form">
            <div class="header-logo">
                <img src="tumblr_o4ti6j8k6r1unefbyo1_400.png" alt="PLMun Logo">
            </div>

           
        </div>
       </div>
        <script src="script.js"></script>
</body>
</html>
