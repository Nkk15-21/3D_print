<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $passwordRepeat = (string)($_POST['password_repeat'] ?? '');

    if (!isValidName($name)) {
        $errors[] = t('register.name_error');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('register.email_error');
    }

    if (!isValidPhone($phone)) {
        $errors[] = t('register.phone_error');
    }

    if (mb_strlen($password) < 6) {
        $errors[] = t('register.password_error');
    }

    if ($password !== $passwordRepeat) {
        $errors[] = t('register.password_match_error');
    }

    if (!$errors) {
        $stmt = $mysqli->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $existingUser = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existingUser) {
            $errors[] = t('register.email_exists');
        }
    }

    if (!$errors) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $mysqli->prepare("
            INSERT INTO users (
                name,
                email,
                phone,
                password_hash,
                role
            )
            VALUES (?, ?, ?, ?, 'user')
        ");
        $stmt->bind_param('ssss', $name, $email, $phone, $passwordHash);

        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            $stmt->close();

            $_SESSION['user_id'] = (int)$userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = 'user';

            setFlash('success', t('register.success'));
            redirect('/3d_print_shop/profile.php');
        } else {
            $stmt->close();
            $errors[] = t('register.save_error');
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

    <div class="page-header">
        <h1><?= e(t('register.title')) ?></h1>
        <p><?= e(t('register.subtitle')) ?></p>
    </div>

<?php if (!empty($errors)): ?>
    <div class="message error">
        <?php foreach ($errors as $error): ?>
            <div><?= e($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

    <form method="post">
        <label for="name"><?= e(t('common.name')) ?></label>
        <input
                type="text"
                id="name"
                name="name"
                value="<?= e(old('name')) ?>"
                required
        >

        <label for="email"><?= e(t('common.email')) ?></label>
        <input
                type="email"
                id="email"
                name="email"
                value="<?= e(old('email')) ?>"
                required
        >

        <label for="phone"><?= e(t('common.phone')) ?></label>
        <input
                type="text"
                id="phone"
                name="phone"
                value="<?= e(old('phone')) ?>"
        >

        <label for="password"><?= e(t('common.password')) ?></label>
        <input
                type="password"
                id="password"
                name="password"
                required
        >

        <label for="password_repeat"><?= e(t('register.password_repeat')) ?></label>
        <input
                type="password"
                id="password_repeat"
                name="password_repeat"
                required
        >

        <button type="submit"><?= e(t('register.submit')) ?></button>
    </form>

<?php
require_once __DIR__ . '/includes/footer.php';