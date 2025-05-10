-- Veritabanı oluşturma
CREATE DATABASE gelir_gider_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gelir_gider_db;

-- Kullanıcılar tablosu
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Kategoriler tablosu
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- İşlemler tablosu
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Varsayılan kategoriler ekleme
INSERT INTO categories (name, type, user_id) VALUES
('Maaş', 'income', NULL),
('Freelance', 'income', NULL),
('Yatırım', 'income', NULL),
('Diğer Gelir', 'income', NULL),
('Market', 'expense', NULL),
('Fatura', 'expense', NULL),
('Kira', 'expense', NULL),
('Ulaşım', 'expense', NULL),
('Yeme-İçme', 'expense', NULL),
('Diğer Gider', 'expense', NULL);