<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = (int)$_SESSION['user_id'];

$stmt = $mysqli->prepare("
    SELECT id, name, email, phone
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    setFlash('error', t('login.error'));
    redirect('/3d_print_shop/logout.php');
}

$stmt = $mysqli->prepare("
    SELECT
        ci.id,
        ci.quantity,
        p.id AS product_id,
        p.name,
        p.name_ru,
        p.name_en,
        p.name_et,
        p.price
    FROM cart_items ci
    JOIN products p ON p.id = ci.product_id
    WHERE ci.user_id = ?
    ORDER BY ci.created_at DESC, ci.id DESC
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!$cartItems) {
    setFlash('error', t('cart.empty_error'));
    redirect('/3d_print_shop/cart.php');
}

$totalAmount = 0.0;

foreach ($cartItems as $item) {
    $totalAmount += ((float)$item['price'] * (int)$item['quantity']);
}

$mysqli->begin_transaction();

try {
    $stmt = $mysqli->prepare("
        INSERT INTO orders (
            user_id,
            customer_name,
            customer_email,
            customer_phone,
            total_amount,
            status,
            status_id
        )
        VALUES (?, ?, ?, ?, ?, 'new', 1)
    ");

    $stmt->bind_param(
        'isssd',
        $userId,
        $user['name'],
        $user['email'],
        $user['phone'],
        $totalAmount
    );

    $stmt->execute();
    $orderId = $stmt->insert_id;
    $stmt->close();

    foreach ($cartItems as $item) {
        $productId = (int)$item['product_id'];
        $quantity = (int)$item['quantity'];
        $unitPrice = (float)$item['price'];

        $stmt = $mysqli->prepare("
            INSERT INTO order_items (
                order_id,
                product_id,
                quantity,
                unit_price
            )
            VALUES (?, ?, ?, ?)
        ");

        $stmt->bind_param('iiid', $orderId, $productId, $quantity, $unitPrice);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $mysqli->prepare("
        INSERT INTO order_status_history (order_id, status_id)
        VALUES (?, 1)
    ");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $stmt->close();

    $stmt = $mysqli->prepare("
        DELETE FROM cart_items
        WHERE user_id = ?
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    $mysqli->commit();

    require_once __DIR__ . '/mail/mailer.php';

    $itemsHtml = '';

    foreach ($cartItems as $item) {
        $itemsHtml .= '<li>' .
            htmlspecialchars(tdb($item, 'name'), ENT_QUOTES, 'UTF-8') .
            ' — ' .
            (int)$item['quantity'] .
            ' × €' .
            number_format((float)$item['price'], 2) .
            '</li>';
    }

    $mailBody = '
        <h2>Новый заказ из корзины</h2>
        <p><strong>Заказ #:</strong> ' . (int)$orderId . '</p>
        <p><strong>Клиент:</strong> ' . htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') . '</p>
        <p><strong>Email:</strong> ' . htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') . '</p>
        <p><strong>Телефон:</strong> ' . htmlspecialchars((string)($user['phone'] ?: '—'), ENT_QUOTES, 'UTF-8') . '</p>
        <p><strong>Товары:</strong></p>
        <ul>' . $itemsHtml . '</ul>
        <p><strong>Итог:</strong> €' . number_format($totalAmount, 2) . '</p>
        <p><strong>Дата:</strong> ' . date('Y-m-d H:i:s') . '</p>
    ';

    sendMailToAdmin('New cart order / Новый заказ из корзины #' . $orderId, $mailBody);

    setFlash('success', t('cart.checkout_success'));
    redirect('/3d_print_shop/profile.php');
} catch (Throwable $e) {
    $mysqli->rollback();

    setFlash('error', t('cart.checkout_error'));
    redirect('/3d_print_shop/cart.php');
}