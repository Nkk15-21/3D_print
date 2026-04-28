<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
?>

    <div class="page-header">
        <h1><?= e(t('services.title')) ?></h1>
        <p><?= e(t('services.subtitle')) ?></p>
    </div>

    <div class="grid-3">
        <div class="card">
            <h3><?= e(t('services.ready.title')) ?></h3>
            <p><?= e(t('services.ready.text')) ?></p>
        </div>

        <div class="card">
            <h3><?= e(t('services.proto.title')) ?></h3>
            <p><?= e(t('services.proto.text')) ?></p>
        </div>

        <div class="card">
            <h3><?= e(t('services.parts.title')) ?></h3>
            <p><?= e(t('services.parts.text')) ?></p>
        </div>

        <div class="card">
            <h3><?= e(t('services.modeling.title')) ?></h3>
            <p><?= e(t('services.modeling.text')) ?></p>
        </div>

        <div class="card">
            <h3><?= e(t('services.post.title')) ?></h3>
            <p><?= e(t('services.post.text')) ?></p>
        </div>

        <div class="card">
            <h3><?= e(t('services.materials.title')) ?></h3>
            <p><?= e(t('services.materials.text')) ?></p>
        </div>
    </div>

<?php
require_once __DIR__ . '/includes/footer.php';