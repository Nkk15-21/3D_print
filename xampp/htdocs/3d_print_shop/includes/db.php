<?php
// includes/db.php

// Настройки БД — пока так, потом сделаем базу через phpMyAdmin
$db_host = 'localhost';
$db_user = 'root';          // в XAMPP по умолчанию root
$db_pass = '';              // в XAMPP по умолчанию пустой пароль
$db_name = '3d_print_shop'; // так назовём нашу базу данных

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);


if ($mysqli->connect_error) {
    die('Ошибка подключения к базе данных: ' . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');
