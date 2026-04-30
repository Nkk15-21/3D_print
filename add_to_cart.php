<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = (int)$_SESSION['user_id'];
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    setFlash('error', t('product.not_found'));
    redirect('/3d_print_shop/catalog.php');
}

$stmt = $mysqli->prepare("
    SELECT id
    FROM products
    WHERE id = ? AND is_active = 1
    LIMIT 1
");
$stmt->bind_param('i', $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    setFlash('error', t('product.not_found'));
    redirect('/3d_print_shop/catalog.php');
}

$stmt = $mysqli->prepare("
    SELECT id, quantity
    FROM cart_items
    WHERE user_id = ? AND product_id = ?
    LIMIT 1
");
$stmt->bind_param('ii', $userId, $productId);
$stmt->execute();
$existingItem = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existingItem) {
    $newQuantity = (int)$existingItem['quantity'] + 1;

    $stmt = $mysqli->prepare("
        UPDATE cart_items
        SET quantity = ?
        WHERE id = ?
    ");
    $stmt->bind_param('ii', $newQuantity, $existingItem['id']);
    $stmt->execute();
    $stmt->close();
} else {
    $quantity = 1;

    $stmt = $mysqli->prepare("
        INSERT INTO cart_items (user_id, product_id, quantity)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param('iii', $userId, $productId, $quantity);
    $stmt->execute();
    $stmt->close();
}

setFlash('success', t('cart.added'));
redirect('/3d_print_shop/cart.php');