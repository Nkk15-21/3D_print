<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../lang/i18n.php';

$flashSuccess = getFlash('success');
$flashError = getFlash('error');
?>
<!DOCTYPE html>
<html lang="<?= e(currentLang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('admin.title')) ?> | <?= e(t('site.title')) ?></title>
    <link rel="stylesheet" href="/3d_print_shop/css/style.css">
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-sidebar__logo">
            <a href="/3d_print_shop/admin/index.php"><?= e(t('admin.title')) ?></a>
        </div>

        <nav class="admin-sidebar__nav">
            <a href="/3d_print_shop/admin/index.php"><?= e(t('nav.home')) ?></a>
            <a href="/3d_print_shop/admin/orders.php"><?= e(t('admin.orders')) ?></a>
            <a href="/3d_print_shop/admin/custom_orders.php"><?= e(t('admin.custom_orders')) ?></a>
            <a href="/3d_print_shop/admin/contacts.php"><?= e(t('admin.contacts')) ?></a>
            <a href="/3d_print_shop/admin/products/index.php"><?= e(t('admin.products')) ?></a>
            <a href="/3d_print_shop/admin/categories.php"><?= e(t('common.categories')) ?></a>
            <a href="/3d_print_shop/admin/users.php"><?= e(t('admin.users')) ?></a>
            <a href="/3d_print_shop/index.php"><?= e(t('admin.to_site')) ?></a>
            <a href="/3d_print_shop/logout.php"
               class="danger-link"
               data-confirm="true"
               data-confirm-title="<?= e(t('logout.title')) ?>"
               data-confirm-text="<?= e(t('logout.text')) ?>"
               data-confirm-button="<?= e(t('logout.button')) ?>">
                <?= e(t('nav.logout')) ?>
            </a>
        </nav>
    </aside>

    <div class="admin-content">


        <main class="admin-main">
            <?php if ($flashSuccess): ?>
                <div class="message success"><?= e($flashSuccess) ?></div>
            <?php endif; ?>

            <?php if ($flashError): ?>
                <div class="message error"><?= e($flashError) ?></div>
            <?php endif; ?>