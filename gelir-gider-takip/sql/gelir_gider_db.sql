-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 11 May 2025, 09:07:38
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `gelir_gider_db`
--

DELIMITER $$
--
-- Yordamlar
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetCategoryBreakdown` (IN `userId` INT, IN `transactionType` VARCHAR(10), IN `startDate` DATE, IN `endDate` DATE)   BEGIN
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
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserSummary` (IN `userId` INT, IN `startDate` DATE, IN `endDate` DATE)   BEGIN
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as total_income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as total_expense,
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END), 0) as net_balance
    FROM transactions
    WHERE user_id = userId
    AND transaction_date BETWEEN startDate AND endDate;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `achievements`
--

CREATE TABLE `achievements` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `points` int(11) DEFAULT 0,
  `badge_type` enum('bronze','silver','gold','platinum') DEFAULT 'bronze',
  `criteria_type` varchar(50) NOT NULL,
  `criteria_value` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `achievements`
--

INSERT INTO `achievements` (`id`, `name`, `description`, `icon`, `points`, `badge_type`, `criteria_type`, `criteria_value`, `created_at`) VALUES
(1, 'İlk Adım', 'İlk işleminizi eklediniz', 'bi-star', 10, 'bronze', 'transaction_count', 1.00, '2025-05-10 21:56:59'),
(2, '10 İşlem', '10 işlem eklediniz', 'bi-star-fill', 50, 'bronze', 'transaction_count', 10.00, '2025-05-10 21:56:59'),
(3, '50 İşlem', '50 işlem eklediniz', 'bi-star-half', 150, 'silver', 'transaction_count', 50.00, '2025-05-10 21:56:59'),
(4, '100 İşlem', '100 işlem eklediniz', 'bi-stars', 300, 'gold', 'transaction_count', 100.00, '2025-05-10 21:56:59'),
(5, 'İlk Birikim', '₺1.000 biriktirdiniz', 'bi-piggy-bank', 100, 'bronze', 'savings_amount', 1000.00, '2025-05-10 21:56:59'),
(6, 'Tasarruf Başlangıcı', '₺5.000 biriktirdiniz', 'bi-piggy-bank', 250, 'silver', 'savings_amount', 5000.00, '2025-05-10 21:56:59'),
(7, 'Tasarruf Ustası', '₺10.000 biriktirdiniz', 'bi-piggy-bank-fill', 500, 'gold', 'savings_amount', 10000.00, '2025-05-10 21:56:59'),
(8, 'Tasarruf Kralı', '₺50.000 biriktirdiniz', 'bi-piggy-bank-fill', 1000, 'platinum', 'savings_amount', 50000.00, '2025-05-10 21:56:59'),
(9, 'Kategori Uzmanı', '5 farklı kategori oluşturdunuz', 'bi-tags', 50, 'bronze', 'category_count', 5.00, '2025-05-10 21:56:59'),
(10, 'Düzenli Kullanıcı', '7 gün üst üste giriş yaptınız', 'bi-calendar-check', 75, 'bronze', 'streak_days', 7.00, '2025-05-10 21:56:59'),
(11, 'Aylık Kullanıcı', '30 gün üst üste giriş yaptınız', 'bi-calendar-check-fill', 300, 'silver', 'streak_days', 30.00, '2025-05-10 21:56:59');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `banks`
--

CREATE TABLE `banks` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `short_name` varchar(20) DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `banks`
--

