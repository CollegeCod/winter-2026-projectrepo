<?php
require_once __DIR__ . "/../includes/session_manager.php";
require_once __DIR__ . "/../includes/csrf.php";
require_once __DIR__ . "/../includes/auth_functions.php";
require_once __DIR__ . "/../includes/mail_sender.php";

start_secure_session();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: forgot_email.php");
    exit();
}

validate_csrf_token($_POST["csrf_token"] ?? null);

$user_email = trim($_POST["user_email"] ?? "");

if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
    header("Location: forgot_email.php?status=sent");
    exit();
}

/**
 * Always respond with a generic success message.
 */
$user_record = get_user_by_email_for_reset($user_email);

if ($user_record) {
    // 5-minute resend cooldown
    //
    // todo
    // sends a code, but sees code as invalid for 5 minutes
    $last_sent = $user_record["RESET_CODE_LAST_SENT_AT"];
    if ($last_sent) {
        $seconds_since = time() - strtotime($last_sent);
        if ($seconds_since < 300) {
            header("Location: forgot_email.php?status=sent");
            exit();
        }
    }

    // Generate 6-digit code
    $reset_code = str_pad((string) random_int(0, 999999), 6, "0", STR_PAD_LEFT);

    // Hash it
    $reset_code_hash = password_hash($reset_code, PASSWORD_DEFAULT);

    // Expiry 15 minutes
    //
    // todo
    // doesnt work, sets a 6 hour expiry for some reason
    $expires_at_mysql = date("Y-m-d H:i:s", time() + 900);

    set_reset_code_for_user(
        (int) $user_record["USER_ID"],
        $reset_code_hash,
        $expires_at_mysql,
    );

    // Send email
    send_reset_code_email($user_email, $reset_code);
}

/**
 * Redirect to verify page
 */
$_SESSION["RESET_EMAIL"] = $user_email;
header("Location: forgot_verify.php?status=sent");
exit();
