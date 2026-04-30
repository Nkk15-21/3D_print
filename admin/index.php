<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/header.php';

function adminCount(mysqli $mysqli, string $sql): int
{
    $result = $mysqli->query($sql);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return (int)($row['cnt'] ?? 0);
}

function adminMoney(mysqli $mysqli, string $sql): float
{
    $result = $mysqli->query($sql);

    if (!$result) {
        return 0.0;
    }

    $row = $result->fetch_assoc();

    return (float)($row['total'] ?? 0);
}

$usersCount = adminCount($mysqli, "SELECT COUNT(*) AS cnt FROM users");
$productsCount = adminCount($mysqli, "SELECT COUNT(*) AS cnt FROM products");
$activeProductsCount = adminCount($mysqli, "SELECT COUNT(*) AS cnt FROM products WHERE is_active = 1");
$ordersCount = adminCount($mysqli, "SELECT COUNT(*) AS cnt FROM orders");
$newOrdersCount = adminCount($mysqli, "SELECT COUNT(*) AS cnt FROM orders WHERE status = 'new'");
$customOrdersCount = adminCount($mysqli, "SELECT COUNT(*) AS cnt FROM custom_orders");
$newCustomOrdersCount = adminCount($mysqli, "SELECT COUNT(*) AS cnt FROM custom_orders WHERE status = 'new'");
$contactsCount = adminCount($mysqli, "SELECT COUNT(*) AS cnt FROM contacts");
$unreadContactsCount = adminCount($mysqli, "SELECT COUNT(*) AS cnt FROM contacts WHERE is_read = 0");

