<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../lang/i18n.php';

/* =========================================================
   ЭКРАНИРОВАНИЕ ВЫВОДА
   ========================================================= */

/**
 * Безопасный вывод текста в HTML.
 */
function e(string|int|float|null $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


/* =========================================================
   РЕДИРЕКТЫ
   ========================================================= */

/**
 * Перенаправление на другую страницу.
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}


/* =========================================================
   FLASH-СООБЩЕНИЯ
   ========================================================= */

/**
 * Сохраняет flash-сообщение в сессии.
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Получает и удаляет flash-сообщение из сессии.
 */
function getFlash(string $type): ?string
{
    $key = 'flash_' . $type;

    if (!isset($_SESSION[$key])) {
        return null;
    }

    $message = (string)$_SESSION[$key];
    unset($_SESSION[$key]);

    return $message;
}


/* =========================================================
   АВТОРИЗАЦИЯ
   ========================================================= */

/**
 * Проверяет, вошёл ли пользователь в аккаунт.
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Требует вход в аккаунт.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('error', t('login.error'));
        redirect('/3d_print_shop/login.php');
    }
}


/* =========================================================
   ВАЛИДАЦИЯ ПОЛЕЙ
   ========================================================= */

/**
 * Проверка имени.
 * Разрешены буквы, пробелы и дефисы.
 */
function isValidName(string $name): bool
{
    return (bool)preg_match('/^[\p{L}\s\-]{2,100}$/u', $name);
}

/**
 * Проверка телефона.
 * Разрешены цифры, пробелы, +, -, скобки.
 * Пустое значение тоже допустимо.
 */
function isValidPhone(string $phone): bool
{
    if ($phone === '') {
        return true;
    }

    return (bool)preg_match('/^[0-9+\-\s()]{5,50}$/', $phone);
}


/* =========================================================
   СТАРЫЕ ДАННЫЕ ФОРМЫ
   ========================================================= */

/**
 * Возвращает старое значение из POST,
 * чтобы форма не очищалась после ошибки.
 */
function old(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}


/* =========================================================
   СПИСОК МАТЕРИАЛОВ ДЛЯ 3D-ПЕЧАТИ
   ========================================================= */

/**
 * Возвращает список доступных материалов.
 */
function getMaterialsList(): array
{
    return [
        'Bambu PLA Basic',
        'Bambu PLA Matte',
        'Bambu PLA Tough',
        'Bambu PLA Silk',
        'Bambu PLA Silk+',
        'Bambu PLA Galaxy',
        'Bambu PLA Marble',
        'Bambu PLA Translucent',
        'Bambu PLA Dynamic',
        'Bambu PLA Glow',
        'Bambu PLA Metal',
        'Bambu PLA Wood',
        'Bambu PLA-CF',
        'Bambu PETG Basic',
        'Bambu PETG Translucent',
        'Bambu PETG HF',
        'Bambu PETG-CF',
        'Bambu ABS',
        'Bambu ASA',
        'Bambu PC',
        'Bambu PA (Nylon)',
        'Bambu PA-CF',
        'Bambu PAHT-CF',
        'Bambu TPU 95A',
        'Bambu Support G',
        'Bambu Support W',
        'Другое (указать в комментарии)',
    ];
}


/* =========================================================
   КАЛЬКУЛЯТОР ПРИМЕРНОЙ СТОИМОСТИ
   ========================================================= */

/**
 * Считает примерную стоимость индивидуального заказа.
 */
function calculateCustomOrderPrice(string $material, float $weight, float $layerHeight, int $infill): float
{
    $materialRates = [
        'Bambu PLA Basic' => 0.35,
        'Bambu PLA Matte' => 0.37,
        'Bambu PLA Tough' => 0.42,
        'Bambu PLA Silk' => 0.40,
        'Bambu PLA Silk+' => 0.43,
        'Bambu PLA Galaxy' => 0.41,
        'Bambu PLA Marble' => 0.43,
        'Bambu PLA Translucent' => 0.39,
        'Bambu PLA Dynamic' => 0.40,
        'Bambu PLA Glow' => 0.50,
        'Bambu PLA Metal' => 0.48,
        'Bambu PLA Wood' => 0.46,
        'Bambu PLA-CF' => 0.55,
        'Bambu PETG Basic' => 0.40,
        'Bambu PETG Translucent' => 0.42,
        'Bambu PETG HF' => 0.45,
        'Bambu PETG-CF' => 0.58,
        'Bambu ABS' => 0.44,
        'Bambu ASA' => 0.46,
        'Bambu PC' => 0.60,
        'Bambu PA (Nylon)' => 0.62,
        'Bambu PA-CF' => 0.70,
        'Bambu PAHT-CF' => 0.78,
        'Bambu TPU 95A' => 0.52,
        'Bambu Support G' => 0.65,
        'Bambu Support W' => 0.68,
        'Другое (указать в комментарии)' => 0.50,
    ];

    $basePrice = 3.00;
    $ratePerGram = $materialRates[$material] ?? 0.50;

    $layerCoefficient = 1.0;

    if ($layerHeight <= 0.12) {
        $layerCoefficient = 1.35;
    } elseif ($layerHeight <= 0.16) {
        $layerCoefficient = 1.20;
    } elseif ($layerHeight <= 0.20) {
        $layerCoefficient = 1.10;
    } elseif ($layerHeight <= 0.28) {
        $layerCoefficient = 1.00;
    } else {
        $layerCoefficient = 0.95;
    }

    $infillCoefficient = 1.0 + ($infill / 200);

    $price = ($basePrice + ($weight * $ratePerGram)) * $layerCoefficient * $infillCoefficient;

    return round($price, 2);
}


/* =========================================================
   МУЛЬТИЯЗЫЧНЫЕ ПОЛЯ ИЗ БД
   ========================================================= */

/**
 * Возвращает имя поля с языковым суффиксом.
 * Например: fieldByLang('name') => name_ru
 */
function fieldByLang(string $baseField, ?string $lang = null): string
{
    $lang = $lang ?? currentLang();
    return $baseField . '_' . $lang;
}

/**
 * Возвращает значение мультиязычного поля из БД
 * с fallback на другие языки и старое поле без суффикса.
 */
function tdb(array $row, string $field): string
{
    $lang = currentLang();
    $langField = $field . '_' . $lang;

    if (!empty($row[$langField])) {
        return (string)$row[$langField];
    }

    foreach (['ru', 'en', 'et'] as $fallbackLang) {
        $fallbackField = $field . '_' . $fallbackLang;

        if (!empty($row[$fallbackField])) {
            return (string)$row[$fallbackField];
        }
    }

    return (string)($row[$field] ?? '');
}

/**
 * Генерирует URL для переключения языка,
 * сохраняя текущую страницу и остальные GET-параметры.
 */
function langUrl(string $lang): string
{
    $params = $_GET;
    $params['lang'] = $lang;

    $path = strtok($_SERVER['REQUEST_URI'], '?');
    return $path . '?' . http_build_query($params);
}