<?php
// catalog.php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

// Запрос: берём товары + название категории
$sql = "
    SELECT p.id,
           p.name,
           p.short_description,
           p.price,
           p.image,
           c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_active = 1
    ORDER BY p.created_at DESC
";

/** @var mysqli $mysqli */
$result = $mysqli->query($sql);

if (!$result) {
    echo '<p>Ошибка запроса: ' . htmlspecialchars($mysqli->error) . '</p>';
} else {
    ?>
    <h2>Каталог товаров</h2>
    <?php

    if ($result->num_rows === 0) {
        echo '<p>Пока нет доступных товаров.</p>';
    } else {
        echo '<div class="product-list">';
        while ($row = $result->fetch_assoc()) {
            ?>
            <div class="product-card">
                <h3><?= htmlspecialchars($row['name']) ?></h3>

                <?php if (!empty($row['image'])): ?>
                    <div class="product-image">
                        <img src="uploads/images/<?= htmlspecialchars($row['image']) ?>"
                             alt="<?= htmlspecialchars($row['name']) ?>">
                    </div>
                <?php endif; ?>

                <p class="product-category">
                    Категория:
                    <?= htmlspecialchars($row['category_name'] ?? 'Без категории') ?>
                </p>

                <?php if (!empty($row['short_description'])): ?>
                    <p><?= htmlspecialchars($row['short_description']) ?></p>
                <?php endif; ?>

                <p class="product-price">
                    Цена: <?= number_format($row['price'], 2, '.', ' ') ?> €
                </p>

                <p>
                    <a href="product.php?id=<?= (int)$row['id'] ?>">Подробнее</a>
                </p>
            </div>
            <?php
        }
        echo '</div>';
    }
}

require_once __DIR__ . '/includes/footer.php';
?>
