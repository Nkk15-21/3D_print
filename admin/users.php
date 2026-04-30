<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/header.php';

$result = $mysqli->query("
    SELECT id, name, email, phone, role, created_at
    FROM users
    ORDER BY created_at DESC, id DESC
");
?>

    <div class="page-header">
        <h2><?= e(t('admin.users.title')) ?></h2>
        <p><?= e(t('admin.users.subtitle')) ?></p>
    </div>

<?php if ($result && $result->num_rows > 0): ?>
    <table>
        <tr>
            <th>ID</th>
            <th><?= e(t('common.name')) ?></th>
            <th><?= e(t('common.email')) ?></th>
            <th><?= e(t('common.phone')) ?></th>
            <th><?= e(t('common.status')) ?></th>
            <th><?= e(t('common.created_at')) ?></th>
        </tr>

        <?php while ($user = $result->fetch_assoc()): ?>
            <tr>
                <td><?= (int)$user['id'] ?></td>
                <td><?= e($user['name']) ?></td>
                <td><?= e($user['email']) ?></td>
                <td><?= e($user['phone'] ?: t('common.none')) ?></td>
                <td>
                    <?php if ($user['role'] === 'admin'): ?>
                        <span class="badge"><?= e(t('nav.admin')) ?></span>
                    <?php else: ?>
                        <span class="badge badge-new">user</span>
                    <?php endif; ?>
                </td>
                <td><?= e($user['created_at']) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <div class="message info"><?= e(t('admin.users.empty')) ?></div>
<?php endif; ?>

<?php
require_once __DIR__ . '/footer.php';