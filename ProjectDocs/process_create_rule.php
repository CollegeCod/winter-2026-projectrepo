<?php
require_once __DIR__ . "/includes/session_manager.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/db_connection.php";

start_secure_session();
$pdo = create_database_connection();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: settings.php");
    exit();
}

validate_csrf_token($_POST["csrf_token"] ?? null);

$rule_name = trim($_POST["RULE_NAME"] ?? "");
$min_age = trim($_POST["MIN_AGE"] ?? "");
$open_hour = trim($_POST["OPEN_HOUR"] ?? "");
$close_hour = trim($_POST["CLOSE_HOUR"] ?? "");

if (
    $rule_name === "" ||
    $min_age === "" ||
    $open_hour === "" ||
    $close_hour === ""
) {
    header("Location: settings.php?error=missing_fields_rule");
    exit();
}

if (!is_numeric($min_age)) {
    header("Location: settings.php?error=bad_min_age");
    exit();
}

$min_age = (int) $min_age;

if ($min_age < 0 || $min_age > 120) {
    header("Location: settings.php?error=bad_min_age");
    exit();
}

// For HH:MM input, string comparison works reliably here
if ($close_hour <= $open_hour) {
    header("Location: settings.php?error=bad_time_range");
    exit();
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO access_rules (
            RULE_NAME,
            MIN_AGE,
            OPEN_HOUR,
            CLOSE_HOUR
        ) VALUES (
            :RULE_NAME,
            :MIN_AGE,
            :OPEN_HOUR,
            :CLOSE_HOUR
        )
    ");

    $stmt->execute([
        ":RULE_NAME" => $rule_name,
        ":MIN_AGE" => $min_age,
        ":OPEN_HOUR" => $open_hour,
        ":CLOSE_HOUR" => $close_hour,
    ]);

    header("Location: settings.php?success=rule_created");
    exit();
} catch (PDOException $e) {
    header("Location: settings.php?error=rule_create_failed");
    exit();
}
