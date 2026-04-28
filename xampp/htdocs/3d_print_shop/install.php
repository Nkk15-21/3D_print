<?php
declare(strict_types=1);

$host = 'localhost';
$user = 'root';
$password = '';
$projectDbName = '3d_print_shop';

mysqli_report(MYSQLI_REPORT_OFF);

$conn = @new mysqli($host, $user, $password);

if ($conn->connect_errno) {
    die('Ошибка подключения к MySQL: (' . $conn->connect_errno . ') ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

$sqlFile = __DIR__ . '/db/install.sql';

if (!file_exists($sqlFile)) {
    $conn->close();
    die('Файл db/install.sql не найден.');
}

$sql = file_get_contents($sqlFile);

if ($sql === false || trim($sql) === '') {
    $conn->close();
    die('Не удалось прочитать install.sql или файл пустой.');
}

/* =========================================================
   СОЗДАНИЕ НУЖНЫХ ПАПОК
   ========================================================= */

$directories = [
    __DIR__ . '/uploads',
    __DIR__ . '/uploads/images',
    __DIR__ . '/uploads/models',
];

foreach ($directories as $directory) {
    if (!is_dir($directory)) {
        if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
            $conn->close();
            die('Не удалось создать папку: ' . $directory);
        }
    }
}

/* =========================================================
   ВЫПОЛНЕНИЕ SQL-ФАЙЛА
   ========================================================= */

if (!$conn->multi_query($sql)) {
    $error = $conn->error;
    $conn->close();
    die('Ошибка выполнения SQL: ' . $error);
}

$queryIndex = 0;

do {
    $queryIndex++;

    if ($result = $conn->store_result()) {
        $result->free();
    }

    if ($conn->errno) {
        $error = $conn->error;
        $conn->close();
        die('Ошибка после SQL-запроса #' . $queryIndex . ': ' . $error);
    }
} while ($conn->more_results() && $conn->next_result());

if ($conn->errno) {
    $error = $conn->error;
    $conn->close();
    die('Ошибка после выполнения SQL: ' . $error);
}

/* =========================================================
   ДОПОЛНИТЕЛЬНАЯ ПРОВЕРКА, ЧТО БД СОЗДАНА
   ========================================================= */

$checkDb = $conn->query("SHOW DATABASES LIKE '" . $conn->real_escape_string($projectDbName) . "'");

if (!$checkDb || $checkDb->num_rows === 0) {
    $conn->close();
    die('Установка завершилась без ошибок, но база данных ' . $projectDbName . ' не найдена.');
}

if ($checkDb instanceof mysqli_result) {
    $checkDb->free();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Установка проекта</title>
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
</head>
<body>
<main class="container">
    <div class="page-header">
        <h1>Установка завершена</h1>
        <p class="small-text">
            База данных <strong><?= htmlspecialchars($projectDbName, ENT_QUOTES, 'UTF-8') ?></strong> успешно создана,
            SQL-структура применена, а папки для загрузок подготовлены.
        </p>
    </div>

    <div class="message success">
        Проект готов к работе.
    </div>

    <div class="card">
        <h3>Что делать дальше</h3>
        <ul class="clean-list">
            <li>Открой главную страницу сайта</li>
            <li>Проверь регистрацию и вход</li>
            <li>Проверь админку</li>
            <li>Если ты добавлял тестовые данные в install.sql, проверь товары и категории</li>
        </ul>

        <div style="margin-top: 20px; display: flex; gap: 12px; flex-wrap: wrap;">
            <a class="btn" href="index.php">Перейти на главную</a>
            <a class="btn btn-secondary" href="admin/index.php">Открыть админку</a>
        </div>
    </div>
</main>
</body>
</html>