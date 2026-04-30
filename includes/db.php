<?php
declare(strict_types=1);

$host = 'localhost';
$user = 'root';
$password = '';
$database = '3d_print_shop';

$mysqli = @new mysqli($host, $user, $password, $database);

if ($mysqli->connect_errno) {
    die(
        '<h2>Ошибка подключения к базе данных</h2>' .
        '<p>Не удалось подключиться к базе <strong>3d_print_shop</strong>.</p>' .
        '<p>Запусти <a href="install.php">install.php</a> и проверь, что MySQL запущен в XAMPP.</p>' .
        '<p>Техническая информация: (' . $mysqli->connect_errno . ') ' . htmlspecialchars($mysqli->connect_error) . '</p>'
    );
}

$mysqli->set_charset('utf8mb4');