<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/header.php';

/* =========================================================
   ДЕЙСТВИЯ
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $contactId = isset($_POST['contact_id']) ? (int)$_POST['contact_id'] : 0;

    if ($contactId > 0) {
        if ($action === 'mark_read') {
            $stmt = $mysqli->prepare("
                UPDATE contacts
                SET is_read = 1
                WHERE id = ?
            ");
            $stmt->bind_param('i', $contactId);
            $stmt->execute();
            $stmt->close();

            setFlash('success', 'Сообщение отмечено как прочитанное.');
        }

        if ($action === 'mark_unread') {
            $stmt = $mysqli->prepare("
                UPDATE contacts
                SET is_read = 0
                WHERE id = ?
            ");
            $stmt->bind_param('i', $contactId);
            $stmt->execute();
            $stmt->close();

            setFlash('success', 'Сообщение отмечено как непрочитанное.');
        }
    }

    redirect('/3d_print_shop/admin/contacts.php');
}

$result = $mysqli->query("
    SELECT
        id,
        name,
        email,
        subject,
        message,
        is_read,
        created_at
    FROM contacts
    ORDER BY is_read ASC, created_at DESC, id DESC
");
?>

    <div class="page-header">
        <h2><?= e(t('admin.contacts.title')) ?></h2>
        <p><?= e(t('admin.contacts.subtitle')) ?></p>
    </div>

<?php if ($result && $result->num_rows > 0): ?>
    <table>
        <tr>
            <th>ID</th>
            <th><?= e(t('common.name')) ?></th>
            <th><?= e(t('common.email')) ?></th>
            <th><?= e(t('common.subject')) ?></th>
            <th><?= e(t('common.message')) ?></th>
            <th><?= e(t('common.status')) ?></th>
            <th><?= e(t('common.date')) ?></th>
            <th><?= e(t('common.actions')) ?></th>
        </tr>

        <?php while ($contact = $result->fetch_assoc()): ?>
            <tr>
                <td><?= (int)$contact['id'] ?></td>
                <td><?= e($contact['name']) ?></td>
                <td>
                    <a href="mailto:<?= e($contact['email']) ?>">
                        <?= e($contact['email']) ?>
                    </a>
                </td>
                <td><?= e($contact['subject'] ?: t('common.none')) ?></td>
                <td>
                    <div class="admin-message-box">
                        <?= nl2br(e($contact['message'])) ?>
                    </div>
                </td>
                <td>
                    <?php if ((int)$contact['is_read'] === 1): ?>
                        <span class="badge badge-done">Прочитано</span>
                    <?php else: ?>
                        <span class="badge badge-processing">Непрочитано</span>
                    <?php endif; ?>
                </td>
                <td><?= e($contact['created_at']) ?></td>
                <td>
                    <form method="post" class="admin-inline-form">
                        <input type="hidden" name="contact_id" value="<?= (int)$contact['id'] ?>">

                        <?php if ((int)$contact['is_read'] === 1): ?>
                            <input type="hidden" name="action" value="mark_unread">
                            <button type="submit" class="btn btn-secondary">Сделать непрочитанным</button>
                        <?php else: ?>
                            <input type="hidden" name="action" value="mark_read">
                            <button type="submit"><?= e(t('common.save')) ?></button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <div class="message info"><?= e(t('admin.contacts.empty')) ?></div>
<?php endif; ?>

<?php
require_once __DIR__ . '/footer.php';