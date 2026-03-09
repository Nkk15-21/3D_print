<?php
// login.php — вход пользователя

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$email = '';
$errors = [];

// Если уже вошёл — отправим в профиль
if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Введите e-mail и пароль.';
    } else {
        $stmt = $mysqli->prepare("SELECT id, name, password_hash, role FROM users WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($id, $name, $password_hash, $role);
                $stmt->fetch();

                if (password_verify($password, $password_hash)) {
                    // Всё ок — логиним
                    $_SESSION['user_id']   = $id;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_role'] = $role;

                    $_SESSION['flash_success'] = 'Вы успешно вошли в аккаунт.';

                    header('Location: profile.php');
                    exit;
                } else {
                    $errors[] = 'Неверный пароль.';
                }
            } else {
                $errors[] = 'Пользователь с таким e-mail не найден.';
            }

            $stmt->close();
        } else {
            $errors[] = 'Ошибка подготовки запроса: ' . htmlspecialchars($mysqli->error);
        }
    }
}
?>

<h2>Вход</h2>

<?php if (!empty($errors)): ?>
    <div class="message error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="login.php">
    <p>
        <label>E-mail:<br>
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
        </label>
    </p>

    <p>
        <label>Пароль:<br>
            <input type="password" name="password" required>
        </label>
    </p>

    <p>
        <button type="submit">Войти</button>
    </p>
</form>

<p>Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a></p>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
