<?php
// logout.php
// Handles AJAX logout requests - clears all cookies and session

header("Content-Type: application/json");

// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

// ── Clear all browser cookies ─────────────────────────────────────────────
foreach ($_COOKIE as $cookie_name => $cookie_value) {
    setcookie($cookie_name, "", [
        "expires" => time() - 3600,
        "path" => "/",
        "secure" => true,
        "httponly" => true,
        "samesite" => "Strict",
    ]);
    unset($_COOKIE[$cookie_name]);
}

// ── Destroy PHP session if one exists ─────────────────────────────────────
if (session_status() === PHP_SESSION_ACTIVE) {
    session_unset();
    session_destroy();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), "", [
            "expires" => time() - 3600,
            "path" => $params["path"],
            "domain" => $params["domain"],
            "secure" => $params["secure"],
            "httponly" => $params["httponly"],
        ]);
    }
}

// ── Return success ─────────────────────────────────────────────────────────
echo json_encode([
    "success" => true,
    "redirect" => "logged_out.php",
]);
exit();
?>
