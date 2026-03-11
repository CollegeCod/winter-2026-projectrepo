<?php
/**
 * mail_sender.php
 * Provides reusable email sending functions.
 */

require_once __DIR__ . "/config.php";

require_once __DIR__ . "/mailer/PHPMailer.php";
require_once __DIR__ . "/mailer/SMTP.php";
require_once __DIR__ . "/mailer/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * send_reset_code_email
 * Sends a 6-digit password reset code to the user.
 *
 * @param string $to_email
 * @param string $reset_code
 * @return array ['success' => bool, 'error' => string]
 */
function send_reset_code_email($to_email, $reset_code)
{
    $mail = new PHPMailer(true);

    try {
        // SMTP configuration (Gmail)
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;

        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_APP_PASSWORD;

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        // Email headers
        $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
        $mail->addAddress($to_email);

        // Content
        $mail->Subject = "Password Reset Verification Code";
        $mail->Body =
            "Your verification code is: " .
            $reset_code .
            "\n\n" .
            "This code expires in 15 minutes.\n\n" .
            "If you did not request a password reset, you can ignore this email.";

        $mail->send();

        return ["success" => true, "error" => ""];
    } catch (Exception $error_message) {
        // ErrorInfo gives the SMTP/PHPMailer error in a readable form
        return ["success" => false, "error" => $mail->ErrorInfo];
    }
}
?>
