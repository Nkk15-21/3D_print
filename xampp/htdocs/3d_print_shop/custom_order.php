<?php
// custom_order.php — индивидуальный заказ с загрузкой 3D-файла и примерным расчётом цены

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

// Список материалов Bambu Lab для выпадающего списка
$materialsList = [
    'Bambu PLA Basic',
    'Bambu PLA Matte',
    'Bambu PLA Tough',
    'Bambu PLA Silk',
    'Bambu PLA Silk+',
    'Bambu PLA Galaxy',
    'Bambu PLA Marble',
    'Bambu PLA Translucent',
    'Bambu PLA Dynamic',
    'Bambu PLA Glow',
    'Bambu PLA Metal',
    'Bambu PLA Wood',
    'Bambu PLA-CF',

    'Bambu PETG Basic',
    'Bambu PETG Translucent',
    'Bambu PETG HF',
    'Bambu PETG-CF',

    'Bambu ABS',
    'Bambu ASA',
    'Bambu PC',

    'Bambu PA (Nylon)',
    'Bambu PA-CF',
    'Bambu PAHT-CF',

    'Bambu TPU 95A',

    'Bambu Support G',
    'Bambu Support W',

    'Другое (указать в комментарии)',
];

// Пользователь должен быть залогинен
if (!isset($_SESSION['user_id'])) {
    ?>
    <h2>Индивидуальный заказ</h2>
    <p>Чтобы оформить индивидуальный заказ, нужно <a href="login.php">войти</a> или <a href="register.php">зарегистрироваться</a>.</p>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Берём данные пользователя (для записи в таблицу)
$stmtUser = $mysqli->prepare("SELECT name, email, phone FROM users WHERE id = ? LIMIT 1");
if ($stmtUser) {
    $stmtUser->bind_param('i', $userId);
    $stmtUser->execute();
    $stmtUser->bind_result($uName, $uEmail, $uPhone);
    if (!$stmtUser->fetch()) {
        $uName = '';
        $uEmail = '';
        $uPhone = '';
    }
    $stmtUser->close();
} else {
    $uName = '';
    $uEmail = '';
    $uPhone = '';
}

$errors = [];
$successMessage = '';

$material = '';
$color = '';
$layer_height = '';
$infill = '';
$weight = '';
$comment = '';

$estimatedPrice = null;   // то, что сохраняем и показываем
$layerVal = null;         // числовые значения после валидации
$infillVal = null;
$weightVal = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Читаем поля формы
    $material = $_POST['material'] ?? '';
    $color = trim($_POST['color'] ?? '');
    $layer_height = trim($_POST['layer_height'] ?? '');
    $infill = trim($_POST['infill'] ?? '');
    $weight = trim($_POST['weight'] ?? '');
    $comment = trim($_POST['comment'] ?? '');

    // --- Валидация материала ---
    if ($material === '') {
        $errors[] = 'Выберите материал.';
    } elseif (!in_array($material, $materialsList, true)) {
        // На всякий случай защищаемся от ручного POST
        $errors[] = 'Неверное значение материала.';
    }

    // --- Валидация высоты слоя (если указана) ---
    if ($layer_height !== '') {
        $layer_height = str_replace(',', '.', $layer_height);
        if (!is_numeric($layer_height)) {
            $errors[] = 'Высота слоя должна быть числом.';
        } else {
            $lhVal = (float)$layer_height;
            if ($lhVal < 0.05 || $lhVal > 1) {
                $errors[] = 'Высота слоя должна быть в диапазоне 0.05–1 мм.';
            } else {
                $layerVal = $lhVal;
            }
        }
    }

    // --- Валидация заполняемости (если указана) ---
    if ($infill !== '') {
        if (!ctype_digit($infill)) {
            $errors[] = 'Заполняемость должна быть целым числом от 0 до 100.';
        } else {
            $infVal = (int)$infill;
            if ($infVal < 0 || $infVal > 100) {
                $errors[] = 'Заполняемость должна быть от 0 до 100%.';
            } else {
                $infillVal = $infVal;
            }
        }
    }

    // --- Валидация веса (если указан) ---
    if ($weight !== '') {
        $weightClean = str_replace(',', '.', $weight);
        if (!is_numeric($weightClean)) {
            $errors[] = 'Вес должен быть числом (в граммах).';
        } else {
            $wVal = (float)$weightClean;
            if ($wVal <= 0 || $wVal > 2000) {
                $errors[] = 'Вес должен быть в диапазоне 1–2000 г.';
            } else {
                $weightVal = $wVal;
            }
        }
    }

    // --- Предварительный расчёт цены (если есть вес) ---
    if ($weightVal !== null && $weightVal > 0) {
        // Группы материалов с разной ценой за грамм
        $matLower = mb_strtolower($material, 'UTF-8');
        $pricePerGram = 0.06; // базовый PLA

        if (mb_stripos($matLower, 'petg', 0, 'UTF-8') !== false) {
            $pricePerGram = 0.07;
        } elseif (mb_stripos($matLower, 'abs', 0, 'UTF-8') !== false || mb_stripos($matLower, 'asa', 0, 'UTF-8') !== false) {
            $pricePerGram = 0.08;
        } elseif (mb_stripos($matLower, 'pc', 0, 'UTF-8') !== false) {
            $pricePerGram = 0.09;
        } elseif (mb_stripos($matLower, 'paht', 0, 'UTF-8') !== false || mb_stripos($matLower, 'pa-cf', 0, 'UTF-8') !== false) {
            $pricePerGram = 0.11;
        } elseif (mb_stripos($matLower, 'pa', 0, 'UTF-8') !== false) {
            $pricePerGram = 0.10;
        } elseif (mb_stripos($matLower, 'tpu', 0, 'UTF-8') !== false) {
            $pricePerGram = 0.09;
        } elseif (mb_stripos($matLower, 'support', 0, 'UTF-8') !== false) {
            $pricePerGram = 0.05;
        }

        // Берём 0.2 мм как "базовую" высоту слоя, если пользователь ничего не указал
        $layerBase = 0.2;
        $lh = $layerVal ?? $layerBase;

        // Чем меньше слой, тем дольше печать → дороже
        $layerFactor = $layerBase / $lh;
        if ($layerFactor < 0.7) $layerFactor = 0.7;
        if ($layerFactor > 1.6) $layerFactor = 1.6;

        // Заполнение: по умолчанию 20%, коэффициент от 0.3 до 1.5
        $infillPercent = $infillVal !== null ? $infillVal : 20;
        $infillFactor = $infillPercent / 100.0;
        if ($infillFactor < 0.3) $infillFactor = 0.3;
        if ($infillFactor > 1.5) $infillFactor = 1.5;

        $baseCost = 3.0; // стартовая стоимость за запуск принтера
        $estimatedPrice = $baseCost + $weightVal * $pricePerGram * $infillFactor * $layerFactor;
        $estimatedPrice = round($estimatedPrice, 2);
    }

    // --- Проверка файла ---
    $savedPath = null;
    $fileTmp = null;
    $ext = null;

    if (!isset($_FILES['model_file']) || $_FILES['model_file']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Загрузите 3D-файл модели.';
    } else {
        $fileError = $_FILES['model_file']['error'];

        if ($fileError !== UPLOAD_ERR_OK) {
            $errors[] = 'Ошибка загрузки файла.';
        } else {
            $fileName = $_FILES['model_file']['name'];
            $fileTmp  = $_FILES['model_file']['tmp_name'];
            $fileSize = $_FILES['model_file']['size'];

            // Разрешённые расширения
            $allowedExtensions = ['stl', 'obj', 'step', 'stp'];
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExtensions, true)) {
                $errors[] = 'Разрешены файлы только: ' . implode(', ', $allowedExtensions) . '.';
            }

            // Ограничение размера, например 20 МБ
            if ($fileSize > 20 * 1024 * 1024) {
                $errors[] = 'Файл слишком большой (максимум 20 МБ).';
            }
        }
    }

    // --- Сохраняем файл, если ошибок нет ---
    if (empty($errors) && $fileTmp !== null && $ext !== null) {
        $uploadDir = __DIR__ . '/uploads/models/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $newFileName = 'model_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
        $targetPath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmp, $targetPath)) {
            $savedPath = 'uploads/models/' . $newFileName; // относительный путь
        } else {
            $errors[] = 'Не удалось сохранить файл на сервере.';
        }
    }

    // --- Сохраняем запись в БД ---
    if (empty($errors) && $savedPath !== null) {
        $stmt = $mysqli->prepare("
            INSERT INTO custom_orders
                (user_id, customer_name, customer_email, customer_phone,
                 material, color, layer_height, infill,
                 estimated_price, status, model_file, comment)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', ?, ?)
        ");

        if ($stmt) {
            $layerDb = $layerVal !== null ? $layerVal : null;
            $infillDb = $infillVal !== null ? $infillVal : null;
            $priceDb = $estimatedPrice !== null ? $estimatedPrice : null;

            $stmt->bind_param(
                'isssssdidss',
                $userId,
                $uName,
                $uEmail,
                $uPhone,
                $material,
                $color,
                $layerDb,
                $infillDb,
                $priceDb,
                $savedPath,
                $comment
            );

            if ($stmt->execute()) {
                $successMessage = 'Заявка на индивидуальный заказ отправлена! Мы свяжемся с вами по e-mail.';
                if ($estimatedPrice !== null) {
                    $successMessage .= ' Ориентировочная стоимость печати: ~' .
                        number_format($estimatedPrice, 2, ',', ' ') . ' €.';
                }

                // Очищаем поля формы
                $material = '';
                $color = '';
                $layer_height = '';
                $infill = '';
                $weight = '';
                $comment = '';
            } else {
                $errors[] = 'Ошибка сохранения заявки: ' . htmlspecialchars($stmt->error);
            }

            $stmt->close();
        } else {
            $errors[] = 'Ошибка подготовки запроса: ' . htmlspecialchars($mysqli->error);
        }
    }
}
?>

