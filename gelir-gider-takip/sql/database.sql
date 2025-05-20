-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 20 May 2025, 15:30:00
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
(1, 'Türkiye Cumhuriyeti Ziraat Bankası', 'Ziraat', NULL, '#006B3C', 1, '2025-05-10 19:21:05'),
(2, 'Türkiye İş Bankası', 'İş Bankası', NULL, '#0066B3', 1, '2025-05-10 19:21:05'),
(3, 'Türkiye Garanti Bankası', 'Garanti', NULL, '#00A859', 1, '2025-05-10 19:21:05'),
(4, 'Yapı ve Kredi Bankası', 'Yapı Kredi', NULL, '#004990', 1, '2025-05-10 19:21:05'),
(5, 'Akbank', 'Akbank', NULL, '#E30613', 1, '2025-05-10 19:21:05'),
(6, 'Türkiye Halk Bankası', 'Halkbank', NULL, '#0075BE', 1, '2025-05-10 19:21:05'),
(7, 'Türkiye Vakıflar Bankası', 'VakıfBank', NULL, '#003366', 1, '2025-05-10 19:21:05'),
(8, 'Denizbank', 'Denizbank', NULL, '#003DA5', 1, '2025-05-10 19:21:05'),
(9, 'QNB Finansbank', 'Finansbank', NULL, '#800080', 1, '2025-05-10 19:21:05'),
(10, 'HSBC', 'HSBC', NULL, '#DB0011', 1, '2025-05-10 19:21:05'),
(11, 'ING Bank', 'ING', NULL, '#FF6200', 1, '2025-05-10 19:21:05'),
(12, 'Kuveyt Türk', 'Kuveyt Türk', NULL, '#006E3A', 1, '2025-05-10 19:21:05'),
(13, 'Türkiye Finans', 'Türkiye Finans', NULL, '#00529B', 1, '2025-05-10 19:21:05'),
(14, 'Albaraka Türk', 'Albaraka', NULL, '#C8102E', 1, '2025-05-10 19:21:05'),
(15, 'Odeabank', 'Odeabank', NULL, '#ED1C24', 1, '2025-05-10 19:21:05'),
(16, 'TEB', 'TEB', NULL, '#00A0E3', 1, '2025-05-10 19:21:05'),
(17, 'Şekerbank', 'Şekerbank', NULL, '#00A651', 1, '2025-05-10 19:21:05'),
(18, 'Alternatifbank', 'Alternatif', NULL, '#E4002B', 1, '2025-05-10 19:21:05'),
(19, 'Burgan Bank', 'Burgan', NULL, '#EE2E24', 1, '2025-05-10 19:21:05'),
(20, 'Diğer', 'Diğer', NULL, '#6B7280', 1, '2025-05-10 19:21:05');

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
(1, 1, 'İş Bankası', 'Maaş', '', 'TR122222222222222111111111', 'checking', 'TRY', 170.00, 100.00, '#4f46e5', 'bi-bank', 1, '2025-05-11 23:29:15', '2025-05-12 21:57:39'),
(2, 1, 'Garanti', 'Maaş Kartım', '', '', 'checking', 'TRY', 0.00, 0.00, '#23ab07', 'bi-bank', 1, '2025-05-12 21:42:16', '2025-05-12 21:42:57');

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
(1, 1, NULL, 'deposit', 100.00, 200.00, 'Başlangıç bakiyesi', '2025-05-12 01:29:15', NULL, '2025-05-11 23:29:15'),
(2, 1, NULL, 'withdrawal', 100.00, 100.00, 'atmden çekildi', '2025-05-12 23:38:00', '', '2025-05-12 21:39:07'),
(4, 2, NULL, 'transfer_in', 50.00, 50.00, 'Transfer: Para aktarımı', '2025-05-12 23:42:40', 'TRF-1', '2025-05-12 21:42:40'),
(5, 2, NULL, 'transfer_out', 50.00, 0.00, 'Transfer: ', '2025-05-12 23:42:57', 'TRF-2', '2025-05-12 21:42:57'),
(6, 1, NULL, 'transfer_in', 70.00, 120.00, 'Transfer:', '2025-05-12 23:42:00', 'TRF-2', '2025-05-12 21:42:57');

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

--
-- Tablo döküm verisi `bank_transfers`
--

INSERT INTO `bank_transfers` (`id`, `from_account_id`, `to_account_id`, `amount`, `description`, `transfer_date`, `created_at`) VALUES
(1, 1, 2, 50.00, 'Para aktarımı', '2025-05-13 00:42:40', '2025-05-12 21:42:40'),
(2, 2, 1, 50.00, '', '2025-05-13 00:42:57', '2025-05-12 21:42:57');

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
(2, 'Freelance', 'income', 'bi-briefcase', '#0679d0', NULL, 1, 1, '2025-05-11 23:26:14'),
(3, 'Yemek', 'expense', 'bi-cup-straw', '#e70808', NULL, 1, 1, '2025-05-11 23:26:29');

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
(2, 1, 3, 1, 'expense', 100.00, 'Demo', 'daily', 1, NULL, NULL, '2025-05-12', '0000-00-00', '2025-05-13', NULL, 1, 3, '2025-05-11 23:29:49', '2025-05-12 21:37:39');

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
(1, 1, 2, 'income', 3000.00, 'Yazılım satışı', '2025-05-12', '2025-05-11 23:26:50', '2025-05-11 23:26:50'),
(2, 1, 3, 'expense', 300.00, 'iskender yedim', '2025-05-12', '2025-05-11 23:27:05', '2025-05-11 23:27:05');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `dark_mode` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `dark_mode`, `created_at`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$4L0CGmeMqIQ78rnZF3lRcuWTJ.issqcJqYvibiBm5btNKZBLaEfnK', 0, '2025-05-10 18:59:07');

--
-- Dökümü yapılmış tablolar için indeksler
--

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
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_id` (`parent_id`);

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
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `banks`
--
ALTER TABLE `banks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Tablo için AUTO_INCREMENT değeri `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `bank_transactions`
--
ALTER TABLE `bank_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `bank_transfers`
--
ALTER TABLE `bank_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `categories`
--
ALTER TABLE `categories`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;