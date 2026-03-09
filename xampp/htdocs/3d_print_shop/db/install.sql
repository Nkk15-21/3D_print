-- Сначала на всякий случай удалим старую версию базы
DROP DATABASE IF EXISTS 3d_print_shop;

-- Создаём базу заново
CREATE DATABASE IF NOT EXISTS 3d_print_shop
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE 3d_print_shop;


-- ===== Таблица пользователей =====
CREATE TABLE IF NOT EXISTS users (
                                     id INT AUTO_INCREMENT PRIMARY KEY,
                                     name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(50),
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Категории товаров =====
CREATE TABLE IF NOT EXISTS categories (
                                          id INT AUTO_INCREMENT PRIMARY KEY,
                                          name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Товары =====
CREATE TABLE IF NOT EXISTS products (
                                        id INT AUTO_INCREMENT PRIMARY KEY,
                                        category_id INT NULL,
                                        name VARCHAR(150) NOT NULL,
    short_description VARCHAR(255) DEFAULT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(255),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category
    FOREIGN KEY (category_id) REFERENCES categories(id)
    ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Заказы готовых товаров =====
CREATE TABLE IF NOT EXISTS orders (
                                      id INT AUTO_INCREMENT PRIMARY KEY,
                                      user_id INT NULL,
                                      customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(50),
    total_amount DECIMAL(10,2) DEFAULT NULL,
    status ENUM('new','processing','done','cancelled') NOT NULL DEFAULT 'new',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Позиции в заказе =====
CREATE TABLE IF NOT EXISTS order_items (
                                           id INT AUTO_INCREMENT PRIMARY KEY,
                                           order_id INT NOT NULL,
                                           product_id INT NOT NULL,
                                           quantity INT NOT NULL,
                                           unit_price DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_order_items_order
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Индивидуальные заказы (с файлами) =====
CREATE TABLE IF NOT EXISTS custom_orders (
                                             id INT AUTO_INCREMENT PRIMARY KEY,
                                             user_id INT NULL,
                                             customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(50),
    material VARCHAR(150) NOT NULL,
    color VARCHAR(100),
    layer_height DECIMAL(5,2),
    infill INT,
    estimated_price DECIMAL(10,2) DEFAULT NULL,
    status ENUM('new','processing','done','cancelled') NOT NULL DEFAULT 'new',
    model_file VARCHAR(255) NOT NULL,
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_custom_orders_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Сообщения с формы "Контакты" =====
CREATE TABLE IF NOT EXISTS contacts (
                                        id INT AUTO_INCREMENT PRIMARY KEY,
                                        name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(150) DEFAULT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Стартовые категории =====
INSERT INTO categories (name, description) VALUES
                                               ('Mänguasjad', 'Figuriinid, mänguasjad ja väikesed dekoratiivsed mudelid'),
                                               ('Dekor', 'Kodune sisekujundus ja dekoratiivsed esemed'),
                                               ('Detailid', 'Funktsionaalsed detailid ja kronšteinid'),
                                               ('Prototüübid', 'Testdetailid ja prototüübid mehhanismideks');

-- ===== Стартовые товары =====
INSERT INTO products (category_id, name, description, price, image_path) VALUES
                                                                             (1, 'Фигурка дракона',
                                                                              'Väike PLA-draakoni figuur riiulile.',
                                                                              12.50, 'uploads/images/dragon_pla.jpg'),

                                                                             (2, 'Подставка для телефона',
                                                                              'Mugav telefonialus töö- või öökapile.',
                                                                              7.90, 'uploads/images/phone_stand.jpg'),

                                                                             (3, 'Кронштейн для полки',
                                                                              'Tugev PETG kronštein väikesele riiulile.',
                                                                              9.50, 'uploads/images/bracket_petg.jpg'),

                                                                             (4, 'Прототип шестерни',
                                                                              'Testhammasratas mehhanismi sobivuse ja passi kontrolliks.',
                                                                              5.00, 'uploads/images/gear_proto.jpg');
