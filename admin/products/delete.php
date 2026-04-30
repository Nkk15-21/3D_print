<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../auth.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $mysqli->query("DELETE FROM products WHERE id = $id");
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    redirect('index.php');
}
setFlash('success', 'Удалено');
redirect('index.php');