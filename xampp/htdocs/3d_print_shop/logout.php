<?php
// logout.php — выход пользователя

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Удаляем только данные о пользователе, сессию не уничтожаем целиком
unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role']);

// Сообщение
$_SESSION['flash_success'] = 'Вы вышли из аккаунта.';

header('Location: index.php');
exit;
