<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if ($productId <= 0) {
    setFlash('error', t('product.not_found'));
    redirect('/3d_print_shop/catalog.php');
}

$stmt = $mysqli->prepare("
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
        p.is_active,
        c.name AS category_name,
        c.name_ru AS category_name_ru,
        c.name_en AS category_name_en,
        c.name_et AS category_name_et
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.id = ? AND p.is_active = 1
    LIMIT 1
");
$stmt->bind_param('i', $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    setFlash('error', t('product.not_found'));
    redirect('/3d_print_shop/catalog.php');
}

$imagesStmt = $mysqli->prepare("
    SELECT image_path, is_main
    FROM product_images
    WHERE product_id = ?
    ORDER BY is_main DESC, id ASC
");
$imagesStmt->bind_param('i', $productId);
$imagesStmt->execute();
$productImages = $imagesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$imagesStmt->close();

if (!$productImages && !empty($product['image_path'])) {
    $productImages = [
        [
            'image_path' => $product['image_path'],
            'is_main' => 1,
        ]
    ];
}

$isWishlisted = false;
if ($userId > 0) {
    $stmt = $mysqli->prepare("
        SELECT id
        FROM wishlist
        WHERE user_id = ? AND product_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('ii', $userId, $productId);
    $stmt->execute();
    $isWishlisted = (bool)$stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();

    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($quantity < 1 || $quantity > 100) {
        $errors[] = t('product.quantity_error');
    }

    if (!$errors) {
        $actualUserId = (int)$_SESSION['user_id'];

        $stmt = $mysqli->prepare("
            SELECT id, name, email, phone
            FROM users
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $actualUserId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $errors[] = t('login.error');
        } else {
            $totalAmount = $quantity * (float)$product['price'];

            $stmt = $mysqli->prepare("
                INSERT INTO orders (
                    user_id,
                    customer_name,
                    customer_email,
                    customer_phone,
                    total_amount,
                    status,
                    status_id
                )
                VALUES (?, ?, ?, ?, ?, 'new', 1)
            ");
            $stmt->bind_param(
                'isssd',
                $actualUserId,
                $user['name'],
                $user['email'],
                $user['phone'],
                $totalAmount
            );
            $stmt->execute();
            $orderId = $stmt->insert_id;
            $stmt->close();

            $unitPrice = (float)$product['price'];

            $stmt = $mysqli->prepare("
                INSERT INTO order_items (
                    order_id,
                    product_id,
                    quantity,
                    unit_price
                )
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param(
                'iiid',
                $orderId,
                $productId,
                $quantity,
                $unitPrice
            );
            $stmt->execute();
            $stmt->close();

            $stmt = $mysqli->prepare("
                INSERT INTO order_status_history (order_id, status_id)
                VALUES (?, 1)
            ");
            $stmt->bind_param('i', $orderId);
            $stmt->execute();
            $stmt->close();

            require_once __DIR__ . '/mail/mailer.php';

            $mailBody = renderMailTemplate('order_created.php', [
                'orderId' => $orderId,
                'customerName' => $user['name'],
                'customerEmail' => $user['email'],
                'customerPhone' => $user['phone'],
                'productName' => tdb($product, 'name'),
                'quantity' => $quantity,
                'unitPrice' => $unitPrice,
                'totalAmount' => $totalAmount,
                'createdAt' => date('Y-m-d H:i:s'),
            ]);

            sendMailToAdmin('New product order / Новый заказ товара #' . $orderId, $mailBody);

            setFlash('success', t('product.order_success'));
            redirect('/3d_print_shop/profile.php');
        }
    }
}

require_once __DIR__ . '/includes/header.php';

$categoryTitle = tdb([
    'name_ru' => $product['category_name_ru'] ?? '',
    'name_en' => $product['category_name_en'] ?? '',
    'name_et' => $product['category_name_et'] ?? '',
    'name' => $product['category_name'] ?? '',
], 'name');
?>

    <div class="page-header">
        <h1><?= e(tdb($product, 'name')) ?></h1>
        <p class="small-text">
            <?= e(t('common.category')) ?>: <?= e($categoryTitle ?: t('common.none')) ?>
        </p>
    </div>

<?php if (!empty($errors)): ?>
    <div class="message error">
        <?php foreach ($errors as $error): ?>
            <div><?= e($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

    <div class="card">
        <?php if ($productImages): ?>
            <div style="margin-bottom: 20px;">
                <?php $mainImage = $productImages[0]['image_path']; ?>
                <img
                        src="/3d_print_shop/<?= e($mainImage) ?>"
                        alt="<?= e(tdb($product, 'name')) ?>"
                        style="max-width: 100%; border-radius: 16px; margin-bottom: 14px;"
                >

                <?php if (count($productImages) > 1): ?>
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <?php foreach ($productImages as $image): ?>
                            <img
                                    src="/3d_print_shop/<?= e($image['image_path']) ?>"
                                    alt="<?= e(tdb($product, 'name')) ?>"
                                    style="width: 90px; height: 90px; object-fit: cover; border-radius: 12px; border: 1px solid #e5e7eb;"
                            >
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <p>
            <strong><?= e(t('product.short_description')) ?>:</strong>
            <?= e(tdb($product, 'short_description') ?: t('common.none')) ?>
        </p>

        <p>
            <strong><?= e(t('product.description')) ?>:</strong><br>
            <?= nl2br(e(tdb($product, 'description') ?: t('common.none'))) ?>
        </p>

        <p class="price">
            <strong><?= e(t('product.price')) ?>:</strong>
            €<?= number_format((float)$product['price'], 2) ?>
        </p>

        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px;">
            <?php if ($userId > 0): ?>
                <a class="btn btn-secondary" href="/3d_print_shop/add_to_cart.php?id=<?= (int)$product['id'] ?>">В корзину</a>
                <a class="btn <?= $isWishlisted ? 'btn-danger' : 'btn-secondary' ?>" href="/3d_print_shop/toggle_wishlist.php?id=<?= (int)$product['id'] ?>">
                    <?= $isWishlisted ? 'Убрать из избранного' : 'Добавить в избранное' ?>
                </a>
            <?php endif; ?>
        </div>

        <?php if (isLoggedIn()): ?>
            <form method="post">
                <label for="quantity"><?= e(t('common.quantity')) ?></label>
                <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        min="1"
                        max="100"
                        value="1"
                        required
                >

                <button type="submit"><?= e(t('product.order')) ?></button>
            </form>
        <?php else: ?>
            <div class="message info">
                <?= e(t('product.login_required')) ?>
                <a href="/3d_print_shop/login.php"><?= e(t('nav.login')) ?></a>.
            </div>
        <?php endif; ?>
    </div>

<?php
require_once __DIR__ . '/includes/footer.php';