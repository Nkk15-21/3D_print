<?php
declare(strict_types=1);

require_once __DIR__ . '/mail/mailer.php';

$result = sendMailToAdmin(
    'Тест письма',
    '<h1>Проверка SMTP</h1><p>Если это письмо пришло, значит всё работает.</p>'
);

echo $result ? 'Письмо отправлено!' : 'Ошибка отправки';