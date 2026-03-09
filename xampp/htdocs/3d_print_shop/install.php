<?php
// install.php — одноразовый скрипт для создания БД 3d_print_shop в MySQL

$host = 'localhost';
$user = 'root';
$pass = '';          // если у root есть пароль — впиши здесь
$sqlFile = __DIR__ . '/db/install.sql';

echo "<pre>";

if (!file_exists($sqlFile)) {
    die("Файл с SQL-скриптом не найден: db/install.sql\n");
}

$mysqli = @new mysqli($host, $user, $pass);
if ($mysqli->connect_errno) {
    die("Ошибка подключения к MySQL: ({$mysqli->connect_errno}) {$mysqli->connect_error}\n");
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    die("Не удалось прочитать db/install.sql\n");
}

echo "Запускаю SQL-скрипт...\n\n";

if ($mysqli->multi_query($sql)) {
    do {
        // просто пролистываем результаты
    } while ($mysqli->more_results() && $mysqli->next_result());

    if ($mysqli->errno) {
        echo "Готово с ошибками: ({$mysqli->errno}) {$mysqli->error}\n";
    } else {
        echo "База данных и таблицы успешно созданы / обновлены.\n";
    }
} else {
    echo "Ошибка выполнения multi_query: ({$mysqli->errno}) {$mysqli->error}\n";
}

echo "\nТеперь можете открыть сайт: http://localhost/3d_print_shop/index.php\n";
echo "</pre>";
