<?php

require_once __DIR__ . "/../db_connection.php";

/**
 * get_user_by_email
 * Retrieves user record using email address
 *
 * @param string $user_email
 * @return array|false
 */

function get_user_by_email($user_email)
{
    $database_connection = create_database_connection();

    $sql_query = "
        SELECT USER_ID, PERM_ID, USER_EMAIL
        FROM user
        WHERE USER_EMAIL = :user_email
        LIMIT 1
    ";

    $prepared_statement = $database_connection->prepare($sql_query);

    $prepared_statement->bindParam(":user_email", $user_email);

    $prepared_statement->execute();

    return $prepared_statement->fetch(PDO::FETCH_ASSOC);
}

/**
 * get_user_password_hash
 * Retrieves password hash from credential table
 *
 * @param int $user_id
 * @return string|false
 */

function get_user_password_hash($user_id)
{
    $database_connection = create_database_connection();

    $sql_query = "
        SELECT PASSWORD
        FROM cred
        WHERE USER_ID = :user_id
        LIMIT 1
    ";

    $prepared_statement = $database_connection->prepare($sql_query);

    $prepared_statement->bindParam(":user_id", $user_id);

    $prepared_statement->execute();

    $result = $prepared_statement->fetch(PDO::FETCH_ASSOC);

    return $result ? $result["PASSWORD"] : false;
}

/**
 * verify_user_password
 * Compares provided password with stored hash
 *
 * @param string $input_password
 * @param string $stored_hash
 * @return bool
 */

function verify_user_password($input_password, $stored_hash)
{
    return password_verify($input_password, $stored_hash);
}

/**
 * get_user_by_email_for_reset
 * Retrieves user_id by email for reset flow
 *
 * @param string $user_email
 * @return array|false
 */
function get_user_by_email_for_reset($user_email)
{
    $database_connection = create_database_connection();

    $sql_query = "
        SELECT USER_ID, USER_EMAIL, RESET_CODE_LAST_SENT_AT
        FROM user
        WHERE USER_EMAIL = :user_email
        LIMIT 1
    ";

    $prepared_statement = $database_connection->prepare($sql_query);
    $prepared_statement->execute([":user_email" => $user_email]);

    return $prepared_statement->fetch(PDO::FETCH_ASSOC);
}

/**
 * set_reset_code_for_user
 * Stores hashed reset code + expiry + resets attempts
 *
 * @param int $user_id
 * @param string $reset_code_hash
 * @param string $expires_at_mysql
 * @return void
 */
function set_reset_code_for_user($user_id, $reset_code_hash, $expires_at_mysql)
{
    $database_connection = create_database_connection();

    $sql_query = "
        UPDATE user
        SET RESET_CODE_HASH = :hash,
            RESET_CODE_EXPIRES_AT = :expires_at,
            RESET_CODE_ATTEMPTS = 0,
            RESET_CODE_LAST_SENT_AT = NOW()
        WHERE USER_ID = :user_id
        LIMIT 1
    ";

    $prepared_statement = $database_connection->prepare($sql_query);
    $prepared_statement->execute([
        ":hash" => $reset_code_hash,
        ":expires_at" => $expires_at_mysql,
        ":user_id" => $user_id,
    ]);
}

/**
 * get_reset_record
 * Gets reset hash/expiry/attempts for verification
 *
 * @param string $user_email
 * @return array|false
 */
function get_reset_record($user_email)
{
    $database_connection = create_database_connection();

    $sql_query = "
        SELECT USER_ID, RESET_CODE_HASH, RESET_CODE_EXPIRES_AT, RESET_CODE_ATTEMPTS
        FROM user
        WHERE USER_EMAIL = :user_email
        LIMIT 1
    ";

    $prepared_statement = $database_connection->prepare($sql_query);
    $prepared_statement->execute([":user_email" => $user_email]);

    return $prepared_statement->fetch(PDO::FETCH_ASSOC);
}

/**
 * increment_reset_attempts
 *
 * @param int $user_id
 * @return void
 */
function increment_reset_attempts($user_id)
{
    $database_connection = create_database_connection();

    $sql_query = "
        UPDATE user
        SET RESET_CODE_ATTEMPTS = RESET_CODE_ATTEMPTS + 1
        WHERE USER_ID = :user_id
        LIMIT 1
    ";

    $prepared_statement = $database_connection->prepare($sql_query);
    $prepared_statement->execute([":user_id" => $user_id]);
}

/**
 * clear_reset_code
 * Clears reset code fields after success
 *
 * @param int $user_id
 * @return void
 */
function clear_reset_code($user_id)
{
    $database_connection = create_database_connection();

    $sql_query = "
        UPDATE user
        SET RESET_CODE_HASH = NULL,
            RESET_CODE_EXPIRES_AT = NULL,
            RESET_CODE_ATTEMPTS = 0,
            RESET_CODE_LAST_SENT_AT = NULL
        WHERE USER_ID = :user_id
        LIMIT 1
    ";

    $prepared_statement = $database_connection->prepare($sql_query);
    $prepared_statement->execute([":user_id" => $user_id]);
}

/**
 * update_user_password
 * Updates password hash in cred table
 *
 * @param int $user_id
 * @param string $new_password_hash
 * @return void
 */
function update_user_password($user_id, $new_password_hash)
{
    $database_connection = create_database_connection();

    $sql_query = "
        UPDATE cred
        SET PASSWORD = :password_hash
        WHERE USER_ID = :user_id
        LIMIT 1
    ";

    $prepared_statement = $database_connection->prepare($sql_query);
    $prepared_statement->execute([
        ":password_hash" => $new_password_hash,
        ":user_id" => $user_id,
    ]);
}

?>
