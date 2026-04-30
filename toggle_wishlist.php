<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = (int)$_SESSION['user_id'];
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$redirect = trim((string)($_GET['redirect'] ?? ''));

$allowedRedirects = [
    'catalog' => '/3d_print_shop/catalog.php',
    'wishlist' => '/3d_print_shop/wishlist.php',
    'product' => '/3d_print_shop/product.php?id=' . $productId,
];

$redirectUrl = $allowedRedirects[$redirect] ?? '/3d_print_shop/wishlist.php';

if ($productId <= 0) {
    setFlash('error', t('product.not_found'));
    redirect($redirectUrl);
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
    redirect($redirectUrl);
}

$stmt = $mysqli->prepare("
    SELECT id
    FROM wishlist
    WHERE user_id = ? AND product_id = ?
    LIMIT 1
");
$stmt->bind_param('ii', $userId, $productId);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    $stmt = $mysqli->prepare("
        DELETE FROM wishlist
        WHERE id = ?
    ");
    $stmt->bind_param('i', $existing['id']);
    $stmt->execute();
    $stmt->close();

    setFlash('success', t('wishlist.removed'));
} else {
    $stmt = $mysqli->prepare("
        INSERT INTO wishlist (user_id, product_id)
        VALUES (?, ?)
    ");
    $stmt->bind_param('ii', $userId, $productId);
    $stmt->execute();
    $stmt->close();

    setFlash('success', t('wishlist.added'));
}

redirect($redirectUrl);