<?php
require_once __DIR__ . "/../includes/session_manager.php";
require_once __DIR__ . "/../includes/csrf.php";
start_secure_session();

$prefill_email = $_SESSION["RESET_EMAIL"] ?? "";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Verify Code | Belle's Training Solutions</title>

    <link rel="stylesheet" href="../css/auth.css">
</head>

<body>

<div class="login_container">

    <img class="auth_logo_img" src="../images/Belle_Logo.png" alt="Belle's Training Solutions">

    <h2>Verification</h2>
    <p class="auth_subtitle">Enter the verification code sent to your email</p>

    <form action="process_forgot_verify.php" method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars(get_csrf_token()); ?>"
        >

        <label>Email</label>
        <input
            type="email"
            name="user_email"
            value="<?php echo htmlspecialchars($prefill_email); ?>"
            readonly
        >

        <label>Verification Code</label>
        <input
            type="text"
            name="reset_code"
            placeholder="Enter verification code"
            maxlength="6"
            required
        >

        <button type="submit">Verify Code</button>

    </form>

    <p>
        <a href="forgot_email.php">Resend Code</a>
    </p>

    <p>
        <a href="../login.php">Back to Login</a>
    </p>

</div>

</body>
</html>