<h2>Индивидуальный заказ</h2>

<p>Здесь вы можете загрузить свой 3D-файл и указать параметры печати. На основе веса модели и выбранных параметров будет показана примерная стоимость.</p>

<?php if (!empty($successMessage)): ?>
    <div class="message success">
        <?= htmlspecialchars($successMessage) ?>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="message error">
        <ul>
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="custom_order.php" enctype="multipart/form-data">
    <p>
        <label>Материал (официальные пластики Bambu Lab):<br>
            <select name="material" required>
                <option value="">Выберите материал...</option>
                <?php foreach ($materialsList as $mat): ?>
                    <option value="<?= htmlspecialchars($mat) ?>"
                        <?= $material === $mat ? 'selected' : '' ?>>
                        <?= htmlspecialchars($mat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </p>

    <p>
        <label>Цвет (необязательно):<br>
            <input type="text" name="color" value="<?= htmlspecialchars($color) ?>">
        </label>
    </p>

    <p>
        <label>Высота слоя, мм (например, 0.12, 0.20) — необязательно:<br>
            <input
                    type="number"
                    name="layer_height"
                    value="<?= htmlspecialchars($layer_height) ?>"
                    step="0.01"
                    min="0.05"
                    max="1">
        </label>
        <span class="helper-text">По умолчанию берётся 0.20 мм, меньший слой даёт лучшее качество, но дольше печать.</span>
    </p>

    <p>
        <label>Заполняемость, % (например, 15, 20, 100) — необязательно:<br>
            <input
                    type="number"
                    name="infill"
                    value="<?= htmlspecialchars($infill) ?>"
                    min="0"
                    max="100">
        </label>
        <span class="helper-text">По умолчанию считается как 20% заполнения.</span>
    </p>

    <p>
        <label>Ориентировочный вес модели, грамм (желательно для расчёта цены):<br>
            <input
                    type="number"
                    name="weight"
                    value="<?= htmlspecialchars($weight) ?>"
                    min="1"
                    max="2000"
                    step="1">
        </label>
        <span class="helper-text">Можно посмотреть вес в слайсере перед отправкой на печать.</span>
    </p>

    <p>
        <label>3D-файл модели (STL, OBJ, STEP):<br>
            <input type="file" name="model_file" required accept=".stl,.obj,.step,.stp">
        </label>
    </p>

    <p>
        <label>Комментарий (опишите задачу, желаемое качество, размер и т.д.):<br>
            <textarea name="comment" rows="4" cols="50"><?= htmlspecialchars($comment) ?></textarea>
        </label>
    </p>

    <p class="helper-text">
        Примерная стоимость печати: <strong><span id="price-preview">
            <?php
            if ($estimatedPrice !== null && $weightVal !== null && $weightVal > 0) {
                echo htmlspecialchars(number_format($estimatedPrice, 2, ',', ' ') . ' €');
            } else {
                echo '—';
            }
            ?>
        </span></strong>
    </p>

    <p>
        <button type="submit">Отправить заявку</button>
    </p>
</form>

<script>
    // Небольшой JS-калькулятор для предварительной цены на странице
    (function() {
        const materialEl = document.querySelector('select[name="material"]');
        const layerEl = document.querySelector('input[name="layer_height"]');
        const infillEl = document.querySelector('input[name="infill"]');
        const weightEl = document.querySelector('input[name="weight"]');
        const outputEl = document.getElementById('price-preview');

        if (!materialEl || !outputEl) return;

        function pricePerGram(material) {
            material = (material || '').toLowerCase();
            if (material.includes('petg')) return 0.07;
            if (material.includes('abs') || material.includes('asa')) return 0.08;
            if (material.includes('pc')) return 0.09;
            if (material.includes('paht') || material.includes('pa-cf')) return 0.11;
            if (material.includes('pa ')) return 0.10;
            if (material.includes('tpu')) return 0.09;
            if (material.includes('support')) return 0.05;
            return 0.06; // PLA и похожие
        }

        function updatePrice() {
            const weightVal = parseFloat((weightEl && weightEl.value || '').replace(',', '.')) || 0;
            if (!weightVal || weightVal <= 0) {
                outputEl.textContent = '—';
                return;
            }

            const material = materialEl.value;
            const lh = parseFloat((layerEl && layerEl.value || '').replace(',', '.')) || 0.2;
            const inf = parseInt(infillEl && infillEl.value, 10);
            const infillPercent = !isNaN(inf) ? inf : 20;

            const baseCost = 3.0;
            const ppGram = pricePerGram(material);

            let layerFactor = 0.2 / lh;
            if (layerFactor < 0.7) layerFactor = 0.7;
            if (layerFactor > 1.6) layerFactor = 1.6;

            let infillFactor = infillPercent / 100.0;
            if (infillFactor < 0.3) infillFactor = 0.3;
            if (infillFactor > 1.5) infillFactor = 1.5;

            const price = baseCost + weightVal * ppGram * infillFactor * layerFactor;
            outputEl.textContent = price.toFixed(2) + ' €';
        }

        ['change','input'].forEach(ev => {
            materialEl.addEventListener(ev, updatePrice);
            if (layerEl) layerEl.addEventListener(ev, updatePrice);
            if (infillEl) infillEl.addEventListener(ev, updatePrice);
            if (weightEl) weightEl.addEventListener(ev, updatePrice);
        });

        updatePrice();
    })();
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
