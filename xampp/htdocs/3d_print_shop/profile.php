<?php
// profile.php — личный кабинет пользователя

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

// Если пользователь не залогинен — отправляем на вход
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Получаем данные пользователя
$userName = '';
$userEmail = '';
$userPhone = '';
$userCreatedAt = '';

$stmtUser = $mysqli->prepare("
    SELECT name, email, phone, created_at
    FROM users
    WHERE id = ?
    LIMIT 1
");
if ($stmtUser) {
    $stmtUser->bind_param('i', $userId);
    $stmtUser->execute();
    $stmtUser->bind_result($userName, $userEmail, $userPhone, $userCreatedAt);
    $stmtUser->fetch();
    $stmtUser->close();
}

// =========================
// Заказы готовых товаров
// =========================

$productOrders = [];

$sqlOrders = "
    SELECT
        o.id              AS order_id,
        o.created_at      AS order_created_at,
        o.status          AS order_status,
        oi.quantity       AS item_quantity,
        oi.unit_price     AS item_price,
        (oi.quantity * oi.unit_price) AS item_total,
        p.name            AS product_name
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    JOIN products p     ON p.id = oi.product_id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC, o.id DESC
";

$stmtOrders = $mysqli->prepare($sqlOrders);
if ($stmtOrders) {
    $stmtOrders->bind_param('i', $userId);
    $stmtOrders->execute();
    $resultOrders = $stmtOrders->get_result();
    while ($row = $resultOrders->fetch_assoc()) {
        $productOrders[] = $row;
    }
    $stmtOrders->close();
}

// =========================
// Индивидуальные заказы
// =========================

$customOrders = [];

$sqlCustom = "
    SELECT
        id,
        created_at,
        material,
        color,
        layer_height,
        infill,
        estimated_price,
        status,
        model_file
    FROM custom_orders
    WHERE user_id = ?
    ORDER BY created_at DESC, id DESC
";

$stmtCustom = $mysqli->prepare($sqlCustom);
if ($stmtCustom) {
    $stmtCustom->bind_param('i', $userId);
    $stmtCustom->execute();
    $resultCustom = $stmtCustom->get_result();
    while ($row = $resultCustom->fetch_assoc()) {
        $customOrders[] = $row;
    }
    $stmtCustom->close();
}
?>

<h2>Личный кабинет</h2>

<p>Привет, <?= htmlspecialchars($userName ?: 'пользователь') ?>!</p>

<hr>

<h3>Мои данные</h3>
<p><strong>E-mail:</strong> <?= htmlspecialchars($userEmail) ?></p>
<?php if ($userPhone): ?>
    <p><strong>Телефон:</strong> <?= htmlspecialchars($userPhone) ?></p>
<?php endif; ?>
<?php if ($userCreatedAt): ?>
    <p><strong>Аккаунт создан:</strong> <?= htmlspecialchars($userCreatedAt) ?></p>
<?php endif; ?>

<hr>

<h3>Мои заказы товаров</h3>

<?php if (empty($productOrders)): ?>
    <p>Вы ещё не оформляли заказы готовых товаров.</p>
<?php else: ?>
    <table>
        <thead>
        <tr>
            <th>ID заказа</th>
            <th>Дата</th>
            <th>Товар</th>
            <th>Кол-во</th>
            <th>Цена за шт.</th>
            <th>Сумма</th>
            <th>Статус</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($productOrders as $row): ?>
            <tr>
                <td>#<?= (int)$row['order_id'] ?></td>
                <td><?= htmlspecialchars($row['order_created_at']) ?></td>
                <td><?= htmlspecialchars($row['product_name']) ?></td>
                <td><?= (int)$row['item_quantity'] ?></td>
                <td><?= number_format((float)$row['item_price'], 2, ',', ' ') ?> €</td>
                <td><?= number_format((float)$row['item_total'], 2, ',', ' ') ?> €</td>
                <td>
                    <?php
                    switch ($row['order_status']) {
                        case 'processing': echo 'В обработке'; break;
                        case 'done':       echo 'Готов'; break;
                        case 'cancelled':  echo 'Отменён'; break;
                        default:           echo 'Новый';
                    }
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<hr>

<h3>Мои индивидуальные заказы</h3>

<?php if (empty($customOrders)): ?>
    <p>У вас пока нет индивидуальных заказов. Вы можете оформить его на странице <a href="custom_order.php">«Индивидуальный заказ»</a>.</p>
<?php else: ?>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Дата</th>
            <th>Материал</th>
            <th>Цвет</th>
            <th>Высота слоя, мм</th>
            <th>Заполнение, %</th>
            <th>Ориентировочная цена</th>
            <th>Статус</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($customOrders as $row): ?>
            <tr>
                <td>#<?= (int)$row['id'] ?></td>
                <td><?= htmlspecialchars($row['created_at']) ?></td>
                <td><?= htmlspecialchars($row['material']) ?></td>
                <td><?= htmlspecialchars($row['color']) ?></td>
                <td>
                    <?= $row['layer_height'] !== null
                        ? htmlspecialchars(rtrim(rtrim($row['layer_height'], '0'), '.'))
                        : '—' ?>
                </td>
                <td>
                    <?= $row['infill'] !== null
                        ? (int)$row['infill']
                        : '—' ?>
                </td>
                <td>
                    <?php if ($row['estimated_price'] !== null): ?>
                        <?= number_format((float)$row['estimated_price'], 2, ',', ' ') ?> €
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    switch ($row['status']) {
                        case 'processing': echo 'В обработке'; break;
                        case 'done':       echo 'Готов'; break;
                        case 'cancelled':  echo 'Отменён'; break;
                        default:           echo 'Новый';
                    }
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
