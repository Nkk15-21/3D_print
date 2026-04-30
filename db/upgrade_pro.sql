USE 3d_print_shop;

/* =========================================================
   1. МУЛЬТИЯЗЫЧНЫЕ ПОЛЯ ДЛЯ CATEGORIES
   ========================================================= */

ALTER TABLE categories
    ADD COLUMN IF NOT EXISTS name_ru VARCHAR(100) DEFAULT NULL AFTER name,
    ADD COLUMN IF NOT EXISTS name_en VARCHAR(100) DEFAULT NULL AFTER name_ru,
    ADD COLUMN IF NOT EXISTS name_et VARCHAR(100) DEFAULT NULL AFTER name_en,

    ADD COLUMN IF NOT EXISTS description_ru TEXT DEFAULT NULL AFTER description,
    ADD COLUMN IF NOT EXISTS description_en TEXT DEFAULT NULL AFTER description_ru,
    ADD COLUMN IF NOT EXISTS description_et TEXT DEFAULT NULL AFTER description_en;

/* Перенос старых значений в RU */
UPDATE categories
SET
    name_ru = COALESCE(name_ru, name),
    description_ru = COALESCE(description_ru, description);


/* =========================================================
   2. МУЛЬТИЯЗЫЧНЫЕ ПОЛЯ ДЛЯ PRODUCTS
   ========================================================= */

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS name_ru VARCHAR(150) DEFAULT NULL AFTER name,
    ADD COLUMN IF NOT EXISTS name_en VARCHAR(150) DEFAULT NULL AFTER name_ru,
    ADD COLUMN IF NOT EXISTS name_et VARCHAR(150) DEFAULT NULL AFTER name_en,

    ADD COLUMN IF NOT EXISTS short_description_ru TEXT DEFAULT NULL AFTER short_description,
    ADD COLUMN IF NOT EXISTS short_description_en TEXT DEFAULT NULL AFTER short_description_ru,
    ADD COLUMN IF NOT EXISTS short_description_et TEXT DEFAULT NULL AFTER short_description_en,

    ADD COLUMN IF NOT EXISTS description_ru TEXT DEFAULT NULL AFTER description,
    ADD COLUMN IF NOT EXISTS description_en TEXT DEFAULT NULL AFTER description_ru,
    ADD COLUMN IF NOT EXISTS description_et TEXT DEFAULT NULL AFTER description_en;

/* Перенос старых значений в RU */
UPDATE products
SET
    name_ru = COALESCE(name_ru, name),
    short_description_ru = COALESCE(short_description_ru, short_description),
    description_ru = COALESCE(description_ru, description);


/* =========================================================
   3. ТАБЛИЦА СТАТУСОВ ЗАКАЗОВ
   ========================================================= */

CREATE TABLE IF NOT EXISTS order_statuses (
                                              id INT AUTO_INCREMENT PRIMARY KEY,
                                              code VARCHAR(50) NOT NULL UNIQUE,

    name_ru VARCHAR(100) DEFAULT NULL,
    name_en VARCHAR(100) DEFAULT NULL,
    name_et VARCHAR(100) DEFAULT NULL,

    color VARCHAR(20) DEFAULT '#6b7280'
    );

INSERT IGNORE INTO order_statuses (id, code, name_ru, name_en, name_et, color) VALUES
(1, 'new', 'Новый', 'New', 'Uus', '#2563eb'),
(2, 'processing', 'В обработке', 'Processing', 'Töötlemisel', '#f59e0b'),
(3, 'done', 'Завершён', 'Completed', 'Valmis', '#16a34a'),
(4, 'cancelled', 'Отменён', 'Cancelled', 'Tühistatud', '#dc2626');


/* =========================================================
   4. ДОБАВЛЕНИЕ status_id В orders
   ========================================================= */

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS status_id INT DEFAULT 1 AFTER status;

/* Перенос старого ENUM status в status_id */
UPDATE orders
SET status_id = CASE status
                    WHEN 'new' THEN 1
                    WHEN 'processing' THEN 2
                    WHEN 'done' THEN 3
                    WHEN 'cancelled' THEN 4
                    ELSE 1
    END
WHERE status_id IS NULL OR status_id = 1;

/* Внешний ключ добавляем отдельно */
SET @fk_orders_status_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'orders'
      AND CONSTRAINT_NAME = 'fk_orders_status'
);

