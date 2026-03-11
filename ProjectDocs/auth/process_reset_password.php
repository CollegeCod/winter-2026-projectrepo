<?php
require_once __DIR__ . "/../includes/session_manager.php";
require_once __DIR__ . "/../includes/csrf.php";
require_once __DIR__ . "/../includes/auth_functions.php";

start_secure_session();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: reset_password.php");
    exit();
}

validate_csrf_token($_POST["csrf_token"] ?? null);

$allowed =
    !empty($_SESSION["RESET_ALLOWED"]) &&
    !empty($_SESSION["RESET_USER_ID"]) &&
    !empty($_SESSION["RESET_ALLOWED_EXPIRES"]) &&
    time() <= (int) $_SESSION["RESET_ALLOWED_EXPIRES"];

if (!$allowed) {
    header("Location: forgot_email.php");
    exit();
}

$new_password = (string) ($_POST["new_password"] ?? "");
$confirm_password = (string) ($_POST["confirm_password"] ?? "");

if ($new_password !== $confirm_password) {
    header("Location: reset_password.php?error=mismatch");
    exit();
}

// password strength limiter
if (strlen($new_password) < 8) {
    header("Location: reset_password.php?error=weak");
    exit();
}

$user_id = (int) $_SESSION["RESET_USER_ID"];

$new_hash = password_hash($new_password, PASSWORD_DEFAULT);

update_user_password($user_id, $new_hash);
clear_reset_code($user_id);

// Clear reset session flags
unset($_SESSION["RESET_ALLOWED"]);
unset($_SESSION["RESET_USER_ID"]);
unset($_SESSION["RESET_ALLOWED_EXPIRES"]);
unset($_SESSION["RESET_EMAIL"]);

header("Location: ../login.php?status=reset_success");
exit();