$totalRevenue = adminMoney($mysqli, "
    SELECT COALESCE(SUM(total_amount), 0) AS total
    FROM orders
    WHERE status != 'cancelled'
");

$customRevenue = adminMoney($mysqli, "
    SELECT COALESCE(SUM(estimated_price), 0) AS total
    FROM custom_orders
    WHERE status != 'cancelled'
");

$latestOrders = $mysqli->query("
    SELECT
        o.id,
        o.customer_name,
        o.customer_email,
        o.total_amount,
        o.status,
        o.created_at
    FROM orders o
    ORDER BY o.created_at DESC, o.id DESC
    LIMIT 5
");

$latestCustomOrders = $mysqli->query("
    SELECT
        id,
        customer_name,
        customer_email,
        material,
        estimated_price,
        status,
        created_at
    FROM custom_orders
    ORDER BY created_at DESC, id DESC
    LIMIT 5
");

$latestContacts = $mysqli->query("
    SELECT
        id,
        name,
        email,
        subject,
        is_read,
        created_at
    FROM contacts
    ORDER BY is_read ASC, created_at DESC, id DESC
    LIMIT 5
");

function dashboardStatusBadge(string $status): string
{
    return match ($status) {
        'new' => '<span class="badge badge-new">Новый</span>',
        'processing' => '<span class="badge badge-processing">В обработке</span>',
        'done' => '<span class="badge badge-done">Готово</span>',
        'cancelled' => '<span class="badge badge-cancelled">Отменён</span>',
        default => '<span class="badge">' . e($status) . '</span>',
    };
}
?>

    <div class="admin-dashboard-hero">
        <div>
            <div class="admin-dashboard-kicker">Панель управления</div>
            <h2><?= e(t('admin.dashboard.title')) ?></h2>
            <p><?= e(t('admin.dashboard.subtitle')) ?></p>
        </div>

        <div class="admin-dashboard-hero__actions">
            <a href="/3d_print_shop/admin/products/create.php" class="btn">+ Добавить товар</a>
            <a href="/3d_print_shop/admin/orders.php" class="btn btn-secondary">Заказы</a>
        </div>
    </div>

    <div class="admin-stats admin-stats-premium">
        <div class="admin-stat-card">
            <div class="admin-stat-card__icon">👥</div>
            <div class="admin-stat-card__title">Пользователи</div>
            <div class="admin-stat-card__value"><?= $usersCount ?></div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-card__icon">📦</div>
            <div class="admin-stat-card__title">Товары</div>
            <div class="admin-stat-card__value"><?= $productsCount ?></div>
            <div class="admin-stat-card__hint">Активных: <?= $activeProductsCount ?></div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-card__icon">🧾</div>
            <div class="admin-stat-card__title">Заказы товаров</div>
            <div class="admin-stat-card__value"><?= $ordersCount ?></div>
            <div class="admin-stat-card__hint">Новых: <?= $newOrdersCount ?></div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-card__icon">🖨️</div>
            <div class="admin-stat-card__title">Индивидуальные заказы</div>
            <div class="admin-stat-card__value"><?= $customOrdersCount ?></div>
            <div class="admin-stat-card__hint">Новых: <?= $newCustomOrdersCount ?></div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-card__icon">✉️</div>
            <div class="admin-stat-card__title">Сообщения</div>
            <div class="admin-stat-card__value"><?= $contactsCount ?></div>
            <div class="admin-stat-card__hint">Непрочитанных: <?= $unreadContactsCount ?></div>
        </div>
    </div>

    <div class="admin-dashboard-grid">
        <div class="admin-dashboard-panel admin-dashboard-panel--money">
            <div class="admin-panel-header">
                <div>
                    <h3>Финансы</h3>
                    <p>Ориентировочная сумма заказов</p>
                </div>
            </div>

            <div class="admin-money-row">
                <div>
                    <span>Обычные заказы</span>
                    <strong>€<?= number_format($totalRevenue, 2) ?></strong>
                </div>

                <div>
                    <span>Индивидуальные заказы</span>
                    <strong>€<?= number_format($customRevenue, 2) ?></strong>
                </div>

                <div>
                    <span>Всего</span>
                    <strong>€<?= number_format($totalRevenue + $customRevenue, 2) ?></strong>
                </div>
            </div>
        </div>

    </div>

    <div class="admin-dashboard-grid admin-dashboard-grid--three">
        <div class="admin-dashboard-panel">
            <div class="admin-panel-header">
                <div>
                    <h3>Последние заказы</h3>
                    <p>Новые покупки из каталога</p>
                </div>
                <a href="/3d_print_shop/admin/orders.php">Все</a>
            </div>

            <?php if ($latestOrders && $latestOrders->num_rows > 0): ?>
                <div class="admin-list">
                    <?php while ($order = $latestOrders->fetch_assoc()): ?>
                        <div class="admin-list-item">
                            <div>
                                <strong>#<?= (int)$order['id'] ?> — <?= e($order['customer_name']) ?></strong>
                                <span><?= e($order['customer_email']) ?></span>
                                <small><?= e($order['created_at']) ?></small>
                            </div>

                            <div class="admin-list-item__right">
                                <?= dashboardStatusBadge((string)$order['status']) ?>
                                <b>€<?= number_format((float)$order['total_amount'], 2) ?></b>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="message info">Заказов пока нет.</div>
            <?php endif; ?>
        </div>

        <div class="admin-dashboard-panel">
            <div class="admin-panel-header">
                <div>
                    <h3>Индивидуальные заказы</h3>
                    <p>Печать по файлам клиентов</p>
                </div>
                <a href="/3d_print_shop/admin/custom_orders.php">Все</a>
            </div>

            <?php if ($latestCustomOrders && $latestCustomOrders->num_rows > 0): ?>
                <div class="admin-list">
                    <?php while ($customOrder = $latestCustomOrders->fetch_assoc()): ?>
                        <div class="admin-list-item">
                            <div>
                                <strong>#<?= (int)$customOrder['id'] ?> — <?= e($customOrder['customer_name']) ?></strong>
                                <span><?= e($customOrder['material']) ?></span>
                                <small><?= e($customOrder['created_at']) ?></small>
                            </div>

                            <div class="admin-list-item__right">
                                <?= dashboardStatusBadge((string)$customOrder['status']) ?>
                                <b>€<?= number_format((float)$customOrder['estimated_price'], 2) ?></b>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="message info">Индивидуальных заказов пока нет.</div>
            <?php endif; ?>
        </div>

        <div class="admin-dashboard-panel">
            <div class="admin-panel-header">
                <div>
                    <h3>Сообщения</h3>
                    <p>Последние обращения клиентов</p>
                </div>
                <a href="/3d_print_shop/admin/contacts.php">Все</a>
            </div>

            <?php if ($latestContacts && $latestContacts->num_rows > 0): ?>
                <div class="admin-list">
                    <?php while ($contact = $latestContacts->fetch_assoc()): ?>
                        <div class="admin-list-item">
                            <div>
                                <strong><?= e($contact['name']) ?></strong>
                                <span><?= e($contact['subject'] ?: 'Без темы') ?></span>
                                <small><?= e($contact['created_at']) ?></small>
                            </div>

                            <div class="admin-list-item__right">
                                <?php if ((int)$contact['is_read'] === 1): ?>
                                    <span class="badge badge-done">Прочитано</span>
                                <?php else: ?>
                                    <span class="badge badge-processing">Новое</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="message info">Сообщений пока нет.</div>
            <?php endif; ?>
        </div>
    </div>

<?php
require_once __DIR__ . '/footer.php';