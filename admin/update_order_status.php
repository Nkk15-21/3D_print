<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth.php';

/* =========================================================
   ПРОВЕРКА МЕТОДА ЗАПРОСА
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('orders.php');
}

/* =========================================================
   ПОЛУЧЕНИЕ ДАННЫХ ИЗ ФОРМЫ
   ========================================================= */

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$status = trim((string)($_POST['status'] ?? ''));

$allowedStatuses = ['new', 'processing', 'done', 'cancelled'];

/* =========================================================
   ВАЛИДАЦИЯ
   ========================================================= */

if ($orderId <= 0 || !in_array($status, $allowedStatuses, true)) {
    setFlash('error', 'Некорректные данные для обновления статуса.');
    redirect('orders.php');
}

/* =========================================================
   ОБНОВЛЕНИЕ СТАТУСА
   ========================================================= */

$stmt = $mysqli->prepare("
    UPDATE orders
    SET status = ?
    WHERE id = ?
");

$stmt->bind_param('si', $status, $orderId);

if ($stmt->execute()) {
    setFlash('success', 'Статус заказа обновлён.');
} else {
    setFlash('error', 'Не удалось обновить статус заказа.');
}

$stmt->close();

redirect('orders.php');