<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../header.php';

$errors = [];

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    if ($nameRu === '' && $nameEn === '' && $nameEt === '') {
        $errors[] = t('admin.products.name_error');
    }

    if ($price <= 0) {
        $errors[] = t('admin.products.price_error');
    }

    $legacyName = $nameRu !== '' ? $nameRu : ($nameEn !== '' ? $nameEn : $nameEt);
    $legacyShort = $shortRu !== '' ? $shortRu : ($shortEn !== '' ? $shortEn : $shortEt);
    $legacyDesc = $descRu !== '' ? $descRu : ($descEn !== '' ? $descEn : $descEt);

    $uploadedImages = [];
    $mainImagePath = null;

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
            $uploadedImages[] = $savedPath;

            if ($mainImagePath === null) {
                $mainImagePath = $savedPath;
            }
        }
    }

    if (!$errors) {
        $stmt = $mysqli->prepare("
            INSERT INTO products (
                category_id,
                name,
                name_ru,
                name_en,
                name_et,
                short_description,
                short_description_ru,
                short_description_en,
                short_description_et,
                description,
                description_ru,
                description_en,
                description_et,
                price,
                image_path,
                is_active
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            'issssssssssssdsi',
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
            $mainImagePath,
            $isActive
        );

        if ($stmt->execute()) {
            $productId = $stmt->insert_id;
            $stmt->close();

            if ($uploadedImages) {
                foreach ($uploadedImages as $index => $imagePath) {
                    $isMain = $index === 0 ? 1 : 0;

                    $imgStmt = $mysqli->prepare("
                        INSERT INTO product_images (product_id, image_path, is_main)
                        VALUES (?, ?, ?)
                    ");
                    $imgStmt->bind_param('isi', $productId, $imagePath, $isMain);
                    $imgStmt->execute();
                    $imgStmt->close();
                }
            }

            setFlash('success', t('admin.products.create_success'));
            redirect('/3d_print_shop/admin/products/index.php');
        } else {
            $stmt->close();
            $errors[] = t('admin.products.save_error');
        }
    }
}
?>

    <div class="page-header">
        <h2><?= e(t('admin.products.create')) ?></h2>
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
        <input type="text" id="name_ru" name="name_ru" value="<?= e(old('name_ru')) ?>">

        <label for="short_description_ru">Краткое описание (RU)</label>
        <input type="text" id="short_description_ru" name="short_description_ru" value="<?= e(old('short_description_ru')) ?>">

        <label for="description_ru">Описание (RU)</label>
        <textarea id="description_ru" name="description_ru"><?= e(old('description_ru')) ?></textarea>

        <h3>EN</h3>
        <label for="name_en">Name (EN)</label>
        <input type="text" id="name_en" name="name_en" value="<?= e(old('name_en')) ?>">

        <label for="short_description_en">Short description (EN)</label>
        <input type="text" id="short_description_en" name="short_description_en" value="<?= e(old('short_description_en')) ?>">

        <label for="description_en">Description (EN)</label>
        <textarea id="description_en" name="description_en"><?= e(old('description_en')) ?></textarea>

        <h3>ET</h3>
        <label for="name_et">Nimi (ET)</label>
        <input type="text" id="name_et" name="name_et" value="<?= e(old('name_et')) ?>">

        <label for="short_description_et">Lühikirjeldus (ET)</label>
        <input type="text" id="short_description_et" name="short_description_et" value="<?= e(old('short_description_et')) ?>">

        <label for="description_et">Kirjeldus (ET)</label>
        <textarea id="description_et" name="description_et"><?= e(old('description_et')) ?></textarea>

        <label for="price"><?= e(t('common.price')) ?> (€)</label>
        <input type="number" step="0.01" min="0.01" id="price" name="price" value="<?= e(old('price')) ?>" required>

        <label for="category_id"><?= e(t('common.category')) ?></label>
        <select id="category_id" name="category_id">
            <option value=""><?= e(t('common.none')) ?></option>
            <?php foreach ($categories as $category): ?>
                <option
                        value="<?= (int)$category['id'] ?>"
                    <?= old('category_id') === (string)$category['id'] ? 'selected' : '' ?>
                >
                    <?= e(tdb($category, 'name')) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="images">Фотографии товара</label>
        <input type="file" id="images" name="images[]" accept=".jpg,.jpeg,.png,.webp" multiple>

        <label style="display: flex; align-items: center; gap: 10px; font-weight: 600;">
            <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                <?= old('is_active', '1') === '1' ? 'checked' : '' ?>
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