SET @sql_fk_orders_status := IF(
    @fk_orders_status_exists = 0,
    'ALTER TABLE orders ADD CONSTRAINT fk_orders_status FOREIGN KEY (status_id) REFERENCES order_statuses(id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
);

PREPARE stmt FROM @sql_fk_orders_status;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


/* =========================================================
   5. ИСТОРИЯ СТАТУСОВ ЗАКАЗОВ
   ========================================================= */

CREATE TABLE IF NOT EXISTS order_status_history (
                                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                                    order_id INT NOT NULL,
                                                    status_id INT NOT NULL,
                                                    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,

                                                    CONSTRAINT fk_order_status_history_order
                                                    FOREIGN KEY (order_id) REFERENCES orders(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

    CONSTRAINT fk_order_status_history_status
    FOREIGN KEY (status_id) REFERENCES order_statuses(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
    );

/* Первичное заполнение истории по существующим заказам */
INSERT INTO order_status_history (order_id, status_id, changed_at)
SELECT o.id, o.status_id, o.created_at
FROM orders o
         LEFT JOIN order_status_history h ON h.order_id = o.id
WHERE h.id IS NULL;


/* =========================================================
   6. НЕСКОЛЬКО ИЗОБРАЖЕНИЙ ДЛЯ ТОВАРА
   ========================================================= */

CREATE TABLE IF NOT EXISTS product_images (
                                              id INT AUTO_INCREMENT PRIMARY KEY,
                                              product_id INT NOT NULL,
                                              image_path VARCHAR(255) NOT NULL,
    is_main TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_product_images_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
    );

/* Перенос текущего image_path из products в product_images */
INSERT INTO product_images (product_id, image_path, is_main)
SELECT p.id, p.image_path, 1
FROM products p
         LEFT JOIN product_images pi
                   ON pi.product_id = p.id
                       AND pi.image_path = p.image_path
WHERE p.image_path IS NOT NULL
  AND p.image_path <> ''
  AND pi.id IS NULL;


/* =========================================================
   7. ИЗБРАННОЕ
   ========================================================= */

CREATE TABLE IF NOT EXISTS wishlist (
                                        id INT AUTO_INCREMENT PRIMARY KEY,
                                        user_id INT NOT NULL,
                                        product_id INT NOT NULL,
                                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

                                        CONSTRAINT fk_wishlist_user
                                        FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

    CONSTRAINT fk_wishlist_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

    UNIQUE KEY unique_user_product (user_id, product_id)
    );


/* =========================================================
   8. КОРЗИНА
   ========================================================= */

CREATE TABLE IF NOT EXISTS cart_items (
                                          id INT AUTO_INCREMENT PRIMARY KEY,
                                          user_id INT NOT NULL,
                                          product_id INT NOT NULL,
                                          quantity INT NOT NULL DEFAULT 1,
                                          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

                                          CONSTRAINT fk_cart_items_user
                                          FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

    CONSTRAINT fk_cart_items_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

    UNIQUE KEY unique_cart_item (user_id, product_id)
    );


/* =========================================================
   9. ПРОЧИТАНО / НЕ ПРОЧИТАНО ДЛЯ CONTACTS
   ========================================================= */

ALTER TABLE contacts
    ADD COLUMN IF NOT EXISTS is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER message;


/* =========================================================
   10. НАСТРОЙКИ САЙТА
   ========================================================= */

CREATE TABLE IF NOT EXISTS settings (
                                        id INT AUTO_INCREMENT PRIMARY KEY,
                                        `key` VARCHAR(100) NOT NULL UNIQUE,
    value TEXT DEFAULT NULL
    );

INSERT IGNORE INTO settings (`key`, value) VALUES
('site_name', '3D Print Shop'),
('admin_email', 'admin@admin.com'),
('currency', 'EUR');


/* =========================================================
   11. ИНДЕКСЫ
   ========================================================= */

CREATE INDEX idx_products_category_id ON products(category_id);
CREATE INDEX idx_products_is_active ON products(is_active);
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_orders_status_id ON orders(status_id);
CREATE INDEX idx_custom_orders_user_id ON custom_orders(user_id);
CREATE INDEX idx_contacts_is_read ON contacts(is_read);
CREATE INDEX idx_product_images_product_id ON product_images(product_id);
CREATE INDEX idx_wishlist_user_id ON wishlist(user_id);
CREATE INDEX idx_cart_items_user_id ON cart_items(user_id);