<?php
require_once __DIR__ . "/session_manager.php";

/**
 * get_csrf_token
 * Generates a CSRF token and stores it in session
 *
 * @return string
 */
function get_csrf_token()
{
    start_secure_session();

    if (empty($_SESSION["CSRF_TOKEN"])) {
        $_SESSION["CSRF_TOKEN"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["CSRF_TOKEN"];
}

/**
 * validate_csrf_token
 * Validates incoming CSRF token against session token
 *
 * @param string|null $posted_token
 * @return void
 */
function validate_csrf_token($posted_token)
{
    start_secure_session();

    if (empty($_SESSION["CSRF_TOKEN"]) || empty($posted_token)) {
        die("Invalid request (missing CSRF token).");
    }

    if (!hash_equals($_SESSION["CSRF_TOKEN"], $posted_token)) {
        die("Invalid request (bad CSRF token).");
    }
}
?>
