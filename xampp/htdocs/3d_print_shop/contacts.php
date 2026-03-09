<?php
// contacts.php — страница "Контакты"

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

// Куда слать письма
$adminEmail = 'plastikmaailm.domen@gmail.com';

$name = '';
$email = '';
$subject = '';
$messageText = '';
$errors = [];
$success = '';
$emailInfo = ''; // текст про отправку письма

// Функция отправки письма админу
function sendContactMail(string $adminEmail, string $name, string $email, string $subject, string $messageText): bool
{
    // Чистим от переносов строк, чтобы не было подстановки заголовков
    $safeSubjectUser = str_replace(["\r", "\n"], ' ', $subject);

    // Тема письма
    $mailSubject = 'Новое сообщение с сайта 3D Print Shop';
    if ($safeSubjectUser !== '') {
        $mailSubject .= ' — ' . $safeSubjectUser;
    }

    // Текст письма
    $body  = "Новое сообщение с формы контактов 3D Print Shop:\n\n";
    $body .= "Имя: {$name}\n";
    $body .= "E-mail отправителя: {$email}\n";
    if ($safeSubjectUser !== '') {
        $body .= "Тема: {$safeSubjectUser}\n";
    }
    $body .= "\nСообщение:\n{$messageText}\n\n";
    $body .= 'Отправлено: ' . date('Y-m-d H:i:s') . "\n";

    // Заголовки
    $headers  = "From: no-reply@localhost\r\n";   // при реальном хостинге заменить домен
    $headers .= "Reply-To: {$email}\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Пытаемся отправить
    return mail($adminEmail, $mailSubject, $body, $headers);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $messageText = trim($_POST['message'] ?? '');

    // Имя
    if ($name === '') {
        $errors[] = 'Введите имя.';
    } elseif (!preg_match('/^[A-Za-zА-Яа-яЁё\s\-]{2,50}$/u', $name)) {
        $errors[] = 'Имя может содержать только буквы, пробел и дефис (2–50 символов).';
    }

    // E-mail
    if ($email === '') {
        $errors[] = 'Введите e-mail.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Неверный формат e-mail.';
    }

    // Сообщение
    if ($messageText === '') {
        $errors[] = 'Введите текст сообщения.';
    } elseif (mb_strlen($messageText) < 10) {
        $errors[] = 'Сообщение должно быть не короче 10 символов.';
    }

    if (empty($errors)) {
        // Сохраняем в БД
        $stmt = $mysqli->prepare("
            INSERT INTO contacts (name, email, subject, message)
            VALUES (?, ?, ?, ?)
        ");

        if ($stmt) {
            $stmt->bind_param('ssss', $name, $email, $subject, $messageText);
            if ($stmt->execute()) {
                // Пытаемся отправить письмо
                $mailOk = sendContactMail($adminEmail, $name, $email, $subject, $messageText);

                if ($mailOk) {
                    $success = 'Сообщение отправлено! Я отвечу вам на e-mail как можно скорее.';
                } else {
                    // На локальном XAMPP часто сюда попадём, пока не настроен SMTP
                    $success = 'Сообщение сохранено. Попытка отправить письмо не удалась (на сервере почта не настроена).';
                    $emailInfo = 'Код отправки письма есть, но для реальной отправки нужно настроить почтовый сервер или SMTP.';
                }

                // очистим поля формы
                $name = $email = $subject = $messageText = '';
            } else {
                $errors[] = 'Не удалось сохранить сообщение: ' . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        } else {
            $errors[] = 'Ошибка подготовки запроса: ' . htmlspecialchars($mysqli->error);
        }
    }
}
?>

<h2>Контакты</h2>

<p>Здесь вы можете задать вопрос по 3D-печати, уточнить стоимость заказа или предложить идею.</p>

<?php if ($success): ?>
    <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php if ($emailInfo): ?>
        <div class="message info"><?= htmlspecialchars($emailInfo) ?></div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="message error">
        <ul>
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="contacts.php">
    <p>
        <label>Имя:<br>
            <input
                    type="text"
                    name="name"
                    value="<?= htmlspecialchars($name) ?>"
                    required
                    pattern="[A-Za-zА-Яа-яЁё\s\-]{2,50}"
                    title="Только буквы, пробел и дефис, 2–50 символов">
        </label>
    </p>

    <p>
        <label>E-mail:<br>
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
        </label>
    </p>

    <p>
        <label>Тема (необязательно):<br>
            <input type="text" name="subject" value="<?= htmlspecialchars($subject) ?>">
        </label>
    </p>

    <p>
        <label>Сообщение:<br>
            <textarea name="message" rows="5" cols="50" required><?= htmlspecialchars($messageText) ?></textarea>
        </label>
    </p>

    <p>
        <button type="submit">Отправить</button>
    </p>
</form>

<p>Также вы можете связаться со мной через Telegram или по телефону — эти данные можно будет добавить сюда позже.</p>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
