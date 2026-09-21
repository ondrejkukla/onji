<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
require 'phpmailer/Exception.php';

function loadEnv(string $path): void
{
    if (!is_readable($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if (getenv($key) === false) {
            putenv("$key=$value");
        }
    }
}

loadEnv(__DIR__ . '/.env');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name    = htmlspecialchars(trim($_POST["name"] ?? ""));
    $email   = htmlspecialchars(trim($_POST["email"] ?? ""));
    $message = htmlspecialchars(trim($_POST["message"] ?? ""));

    if (empty($email) || empty($message)) {
        http_response_code(400);
        exit("Chybí povinná pole.");
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USERNAME');
        $mail->Password   = getenv('SMTP_PASSWORD');
        $mail->SMTPSecure = 'tls';
        $mail->Port       = (int) getenv('SMTP_PORT');
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(getenv('SMTP_USERNAME'), 'Web kontaktní formulář');
        $mail->addAddress(getenv('MAIL_TO'));
        $mail->addReplyTo($email, $name);

        $mail->Subject = 'Zpráva z webu' . ($name ? " od $name" : '');
        $mail->Body    = "Jméno: $name\nEmail: $email\n\nZpráva:\n$message";

        $mail->send();
        header('Location: index.html?sent=1');
    } catch (Exception $e) {
        error_log($mail->ErrorInfo);
        header('Location: index.html?error=1');
    }
    exit;
}
?>