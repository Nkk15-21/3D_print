<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = (int)$_SESSION['user_id'];

$stmt = $mysqli->prepare("
    SELECT
        w.id AS wishlist_id,
        p.id,
        p.name,
        p.name_ru,
        p.name_en,
        p.name_et,
        p.short_description,
        p.short_description_ru,
        p.short_description_en,
        p.short_description_et,
        p.price,
        p.image_path,
        (
            SELECT pi.image_path
            FROM product_images pi
            WHERE pi.product_id = p.id
            ORDER BY pi.is_main DESC, pi.id ASC
            LIMIT 1
        ) AS gallery_main_image
    FROM wishlist w
    JOIN products p ON p.id = w.product_id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC, w.id DESC
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$wishlistItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once __DIR__ . '/includes/header.php';
?>

    <div class="page-header">
        <h1><?= e(t('wishlist.title')) ?></h1>
        <p class="small-text"><?= e(t('wishlist.subtitle')) ?></p>
    </div>

<?php if (!$wishlistItems): ?>
    <div class="message info"><?= e(t('wishlist.empty')) ?></div>
<?php else: ?>
    <div class="product-list">
        <?php foreach ($wishlistItems as $item): ?>
            <?php $imageToShow = $item['gallery_main_image'] ?: $item['image_path']; ?>

            <div class="product-card">
                <?php if (!empty($imageToShow)): ?>
                    <img
                            src="/3d_print_shop/<?= e($imageToShow) ?>"
                            alt="<?= e(tdb($item, 'name')) ?>"
                    >
                <?php endif; ?>

                <h3><?= e(tdb($item, 'name')) ?></h3>

                <p><?= e(tdb($item, 'short_description') ?: t('common.none')) ?></p>

                <div class="price">€<?= number_format((float)$item['price'], 2) ?></div>

                <div class="product-card__actions">
                    <a class="btn" href="/3d_print_shop/product.php?id=<?= (int)$item['id'] ?>">
                        <?= e(t('wishlist.open')) ?>
                    </a>

                    <a class="btn btn-secondary" href="/3d_print_shop/add_to_cart.php?id=<?= (int)$item['id'] ?>">
                        <?= e(t('cart.add')) ?>
                    </a>

                    <a class="btn btn-danger" href="/3d_print_shop/toggle_wishlist.php?id=<?= (int)$item['id'] ?>&redirect=wishlist">
                        <?= e(t('wishlist.remove')) ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>