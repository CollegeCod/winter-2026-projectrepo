<?php
/**
 * create_database_connection
 * Establishes a PDO connection to the MySQL database
 *
 * @return PDO
 */

function create_database_connection()
{
    //enter credentials here
    $database_host = "";
    $database_name = "";
    $database_user = "";
    $database_password = "";

    try {
        $pdo_connection = new PDO(
            "mysql:host=$database_host;dbname=$database_name;charset=utf8mb4",
            $database_user,
            $database_password,
        );

        $pdo_connection->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION,
        );

        return $pdo_connection;
    } catch (PDOException $error_message) {
        die("Database connection failed: " . $error_message->getMessage());
    }
}
?>
