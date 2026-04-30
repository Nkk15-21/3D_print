<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/header.php';

function adminOrderBadgeClassByCode(string $code): string
{
    return match ($code) {
        'new' => 'badge badge-new',
        'processing' => 'badge badge-processing',
        'done' => 'badge badge-done',
        'cancelled' => 'badge badge-cancelled',
        default => 'badge'
    };
}

/* =========================================================
   СМЕНА СТАТУСА
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $statusId = isset($_POST['status_id']) ? (int)$_POST['status_id'] : 0;

    if ($orderId > 0 && $statusId > 0) {
        $stmt = $mysqli->prepare("
            SELECT code
            FROM order_statuses
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $statusId);
        $stmt->execute();
        $statusRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($statusRow) {
            $statusCode = $statusRow['code'];

            $stmt = $mysqli->prepare("
                UPDATE orders
                SET status_id = ?, status = ?
                WHERE id = ?
            ");
            $stmt->bind_param('isi', $statusId, $statusCode, $orderId);
            $stmt->execute();
            $stmt->close();

            $stmt = $mysqli->prepare("
                INSERT INTO order_status_history (order_id, status_id)
                VALUES (?, ?)
            ");
            $stmt->bind_param('ii', $orderId, $statusId);
            $stmt->execute();
            $stmt->close();

            setFlash('success', 'Статус заказа обновлён.');
        } else {
            setFlash('error', 'Статус не найден.');
        }
    } else {
        setFlash('error', 'Некорректные данные для смены статуса.');
    }

    redirect('/3d_print_shop/admin/orders.php');
}

/* =========================================================
   ЗАГРУЗКА ДАННЫХ
   ========================================================= */

$statusesResult = $mysqli->query("
    SELECT id, code, name_ru, name_en, name_et, color
    FROM order_statuses
    ORDER BY id ASC
");
$statuses = $statusesResult ? $statusesResult->fetch_all(MYSQLI_ASSOC) : [];

$result = $mysqli->query("
    SELECT
        o.id,
        o.customer_name,
        o.customer_email,
        o.customer_phone,
        o.total_amount,
        o.status,
        o.status_id,
        o.created_at,
        os.code AS status_code,
        os.name_ru AS status_name_ru,
        os.name_en AS status_name_en,
        os.name_et AS status_name_et,
        os.color AS status_color
    FROM orders o
    LEFT JOIN order_statuses os ON os.id = o.status_id
    ORDER BY o.created_at DESC, o.id DESC
");
?>

    <div class="page-header">
        <h2><?= e(t('admin.orders.title')) ?></h2>
        <p><?= e(t('admin.orders.subtitle')) ?></p>
    </div>

<?php if ($result && $result->num_rows > 0): ?>
    <table>
        <tr>
            <th>ID</th>
            <th><?= e(t('common.name')) ?></th>
            <th><?= e(t('common.email')) ?></th>
            <th><?= e(t('common.phone')) ?></th>
            <th><?= e(t('admin.orders.items')) ?></th>
            <th><?= e(t('common.total')) ?></th>
            <th><?= e(t('common.status')) ?></th>
            <th><?= e(t('common.date')) ?></th>
            <th><?= e(t('common.actions')) ?></th>
        </tr>

        <?php while ($order = $result->fetch_assoc()): ?>
            <?php
            $statusCode = $order['status_code'] ?: $order['status'] ?: 'new';

            $statusTitle = tdb([
                'name_ru' => $order['status_name_ru'] ?? '',
                'name_en' => $order['status_name_en'] ?? '',
                'name_et' => $order['status_name_et'] ?? '',
                'name' => $statusCode,
            ], 'name');

            $itemsStmt = $mysqli->prepare("
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
            $itemsStmt->bind_param('i', $order['id']);
            $itemsStmt->execute();
            $items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $itemsStmt->close();
            ?>
            <tr>
                <td><?= (int)$order['id'] ?></td>
                <td><?= e($order['customer_name']) ?></td>
                <td><?= e($order['customer_email']) ?></td>
                <td><?= e($order['customer_phone'] ?: t('common.none')) ?></td>
                <td>
                    <?php if ($items): ?>
                        <ul class="clean-list">
                            <?php foreach ($items as $item): ?>
                                <li>
                                    <?= e(tdb($item, 'name')) ?> —
                                    <?= (int)$item['quantity'] ?> × €<?= number_format((float)$item['unit_price'], 2) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <?= e(t('common.none')) ?>
                    <?php endif; ?>
                </td>
                <td>€<?= number_format((float)$order['total_amount'], 2) ?></td>
                <td>
                    <span class="<?= adminOrderBadgeClassByCode($statusCode) ?>">
                        <?= e($statusTitle) ?>
                    </span>
                </td>
                <td><?= e($order['created_at']) ?></td>
                <td>
                    <form method="post" class="admin-inline-form">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">

                        <select name="status_id" required>
                            <?php foreach ($statuses as $status): ?>
                                <option
                                        value="<?= (int)$status['id'] ?>"
                                    <?= (int)$order['status_id'] === (int)$status['id'] ? 'selected' : '' ?>
                                >
                                    <?= e(tdb($status, 'name')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit"><?= e(t('common.save')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <div class="message info"><?= e(t('admin.orders.empty')) ?></div>
<?php endif; ?>

<?php
require_once __DIR__ . '/footer.php';