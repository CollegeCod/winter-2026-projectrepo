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

$user_fname = trim($_POST["USER_FNAME"] ?? "");
$user_lname = trim($_POST["USER_LNAME"] ?? "");
$user_email = trim($_POST["USER_EMAIL"] ?? "");
$password = $_POST["PASSWORD"] ?? "";
$confirm_password = $_POST["CONFIRM_PASSWORD"] ?? "";

// Current system defaults
$default_perm_id = 1;
$generated_user_name = $user_email;

if (
    $user_fname === "" ||
    $user_lname === "" ||
    $user_email === "" ||
    $password === "" ||
    $confirm_password === ""
) {
    header("Location: settings.php?error=missing_fields_user");
    exit();
}

if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
    header("Location: settings.php?error=bad_email");
    exit();
}

if ($password !== $confirm_password) {
    header("Location: settings.php?error=password_mismatch");
    exit();
}

if (strlen($password) < 8) {
    header("Location: settings.php?error=password_short");
    exit();
}

try {
    $check_stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM `user`
        WHERE USER_EMAIL = :USER_EMAIL
           OR USER_NAME = :USER_NAME
    ");
    $check_stmt->execute([
        ":USER_EMAIL" => $user_email,
        ":USER_NAME" => $generated_user_name,
    ]);

    if ((int) $check_stmt->fetchColumn() > 0) {
        header("Location: settings.php?error=user_exists");
        exit();
    }

    $pdo->beginTransaction();

    $user_stmt = $pdo->prepare("
        INSERT INTO `user` (
            PERM_ID,
            USER_NAME,
            USER_FNAME,
            USER_LNAME,
            USER_EMAIL
        ) VALUES (
            :PERM_ID,
            :USER_NAME,
            :USER_FNAME,
            :USER_LNAME,
            :USER_EMAIL
        )
    ");

    $user_stmt->execute([
        ":PERM_ID" => $default_perm_id,
        ":USER_NAME" => $generated_user_name,
        ":USER_FNAME" => $user_fname,
        ":USER_LNAME" => $user_lname,
        ":USER_EMAIL" => $user_email,
    ]);

    $new_user_id = $pdo->lastInsertId();

    if (!$new_user_id) {
        throw new Exception("No USER_ID returned from user insert.");
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $cred_stmt = $pdo->prepare("
        INSERT INTO cred (
            USER_ID,
            PASSWORD
        ) VALUES (
            :USER_ID,
            :PASSWORD
        )
    ");

    $cred_stmt->execute([
        ":USER_ID" => $new_user_id,
        ":PASSWORD" => $password_hash,
    ]);

    $pdo->commit();

    header("Location: settings.php?success=user_created");
    exit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die("Create user failed: " . $e->getMessage());
}
