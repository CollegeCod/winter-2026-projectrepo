<?php
require_once __DIR__ . "/../includes/session_manager.php";
require_once __DIR__ . "/../includes/csrf.php";
require_once __DIR__ . "/../includes/auth_functions.php";

start_secure_session();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /belles_training/auth/forgot_verify.php");
    exit();
}

validate_csrf_token($_POST["csrf_token"] ?? null);

$user_email = trim($_POST["user_email"] ?? "");
$reset_code = trim($_POST["reset_code"] ?? "");

$fail_redirect =
    "Location: /belles_training/auth/forgot_verify.php?error=invalid";

if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
    header($fail_redirect);
    exit();
}

if ($reset_code === "" || strlen($reset_code) !== 6) {
    header($fail_redirect);
    exit();
}

$reset_record = get_reset_record($user_email);

if (!$reset_record) {
    header($fail_redirect);
    exit();
}

$user_id = (int) $reset_record["USER_ID"];
$stored_hash = $reset_record["RESET_CODE_HASH"] ?? "";
$expires_at = $reset_record["RESET_CODE_EXPIRES_AT"] ?? "";
$attempts = (int) ($reset_record["RESET_CODE_ATTEMPTS"] ?? 0);

// Attempt limit
if ($attempts >= 5) {
    header($fail_redirect);
    exit();
}

// Must have a stored hash + expiry
if ($stored_hash === "" || $expires_at === "") {
    header($fail_redirect);
    exit();
}

// Expired?
if (time() > strtotime($expires_at)) {
    header($fail_redirect);
    exit();
}

// Verify code
if (!password_verify($reset_code, $stored_hash)) {
    increment_reset_attempts($user_id);
    header($fail_redirect);
    exit();
}

// Success
$_SESSION["RESET_ALLOWED"] = true;
$_SESSION["RESET_USER_ID"] = $user_id;
$_SESSION["RESET_ALLOWED_EXPIRES"] = time() + 10 * 60; // 10 min window to reset

header("Location: /belles_training/auth/reset_password.php");
exit();
