<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    setFlash('error', 'Сначала войдите в аккаунт.');
    redirect('../login.php');
}

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    setFlash('error', 'У вас нет доступа к админке.');
    redirect('../index.php');
}