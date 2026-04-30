<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $cartItemId = isset($_POST['cart_item_id']) ? (int)$_POST['cart_item_id'] : 0;

    if ($action === 'remove' && $cartItemId > 0) {
        $stmt = $mysqli->prepare("
            DELETE FROM cart_items
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param('ii', $cartItemId, $userId);
        $stmt->execute();
        $stmt->close();

        setFlash('success', t('cart.removed'));
        redirect('/3d_print_shop/cart.php');
    }

    if ($action === 'update' && $cartItemId > 0) {
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

        if ($quantity < 1) {
            $quantity = 1;
        }

        $stmt = $mysqli->prepare("
            UPDATE cart_items
            SET quantity = ?
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param('iii', $quantity, $cartItemId, $userId);
        $stmt->execute();
        $stmt->close();

        setFlash('success', t('cart.updated'));
        redirect('/3d_print_shop/cart.php');
    }
}

$stmt = $mysqli->prepare("
    SELECT
        ci.id,
        ci.quantity,
        p.id AS product_id,
        p.name,
        p.name_ru,
        p.name_en,
        p.name_et,
        p.price,
        p.image_path,
        (
            SELECT pi.image_path
            FROM product_images pi
            WHERE pi.product_id = p.id
            ORDER BY pi.is_main DESC, pi.id ASC
            LIMIT 1
        ) AS gallery_main_image
    FROM cart_items ci
    JOIN products p ON p.id = ci.product_id
    WHERE ci.user_id = ?
    ORDER BY ci.created_at DESC, ci.id DESC
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total = 0.0;

foreach ($cartItems as $item) {
    $total += ((float)$item['price'] * (int)$item['quantity']);
}

require_once __DIR__ . '/includes/header.php';
?>

    <div class="page-header">
        <h1><?= e(t('cart.title')) ?></h1>
        <p class="small-text"><?= e(t('cart.subtitle')) ?></p>
    </div>

<?php if (!$cartItems): ?>
    <div class="message info"><?= e(t('cart.empty')) ?></div>
<?php else: ?>
    <div class="grid-3" style="grid-template-columns: 2fr 1fr; align-items:start;">
        <div>
            <?php foreach ($cartItems as $item): ?>
                <?php $imageToShow = $item['gallery_main_image'] ?: $item['image_path']; ?>

                <div class="card" style="margin-bottom:20px;">
                    <div style="display:flex; gap:16px; flex-wrap:wrap;">
                        <?php if (!empty($imageToShow)): ?>
                            <img
                                    src="/3d_print_shop/<?= e($imageToShow) ?>"
                                    alt="<?= e(tdb($item, 'name')) ?>"
                                    style="width:140px; height:140px; object-fit:cover; border-radius:14px;"
                            >
                        <?php endif; ?>

                        <div style="flex:1 1 260px;">
                            <h3><?= e(tdb($item, 'name')) ?></h3>
                            <p><strong><?= e(t('common.price')) ?>:</strong> €<?= number_format((float)$item['price'], 2) ?></p>

                            <form method="post" class="admin-inline-form" style="margin-bottom:12px;">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="cart_item_id" value="<?= (int)$item['id'] ?>">

                                <label for="qty_<?= (int)$item['id'] ?>" style="margin:0;">
                                    <?= e(t('cart.quantity')) ?>
                                </label>

                                <input
                                        type="number"
                                        id="qty_<?= (int)$item['id'] ?>"
                                        name="quantity"
                                        min="1"
                                        value="<?= (int)$item['quantity'] ?>"
                                        style="max-width:100px; margin:0;"
                                >

                                <button type="submit"><?= e(t('cart.update')) ?></button>
                            </form>

                            <form method="post" style="background:none; box-shadow:none; border:none; padding:0;">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="cart_item_id" value="<?= (int)$item['id'] ?>">
                                <button type="submit" class="btn btn-danger"><?= e(t('cart.remove')) ?></button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <h3><?= e(t('cart.summary')) ?></h3>
            <p><strong><?= e(t('cart.items_count')) ?>:</strong> <?= count($cartItems) ?></p>
            <p><strong><?= e(t('cart.total')) ?>:</strong></p>
            <div class="calculator-price">€<?= number_format($total, 2) ?></div>

            <div style="margin-top:18px; display:flex; flex-direction:column; gap:10px;">
                <a class="btn" href="/3d_print_shop/checkout_cart.php">
                    <?= e(t('cart.checkout')) ?>
                </a>

                <a class="btn btn-secondary" href="/3d_print_shop/catalog.php">
                    <?= e(t('cart.continue')) ?>
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>