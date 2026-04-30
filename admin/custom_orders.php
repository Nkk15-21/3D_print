<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/header.php';

function adminCustomOrderBadgeClassI18n(string $status): string
{
    return match ($status) {
        'new' => 'badge badge-new',
        'processing' => 'badge badge-processing',
        'done' => 'badge badge-done',
        'cancelled' => 'badge badge-cancelled',
        default => 'badge'
    };
}

$result = $mysqli->query("
    SELECT
        id,
        customer_name,
        customer_email,
        customer_phone,
        material,
        color,
        layer_height,
        infill,
        estimated_price,
        status,
        model_file,
        comment,
        created_at
    FROM custom_orders
    ORDER BY created_at DESC, id DESC
");
?>

    <div class="page-header">
        <h2><?= e(t('admin.custom.title')) ?></h2>
        <p><?= e(t('admin.custom.subtitle')) ?></p>
    </div>

<?php if ($result && $result->num_rows > 0): ?>
    <table>
        <tr>
            <th>ID</th>
            <th><?= e(t('common.name')) ?></th>
            <th><?= e(t('common.email')) ?></th>
            <th><?= e(t('common.phone')) ?></th>
            <th><?= e(t('common.material')) ?></th>
            <th><?= e(t('common.color')) ?></th>
            <th><?= e(t('common.layer_height')) ?></th>
            <th><?= e(t('common.infill')) ?></th>
            <th><?= e(t('common.price')) ?></th>
            <th><?= e(t('common.file')) ?></th>
            <th><?= e(t('common.comment')) ?></th>
            <th><?= e(t('common.status')) ?></th>
            <th><?= e(t('common.date')) ?></th>
            <th><?= e(t('common.actions')) ?></th>
        </tr>

        <?php while ($order = $result->fetch_assoc()): ?>
            <tr>
                <td><?= (int)$order['id'] ?></td>
                <td><?= e($order['customer_name']) ?></td>
                <td><?= e($order['customer_email']) ?></td>
                <td><?= e($order['customer_phone']) ?></td>
                <td><?= e($order['material']) ?></td>
                <td><?= e($order['color'] ?: t('common.none')) ?></td>
                <td><?= number_format((float)$order['layer_height'], 2) ?> мм</td>
                <td><?= (int)$order['infill'] ?>%</td>
                <td>€<?= number_format((float)$order['estimated_price'], 2) ?></td>
                <td>
                    <?php if (!empty($order['model_file'])): ?>
                        <a href="/3d_print_shop/<?= e($order['model_file']) ?>" target="_blank"><?= e(t('common.open')) ?></a>
                    <?php else: ?>
                        <?= e(t('common.none')) ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($order['comment'])): ?>
                        <div class="admin-comment-preview"><?= e($order['comment']) ?></div>
                    <?php else: ?>
                        <?= e(t('common.none')) ?>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="<?= adminCustomOrderBadgeClassI18n($order['status']) ?>">
                        <?= e(t('status.' . $order['status'])) ?>
                    </span>
                </td>
                <td><?= e($order['created_at']) ?></td>
                <td>
                    <form method="post" action="/3d_print_shop/admin/update_custom_order_status.php" class="admin-inline-form">
                        <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">

                        <select name="status" required>
                            <option value="new" <?= $order['status'] === 'new' ? 'selected' : '' ?>><?= e(t('status.new')) ?></option>
                            <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>><?= e(t('status.processing')) ?></option>
                            <option value="done" <?= $order['status'] === 'done' ? 'selected' : '' ?>><?= e(t('status.done')) ?></option>
                            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>><?= e(t('status.cancelled')) ?></option>
                        </select>

                        <button type="submit"><?= e(t('common.save')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <div class="message info"><?= e(t('admin.custom.empty')) ?></div>
<?php endif; ?>

<?php
require_once __DIR__ . '/footer.php';