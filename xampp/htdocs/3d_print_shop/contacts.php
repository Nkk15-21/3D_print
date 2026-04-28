<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $subject = trim((string)($_POST['subject'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));

    if (!isValidName($name)) {
        $errors[] = t('contacts.name_error');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('contacts.email_error');
    }

    if (mb_strlen($message) < 10) {
        $errors[] = t('contacts.message_error');
    }

    if (!$errors) {
        $stmt = $mysqli->prepare("
            INSERT INTO contacts (name, email, subject, message)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param('ssss', $name, $email, $subject, $message);

        if ($stmt->execute()) {
            $stmt->close();

            require_once __DIR__ . '/mail/mailer.php';

            $mailBody = renderMailTemplate('contact_message.php', [
                    'name' => $name,
                    'email' => $email,
                    'subject' => $subject,
                    'message' => $message,
                    'createdAt' => date('Y-m-d H:i:s'),
            ]);

            sendMailToAdmin('New contact message / Новое сообщение с сайта', $mailBody);

            setFlash('success', t('contacts.success'));
            redirect('/3d_print_shop/contacts.php');
        } else {
            $stmt->close();
            $errors[] = t('contacts.save_error');
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

    <div class="page-header">
        <h1><?= e(t('contacts.title')) ?></h1>
        <p><?= e(t('contacts.subtitle')) ?></p>
    </div>

<?php if ($errors): ?>
    <div class="message error">
        <?php foreach ($errors as $error): ?>
            <div><?= e($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

    <form method="post">
        <label for="name"><?= e(t('common.name')) ?></label>
        <input type="text" id="name" name="name" value="<?= e(old('name')) ?>" required>

        <label for="email"><?= e(t('common.email')) ?></label>
        <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" required>

        <label for="subject"><?= e(t('common.subject')) ?></label>
        <input type="text" id="subject" name="subject" value="<?= e(old('subject')) ?>">

        <label for="message"><?= e(t('common.message')) ?></label>
        <textarea id="message" name="message" required><?= e(old('message')) ?></textarea>

        <button type="submit"><?= e(t('contacts.send')) ?></button>
    </form>

<?php
require_once __DIR__ . '/includes/footer.php';