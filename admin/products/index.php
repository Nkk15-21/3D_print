<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../header.php';

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
        p.price,
        p.image_path,
        p.is_active,
        p.created_at,

        c.name AS category_name,
        c.name_ru AS category_name_ru,
        c.name_en AS category_name_en,
        c.name_et AS category_name_et,

        (
            SELECT pi.image_path
            FROM product_images pi
            WHERE pi.product_id = p.id
            ORDER BY pi.is_main DESC, pi.id ASC
            LIMIT 1
        ) AS gallery_main_image,

        (
            SELECT COUNT(*)
            FROM product_images pi2
            WHERE pi2.product_id = p.id
        ) AS images_count
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    ORDER BY p.created_at DESC, p.id DESC
");
?>

    <div class="page-header">
        <h2><?= e(t('admin.products.title')) ?></h2>
        <p><?= e(t('admin.products.subtitle')) ?></p>
        <a href="/3d_print_shop/admin/products/create.php" class="btn"><?= e(t('admin.products.add')) ?></a>
    </div>

<?php if ($result && $result->num_rows > 0): ?>
    <table>
        <tr>
            <th>ID</th>
            <th><?= e(t('common.file')) ?></th>
            <th><?= e(t('common.name')) ?></th>
            <th><?= e(t('common.category')) ?></th>
            <th><?= e(t('common.price')) ?></th>
            <th><?= e(t('common.status')) ?></th>
            <th><?= e(t('common.created_at')) ?></th>
            <th><?= e(t('common.actions')) ?></th>
        </tr>

        <?php while ($product = $result->fetch_assoc()): ?>
            <?php
            $imageToShow = $product['gallery_main_image'] ?: $product['image_path'];

            $categoryTitle = tdb([
                'name_ru' => $product['category_name_ru'] ?? '',
                'name_en' => $product['category_name_en'] ?? '',
                'name_et' => $product['category_name_et'] ?? '',
                'name' => $product['category_name'] ?? '',
            ], 'name');
            ?>
            <tr>
                <td><?= (int)$product['id'] ?></td>

                <td>
                    <?php if (!empty($imageToShow)): ?>
                        <img
                                src="/3d_print_shop/<?= e($imageToShow) ?>"
                                alt="<?= e(tdb($product, 'name')) ?>"
                                style="width: 60px; height: 60px; object-fit: cover; border-radius: 10px;"
                        >
                    <?php else: ?>
                        <span class="small-text"><?= e(t('admin.products.no_image')) ?></span>
                    <?php endif; ?>

                    <?php if ((int)$product['images_count'] > 1): ?>
                        <div class="small-text" style="margin-top: 6px;">
                            Фото: <?= (int)$product['images_count'] ?>
                        </div>
                    <?php endif; ?>
                </td>

                <td>
                    <strong><?= e(tdb($product, 'name')) ?></strong><br>
                    <span class="small-text"><?= e(tdb($product, 'short_description') ?: t('common.none')) ?></span>
                </td>

                <td><?= e($categoryTitle ?: t('common.none')) ?></td>

                <td>€<?= number_format((float)$product['price'], 2) ?></td>

                <td>
                    <?php if ((int)$product['is_active'] === 1): ?>
                        <span class="badge badge-done"><?= e(t('admin.products.active_label_short')) ?></span>
                    <?php else: ?>
                        <span class="badge badge-cancelled"><?= e(t('admin.products.hidden_label')) ?></span>
                    <?php endif; ?>
                </td>

                <td><?= e($product['created_at']) ?></td>

                <td>
                    <a
                            href="/3d_print_shop/admin/products/edit.php?id=<?= (int)$product['id'] ?>"
                            class="action-icon edit"
                            title="<?= e(t('common.edit')) ?>"
                    >
                        ✏️
                    </a>

                    <a
                            href="/3d_print_shop/admin/products/delete.php?id=<?= (int)$product['id'] ?>"
                            class="action-icon delete"
                            title="<?= e(t('common.delete')) ?>"
                            data-confirm="true"
                            data-confirm-title="<?= e(t('common.delete')) ?>"
                            data-confirm-text="<?= e(t('admin.products.delete_confirm')) ?>"
                            data-confirm-button="<?= e(t('common.delete')) ?>"
                    >
                        🗑️
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <div class="message info"><?= e(t('admin.products.empty')) ?></div>
<?php endif; ?>

<?php require_once __DIR__ . '/../footer.php'; ?>