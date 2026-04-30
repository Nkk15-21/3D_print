<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth.php';

/* =========================================================
   ПРОВЕРКА МЕТОДА
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('custom_orders.php');
}

/* =========================================================
   ПОЛУЧЕНИЕ ДАННЫХ
   ========================================================= */

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$status = trim((string)($_POST['status'] ?? ''));

$allowedStatuses = ['new', 'processing', 'done', 'cancelled'];

/* =========================================================
   ВАЛИДАЦИЯ
   ========================================================= */

if ($orderId <= 0 || !in_array($status, $allowedStatuses, true)) {
    setFlash('error', 'Некорректные данные для обновления статуса.');
    redirect('custom_orders.php');
}

/* =========================================================
   ОБНОВЛЕНИЕ СТАТУСА
   ========================================================= */

$stmt = $mysqli->prepare("
    UPDATE custom_orders
    SET status = ?
    WHERE id = ?
");

$stmt->bind_param('si', $status, $orderId);

if ($stmt->execute()) {
    setFlash('success', 'Статус индивидуального заказа обновлён.');
} else {
    setFlash('error', 'Не удалось обновить статус индивидуального заказа.');
}

$stmt->close();

redirect('custom_orders.php');