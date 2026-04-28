<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

$wishlistProductIds = [];

if ($userId > 0) {
    $stmt = $mysqli->prepare("
        SELECT product_id
        FROM wishlist
        WHERE user_id = ?
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $wishlistRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($wishlistRows as $row) {
        $wishlistProductIds[] = (int)$row['product_id'];
    }
}

$result = $mysqli->query("
    SELECT
        p.id,
        p.name,
        p.name_ru,
        p.name_en,
        p.name_et,
        p.short_description,
        p.short_description_ru,
        p.short_description_en,
        p.short_description_et,
        p.description,
        p.description_ru,
        p.description_en,
        p.description_et,
        p.price,
        p.image_path,
        (
            SELECT pi.image_path
            FROM product_images pi
            WHERE pi.product_id = p.id
            ORDER BY pi.is_main DESC, pi.id ASC
            LIMIT 1
        ) AS gallery_main_image
    FROM products p
    WHERE p.is_active = 1
    ORDER BY p.created_at DESC, p.id DESC
");

require_once __DIR__ . '/includes/header.php';
?>

    <div class="page-header">
        <h1><?= e(t('catalog.title')) ?></h1>
        <p><?= e(t('catalog.subtitle')) ?></p>
    </div>

<?php if ($result && $result->num_rows > 0): ?>
    <div class="product-list">
        <?php while ($product = $result->fetch_assoc()): ?>
            <?php
            $imageToShow = $product['gallery_main_image'] ?: $product['image_path'];
            $isWishlisted = in_array((int)$product['id'], $wishlistProductIds, true);
            ?>

            <div class="product-card">
                <?php if (!empty($imageToShow)): ?>
                    <img
                            src="/3d_print_shop/<?= e($imageToShow) ?>"
                            alt="<?= e(tdb($product, 'name')) ?>"
                    >
                <?php endif; ?>

                <h3><?= e(tdb($product, 'name')) ?></h3>

                <p>
                    <?= e(tdb($product, 'short_description') ?: tdb($product, 'description')) ?>
                </p>

                <div class="price">€<?= number_format((float)$product['price'], 2) ?></div>

                <div class="product-card__actions">
                    <a class="btn" href="/3d_print_shop/product.php?id=<?= (int)$product['id'] ?>">
                        <?= e(t('catalog.more')) ?>
                    </a>

                    <?php if ($userId > 0): ?>
                        <a class="btn btn-secondary" href="/3d_print_shop/add_to_cart.php?id=<?= (int)$product['id'] ?>">
                            <?= e(t('cart.add')) ?>
                        </a>

                        <a
                                class="btn <?= $isWishlisted ? 'btn-danger' : 'btn-secondary' ?>"
                                href="/3d_print_shop/toggle_wishlist.php?id=<?= (int)$product['id'] ?>&redirect=catalog"
                        >
                            <?= e($isWishlisted ? t('wishlist.remove_short') : t('wishlist.add')) ?>
                        </a>
                    <?php else: ?>
                        <a class="btn btn-secondary" href="/3d_print_shop/login.php">
                            <?= e(t('product.login_to_order')) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="message info"><?= e(t('catalog.empty')) ?></div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>