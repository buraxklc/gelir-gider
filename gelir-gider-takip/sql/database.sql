-- Veritabanı oluşturma
CREATE DATABASE IF NOT EXISTS gelir_gider_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gelir_gider_db;

-- Kullanıcılar tablosu
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kategoriler tablosu
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    icon VARCHAR(50) DEFAULT 'bi-folder',
    color VARCHAR(7) DEFAULT '#000000',
    parent_id INT DEFAULT NULL,
    user_id INT DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_type (type),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- İşlemler tablosu
CREATE TABLE IF NOT EXISTS transactions (
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
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, transaction_date),
    INDEX idx_type (type),
    INDEX idx_date (transaction_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gamification tabloları

-- Başarılar tablosu
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    points INT DEFAULT 0,
    badge_type ENUM('bronze', 'silver', 'gold', 'platinum') DEFAULT 'bronze',
    criteria_type VARCHAR(50) NOT NULL,
    criteria_value DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kullanıcı başarıları tablosu
CREATE TABLE IF NOT EXISTS user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_achievement (user_id, achievement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kullanıcı puanları tablosu
CREATE TABLE IF NOT EXISTS user_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points INT DEFAULT 0,
    level INT DEFAULT 1,
    total_earned INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Görevler tablosu
CREATE TABLE IF NOT EXISTS challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    challenge_type ENUM('weekly', 'monthly', 'special') DEFAULT 'weekly',
    target_type VARCHAR(50) NOT NULL,
    target_value DECIMAL(10,2) NOT NULL,
    reward_points INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kullanıcı görevleri tablosu
CREATE TABLE IF NOT EXISTS user_challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    challenge_id INT NOT NULL,
    progress DECIMAL(10,2) DEFAULT 0,
    is_completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_challenge (user_id, challenge_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Varsayılan kategorileri ekle
INSERT INTO categories (name, type, icon, color, user_id) VALUES
-- Gelir kategorileri
('Maaş', 'income', 'bi-cash', '#28a745', NULL),
('Freelance', 'income', 'bi-laptop', '#17a2b8', NULL),
('Yatırım', 'income', 'bi-graph-up', '#6c757d', NULL),
('Kira Geliri', 'income', 'bi-house-door', '#28a745', NULL),
('Diğer Gelir', 'income', 'bi-plus-circle', '#28a745', NULL),

-- Gider kategorileri
('Market', 'expense', 'bi-cart', '#dc3545', NULL),
('Fatura', 'expense', 'bi-receipt', '#fd7e14', NULL),
('Kira', 'expense', 'bi-house', '#dc3545', NULL),
('Ulaşım', 'expense', 'bi-bus-front', '#6c757d', NULL),
('Yeme-İçme', 'expense', 'bi-cup-straw', '#ffc107', NULL),
('Sağlık', 'expense', 'bi-heart-pulse', '#dc3545', NULL),
('Eğitim', 'expense', 'bi-book', '#6f42c1', NULL),
('Eğlence', 'expense', 'bi-controller', '#e83e8c', NULL),
('Giyim', 'expense', 'bi-bag', '#fd7e14', NULL),
('Teknoloji', 'expense', 'bi-laptop', '#0dcaf0', NULL),
('Diğer Gider', 'expense', 'bi-three-dots', '#dc3545', NULL);

-- Varsayılan başarıları ekle
INSERT INTO achievements (name, description, icon, points, badge_type, criteria_type, criteria_value) VALUES
('İlk Adım', 'İlk işleminizi eklediniz', 'bi-star', 10, 'bronze', 'transaction_count', 1),
('10 İşlem', '10 işlem eklediniz', 'bi-star-fill', 50, 'bronze', 'transaction_count', 10),
('50 İşlem', '50 işlem eklediniz', 'bi-star-half', 150, 'silver', 'transaction_count', 50),
('100 İşlem', '100 işlem eklediniz', 'bi-stars', 300, 'gold', 'transaction_count', 100),
('İlk Birikim', '₺1.000 biriktirdiniz', 'bi-piggy-bank', 100, 'bronze', 'savings_amount', 1000),
('Tasarruf Başlangıcı', '₺5.000 biriktirdiniz', 'bi-piggy-bank', 250, 'silver', 'savings_amount', 5000),
('Tasarruf Ustası', '₺10.000 biriktirdiniz', 'bi-piggy-bank-fill', 500, 'gold', 'savings_amount', 10000),
('Tasarruf Kralı', '₺50.000 biriktirdiniz', 'bi-piggy-bank-fill', 1000, 'platinum', 'savings_amount', 50000),
('Kategori Uzmanı', '5 farklı kategori oluşturdunuz', 'bi-tags', 50, 'bronze', 'category_count', 5),
('Düzenli Kullanıcı', '7 gün üst üste giriş yaptınız', 'bi-calendar-check', 75, 'bronze', 'streak_days', 7),
('Aylık Kullanıcı', '30 gün üst üste giriş yaptınız', 'bi-calendar-check-fill', 300, 'silver', 'streak_days', 30);

-- Örnek görevler ekle
INSERT INTO challenges (name, description, start_date, end_date, challenge_type, target_type, target_value, reward_points) VALUES
('Haftalık Tasarruf', 'Bu hafta ₺500 biriktirin', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'weekly', 'save_amount', 500, 100),
('Aylık İşlem Hedefi', 'Bu ay 20 işlem ekleyin', DATE_FORMAT(CURDATE(), '%Y-%m-01'), LAST_DAY(CURDATE()), 'monthly', 'transaction_count', 20, 200),
('Harcama Kontrolü', '3 gün harcama yapmayın', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'weekly', 'no_expense_days', 3, 75);

-- İndeksler ve performans optimizasyonları
CREATE INDEX idx_transactions_user_type_date ON transactions(user_id, type, transaction_date);
CREATE INDEX idx_categories_user_type ON categories(user_id, type);
CREATE INDEX idx_user_achievements_user ON user_achievements(user_id);
CREATE INDEX idx_user_challenges_user ON user_challenges(user_id);

-- View'lar (isteğe bağlı - raporlama için)
CREATE OR REPLACE VIEW v_monthly_summary AS
SELECT 
    u.id as user_id,
    u.username,
    DATE_FORMAT(t.transaction_date, '%Y-%m') as month,
    t.type,
    SUM(t.amount) as total_amount,
    COUNT(*) as transaction_count
FROM users u
LEFT JOIN transactions t ON u.id = t.user_id
GROUP BY u.id, u.username, DATE_FORMAT(t.transaction_date, '%Y-%m'), t.type
ORDER BY u.username, month;

CREATE OR REPLACE VIEW v_category_summary AS
SELECT 
    u.id as user_id,
    u.username,
    c.name as category_name,
    c.type as category_type,
    COUNT(t.id) as transaction_count,
    COALESCE(SUM(t.amount), 0) as total_amount
FROM users u
CROSS JOIN categories c
LEFT JOIN transactions t ON u.id = t.user_id AND c.id = t.category_id
GROUP BY u.id, u.username, c.id, c.name, c.type
ORDER BY u.username, c.type, total_amount DESC;

-- Stored Procedures (isteğe bağlı)
DELIMITER //

CREATE PROCEDURE GetUserSummary(IN userId INT, IN startDate DATE, IN endDate DATE)
BEGIN
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as total_income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as total_expense,
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END), 0) as net_balance
    FROM transactions
    WHERE user_id = userId
    AND transaction_date BETWEEN startDate AND endDate;
END //

CREATE PROCEDURE GetCategoryBreakdown(IN userId INT, IN transactionType VARCHAR(10), IN startDate DATE, IN endDate DATE)
BEGIN
    SELECT 
        c.name as category_name,
        c.icon,
        c.color,
        COUNT(t.id) as transaction_count,
        COALESCE(SUM(t.amount), 0) as total_amount,
        COALESCE(AVG(t.amount), 0) as average_amount
    FROM categories c
    LEFT JOIN transactions t ON c.id = t.category_id 
        AND t.user_id = userId 
        AND t.type = transactionType
        AND t.transaction_date BETWEEN startDate AND endDate
    WHERE c.type = transactionType
    GROUP BY c.id, c.name, c.icon, c.color
    ORDER BY total_amount DESC;
END //

DELIMITER ;

-- Trigger'lar (isteğe bağlı)
DELIMITER //

CREATE TRIGGER after_transaction_insert
AFTER INSERT ON transactions
FOR EACH ROW
BEGIN
    -- Kullanıcının puan bilgisini kontrol et
    INSERT INTO user_points (user_id, points, level, total_earned)
    VALUES (NEW.user_id, 5, 1, 5)
    ON DUPLICATE KEY UPDATE
        points = points + 5,
        total_earned = total_earned + 5,
        level = FLOOR(total_earned / 1000) + 1;
END //

DELIMITER ;

-- Güvenlik ve yetkilendirme (production için önerilir)
-- CREATE USER 'gelir_gider_user'@'localhost' IDENTIFIED BY 'güçlü_şifre';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON gelir_gider_db.* TO 'gelir_gider_user'@'localhost';
-- FLUSH PRIVILEGES;