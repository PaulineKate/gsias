<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/load_env.php';

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['MAIL_USER'];
    $mail->Password   = $_ENV['MAIL_PASS'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->SMTPDebug  = 2; // ← shows full SMTP conversation

    $mail->setFrom($_ENV['MAIL_USER'], 'GSIAS Test');
    $mail->addAddress($_ENV['MAIL_USER']); // send to yourself
    $mail->Subject = 'GSIAS Test Email';
    $mail->Body    = 'If you see this, SMTP is working!';

    $mail->send();
    echo "SUCCESS: Email sent!";
} catch (Exception $e) {
    echo "FAILED: " . $mail->ErrorInfo;
}
?>