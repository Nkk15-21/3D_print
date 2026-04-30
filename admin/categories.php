<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/header.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));

    if ($action === 'create') {
        $nameRu = trim((string)($_POST['name_ru'] ?? ''));
        $nameEn = trim((string)($_POST['name_en'] ?? ''));
        $nameEt = trim((string)($_POST['name_et'] ?? ''));

        $descRu = trim((string)($_POST['description_ru'] ?? ''));
        $descEn = trim((string)($_POST['description_en'] ?? ''));
        $descEt = trim((string)($_POST['description_et'] ?? ''));

        if ($nameRu === '' && $nameEn === '' && $nameEt === '') {
            $errors[] = t('admin.categories.name_error');
        }

        $legacyName = $nameRu !== '' ? $nameRu : ($nameEn !== '' ? $nameEn : $nameEt);
        $legacyDesc = $descRu !== '' ? $descRu : ($descEn !== '' ? $descEn : $descEt);

        if (!$errors) {
            $stmt = $mysqli->prepare("
                INSERT INTO categories (
                    name,
                    name_ru,
                    name_en,
                    name_et,
                    description,
                    description_ru,
                    description_en,
                    description_et
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                    'ssssssss',
                    $legacyName,
                    $nameRu,
                    $nameEn,
                    $nameEt,
                    $legacyDesc,
                    $descRu,
                    $descEn,
                    $descEt
            );
            $stmt->execute();
            $stmt->close();

            setFlash('success', t('admin.categories.create_success'));
            redirect('/3d_print_shop/admin/categories.php');
        }
    }

    if ($action === 'delete') {
        $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;

        if ($categoryId <= 0) {
            setFlash('error', t('admin.categories.delete_error'));
            redirect('/3d_print_shop/admin/categories.php');
        }

        $stmt = $mysqli->prepare("
            SELECT id
            FROM categories
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();
        $category = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$category) {
            setFlash('error', t('admin.categories.not_found'));
            redirect('/3d_print_shop/admin/categories.php');
        }

        $stmt = $mysqli->prepare("
            SELECT COUNT(*) AS cnt
            FROM products
            WHERE category_id = ?
        ");
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();
        $productsCount = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
        $stmt->close();

        if ($productsCount > 0) {
            setFlash('error', t('admin.categories.products_error') . ' (' . $productsCount . ')');
            redirect('/3d_print_shop/admin/categories.php');
        }

        $stmt = $mysqli->prepare("
            DELETE FROM categories
            WHERE id = ?
        ");
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();
        $stmt->close();

        setFlash('success', t('admin.categories.delete_success'));
        redirect('/3d_print_shop/admin/categories.php');
    }
}

$result = $mysqli->query("
    SELECT
        c.id,
        c.name,
        c.name_ru,
        c.name_en,
        c.name_et,
        c.description,
        c.description_ru,
        c.description_en,
        c.description_et,
        c.created_at,
        COUNT(p.id) AS products_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id
    ORDER BY c.created_at DESC, c.id DESC
");
?>

    <div class="page-header">
        <h2><?= e(t('admin.categories.title')) ?></h2>
        <p><?= e(t('admin.categories.subtitle')) ?></p>
    </div>

<?php if (!empty($errors)): ?>
    <div class="message error">
        <?php foreach ($errors as $error): ?>
            <div><?= e($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

    <div class="card" style="margin-bottom: 24px;">
        <h3><?= e(t('admin.categories.add')) ?></h3>

        <form method="post">
            <input type="hidden" name="action" value="create">

            <h3>RU</h3>
            <label for="name_ru">Название (RU)</label>
            <input type="text" id="name_ru" name="name_ru">

            <label for="description_ru">Описание (RU)</label>
            <textarea id="description_ru" name="description_ru"></textarea>

            <h3>EN</h3>
            <label for="name_en">Name (EN)</label>
            <input type="text" id="name_en" name="name_en">

            <label for="description_en">Description (EN)</label>
            <textarea id="description_en" name="description_en"></textarea>

            <h3>ET</h3>
            <label for="name_et">Nimi (ET)</label>
            <input type="text" id="name_et" name="name_et">

            <label for="description_et">Kirjeldus (ET)</label>
            <textarea id="description_et" name="description_et"></textarea>

            <button type="submit"><?= e(t('common.add')) ?></button>
        </form>
    </div>

<?php if ($result && $result->num_rows > 0): ?>
    <table>
        <tr>
            <th>ID</th>
            <th><?= e(t('common.name')) ?></th>
            <th><?= e(t('common.description')) ?></th>
            <th><?= e(t('admin.categories.products_count')) ?></th>
            <th><?= e(t('common.created_at')) ?></th>
            <th><?= e(t('common.actions')) ?></th>
        </tr>

        <?php while ($category = $result->fetch_assoc()): ?>
            <tr>
                <td><?= (int)$category['id'] ?></td>
                <td><?= e(tdb($category, 'name')) ?></td>
                <td><?= e(tdb($category, 'description') ?: t('common.none')) ?></td>
                <td><?= (int)$category['products_count'] ?></td>
                <td><?= e($category['created_at']) ?></td>
                <td>
                    <?php if ((int)$category['products_count'] > 0): ?>
                        <span class="small-text"><?= e(t('admin.categories.cannot_delete')) ?></span>
                    <?php else: ?>
                        <form method="post" class="admin-inline-form">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="category_id" value="<?= (int)$category['id'] ?>">

                            <button
                                    type="button"
                                    class="btn btn-danger"
                                    data-confirm="true"
                                    data-confirm-title="<?= e(t('common.delete')) ?>"
                                    data-confirm-text="<?= e(t('admin.categories.delete_confirm')) ?>"
                                    data-confirm-button="<?= e(t('common.delete')) ?>"
                            >
                                <?= e(t('common.delete')) ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <div class="message info"><?= e(t('admin.categories.empty')) ?></div>
<?php endif; ?>

<?php
require_once __DIR__ . '/footer.php';