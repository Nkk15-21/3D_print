<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
?>

    <section class="hero">
        <h1><?= e(t('home.hero.title')) ?></h1>
        <p><?= e(t('home.hero.text')) ?></p>

        <div class="hero-actions">
            <a class="btn" href="/3d_print_shop/catalog.php"><?= e(t('home.hero.catalog')) ?></a>
            <a class="btn btn-secondary" href="/3d_print_shop/custom_order.php"><?= e(t('home.hero.custom')) ?></a>
        </div>
    </section>

    <section style="margin-bottom: 32px;">
        <h2 class="section-title"><?= e(t('home.offer.title')) ?></h2>

        <div class="grid-3">
            <div class="info-card">
                <h3><?= e(t('home.offer.ready.title')) ?></h3>
                <p><?= e(t('home.offer.ready.text')) ?></p>
            </div>

            <div class="info-card">
                <h3><?= e(t('home.offer.file.title')) ?></h3>
                <p><?= e(t('home.offer.file.text')) ?></p>
            </div>

            <div class="info-card">
                <h3><?= e(t('home.offer.material.title')) ?></h3>
                <p><?= e(t('home.offer.material.text')) ?></p>
            </div>
        </div>
    </section>

    <section>
        <h2 class="section-title"><?= e(t('home.why.title')) ?></h2>

        <div class="grid-3">
            <div class="card">
                <h3><?= e(t('home.why.fast.title')) ?></h3>
                <p><?= e(t('home.why.fast.text')) ?></p>
            </div>

            <div class="card">
                <h3><?= e(t('home.why.price.title')) ?></h3>
                <p><?= e(t('home.why.price.text')) ?></p>
            </div>

            <div class="card">
                <h3><?= e(t('home.why.contact.title')) ?></h3>
                <p><?= e(t('home.why.contact.text')) ?></p>
            </div>
        </div>
    </section>

<?php
require_once __DIR__ . '/includes/footer.php';