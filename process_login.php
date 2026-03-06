<?php

require_once "../includes/session_manager.php";
require_once "../includes/auth_functions.php";

start_secure_session();

/**
 * Validate login request
 */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit();
}

$user_email = $_POST["user_email"];
$user_password = $_POST["user_password"];

/**
 * Get user record
 */

$user_record = get_user_by_email($user_email);

if (!$user_record) {
    header("Location: ../login.php?error=invalid_login");
    exit();
}

$user_id = $user_record["USER_ID"];
$user_permission = $user_record["PERM_ID"];

/**
 * Fetch stored password hash
 */

$password_hash = get_user_password_hash($user_id);

if (!$password_hash) {
    header("Location: ../login.php?error=invalid_login");
    exit();
}

/**
 * Verify password
 */

$password_valid = verify_user_password($user_password, $password_hash);

if (!$password_valid) {
    header("Location: ../login.php?error=invalid_login");
    exit();
}

/**
 * Login successful
 */

session_regenerate_id(true);

$_SESSION["USER_ID"] = $user_id;
$_SESSION["PERM_ID"] = $user_permission;

header("Location: ../dashboard.php");
exit();

?>
