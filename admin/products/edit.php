<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../header.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    setFlash('error', t('admin.products.not_found'));
    redirect('/3d_print_shop/admin/products/index.php');
}

$stmt = $mysqli->prepare("
    SELECT *
    FROM products
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param('i', $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    setFlash('error', t('admin.products.not_found'));
    redirect('/3d_print_shop/admin/products/index.php');
}

$categoriesResult = $mysqli->query("
    SELECT
        id,
        name,
        name_ru,
        name_en,
        name_et
    FROM categories
    ORDER BY COALESCE(name_ru, name, id) ASC
");
$categories = $categoriesResult ? $categoriesResult->fetch_all(MYSQLI_ASSOC) : [];

$imagesStmt = $mysqli->prepare("
    SELECT id, image_path, is_main
    FROM product_images
    WHERE product_id = ?
    ORDER BY is_main DESC, id ASC
");
$imagesStmt->bind_param('i', $productId);
$imagesStmt->execute();
$productImages = $imagesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$imagesStmt->close();

$errors = [];

/* =========================================================
   УДАЛЕНИЕ ФОТО
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_image') {
    $imageId = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 0;

    if ($imageId > 0) {
        $stmt = $mysqli->prepare("
            SELECT id, image_path, is_main
            FROM product_images
            WHERE id = ? AND product_id = ?
            LIMIT 1
        ");
        $stmt->bind_param('ii', $imageId, $productId);
        $stmt->execute();
        $imageRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($imageRow) {
            $stmt = $mysqli->prepare("
                DELETE FROM product_images
                WHERE id = ?
            ");
            $stmt->bind_param('i', $imageId);
            $stmt->execute();
            $stmt->close();

            $filePath = __DIR__ . '/../../' . $imageRow['image_path'];
            if (is_file($filePath)) {
                unlink($filePath);
            }

            if ((int)$imageRow['is_main'] === 1) {
                $stmt = $mysqli->prepare("
                    SELECT id, image_path
                    FROM product_images
                    WHERE product_id = ?
                    ORDER BY id ASC
                    LIMIT 1
                ");
                $stmt->bind_param('i', $productId);
                $stmt->execute();
                $newMain = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($newMain) {
                    $stmt = $mysqli->prepare("
                        UPDATE product_images
                        SET is_main = 1
                        WHERE id = ?
                    ");
                    $stmt->bind_param('i', $newMain['id']);
                    $stmt->execute();
                    $stmt->close();

                    $stmt = $mysqli->prepare("
                        UPDATE products
                        SET image_path = ?
                        WHERE id = ?
                    ");
                    $stmt->bind_param('si', $newMain['image_path'], $productId);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $stmt = $mysqli->prepare("
                        UPDATE products
                        SET image_path = NULL
                        WHERE id = ?
                    ");
                    $stmt->bind_param('i', $productId);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            setFlash('success', 'Фотография удалена.');
        }
    }

    redirect('/3d_print_shop/admin/products/edit.php?id=' . $productId);
}

/* =========================================================
   СДЕЛАТЬ ГЛАВНОЙ
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_main_image') {
    $imageId = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 0;

    if ($imageId > 0) {
        $stmt = $mysqli->prepare("
            UPDATE product_images
            SET is_main = 0
            WHERE product_id = ?
        ");
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $stmt->close();

        $stmt = $mysqli->prepare("
            UPDATE product_images
            SET is_main = 1
            WHERE id = ? AND product_id = ?
        ");
        $stmt->bind_param('ii', $imageId, $productId);
        $stmt->execute();
        $stmt->close();

        $stmt = $mysqli->prepare("
            SELECT image_path
            FROM product_images
            WHERE id = ? AND product_id = ?
            LIMIT 1
        ");
        $stmt->bind_param('ii', $imageId, $productId);
        $stmt->execute();
        $mainRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($mainRow) {
            $stmt = $mysqli->prepare("
                UPDATE products
                SET image_path = ?
                WHERE id = ?
            ");
            $stmt->bind_param('si', $mainRow['image_path'], $productId);
            $stmt->execute();
            $stmt->close();

            setFlash('success', 'Главное фото обновлено.');
        }
    }

    redirect('/3d_print_shop/admin/products/edit.php?id=' . $productId);
}

/* =========================================================
   ОБНОВЛЕНИЕ ТОВАРА
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $nameRu = trim((string)($_POST['name_ru'] ?? ''));
    $nameEn = trim((string)($_POST['name_en'] ?? ''));
    $nameEt = trim((string)($_POST['name_et'] ?? ''));

    $shortRu = trim((string)($_POST['short_description_ru'] ?? ''));
    $shortEn = trim((string)($_POST['short_description_en'] ?? ''));
    $shortEt = trim((string)($_POST['short_description_et'] ?? ''));

    $descRu = trim((string)($_POST['description_ru'] ?? ''));
    $descEn = trim((string)($_POST['description_en'] ?? ''));
    $descEt = trim((string)($_POST['description_et'] ?? ''));

    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $categoryId = isset($_POST['category_id']) && $_POST['category_id'] !== ''
        ? (int)$_POST['category_id']
        : null;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $currentImagePath = $product['image_path'];

    if ($nameRu === '' && $nameEn === '' && $nameEt === '') {
        $errors[] = t('admin.products.name_error');
    }

    if ($price <= 0) {
        $errors[] = t('admin.products.price_error');
    }

    $legacyName = $nameRu !== '' ? $nameRu : ($nameEn !== '' ? $nameEn : $nameEt);
    $legacyShort = $shortRu !== '' ? $shortRu : ($shortEn !== '' ? $shortEn : $shortEt);
    $legacyDesc = $descRu !== '' ? $descRu : ($descEn !== '' ? $descEn : $descEt);

    $newUploadedImages = [];

    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $uploadDir = __DIR__ . '/../../uploads/images/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($_FILES['images']['name'] as $index => $originalName) {
            $originalName = (string)$originalName;
            $tmpPath = (string)($_FILES['images']['tmp_name'][$index] ?? '');
            $errorCode = (int)($_FILES['images']['error'][$index] ?? UPLOAD_ERR_NO_FILE);
            $fileSize = (int)($_FILES['images']['size'][$index] ?? 0);

            if ($errorCode === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($errorCode !== UPLOAD_ERR_OK) {
                $errors[] = t('admin.products.image_upload_error');
                continue;
            }

            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!in_array($extension, $allowedExtensions, true)) {
                $errors[] = t('admin.products.image_type_error');
                continue;
            }

            if ($fileSize > 5 * 1024 * 1024) {
                $errors[] = t('admin.products.image_size_error');
                continue;
            }

            $newFileName = uniqid('product_', true) . '.' . $extension;
            $destination = $uploadDir . $newFileName;

            if (!move_uploaded_file($tmpPath, $destination)) {
                $errors[] = t('admin.products.image_save_error');
                continue;
            }

            $savedPath = 'uploads/images/' . $newFileName;
            $newUploadedImages[] = $savedPath;

            if ($currentImagePath === null || $currentImagePath === '') {
                $currentImagePath = $savedPath;
            }
        }
    }

    if (!$errors) {
        $stmt = $mysqli->prepare("
            UPDATE products
            SET
                category_id = ?,
                name = ?,
                name_ru = ?,
                name_en = ?,
                name_et = ?,
                short_description = ?,
                short_description_ru = ?,
                short_description_en = ?,
                short_description_et = ?,
                description = ?,
                description_ru = ?,
                description_en = ?,
                description_et = ?,
                price = ?,
                image_path = ?,
                is_active = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            'issssssssssssdsii',
            $categoryId,
            $legacyName,
            $nameRu,
            $nameEn,
            $nameEt,
            $legacyShort,
            $shortRu,
            $shortEn,
            $shortEt,
            $legacyDesc,
            $descRu,
            $descEn,
            $descEt,
            $price,
            $currentImagePath,
            $isActive,
            $productId
        );

        if ($stmt->execute()) {
            $stmt->close();

            if ($newUploadedImages) {
                $hasImagesStmt = $mysqli->prepare("
                    SELECT COUNT(*) AS cnt
                    FROM product_images
                    WHERE product_id = ?
                ");
                $hasImagesStmt->bind_param('i', $productId);
                $hasImagesStmt->execute();
                $hasImagesCount = (int)($hasImagesStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
                $hasImagesStmt->close();

                foreach ($newUploadedImages as $index => $imagePath) {
                    $isMain = ($hasImagesCount === 0 && $index === 0) ? 1 : 0;

                    $imgStmt = $mysqli->prepare("
                        INSERT INTO product_images (product_id, image_path, is_main)
                        VALUES (?, ?, ?)
                    ");
                    $imgStmt->bind_param('isi', $productId, $imagePath, $isMain);
                    $imgStmt->execute();
                    $imgStmt->close();

                    if ($isMain === 1) {
                        $stmt = $mysqli->prepare("
                            UPDATE products
                            SET image_path = ?
                            WHERE id = ?
                        ");
                        $stmt->bind_param('si', $imagePath, $productId);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }

            setFlash('success', t('admin.products.update_success'));
            redirect('/3d_print_shop/admin/products/edit.php?id=' . $productId);
        } else {
            $stmt->close();
            $errors[] = t('admin.products.update_error');
        }
    }

    $product['name_ru'] = $nameRu;
    $product['name_en'] = $nameEn;
    $product['name_et'] = $nameEt;
    $product['short_description_ru'] = $shortRu;
    $product['short_description_en'] = $shortEn;
    $product['short_description_et'] = $shortEt;
    $product['description_ru'] = $descRu;
    $product['description_en'] = $descEn;
    $product['description_et'] = $descEt;
    $product['price'] = $price;
    $product['category_id'] = $categoryId;
    $product['image_path'] = $currentImagePath;
    $product['is_active'] = $isActive;
}

$imagesStmt = $mysqli->prepare("
    SELECT id, image_path, is_main
    FROM product_images
    WHERE product_id = ?
    ORDER BY is_main DESC, id ASC
");
$imagesStmt->bind_param('i', $productId);
$imagesStmt->execute();
$productImages = $imagesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$imagesStmt->close();
?>

    <div class="page-header">
        <h2><?= e(t('admin.products.edit')) ?></h2>
        <p><?= e(t('admin.products.subtitle')) ?></p>
    </div>

<?php if (!empty($errors)): ?>
    <div class="message error">
        <?php foreach ($errors as $error): ?>
            <div><?= e($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <h3>RU</h3>
        <label for="name_ru">Название (RU)</label>
        <input type="text" id="name_ru" name="name_ru" value="<?= e((string)($product['name_ru'] ?? '')) ?>">

        <label for="short_description_ru">Краткое описание (RU)</label>
        <input type="text" id="short_description_ru" name="short_description_ru" value="<?= e((string)($product['short_description_ru'] ?? '')) ?>">

        <label for="description_ru">Описание (RU)</label>
        <textarea id="description_ru" name="description_ru"><?= e((string)($product['description_ru'] ?? '')) ?></textarea>

        <h3>EN</h3>
        <label for="name_en">Name (EN)</label>
        <input type="text" id="name_en" name="name_en" value="<?= e((string)($product['name_en'] ?? '')) ?>">

        <label for="short_description_en">Short description (EN)</label>
        <input type="text" id="short_description_en" name="short_description_en" value="<?= e((string)($product['short_description_en'] ?? '')) ?>">

        <label for="description_en">Description (EN)</label>
        <textarea id="description_en" name="description_en"><?= e((string)($product['description_en'] ?? '')) ?></textarea>

        <h3>ET</h3>
        <label for="name_et">Nimi (ET)</label>
        <input type="text" id="name_et" name="name_et" value="<?= e((string)($product['name_et'] ?? '')) ?>">

        <label for="short_description_et">Lühikirjeldus (ET)</label>
        <input type="text" id="short_description_et" name="short_description_et" value="<?= e((string)($product['short_description_et'] ?? '')) ?>">

        <label for="description_et">Kirjeldus (ET)</label>
        <textarea id="description_et" name="description_et"><?= e((string)($product['description_et'] ?? '')) ?></textarea>

        <label for="price"><?= e(t('common.price')) ?> (€)</label>
        <input type="number" step="0.01" min="0.01" id="price" name="price" value="<?= e((string)$product['price']) ?>" required>

        <label for="category_id"><?= e(t('common.category')) ?></label>
        <select id="category_id" name="category_id">
            <option value=""><?= e(t('common.none')) ?></option>
            <?php foreach ($categories as $category): ?>
                <option
                        value="<?= (int)$category['id'] ?>"
                    <?= (string)$product['category_id'] === (string)$category['id'] ? 'selected' : '' ?>
                >
                    <?= e(tdb($category, 'name')) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($productImages): ?>
            <div style="margin-bottom: 20px;">
                <p><strong>Фотографии товара:</strong></p>

                <div style="display:flex; gap:14px; flex-wrap:wrap;">
                    <?php foreach ($productImages as $image): ?>
                        <div style="width:160px; background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:10px; box-shadow:0 6px 18px rgba(15,23,42,0.05);">
                            <img
                                    src="/3d_print_shop/<?= e((string)$image['image_path']) ?>"
                                    alt="<?= e(tdb($product, 'name')) ?>"
                                    style="width:100%; height:120px; object-fit:cover; border-radius:12px; margin-bottom:10px;"
                            >

                            <?php if ((int)$image['is_main'] === 1): ?>
                                <div class="badge badge-done" style="margin-bottom:10px;">Главное фото</div>
                            <?php else: ?>
                                <form method="post" style="background:none; box-shadow:none; border:none; padding:0; margin-bottom:10px;">
                                    <input type="hidden" name="action" value="set_main_image">
                                    <input type="hidden" name="image_id" value="<?= (int)$image['id'] ?>">
                                    <button type="submit" class="btn btn-secondary" style="width:100%;">Сделать главным</button>
                                </form>
                            <?php endif; ?>

                            <form method="post" style="background:none; box-shadow:none; border:none; padding:0;">
                                <input type="hidden" name="action" value="delete_image">
                                <input type="hidden" name="image_id" value="<?= (int)$image['id'] ?>">
                                <button
                                        type="submit"
                                        class="btn btn-danger"
                                        style="width:100%;"
                                        data-confirm="true"
                                        data-confirm-title="Удаление фото"
                                        data-confirm-text="Удалить эту фотографию?"
                                        data-confirm-button="Удалить"
                                >
                                    Удалить
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <label for="images">Добавить новые фотографии</label>
        <input type="file" id="images" name="images[]" accept=".jpg,.jpeg,.png,.webp" multiple>

        <label style="display: flex; align-items: center; gap: 10px; font-weight: 600;">
            <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                <?= (int)$product['is_active'] === 1 ? 'checked' : '' ?>
                    style="width: auto; margin: 0;"
            >
            <?= e(t('admin.products.active_label')) ?>
        </label>

        <div style="margin-top: 20px; display: flex; gap: 12px; flex-wrap: wrap;">
            <button type="submit"><?= e(t('common.save')) ?></button>
            <a href="/3d_print_shop/admin/products/index.php" class="btn btn-secondary"><?= e(t('common.cancel')) ?></a>
        </div>
    </form>

<?php require_once __DIR__ . '/../footer.php'; ?>