<?php
// product.php — страница одного товара + оформление заказа

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

// Берём id из адреса: product.php?id=1
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo '<p>Товар не найден.</p>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// --- Загружаем товар из базы ---
$sql = "
    SELECT p.id,
           p.name,
           p.short_description,
           p.description,
           p.price,
           p.image,
           c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = $id
    LIMIT 1
";

$result = $mysqli->query($sql);

if (!$result) {
    echo '<p>Ошибка запроса: ' . htmlspecialchars($mysqli->error) . '</p>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

if ($result->num_rows === 0) {
    echo '<p>Товар не найден.</p>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$product = $result->fetch_assoc();

// --- Обработка формы заказа ---
$orderErrors = [];
$orderSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_submit'])) {

    if (!isset($_SESSION['user_id'])) {
        $orderErrors[] = 'Для оформления заказа нужно войти в систему.';
    } else {
        $userId = (int)$_SESSION['user_id'];
        $quantity = (int)($_POST['quantity'] ?? 1);
        $comment  = trim($_POST['comment'] ?? '');

        if ($quantity <= 0) {
            $orderErrors[] = 'Количество должно быть больше 0.';
        }

        // Берём данные пользователя из таблицы users
        if (empty($orderErrors)) {
            $stmtUser = $mysqli->prepare("SELECT name, email, phone FROM users WHERE id = ? LIMIT 1");
            if ($stmtUser) {
                $stmtUser->bind_param('i', $userId);
                $stmtUser->execute();
                $stmtUser->bind_result($uName, $uEmail, $uPhone);
                if ($stmtUser->fetch()) {
                    // Всё ок, есть пользователь
                } else {
                    $orderErrors[] = 'Пользователь не найден.';
                }
                $stmtUser->close();
            } else {
                $orderErrors[] = 'Ошибка подготовки запроса (user): ' . htmlspecialchars($mysqli->error);
            }
        }

        // Сохраняем заказ
        if (empty($orderErrors)) {
            $stmtOrder = $mysqli->prepare("
                INSERT INTO orders
                    (user_id, product_id, customer_name, customer_email, customer_phone, quantity, status, comment)
                VALUES
                    (?, ?, ?, ?, ?, ?, 'new', ?)
            ");

            if ($stmtOrder) {
                $stmtOrder->bind_param(
                    'iisssis',
                    $userId,
                    $product['id'],
                    $uName,
                    $uEmail,
                    $uPhone,
                    $quantity,
                    $comment
                );

                if ($stmtOrder->execute()) {
                    $orderSuccess = 'Заказ успешно оформлен! Мы свяжемся с вами для подтверждения.';
                } else {
                    $orderErrors[] = 'Не удалось сохранить заказ: ' . htmlspecialchars($stmtOrder->error);
                }

                $stmtOrder->close();
            } else {
                $orderErrors[] = 'Ошибка подготовки запроса (order): ' . htmlspecialchars($mysqli->error);
            }
        }
    }
}
?>

<h2><?= htmlspecialchars($product['name']) ?></h2>

<div class="product-detail">
    <?php if (!empty($product['image'])): ?>
        <div class="product-detail-image">
            <img src="uploads/images/<?= htmlspecialchars($product['image']) ?>"
                 alt="<?= htmlspecialchars($product['name']) ?>">
        </div>
    <?php endif; ?>

    <div class="product-detail-info">
        <p class="product-category">
            Категория:
            <?= htmlspecialchars(isset($product['category_name']) ? $product['category_name'] : 'Без категории') ?>
        </p>

        <?php if (!empty($product['short_description'])): ?>
            <p><strong>Кратко:</strong> <?= htmlspecialchars($product['short_description']) ?></p>
        <?php endif; ?>

        <?php if (!empty($product['description'])): ?>
            <p><strong>Описание:</strong><br>
                <?= nl2br(htmlspecialchars($product['description'])) ?>
            </p>
        <?php endif; ?>

        <p class="product-price">
            Цена: <?= number_format($product['price'], 2, '.', ' ') ?> €
        </p>

        <div class="product-actions">
            <?php if (!empty($orderSuccess)): ?>
                <div class="message success">
                    <?= htmlspecialchars($orderSuccess) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($orderErrors)): ?>
                <div class="message error">
                    <ul>
                        <?php foreach ($orderErrors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>


            <?php if (isset($_SESSION['user_id'])): ?>
                <h3>Оформить заказ</h3>
                <form method="post" action="product.php?id=<?= (int)$product['id'] ?>">
                    <p>
                        <label>Количество:<br>
                            <input type="number" name="quantity" value="1" min="1" max="100" required>
                        </label>
                    </p>
                    <p>
                        <label>Комментарий к заказу (необязательно):<br>
                            <textarea name="comment" rows="3" cols="40"></textarea>
                        </label>
                    </p>
                    <p>
                        <button type="submit" name="order_submit" value="1">Отправить заказ</button>
                    </p>
                </form>
            <?php else: ?>
                <p>
                    Чтобы оформить заказ,
                    <a href="register.php">зарегистрируйтесь</a>
                    или <a href="login.php">войдите</a>.
                </p>
            <?php endif; ?>

            <p><a href="catalog.php">← Вернуться в каталог</a></p>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
