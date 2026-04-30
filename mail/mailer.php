<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../includes/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../includes/phpmailer/SMTP.php';
require_once __DIR__ . '/../includes/phpmailer/Exception.php';

$configPath = __DIR__ . '/config.php';

if (!file_exists($configPath)) {
    file_put_contents(
        __DIR__ . '/mail_errors.log',
        date('Y-m-d H:i:s') . ' | mail/config.php not found. Copy mail/config.example.php to mail/config.php' . PHP_EOL,
        FILE_APPEND
    );
}

$config = file_exists($configPath)
    ? require $configPath
    : ['mail' => []];

$mailConfig = $config['mail'] ?? [];

function renderMailTemplate(string $templateName, array $data = []): string
{
    $templatePath = __DIR__ . '/templates/' . $templateName;

    if (!file_exists($templatePath)) {
        return '<p>Template not found: ' . htmlspecialchars($templateName, ENT_QUOTES, 'UTF-8') . '</p>';
    }

    extract($data, EXTR_SKIP);

    ob_start();
    require $templatePath;
    return (string)ob_get_clean();
}

function sendMailToAdmin(string $subject, string $body): bool
{
    global $mailConfig;

    $requiredKeys = [
        'host',
        'username',
        'password',
        'port',
        'from_email',
        'from_name',
        'admin_email',
    ];

    foreach ($requiredKeys as $key) {
        if (empty($mailConfig[$key])) {
            file_put_contents(
                __DIR__ . '/mail_errors.log',
                date('Y-m-d H:i:s') . ' | Missing mail config key: ' . $key . PHP_EOL,
                FILE_APPEND
            );

            return false;
        }
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = (string)$mailConfig['host'];
        $mail->SMTPAuth = true;
        $mail->Username = (string)$mailConfig['username'];
        $mail->Password = (string)$mailConfig['password'];
        $mail->Port = (int)$mailConfig['port'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->setFrom(
            (string)$mailConfig['from_email'],
            (string)$mailConfig['from_name']
        );

        $mail->addAddress((string)$mailConfig['admin_email']);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags(
            str_replace(['<br>', '<br/>', '<br />'], "\n", $body)
        );

        return $mail->send();
    } catch (Exception $e) {
        file_put_contents(
            __DIR__ . '/mail_errors.log',
            date('Y-m-d H:i:s') .
            ' | SUBJECT: ' . $subject .
            ' | ERROR_INFO: ' . $mail->ErrorInfo .
            ' | EXCEPTION: ' . $e->getMessage() .
            PHP_EOL,
            FILE_APPEND
        );

        return false;
    }
}