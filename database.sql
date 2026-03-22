-- ============================================================
-- FlashShop Database — Electronics Store
-- Full Schema + Seed Data (Electronics Only)
-- Import: mysql -u root -p < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS flashshop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE flashshop;
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)  NOT NULL,
    email         VARCHAR(150)  UNIQUE NOT NULL,
    password      VARCHAR(255)  NOT NULL,
    role          ENUM('customer','admin') DEFAULT 'customer',
    is_banned     TINYINT(1)   DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role  (role)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: category
-- ============================================================
CREATE TABLE IF NOT EXISTS category (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(120) UNIQUE NOT NULL,
    description TEXT,
    icon        VARCHAR(10)  DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: products
-- ============================================================
CREATE TABLE IF NOT EXISTS products (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    category_id   INT          NOT NULL,
    name          VARCHAR(200) NOT NULL,
    slug          VARCHAR(220) UNIQUE NOT NULL,
    description   TEXT,
    price         DECIMAL(10,2) NOT NULL,
    stock         INT           DEFAULT 0,
    image_url     VARCHAR(500),
    is_featured   TINYINT(1)   DEFAULT 0,
    is_active     TINYINT(1)   DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE CASCADE,
    INDEX idx_category   (category_id),
    INDEX idx_featured   (is_featured),
    INDEX idx_active     (is_active),
    INDEX idx_created    (created_at),
    FULLTEXT idx_search  (name, description)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: cart
-- ============================================================
CREATE TABLE IF NOT EXISTS cart (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    product_id  INT NOT NULL,
    quantity    INT DEFAULT 1,
    added_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: orders (replaces "payment" — now tracks WhatsApp orders)
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    total_amount    DECIMAL(10,2) NOT NULL,
    whatsapp_sent   TINYINT(1)   DEFAULT 0,
    status          ENUM('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
    customer_note   TEXT,
    transaction_ref VARCHAR(100) UNIQUE,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user   (user_id),
    INDEX idx_status (status),
    INDEX idx_created(created_at)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: order_items
-- ============================================================
CREATE TABLE IF NOT EXISTS order_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT  NOT NULL,
    product_id  INT  NULL,
    product_name VARCHAR(200) NOT NULL,
    quantity    INT           NOT NULL,
    unit_price  DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: rate_limit (brute-force protection)
-- ============================================================
CREATE TABLE IF NOT EXISTS rate_limit (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ip_address  VARCHAR(45)  NOT NULL,
    action      VARCHAR(50)  NOT NULL,
    attempts    INT          DEFAULT 1,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ip_action (ip_address, action)
) ENGINE=InnoDB;

-- ============================================================
-- SEED: Admin user
-- Password: Admin@FlashShop2026
-- Generated with: password_hash('Admin@FlashShop2026', PASSWORD_BCRYPT, ['cost'=>12])
-- ============================================================
INSERT INTO users (name, email, password, role) VALUES
(
    'Admin FlashShop',
    'admin@flashshop.com',
    '$2y$12$6T5VQ6N1ZzXkV0c5Qk8BreHsNq.p7kPZ9UXS3kUvG1RuG7q2VJmO.',
    'admin'
);

-- ============================================================
-- SEED: Electronics Categories
-- ============================================================
INSERT INTO category (name, slug, description, icon) VALUES
('Smartphones',  'smartphones',  'Latest flagship and budget smartphones', '📱'),
('Laptops',      'laptops',      'Business, gaming and ultrabook laptops', '💻'),
('Audio',        'audio',        'Headphones, earbuds, speakers and DACs', '🎧'),
('Accessories',  'accessories',  'Cables, chargers, cases and more',       '🔌'),
('Gaming',       'gaming',       'Consoles, controllers and peripherals',   '🎮'),
('Smart Home',   'smart-home',   'Smart speakers, cameras and automation', '🏠');

-- ============================================================
-- SEED: Electronics Products (with Unsplash images)
-- ============================================================
INSERT INTO products (category_id, name, slug, description, price, stock, image_url, is_featured) VALUES

-- ── Smartphones (cat 1) ──────────────────────────────────────
(1, 'Samsung Galaxy S24 Ultra',
 'samsung-galaxy-s24-ultra',
 'The ultimate Samsung flagship. 200MP camera, built-in S Pen, Snapdragon 8 Gen 3, 5000mAh battery and 12GB RAM. Available in Titanium Black, Gray and Violet.',
 899000, 25,
 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=600&q=80',
 1),

(1, 'iPhone 15 Pro Max',
 'iphone-15-pro-max',
 'Apple\'s most powerful iPhone. A17 Pro chip, ProRAW 48MP camera system, titanium design, Action Button and USB-C with 3× optical zoom.',
 1050000, 18,
 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=600&q=80',
 1),

(1, 'Xiaomi 14 Pro',
 'xiaomi-14-pro',
 'Leica-tuned triple camera, Snapdragon 8 Gen 3, 120W HyperCharge, 6.73" AMOLED 2K display. Lightning-fast charging in under 20 minutes.',
 620000, 30,
 'https://images.unsplash.com/photo-1574944985070-8f3ebc6b79d2?w=600&q=80',
 0),

(1, 'Samsung Galaxy A55 5G',
 'samsung-galaxy-a55',
 'Smooth 120Hz Super AMOLED, Exynos 1480, 50MP OIS camera, 5000mAh battery. The perfect daily-driver for power users on a budget.',
 285000, 50,
 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=600&q=80',
 0),

-- ── Laptops (cat 2) ───────────────────────────────────────────
(2, 'MacBook Pro 14" M3 Pro',
 'macbook-pro-14-m3-pro',
 'M3 Pro chip with 12-core CPU and 18-core GPU. Liquid Retina XDR display, 18GB unified memory, 18-hour battery life — built for professionals.',
 1480000, 12,
 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=600&q=80',
 1),

(2, 'ASUS ROG Zephyrus G16',
 'asus-rog-zephyrus-g16',
 'Intel Core Ultra 9, NVIDIA RTX 4090, 32GB DDR5, 240Hz OLED display. The thinnest and lightest ROG gaming laptop to date.',
 1250000, 8,
 'https://images.unsplash.com/photo-1593642632559-0c6d3fc62b89?w=600&q=80',
 1),

(2, 'Dell XPS 15',
 'dell-xps-15',
 'Intel Core i9-13900H, 32GB RAM, 1TB NVMe SSD, OLED InfinityEdge display. Premium ultra-thin performance for creators and professionals.',
 980000, 15,
 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=600&q=80',
 0),

(2, 'Lenovo ThinkPad X1 Carbon Gen 12',
 'lenovo-thinkpad-x1-carbon-gen12',
 'Ultra-light 1.12kg business laptop. Intel Core Ultra 7, 32GB LPDDR5, Wi-Fi 7, military-grade durability, 15-hour battery.',
 895000, 20,
 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=600&q=80',
 0),

-- ── Audio (cat 3) ─────────────────────────────────────────────
(3, 'Sony WH-1000XM5',
 'sony-wh-1000xm5',
 'Industry-leading noise cancellation, 30-hour battery, multipoint Bluetooth 5.2, speak-to-chat, Hi-Res Audio certified. The gold standard in wireless headphones.',
 189000, 40,
 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&q=80',
 1),

(3, 'Apple AirPods Pro 2nd Gen',
 'airpods-pro-2nd-gen',
 'H2 chip, active noise cancellation, adaptive transparency, lossless audio with Apple Vision Pro, 6-hour battery + 30 hours with case.',
 155000, 55,
 'https://images.unsplash.com/photo-1600294037681-c80b4cb5b434?w=600&q=80',
 1),

(3, 'JBL Charge 5 Bluetooth Speaker',
 'jbl-charge-5',
 'IP67 waterproof, 20-hour playtime, powerful bass radiator, PartyBoost to link multiple JBL speakers, built-in power bank. Made for adventure.',
 95000, 35,
 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=600&q=80',
 0),

-- ── Accessories (cat 4) ───────────────────────────────────────
(4, 'Anker 240W USB-C GaN Charger',
 'anker-240w-gan-charger',
 '4-port desktop charger — 3× USB-C + 1× USB-A. Charges a MacBook Pro, iPad, iPhone and AirPods simultaneously. ActiveShield 2.0 protection.',
 45000, 80,
 'https://images.unsplash.com/photo-1583394838336-acd977736f90?w=600&q=80',
 0),

(4, 'Samsung 25W Super-Fast Charger',
 'samsung-25w-charger',
 'Official Samsung 25W PD 3.0 fast charger with USB-C cable. Compatible with Galaxy S/A series, Tab series and all USB-C devices.',
 18500, 120,
 'https://images.unsplash.com/photo-1601972599720-36938d4ecd31?w=600&q=80',
 0),

(4, 'Baseus 20000mAh Power Bank 65W',
 'baseus-20000mah-powerbank',
 '65W PD fast charge, 3 outputs (2C+1A), LED display, charges a laptop twice. Slim enough to fit in your jacket pocket.',
 65000, 60,
 'https://images.unsplash.com/photo-1585338477986-5ab6f3e08c56?w=600&q=80',
 0),

-- ── Gaming (cat 5) ────────────────────────────────────────────
(5, 'PlayStation 5 Slim',
 'playstation-5-slim',
 'Sony PS5 Slim console — 1TB SSD, 4K 120fps gaming, ray tracing, DualSense wireless controller included. The future of gaming, made compact.',
 520000, 10,
 'https://images.unsplash.com/photo-1607853202273-797f1c22a38e?w=600&q=80',
 1),

(5, 'Xbox Series X',
 'xbox-series-x',
 '12 teraflops GPU, 1TB NVMe SSD, 4K 120fps, Quick Resume, Xbox Game Pass Ultimate compatible. The most powerful Xbox ever made.',
 480000, 12,
 'https://images.unsplash.com/photo-1605901309584-818e25960a8f?w=600&q=80',
 0),

(5, 'SteelSeries Arctis Nova Pro Wireless',
 'steelseries-arctis-nova-pro-wireless',
 'Premium wireless gaming headset with active noise cancellation, lossless 2.4GHz + Bluetooth 5.0, hot-swap battery system, 22-hour battery.',
 145000, 22,
 'https://images.unsplash.com/photo-1612198188060-c7c2a3b66eae?w=600&q=80',
 0),

-- ── Smart Home (cat 6) ────────────────────────────────────────
(6, 'Amazon Echo Show 10',
 'amazon-echo-show-10',
 'Smart display with 10.1" HD screen, Alexa built-in, 360° auto-rotating screen, 13MP camera for video calls, Zigbee hub included.',
 125000, 28,
 'https://images.unsplash.com/photo-1543512214-318c7553f230?w=600&q=80',
 0),

(6, 'TP-Link Tapo C220 4MP Smart Camera',
 'tplink-tapo-c220',
 '4MP ultra-clear 2K resolution, 360° pan & tilt, color night vision, two-way audio, motion detection, works with Alexa and Google Home.',
 38000, 45,
 'https://images.unsplash.com/photo-1557597774-9d273605dfa9?w=600&q=80',
 0),

(6, 'Philips Hue Starter Kit (4 bulbs)',
 'philips-hue-starter-kit',
 '4× smart E27 bulbs + Hue Bridge. 16 million colors, voice control via Alexa/Google/Siri, routines, energy monitoring. Transform your home lighting.',
 89000, 30,
 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&q=80',
 0);
