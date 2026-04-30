DROP DATABASE IF EXISTS 3d_print_shop;
CREATE DATABASE 3d_print_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE 3d_print_shop;

/* =========================================================
   USERS
   ========================================================= */

CREATE TABLE users (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       name VARCHAR(100) NOT NULL,
                       email VARCHAR(150) NOT NULL UNIQUE,
                       phone VARCHAR(50) DEFAULT NULL,
                       password_hash VARCHAR(255) NOT NULL,
                       role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
                       created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (
    name,
    email,
    phone,
    password_hash,
    role
) VALUES (
             'Admin',
             'admin@admin.com',
             '',
             '$2y$10$IMXfVw/RnHmusrspN8mwT.xMK93q04jbIZLvpsVMfqCPK/aF38bGO',
             'admin'
         );

/* =========================================================
   CATEGORIES
   ========================================================= */

CREATE TABLE categories (
                            id INT AUTO_INCREMENT PRIMARY KEY,

                            name VARCHAR(100) NOT NULL,
                            name_ru VARCHAR(100) DEFAULT NULL,
                            name_en VARCHAR(100) DEFAULT NULL,
                            name_et VARCHAR(100) DEFAULT NULL,

                            description TEXT DEFAULT NULL,
                            description_ru TEXT DEFAULT NULL,
                            description_en TEXT DEFAULT NULL,
                            description_et TEXT DEFAULT NULL,

                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

/* =========================================================
   PRODUCTS
   ========================================================= */

CREATE TABLE products (
                          id INT AUTO_INCREMENT PRIMARY KEY,

                          category_id INT DEFAULT NULL,

                          name VARCHAR(150) NOT NULL,
                          name_ru VARCHAR(150) DEFAULT NULL,
                          name_en VARCHAR(150) DEFAULT NULL,
                          name_et VARCHAR(150) DEFAULT NULL,

                          short_description TEXT DEFAULT NULL,
                          short_description_ru TEXT DEFAULT NULL,
                          short_description_en TEXT DEFAULT NULL,
                          short_description_et TEXT DEFAULT NULL,

                          description TEXT DEFAULT NULL,
                          description_ru TEXT DEFAULT NULL,
                          description_en TEXT DEFAULT NULL,
                          description_et TEXT DEFAULT NULL,

                          price DECIMAL(10,2) NOT NULL,
                          image_path VARCHAR(255) DEFAULT NULL,
                          is_active TINYINT(1) NOT NULL DEFAULT 1,
                          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

                          CONSTRAINT fk_products_category
                              FOREIGN KEY (category_id) REFERENCES categories(id)
                                  ON DELETE SET NULL
                                  ON UPDATE CASCADE
);

/* =========================================================
   ORDER STATUSES
   ========================================================= */

CREATE TABLE order_statuses (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                code VARCHAR(50) NOT NULL UNIQUE,

                                name_ru VARCHAR(100) DEFAULT NULL,
                                name_en VARCHAR(100) DEFAULT NULL,
                                name_et VARCHAR(100) DEFAULT NULL,

                                color VARCHAR(20) DEFAULT '#6b7280'
);

/* =========================================================
   ORDERS
   ========================================================= */

CREATE TABLE orders (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT DEFAULT NULL,
                        customer_name VARCHAR(100) NOT NULL,
                        customer_email VARCHAR(150) NOT NULL,
                        customer_phone VARCHAR(50) DEFAULT NULL,
                        total_amount DECIMAL(10,2) DEFAULT NULL,

                        status ENUM('new','processing','done','cancelled') NOT NULL DEFAULT 'new',
                        status_id INT DEFAULT 1,

                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

                        CONSTRAINT fk_orders_user
                            FOREIGN KEY (user_id) REFERENCES users(id)
                                ON DELETE SET NULL
                                ON UPDATE CASCADE,

                        CONSTRAINT fk_orders_status
                            FOREIGN KEY (status_id) REFERENCES order_statuses(id)
                                ON DELETE SET NULL
                                ON UPDATE CASCADE
);

/* =========================================================
   ORDER ITEMS
   ========================================================= */

CREATE TABLE order_items (
                             id INT AUTO_INCREMENT PRIMARY KEY,
                             order_id INT NOT NULL,
                             product_id INT NOT NULL,
                             quantity INT NOT NULL,
                             unit_price DECIMAL(10,2) NOT NULL,

                             CONSTRAINT fk_order_items_order
                                 FOREIGN KEY (order_id) REFERENCES orders(id)
                                     ON DELETE CASCADE
                                     ON UPDATE CASCADE,

                             CONSTRAINT fk_order_items_product
                                 FOREIGN KEY (product_id) REFERENCES products(id)
                                     ON DELETE CASCADE
                                     ON UPDATE CASCADE
);

/* =========================================================
   ORDER STATUS HISTORY
   ========================================================= */

CREATE TABLE order_status_history (
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

/* =========================================================
   CUSTOM ORDERS
   ========================================================= */

CREATE TABLE custom_orders (
                               id INT AUTO_INCREMENT PRIMARY KEY,
                               user_id INT DEFAULT NULL,
                               customer_name VARCHAR(100) NOT NULL,
                               customer_email VARCHAR(150) NOT NULL,
                               customer_phone VARCHAR(50) DEFAULT NULL,
                               material VARCHAR(150) NOT NULL,
                               color VARCHAR(100) DEFAULT NULL,
                               layer_height DECIMAL(5,2) DEFAULT NULL,
                               infill INT DEFAULT NULL,
                               estimated_price DECIMAL(10,2) DEFAULT NULL,
                               status ENUM('new','processing','done','cancelled') NOT NULL DEFAULT 'new',
                               model_file VARCHAR(255) NOT NULL,
                               comment TEXT DEFAULT NULL,
                               created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

                               CONSTRAINT fk_custom_orders_user
                                   FOREIGN KEY (user_id) REFERENCES users(id)
                                       ON DELETE SET NULL
                                       ON UPDATE CASCADE
);

/* =========================================================
   CONTACTS
   ========================================================= */

CREATE TABLE contacts (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          name VARCHAR(100) NOT NULL,
                          email VARCHAR(150) NOT NULL,
                          subject VARCHAR(150) DEFAULT NULL,
                          message TEXT NOT NULL,
                          is_read TINYINT(1) NOT NULL DEFAULT 0,
                          created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

/* =========================================================
   PRODUCT IMAGES
   ========================================================= */

CREATE TABLE product_images (
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

/* =========================================================
   WISHLIST
   ========================================================= */

CREATE TABLE wishlist (
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
   CART ITEMS
   ========================================================= */

CREATE TABLE cart_items (
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
   SETTINGS
   ========================================================= */

CREATE TABLE settings (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          `key` VARCHAR(100) NOT NULL UNIQUE,
                          value TEXT DEFAULT NULL
);

/* =========================================================
   INDEXES
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

/* =========================================================
   SEED: ORDER STATUSES
   ========================================================= */

INSERT INTO order_statuses (id, code, name_ru, name_en, name_et, color) VALUES
                                                                            (1, 'new', 'Новый', 'New', 'Uus', '#2563eb'),
                                                                            (2, 'processing', 'В обработке', 'Processing', 'Töötlemisel', '#f59e0b'),
                                                                            (3, 'done', 'Завершён', 'Completed', 'Valmis', '#16a34a'),
                                                                            (4, 'cancelled', 'Отменён', 'Cancelled', 'Tühistatud', '#dc2626');

/* =========================================================
   SEED: SETTINGS
   ========================================================= */

INSERT INTO settings (`key`, value) VALUES
                                        ('site_name', '3D Print Shop'),
                                        ('admin_email', 'admin@admin.com'),
                                        ('currency', 'EUR');

/* =========================================================
   SEED: CATEGORIES
   ========================================================= */

INSERT INTO categories (
    name,
    name_ru,
    name_en,
    name_et,
    description,
    description_ru,
    description_en,
    description_et
) VALUES
      (
          'Декор',
          'Декор',
          'Decor',
          'Dekoor',
          'Декоративные изделия для дома и интерьера',
          'Декоративные изделия для дома и интерьера',
          'Decorative items for home and interior',
          'Dekoratiivsed tooted koju ja interjööri'
      ),
      (
          'Аксессуары',
          'Аксессуары',
          'Accessories',
          'Aksessuaarid',
          'Полезные аксессуары и держатели',
          'Полезные аксессуары и держатели',
          'Useful accessories and holders',
          'Kasulikud aksessuaarid ja hoidikud'
      ),
      (
          'Запчасти',
          'Запчасти',
          'Parts',
          'Varuosad',
          'Функциональные детали и крепления',
          'Функциональные детали и крепления',
          'Functional parts and mounts',
          'Funktsionaalsed detailid ja kinnitused'
      ),
      (
          'Фигурки',
          'Фигурки',
          'Figures',
          'Kujukesed',
          'Коллекционные и декоративные модели',
          'Коллекционные и декоративные модели',
          'Collectible and decorative models',
          'Kollektsiooni- ja dekoratiivmudelid'
      );

/* =========================================================
   SEED: PRODUCTS
   ========================================================= */

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
) VALUES
      (
          2,
          'Подставка для телефона',
          'Подставка для телефона',
          'Phone Stand',
          'Telefonialus',

          'Удобная подставка для смартфона',
          'Удобная подставка для смартфона',
          'Convenient stand for a smartphone',
          'Mugav alus nutitelefonile',

          'Компактная 3D-печатная подставка для телефона. Подходит для рабочего стола и дома.',
          'Компактная 3D-печатная подставка для телефона. Подходит для рабочего стола и дома.',
          'Compact 3D printed phone stand. Suitable for desk and home use.',
          'Kompaktne 3D-prinditud telefonialus. Sobib töölauale ja koju.',

          12.50,
          'uploads/images/phone_stand.jpg',
          1
      ),
      (
          2,
          'Органайзер для кабелей',
          'Органайзер для кабелей',
          'Cable Organizer',
          'Kaablihoidja',

          'Держатель для проводов на столе',
          'Держатель для проводов на столе',
          'Desk holder for cables',
          'Juhtmete hoidik lauale',

          'Практичный органайзер для аккуратного размещения кабелей и зарядок.',
          'Практичный органайзер для аккуратного размещения кабелей и зарядок.',
          'Practical organizer for neat placement of cables and chargers.',
          'Praktiline hoidik juhtmete ja laadijate korrastamiseks.',

          7.90,
          'uploads/images/cable_holder.jpg',
          1
      ),
      (
          4,
          'Фигурка дракона',
          'Фигурка дракона',
          'Dragon Figure',
          'Draakoni kujuke',

          'Декоративная фигурка для коллекции',
          'Декоративная фигурка для коллекции',
          'Decorative figure for collection',
          'Dekoratiivne kujuke kollektsiooni jaoks',

          'Эффектная фигурка дракона. Подойдёт как подарок или украшение полки.',
          'Эффектная фигурка дракона. Подойдёт как подарок или украшение полки.',
          'An impressive dragon figure. Suitable as a gift or shelf decoration.',
          'Efektne draakoni kujuke. Sobib kingituseks või riiuli kaunistuseks.',

          24.99,
          'uploads/images/dragon.jpg',
          1
      );

/* =========================================================
   SEED: PRODUCT IMAGES
   ========================================================= */

INSERT INTO product_images (product_id, image_path, is_main) VALUES
                                                                 (1, 'uploads/images/phone_stand.jpg', 1),
                                                                 (2, 'uploads/images/cable_holder.jpg', 1),
                                                                 (3, 'uploads/images/dragon.jpg', 1);