<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = (int)$_SESSION['user_id'];

$stmt = $mysqli->prepare("
    SELECT name, email, phone, created_at
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $mysqli->prepare("
    SELECT
        o.*,
        os.code AS status_code,
        os.name_ru AS status_name_ru,
        os.name_en AS status_name_en,
        os.name_et AS status_name_et
    FROM orders o
    LEFT JOIN order_statuses os ON os.id = o.status_id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $mysqli->prepare("
    SELECT *
    FROM custom_orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$customOrders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function getStatusBadgeClassProfile(string $status): string
{
    return match ($status) {
        'new' => 'badge badge-new',
        'processing' => 'badge badge-processing',
        'done' => 'badge badge-done',
        'cancelled' => 'badge badge-cancelled',
        default => 'badge'
    };
}

require_once __DIR__ . '/includes/header.php';
?>

    <div class="page-header">
        <h1><?= e(t('profile.title')) ?></h1>
    </div>

    <div class="card">
        <h3><?= e(t('profile.user_data')) ?></h3>
        <p><strong><?= e(t('common.name')) ?>:</strong> <?= e($user['name']) ?></p>
        <p><strong><?= e(t('common.email')) ?>:</strong> <?= e($user['email']) ?></p>
        <p><strong><?= e(t('common.phone')) ?>:</strong> <?= e($user['phone'] ?: t('common.none')) ?></p>
        <p><strong><?= e(t('profile.register_date')) ?>:</strong> <?= e($user['created_at']) ?></p>
    </div>

    <br>

    <h2 class="section-title"><?= e(t('profile.orders')) ?></h2>

<?php if (!$orders): ?>
    <div class="message info"><?= e(t('profile.no_orders')) ?></div>
<?php else: ?>
    <?php foreach ($orders as $order): ?>
        <?php
        $statusCode = $order['status_code'] ?: $order['status'] ?: 'new';
        $statusTitle = tdb([
            'name_ru' => $order['status_name_ru'] ?? '',
            'name_en' => $order['status_name_en'] ?? '',
            'name_et' => $order['status_name_et'] ?? '',
            'name' => $statusCode,
        ], 'name');
        ?>
        <div class="card" style="margin-bottom: 20px;">
            <p>
                <strong>#<?= (int)$order['id'] ?></strong>
                —
                <span class="<?= getStatusBadgeClassProfile($statusCode) ?>">
                    <?= e($statusTitle) ?>
                </span>
            </p>

            <p><strong><?= e(t('common.date')) ?>:</strong> <?= e($order['created_at']) ?></p>
            <p><strong><?= e(t('common.total')) ?>:</strong> €<?= number_format((float)$order['total_amount'], 2) ?></p>

            <h4><?= e(t('admin.orders.items')) ?>:</h4>

            <ul class="clean-list">
                <?php
                $stmt = $mysqli->prepare("
                    SELECT
                        oi.quantity,
                        oi.unit_price,
                        p.name,
                        p.name_ru,
                        p.name_en,
                        p.name_et
                    FROM order_items oi
                    JOIN products p ON p.id = oi.product_id
                    WHERE oi.order_id = ?
                ");
                $stmt->bind_param('i', $order['id']);
                $stmt->execute();
                $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                ?>

                <?php foreach ($items as $item): ?>
                    <li>
                        <?= e(tdb($item, 'name')) ?> —
                        <?= (int)$item['quantity'] ?> × €<?= number_format((float)$item['unit_price'], 2) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

    <h2 class="section-title"><?= e(t('profile.custom_orders')) ?></h2>

<?php if (!$customOrders): ?>
    <div class="message info"><?= e(t('profile.no_custom_orders')) ?></div>
<?php else: ?>
    <?php foreach ($customOrders as $order): ?>
        <div class="card" style="margin-bottom: 20px;">
            <p>
                <strong>#<?= (int)$order['id'] ?></strong>
                —
                <span class="<?= getStatusBadgeClassProfile($order['status']) ?>">
                    <?= e(t('status.' . $order['status'])) ?>
                </span>
            </p>

            <p><strong><?= e(t('common.material')) ?>:</strong> <?= e($order['material']) ?></p>
            <p><strong><?= e(t('common.color')) ?>:</strong> <?= e($order['color'] ?: t('common.none')) ?></p>
            <p><strong><?= e(t('common.layer_height')) ?>:</strong> <?= e((string)$order['layer_height']) ?> мм</p>
            <p><strong><?= e(t('common.infill')) ?>:</strong> <?= e((string)$order['infill']) ?>%</p>

            <?php if ($order['estimated_price'] !== null): ?>
                <p><strong><?= e(t('profile.estimated_price')) ?>:</strong> €<?= number_format((float)$order['estimated_price'], 2) ?></p>
            <?php endif; ?>

            <p>
                <strong><?= e(t('common.file')) ?>:</strong>
                <a href="/3d_print_shop/<?= e($order['model_file']) ?>" target="_blank"><?= e(t('common.download')) ?></a>
            </p>

            <p><strong><?= e(t('common.date')) ?>:</strong> <?= e($order['created_at']) ?></p>

            <?php if (!empty($order['comment'])): ?>
                <p><strong><?= e(t('common.comment')) ?>:</strong><br><?= nl2br(e($order['comment'])) ?></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';