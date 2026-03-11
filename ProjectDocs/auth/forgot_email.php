<?php
require_once __DIR__ . "/../includes/session_manager.php";
require_once __DIR__ . "/../includes/csrf.php";
start_secure_session();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password | Belle's Training Solutions</title>

    <link rel="stylesheet" href="../css/auth.css">
</head>

<body>

<div class="login_container">

    <img class="auth_logo_img" src="../images/Belle_Logo.png" alt="Belle's Training Solutions">

    <h2>Forgot Password</h2>
    <p class="auth_subtitle">Enter your email to receive a verification code</p>

    <form action="process_forgot_email.php" method="POST">
        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars(get_csrf_token()); ?>"
        >

        <label>Email</label>
        <input
            type="email"
            name="user_email"
            placeholder="Enter your email"
            required
        >

        <button type="submit">Send Code</button>
    </form>

    <p><a href="../login.php">Back to Login</a></p>

</div>

</body>
</html>
