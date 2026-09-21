<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
require 'phpmailer/Exception.php';

function loadEnv(string $path): array
{
    $env = [];
    if (!is_readable($path)) {
        return $env;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
    }
    return $env;
}

$env = loadEnv(__DIR__ . '/.env');

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
        $mail->Host       = $env['SMTP_HOST'] ?? '';
        $mail->SMTPAuth   = true;
        $mail->Username   = $env['SMTP_USERNAME'] ?? '';
        $mail->Password   = $env['SMTP_PASSWORD'] ?? '';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = (int) ($env['SMTP_PORT'] ?? 587);
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($env['SMTP_USERNAME'] ?? '', 'Web kontaktní formulář');
        $mail->addAddress($env['MAIL_TO'] ?? '');
        $mail->addReplyTo($email, $name);

        $mail->Subject = 'Zpráva z webu' . ($name ? " od $name" : '');
        $mail->Body    = "Jméno: $name\nEmail: $email\n\nZpráva:\n$message";

        $mail->send();
        http_response_code(200);
        echo 'OK';
    } catch (Exception $e) {
        error_log($mail->ErrorInfo);
        http_response_code(500);
        echo 'Odeslání se nezdařilo.';
    }
    exit;
}
?>