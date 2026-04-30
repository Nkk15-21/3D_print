<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $errors[] = t('login.error');
    } else {
        $stmt = $mysqli->prepare("
            SELECT id, name, email, password_hash, role
            FROM users
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            setFlash('success', t('login.success'));
            redirect('/3d_print_shop/profile.php');
        } else {
            $errors[] = t('login.error');
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

    <div class="page-header">
        <h1><?= e(t('login.title')) ?></h1>
        <p><?= e(t('login.subtitle')) ?></p>
    </div>

<?php if (!empty($errors)): ?>
    <div class="message error">
        <?php foreach ($errors as $error): ?>
            <div><?= e($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

    <form method="post">
        <label for="email"><?= e(t('common.email')) ?></label>
        <input
                type="email"
                id="email"
                name="email"
                value="<?= e(old('email')) ?>"
                required
        >

        <label for="password"><?= e(t('common.password')) ?></label>
        <input
                type="password"
                id="password"
                name="password"
                required
        >

        <button type="submit"><?= e(t('login.submit')) ?></button>
    </form>

<?php
require_once __DIR__ . '/includes/footer.php';