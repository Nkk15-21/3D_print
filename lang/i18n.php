<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$availableLanguages = ['ru', 'en', 'et'];

if (isset($_GET['lang'])) {
    $requestedLang = trim((string)$_GET['lang']);

    if (in_array($requestedLang, $availableLanguages, true)) {
        $_SESSION['lang'] = $requestedLang;
    }
}

$currentLang = $_SESSION['lang'] ?? 'ru';

if (!in_array($currentLang, $availableLanguages, true)) {
    $currentLang = 'ru';
}

$translations = require __DIR__ . '/' . $currentLang . '.php';

function t(string $key): string
{
    global $translations;

    return $translations[$key] ?? $key;
}

function currentLang(): string
{
    return $_SESSION['lang'] ?? 'ru';
}

function availableLanguages(): array
{
    return ['ru', 'en', 'et'];
}