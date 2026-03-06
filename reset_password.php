<?php
require_once __DIR__ . "/../includes/session_manager.php";
require_once __DIR__ . "/../includes/csrf.php";
start_secure_session();

$allowed =
    !empty($_SESSION["RESET_ALLOWED"]) &&
    !empty($_SESSION["RESET_USER_ID"]) &&
    !empty($_SESSION["RESET_ALLOWED_EXPIRES"]) &&
    time() <= (int) $_SESSION["RESET_ALLOWED_EXPIRES"];

if (!$allowed) {
    header("Location: forgot_email.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | Belle's Training Solutions</title>

    <link rel="stylesheet" href="../css/auth.css">
</head>

<body>

<div class="login_container">

    <img class="auth_logo_img" src="../images/Belle_Logo.png" alt="Belle's Training Solutions">

    <h2>Reset Password</h2>
    <p class="auth_subtitle">Create a new password for your account</p>

    <form action="process_reset_password.php" method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars(get_csrf_token()); ?>"
        >

        <label>New Password</label>
        <input
            type="password"
            name="new_password"
            placeholder="Enter a new password"
            required
        >

        <label>Confirm Password</label>
        <input
            type="password"
            name="confirm_password"
            placeholder="Re-enter your new password"
            required
        >

        <button type="submit">Update Password</button>
    </form>

    <p><a href="../login.php">Back to Login</a></p>

</div>

</body>
</html>
