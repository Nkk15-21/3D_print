<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/header.php';
?>

    <div class="page-header">
        <h1><?= e(t('blog.title')) ?></h1>
        <p><?= e(t('blog.subtitle')) ?></p>
    </div>

    <div class="grid-3">
        <div class="card">
            <h3><?= e(t('blog.prepare.title')) ?></h3>
            <p><?= e(t('blog.prepare.text')) ?></p>
        </div>

        <div class="card">
            <h3><?= e(t('blog.material.title')) ?></h3>
            <p><?= e(t('blog.material.text')) ?></p>
        </div>

        <div class="card">
            <h3><?= e(t('blog.price.title')) ?></h3>
            <p><?= e(t('blog.price.text')) ?></p>
        </div>
    </div>

<?php
require_once __DIR__ . '/includes/footer.php';