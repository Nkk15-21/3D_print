<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';

$flashSuccess = getFlash('success');
$flashError = getFlash('error');

$wishlistCount = 0;
$cartCount = 0;

if (!empty($_SESSION['user_id']) && isset($mysqli) && $mysqli instanceof mysqli) {
    $userId = (int)$_SESSION['user_id'];

    $stmt = $mysqli->prepare("
        SELECT COUNT(*) AS cnt
        FROM wishlist
        WHERE user_id = ?
    ");
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $wishlistCount = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
        $stmt->close();
    }

    $stmt = $mysqli->prepare("
        SELECT COUNT(*) AS cnt
        FROM cart_items
        WHERE user_id = ?
    ");
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $cartCount = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
        $stmt->close();
    }

    if (!isset($mysqli)) {
    require_once __DIR__ . '/db.php';
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e(currentLang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('site.title')) ?></title>
    <link rel="stylesheet" href="/3d_print_shop/css/style.css?v=<?= time() ?>">
</head>
<body>
<header class="site-header">
    <div class="container site-header__inner">
        <div class="site-header__top">
            <a href="/3d_print_shop/index.php" class="logo"><?= e(t('site.title')) ?></a>

            <div class="site-header__controls">
                <div class="lang-switcher">
                    <a href="<?= e(langUrl('ru')) ?>" class="lang-btn <?= currentLang() === 'ru' ? 'active' : '' ?>">Rus</a>
                    <a href="<?= e(langUrl('en')) ?>" class="lang-btn <?= currentLang() === 'en' ? 'active' : '' ?>">Eng</a>
                    <a href="<?= e(langUrl('et')) ?>" class="lang-btn <?= currentLang() === 'et' ? 'active' : '' ?>">Est</a>
                </div>

                <nav class="nav nav-actions">
                    <a href="/3d_print_shop/contacts.php" class="nav-circle-btn" title="<?= e(t('nav.contacts')) ?>" aria-label="<?= e(t('nav.contacts')) ?>">
                        <svg viewBox="0 0 24 24" class="nav-svg" aria-hidden="true">
                            <path d="M3 6h18v12H3z" fill="none" stroke="currentColor" stroke-width="2"/>
                            <path d="M3 7l9 7 9-7" fill="none" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </a>

                    <?php if (!empty($_SESSION['user_id'])): ?>
                        <a href="/3d_print_shop/wishlist.php" class="nav-circle-btn" title="Избранное" aria-label="Избранное" style="position:relative;">
                            <svg viewBox="0 0 24 24" class="nav-svg" aria-hidden="true">
                                <path d="M12 21s-7-4.35-9-8.5C1.3 8.9 3.1 5 6.7 5c2 0 3.2 1.1 4.3 2.5C12.1 6.1 13.3 5 15.3 5 18.9 5 20.7 8.9 21 12.5 19 16.65 12 21 12 21z" fill="none" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <?php if ($wishlistCount > 0): ?>
                                <span class="nav-counter nav-counter--danger"><?= $wishlistCount ?></span>
                            <?php endif; ?>
                        </a>

                        <a href="/3d_print_shop/cart.php" class="nav-circle-btn" title="Корзина" aria-label="Корзина" style="position:relative;">
                            <svg viewBox="0 0 24 24" class="nav-svg" aria-hidden="true">
                                <circle cx="9" cy="20" r="1.7" fill="currentColor"/>
                                <circle cx="18" cy="20" r="1.7" fill="currentColor"/>
                                <path d="M3 4h2l2.2 10.2a1 1 0 0 0 1 .8H18a1 1 0 0 0 1-.8L21 8H7" fill="none" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <?php if ($cartCount > 0): ?>
                                <span class="nav-counter nav-counter--primary"><?= $cartCount ?></span>
                            <?php endif; ?>
                        </a>

                        <a href="/3d_print_shop/profile.php" class="nav-profile-btn" title="<?= e(t('nav.profile')) ?>">
                            <span class="nav-profile-icon-wrap">
                                <svg viewBox="0 0 24 24" class="nav-svg" aria-hidden="true">
                                    <circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="2"/>
                                    <path d="M4 20c0-4 4-6 8-6s8 2 8 6" fill="none" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </span>
                            <span class="nav-profile-name"><?= e($_SESSION['user_name'] ?? t('nav.profile')) ?></span>
                        </a>

                        <a href="/3d_print_shop/logout.php"
                           class="nav-circle-btn nav-logout-btn"
                           title="<?= e(t('nav.logout')) ?>"
                           aria-label="<?= e(t('nav.logout')) ?>"
                           data-confirm="true"
                           data-confirm-title="<?= e(t('logout.title')) ?>"
                           data-confirm-text="<?= e(t('logout.text')) ?>"
                           data-confirm-button="<?= e(t('logout.button')) ?>">
                            <svg viewBox="0 0 24 24" class="nav-svg" aria-hidden="true">
                                <path d="M16 17l5-5-5-5" fill="none" stroke="currentColor" stroke-width="2"/>
                                <path d="M21 12H9" fill="none" stroke="currentColor" stroke-width="2"/>
                                <path d="M13 4H5v16h8" fill="none" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </a>
                    <?php else: ?>
                        <a href="/3d_print_shop/login.php"><?= e(t('nav.login')) ?></a>
                        <a href="/3d_print_shop/register.php"><?= e(t('nav.register')) ?></a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>

        <nav class="nav nav-main nav-main--header">
            <a href="/3d_print_shop/index.php"><?= e(t('nav.home')) ?></a>
            <a href="/3d_print_shop/catalog.php"><?= e(t('nav.catalog')) ?></a>
            <a href="/3d_print_shop/custom_order.php"><?= e(t('nav.custom_order')) ?></a>
            <a href="/3d_print_shop/services.php"><?= e(t('nav.services')) ?></a>
            <?php if (!empty($_SESSION['user_id']) && (($_SESSION['user_role'] ?? '') === 'admin')): ?>
                <a href="/3d_print_shop/admin/index.php"><?= e(t('nav.admin')) ?></a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="container">
    <?php if ($flashSuccess): ?>
        <div class="message success"><?= e($flashSuccess) ?></div>
    <?php endif; ?>

    <?php if ($flashError): ?>
        <div class="message error"><?= e($flashError) ?></div>
    <?php endif; ?>