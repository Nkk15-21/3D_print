<?php
// portfolio.php — портфолио выполненных работ

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';
?>

<h2>Портфолио</h2>

<p>Здесь собраны примеры работ, напечатанных на 3D-принтере: декоративные изделия, полезные детали и прототипы.</p>

<div class="product-list">
    <div class="product-card">
        <h3>Фигурка дракона</h3>
        <div class="product-image">
            <img src="uploads/images/dragon_pla.jpg" alt="Фигурка дракона">
        </div>
        <p class="product-category">Материал: Bambu PLA Basic</p>
        <p>Декоративная фигурка, высота ~12 см. Печать с высотой слоя 0.12 мм.</p>
    </div>

    <div class="product-card">
        <h3>Подставка для телефона</h3>
        <div class="product-image">
            <img src="uploads/images/phone_stand.jpg" alt="Подставка для телефона">
        </div>
        <p class="product-category">Материал: Bambu PETG Basic</p>
        <p>Устойчивая подставка, рассчитанная на ежедневное использование.</p>
    </div>

    <div class="product-card">
        <h3>Кронштейн для полки</h3>
        <div class="product-image">
            <img src="uploads/images/bracket_petg.jpg" alt="Кронштейн для полки">
        </div>
        <p class="product-category">Материал: Bambu PETG-CF</p>
        <p>Функциональный кронштейн с усиленной прочностью, 40% заполнение.</p>
    </div>

    <div class="product-card">
        <h3>Прототип шестерни</h3>
        <div class="product-image">
            <img src="uploads/images/gear_proto.jpg" alt="Прототип шестерни">
        </div>
        <p class="product-category">Материал: Bambu PLA Tough</p>
        <p>Тестовая деталь для проверки посадки и зацепления в механизме.</p>
    </div>
</div>

<p>Позже сюда можно добавить реальные фото с принтера и реальные кейсы клиентов.</p>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
