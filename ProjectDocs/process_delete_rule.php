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

$rule_id = trim($_POST["RULE_ID"] ?? "");

if ($rule_id === "" || !ctype_digit($rule_id)) {
    header("Location: settings.php?error=rule_delete_failed");
    exit();
}

try {
    $stmt = $pdo->prepare("
        DELETE FROM access_rules
        WHERE RULE_ID = :RULE_ID
    ");

    $stmt->execute([
        ":RULE_ID" => (int) $rule_id,
    ]);

    header("Location: settings.php?success=rule_deleted");
    exit();
} catch (PDOException $e) {
    header("Location: settings.php?error=rule_delete_failed");
    exit();
}
