<?php
// register.php — регистрация нового пользователя

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$name = '';
$email = '';
$phone = '';
$errors = [];

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Простая проверка
    if ($name === '') {
        $errors[] = 'Введите имя.';
    } elseif (!preg_match('/^[A-Za-zА-Яа-яЁё\s\-]{2,50}$/u', $name)) {
        $errors[] = 'Имя может содержать только буквы, пробел и дефис (2–50 символов).';
    }

    if ($email === '') {
        $errors[] = 'Введите e-mail.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Неверный формат e-mail.';
    }

    if ($phone !== '' && !preg_match('/^[0-9+\-\s]{5,20}$/', $phone)) {
        $errors[] = 'Телефон может содержать только цифры, пробелы, + и - (5–20 символов).';
    }

    if ($password === '') {
        $errors[] = 'Введите пароль.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Пароль должен быть не короче 6 символов.';
    }

    if ($password !== $password_confirm) {
        $errors[] = 'Пароль и подтверждение не совпадают.';
    }

    // Проверяем, есть ли уже такой e-mail
    if (empty($errors)) {
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $errors[] = 'Пользователь с таким e-mail уже зарегистрирован.';
            }

            $stmt->close();
        } else {
            $errors[] = 'Ошибка подготовки запроса: ' . htmlspecialchars($mysqli->error);
        }
    }

    // Если ошибок нет — создаём пользователя
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $mysqli->prepare("INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'user')");
        if ($stmt) {
            $stmt->bind_param('ssss', $name, $email, $phone, $password_hash);
            $ok = $stmt->execute();
            if ($ok) {
                $newUserId = $stmt->insert_id;
                $stmt->close();

                // Автоматически логиним пользователя
                $_SESSION['user_id']   = $newUserId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_role'] = 'user';

                // Флеш-сообщение
                $_SESSION['flash_success'] = 'Вы успешно зарегистрировались!';

                header('Location: profile.php');
                exit;
            } else {
                $errors[] = 'Не удалось создать пользователя: ' . htmlspecialchars($stmt->error);
                $stmt->close();
            }
        } else {
            $errors[] = 'Ошибка подготовки запроса: ' . htmlspecialchars($mysqli->error);
        }
    }
}
?>

<h2>Регистрация</h2>

<?php if (!empty($errors)): ?>
    <div class="message error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="register.php">
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
        <label>E-mail (логин):<br>
            <input
                    type="email"
                    name="email"
                    value="<?= htmlspecialchars($email) ?>"
                    required>
        </label>
    </p>

    <p>
        <label>Телефон (необязательно):<br>
            <input
                    type="tel"
                    name="phone"
                    value="<?= htmlspecialchars($phone) ?>"
                    pattern="[0-9+\-\s]{5,20}"
                    title="Только цифры, пробелы, + и -, 5–20 символов">
        </label>
    </p>

    <p>
        <label>Пароль:<br>
            <input type="password" name="password" required minlength="6">
        </label>
    </p>

    <p>
        <label>Повторите пароль:<br>
            <input type="password" name="password_confirm" required minlength="6">
        </label>
    </p>

    <p>
        <button type="submit">Зарегистрироваться</button>
    </p>
</form>

<p>Уже есть аккаунт? <a href="login.php">Войти</a></p>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