INSERT INTO `banks` (`id`, `name`, `short_name`, `logo_url`, `color`, `is_active`, `created_at`) VALUES
(1, 'Türkiye Cumhuriyeti Ziraat Bankası', 'Ziraat', NULL, '#006B3C', 1, '2025-05-10 22:21:05'),
(2, 'Türkiye İş Bankası', 'İş Bankası', NULL, '#0066B3', 1, '2025-05-10 22:21:05'),
(3, 'Türkiye Garanti Bankası', 'Garanti', NULL, '#00A859', 1, '2025-05-10 22:21:05'),
(4, 'Yapı ve Kredi Bankası', 'Yapı Kredi', NULL, '#004990', 1, '2025-05-10 22:21:05'),
(5, 'Akbank', 'Akbank', NULL, '#E30613', 1, '2025-05-10 22:21:05'),
(6, 'Türkiye Halk Bankası', 'Halkbank', NULL, '#0075BE', 1, '2025-05-10 22:21:05'),
(7, 'Türkiye Vakıflar Bankası', 'VakıfBank', NULL, '#003366', 1, '2025-05-10 22:21:05'),
(8, 'Denizbank', 'Denizbank', NULL, '#003DA5', 1, '2025-05-10 22:21:05'),
(9, 'QNB Finansbank', 'Finansbank', NULL, '#800080', 1, '2025-05-10 22:21:05'),
(10, 'HSBC', 'HSBC', NULL, '#DB0011', 1, '2025-05-10 22:21:05'),
(11, 'ING Bank', 'ING', NULL, '#FF6200', 1, '2025-05-10 22:21:05'),
(12, 'Kuveyt Türk', 'Kuveyt Türk', NULL, '#006E3A', 1, '2025-05-10 22:21:05'),
(13, 'Türkiye Finans', 'Türkiye Finans', NULL, '#00529B', 1, '2025-05-10 22:21:05'),
(14, 'Albaraka Türk', 'Albaraka', NULL, '#C8102E', 1, '2025-05-10 22:21:05'),
(15, 'Odeabank', 'Odeabank', NULL, '#ED1C24', 1, '2025-05-10 22:21:05'),
(16, 'TEB', 'TEB', NULL, '#00A0E3', 1, '2025-05-10 22:21:05'),
(17, 'Şekerbank', 'Şekerbank', NULL, '#00A651', 1, '2025-05-10 22:21:05'),
(18, 'Alternatifbank', 'Alternatif', NULL, '#E4002B', 1, '2025-05-10 22:21:05'),
(19, 'Burgan Bank', 'Burgan', NULL, '#EE2E24', 1, '2025-05-10 22:21:05'),
(20, 'Diğer', 'Diğer', NULL, '#6B7280', 1, '2025-05-10 22:21:05');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `iban` varchar(34) DEFAULT NULL,
  `account_type` enum('checking','savings','investment','credit') DEFAULT 'checking',
  `currency` varchar(3) DEFAULT 'TRY',
  `current_balance` decimal(15,2) DEFAULT 0.00,
  `initial_balance` decimal(15,2) DEFAULT 0.00,
  `color` varchar(7) DEFAULT '#4F46E5',
  `icon` varchar(50) DEFAULT 'bi-bank',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `bank_accounts`
--

