<?php
require_once __DIR__ . "/includes/session_manager.php";
start_secure_session();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login | Belle's Training Solutions</title>

    <link rel="stylesheet" href="css/auth.css">
</head>

<body>

<div class="login_container">

    <!-- Logo -->
    <img class="auth_logo_img" src="images/Belle_Logo.png">

    <!-- Title -->
    <h2>Welcome Back</h2>
    <p class="auth_subtitle">Login to your account</p>

    <!-- Login Form -->
    <form action="auth/process_login.php" method="POST">

        <label>Email</label>
        <input
            type="email"
            name="user_email"
            placeholder="Enter your email"
            required
        >

        <label>Password</label>
        <input
            type="password"
            name="user_password"
            placeholder="Enter your password"
            required
        >

        <button type="submit">Login</button>

    </form>

    <!-- Forgot Password -->
    <p>
        <a href="auth/forgot_email.php">Forgot Password?</a>
    </p>

</div>

</body>
</html>
