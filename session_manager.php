<?php
/**
 * start_secure_session
 * Starts a PHP session
 */
function start_secure_session()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * destroy_user_session
 * Logs the user out and destroys session data
 */
function destroy_user_session()
{
    if (session_status() !== PHP_SESSION_NONE) {
        session_unset();
        session_destroy();
    }
}