INSERT INTO `bank_accounts` (`id`, `user_id`, `bank_name`, `account_name`, `account_number`, `iban`, `account_type`, `currency`, `current_balance`, `initial_balance`, `color`, `icon`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Yapı Kredi', 'Maaş Hesabı', '12367128931923', 'TR123612367916231627', 'checking', 'TRY', 200.00, 100.00, '#4f46e5', 'bi-bank', 1, '2025-05-10 22:37:33', '2025-05-10 22:37:33');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `bank_transactions`
--

CREATE TABLE `bank_transactions` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `transaction_type` enum('deposit','withdrawal','transfer_in','transfer_out','fee','interest') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `transaction_date` datetime DEFAULT current_timestamp(),
  `reference_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `bank_transactions`
--

INSERT INTO `bank_transactions` (`id`, `account_id`, `transaction_id`, `transaction_type`, `amount`, `balance_after`, `description`, `transaction_date`, `reference_number`, `created_at`) VALUES
(1, 1, NULL, 'deposit', 100.00, 200.00, 'Başlangıç bakiyesi', '2025-05-11 00:37:33', NULL, '2025-05-10 22:37:33');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `bank_transfers`
--

CREATE TABLE `bank_transfers` (
  `id` int(11) NOT NULL,
  `from_account_id` int(11) NOT NULL,
  `to_account_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `transfer_date` datetime DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `icon` varchar(50) DEFAULT 'bi-folder',
  `color` varchar(7) DEFAULT '#000000',
  `parent_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `categories`
--

INSERT INTO `categories` (`id`, `name`, `type`, `icon`, `color`, `parent_id`, `user_id`, `is_active`, `created_at`) VALUES
(1, 'Maaş', 'income', 'bi-cash', '#28a745', NULL, NULL, 1, '2025-05-10 21:56:59'),
(2, 'Freelance', 'income', 'bi-laptop', '#17a2b8', NULL, NULL, 1, '2025-05-10 21:56:59'),
(3, 'Yatırım', 'income', 'bi-graph-up', '#6c757d', NULL, NULL, 1, '2025-05-10 21:56:59'),
(4, 'Kira Geliri', 'income', 'bi-house-door', '#28a745', NULL, NULL, 1, '2025-05-10 21:56:59'),
(5, 'Diğer Gelir', 'income', 'bi-plus-circle', '#28a745', NULL, NULL, 1, '2025-05-10 21:56:59'),
(6, 'Market', 'expense', 'bi-cart', '#dc3545', NULL, NULL, 1, '2025-05-10 21:56:59'),
(7, 'Fatura', 'expense', 'bi-receipt', '#fd7e14', NULL, NULL, 1, '2025-05-10 21:56:59'),
(8, 'Kira', 'expense', 'bi-house', '#dc3545', NULL, NULL, 1, '2025-05-10 21:56:59'),
(9, 'Ulaşım', 'expense', 'bi-bus-front', '#6c757d', NULL, NULL, 1, '2025-05-10 21:56:59'),
(10, 'Yeme-İçme', 'expense', 'bi-cup-straw', '#ffc107', NULL, NULL, 1, '2025-05-10 21:56:59'),
(11, 'Sağlık', 'expense', 'bi-heart-pulse', '#dc3545', NULL, NULL, 1, '2025-05-10 21:56:59'),
(12, 'Eğitim', 'expense', 'bi-book', '#6f42c1', NULL, NULL, 1, '2025-05-10 21:56:59'),
(13, 'Eğlence', 'expense', 'bi-controller', '#e83e8c', NULL, NULL, 1, '2025-05-10 21:56:59'),
(14, 'Giyim', 'expense', 'bi-bag', '#fd7e14', NULL, NULL, 1, '2025-05-10 21:56:59'),
(15, 'Teknoloji', 'expense', 'bi-laptop', '#0dcaf0', NULL, NULL, 1, '2025-05-10 21:56:59'),
(16, 'Diğer Gider', 'expense', 'bi-three-dots', '#dc3545', NULL, NULL, 1, '2025-05-10 21:56:59');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `challenges`
--

CREATE TABLE `challenges` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `challenge_type` enum('weekly','monthly','special') DEFAULT 'weekly',
  `target_type` varchar(50) NOT NULL,
  `target_value` decimal(10,2) NOT NULL,
  `reward_points` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `challenges`
--

INSERT INTO `challenges` (`id`, `name`, `description`, `start_date`, `end_date`, `challenge_type`, `target_type`, `target_value`, `reward_points`, `is_active`, `created_at`) VALUES
(1, 'Haftalık Tasarruf', 'Bu hafta ₺500 biriktirin', '2025-05-11', '2025-05-18', 'weekly', 'save_amount', 500.00, 100, 1, '2025-05-10 21:56:59'),
(2, 'Aylık İşlem Hedefi', 'Bu ay 20 işlem ekleyin', '2025-05-01', '2025-05-31', 'monthly', 'transaction_count', 20.00, 200, 1, '2025-05-10 21:56:59'),
(3, 'Harcama Kontrolü', '3 gün harcama yapmayın', '2025-05-11', '2025-05-18', 'weekly', 'no_expense_days', 3.00, 75, 1, '2025-05-10 21:56:59');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('reminder','overdue','info','warning') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `related_table` varchar(50) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `is_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `recurring_transactions`
--

CREATE TABLE `recurring_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `bank_account_id` int(11) DEFAULT NULL,
  `type` enum('income','expense') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `frequency` enum('daily','weekly','monthly','yearly') NOT NULL,
  `frequency_interval` int(11) DEFAULT 1,
  `day_of_week` int(11) DEFAULT NULL COMMENT 'For weekly: 1=Monday, 7=Sunday',
  `day_of_month` int(11) DEFAULT NULL COMMENT 'For monthly: 1-31',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `next_occurrence` date NOT NULL,
  `last_processed` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `notification_days` int(11) DEFAULT 3,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `recurring_transactions`
--

INSERT INTO `recurring_transactions` (`id`, `user_id`, `category_id`, `bank_account_id`, `type`, `amount`, `description`, `frequency`, `frequency_interval`, `day_of_week`, `day_of_month`, `start_date`, `end_date`, `next_occurrence`, `last_processed`, `is_active`, `notification_days`, `created_at`, `updated_at`) VALUES
(1, 1, 15, 1, 'expense', 60.00, 'Spotify ödeme', 'monthly', 1, NULL, 30, '2025-05-11', '2025-05-12', '2025-06-30', NULL, 0, 3, '2025-05-10 22:59:45', '2025-05-10 23:00:01'),
(2, 1, 7, 1, 'expense', 60.00, 'Spotify ücreti', 'monthly', 1, NULL, 11, '2025-03-10', '0000-00-00', '2025-06-11', NULL, 1, 3, '2025-05-10 23:01:01', '2025-05-10 23:01:01'),
(3, 1, 2, 1, 'income', 15000.00, 'Ajanslar için aylık gelir modülü', 'monthly', 1, NULL, 11, '2025-04-11', '0000-00-00', '2025-06-11', NULL, 1, 3, '2025-05-10 23:05:06', '2025-05-10 23:05:06'),
(4, 1, 5, 1, 'income', 600.00, 'yazılım parası', 'daily', 1, NULL, NULL, '2025-03-10', '0000-00-00', '2025-05-12', NULL, 1, 3, '2025-05-10 23:08:58', '2025-05-10 23:08:58');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `category_id`, `type`, `amount`, `description`, `transaction_date`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'income', 500.00, 'Günlük maaş', '2025-05-11', '2025-05-10 22:04:06', '2025-05-10 22:04:06'),
(3, 1, 5, 'income', 845.00, '1', '2025-05-11', '2025-05-10 22:07:42', '2025-05-10 22:07:42'),
(5, 1, 12, 'expense', 10.00, '', '2025-05-11', '2025-05-10 22:14:12', '2025-05-10 22:14:12');

--
-- Tetikleyiciler `transactions`
--
DELIMITER $$
CREATE TRIGGER `after_transaction_insert` AFTER INSERT ON `transactions` FOR EACH ROW BEGIN
    -- Kullanıcının puan bilgisini kontrol et
    INSERT INTO user_points (user_id, points, level, total_earned)
    VALUES (NEW.user_id, 5, 1, 5)
    ON DUPLICATE KEY UPDATE
        points = points + 5,
        total_earned = total_earned + 5,
        level = FLOOR(total_earned / 1000) + 1;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$4L0CGmeMqIQ78rnZF3lRcuWTJ.issqcJqYvibiBm5btNKZBLaEfnK', '2025-05-10 21:59:07');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_achievements`
--

CREATE TABLE `user_achievements` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `achievement_id` int(11) NOT NULL,
  `earned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `user_achievements`
--

INSERT INTO `user_achievements` (`id`, `user_id`, `achievement_id`, `earned_at`) VALUES
(1, 1, 1, '2025-05-10 22:04:06'),
(2, 1, 5, '2025-05-10 22:07:42');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_challenges`
--

CREATE TABLE `user_challenges` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `progress` decimal(10,2) DEFAULT 0.00,
  `is_completed` tinyint(1) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_points`
--

CREATE TABLE `user_points` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `points` int(11) DEFAULT 0,
  `level` int(11) DEFAULT 1,
  `total_earned` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `user_points`
--

INSERT INTO `user_points` (`id`, `user_id`, `points`, `level`, `total_earned`, `updated_at`) VALUES
(1, 1, 160, 1, 160, '2025-05-10 22:14:12');

-- --------------------------------------------------------

--
-- Görünüm yapısı durumu `v_category_summary`
-- (Asıl görünüm için aşağıya bakın)
--
CREATE TABLE `v_category_summary` (
`user_id` int(11)
,`username` varchar(50)
,`category_name` varchar(100)
,`category_type` enum('income','expense')
,`transaction_count` bigint(21)
,`total_amount` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Görünüm yapısı durumu `v_monthly_summary`
-- (Asıl görünüm için aşağıya bakın)
--
CREATE TABLE `v_monthly_summary` (
`user_id` int(11)
,`username` varchar(50)
,`month` varchar(7)
,`type` enum('income','expense')
,`total_amount` decimal(32,2)
,`transaction_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Görünüm yapısı `v_category_summary`
--
DROP TABLE IF EXISTS `v_category_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_category_summary`  AS SELECT `u`.`id` AS `user_id`, `u`.`username` AS `username`, `c`.`name` AS `category_name`, `c`.`type` AS `category_type`, count(`t`.`id`) AS `transaction_count`, coalesce(sum(`t`.`amount`),0) AS `total_amount` FROM ((`users` `u` join `categories` `c`) left join `transactions` `t` on(`u`.`id` = `t`.`user_id` and `c`.`id` = `t`.`category_id`)) GROUP BY `u`.`id`, `u`.`username`, `c`.`id`, `c`.`name`, `c`.`type` ORDER BY `u`.`username` ASC, `c`.`type` ASC, coalesce(sum(`t`.`amount`),0) DESC ;

-- --------------------------------------------------------

--
-- Görünüm yapısı `v_monthly_summary`
--
DROP TABLE IF EXISTS `v_monthly_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_monthly_summary`  AS SELECT `u`.`id` AS `user_id`, `u`.`username` AS `username`, date_format(`t`.`transaction_date`,'%Y-%m') AS `month`, `t`.`type` AS `type`, sum(`t`.`amount`) AS `total_amount`, count(0) AS `transaction_count` FROM (`users` `u` left join `transactions` `t` on(`u`.`id` = `t`.`user_id`)) GROUP BY `u`.`id`, `u`.`username`, date_format(`t`.`transaction_date`,'%Y-%m'), `t`.`type` ORDER BY `u`.`username` ASC, date_format(`t`.`transaction_date`,'%Y-%m') ASC ;

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `banks`
--
ALTER TABLE `banks`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Tablo için indeksler `bank_transactions`
--
ALTER TABLE `bank_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `idx_account_date` (`account_id`,`transaction_date`),
  ADD KEY `idx_type` (`transaction_type`);

--
-- Tablo için indeksler `bank_transfers`
--
ALTER TABLE `bank_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `to_account_id` (`to_account_id`),
  ADD KEY `idx_accounts` (`from_account_id`,`to_account_id`);

--
-- Tablo için indeksler `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_categories_user_type` (`user_id`,`type`);

--
-- Tablo için indeksler `challenges`
--
ALTER TABLE `challenges`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_unread` (`user_id`,`is_read`),
  ADD KEY `idx_created` (`created_at`);

--
-- Tablo için indeksler `recurring_transactions`
--
ALTER TABLE `recurring_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `bank_account_id` (`bank_account_id`),
  ADD KEY `idx_next_occurrence` (`next_occurrence`),
  ADD KEY `idx_user_active` (`user_id`,`is_active`);

--
-- Tablo için indeksler `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_user_date` (`user_id`,`transaction_date`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_date` (`transaction_date`),
  ADD KEY `idx_transactions_user_type_date` (`user_id`,`type`,`transaction_date`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Tablo için indeksler `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_achievement` (`user_id`,`achievement_id`),
  ADD KEY `achievement_id` (`achievement_id`),
  ADD KEY `idx_user_achievements_user` (`user_id`);

--
-- Tablo için indeksler `user_challenges`
--
ALTER TABLE `user_challenges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_challenge` (`user_id`,`challenge_id`),
  ADD KEY `challenge_id` (`challenge_id`),
  ADD KEY `idx_user_challenges_user` (`user_id`);

--
-- Tablo için indeksler `user_points`
--
ALTER TABLE `user_points`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user` (`user_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Tablo için AUTO_INCREMENT değeri `banks`
--
ALTER TABLE `banks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Tablo için AUTO_INCREMENT değeri `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `bank_transactions`
--
ALTER TABLE `bank_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `bank_transfers`
--
ALTER TABLE `bank_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Tablo için AUTO_INCREMENT değeri `challenges`
--
ALTER TABLE `challenges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `recurring_transactions`
--
ALTER TABLE `recurring_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `user_achievements`
--
ALTER TABLE `user_achievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `user_challenges`
--
ALTER TABLE `user_challenges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `user_points`
--
ALTER TABLE `user_points`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD CONSTRAINT `bank_accounts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `bank_transactions`
--
ALTER TABLE `bank_transactions`
  ADD CONSTRAINT `bank_transactions_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bank_transactions_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `bank_transfers`
--
ALTER TABLE `bank_transfers`
  ADD CONSTRAINT `bank_transfers_ibfk_1` FOREIGN KEY (`from_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bank_transfers_ibfk_2` FOREIGN KEY (`to_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `categories_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `recurring_transactions`
--
ALTER TABLE `recurring_transactions`
  ADD CONSTRAINT `recurring_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recurring_transactions_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recurring_transactions_ibfk_3` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD CONSTRAINT `user_achievements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_achievements_ibfk_2` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `user_challenges`
--
ALTER TABLE `user_challenges`
  ADD CONSTRAINT `user_challenges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_challenges_ibfk_2` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `user_points`
--
ALTER TABLE `user_points`
  ADD CONSTRAINT `user_points_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
