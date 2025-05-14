<?php
require_once __DIR__ . '/db.php';

// XSS koruması
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Kullanıcı giriş kontrolü
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Kullanıcı girişi zorunlu
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Kullanıcı bilgilerini getir
function getUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Kategorileri getir
function getCategories($type = null, $userId = null) {
    global $pdo;
    
    $sql = "SELECT * FROM categories WHERE (user_id IS NULL OR user_id = ?)";
    $params = [$userId];
    
    // is_active kontrolü
    if ($pdo->query("SHOW COLUMNS FROM categories LIKE 'is_active'")->rowCount() > 0) {
        $sql .= " AND is_active = TRUE";
    }
    
    if ($type) {
        $sql .= " AND type = ?";
        $params[] = $type;
    }
    
    $sql .= " ORDER BY name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// İşlemleri getir
function getTransactions($userId, $filters = []) {
    global $pdo;
    
    $sql = "SELECT t.*, c.name as category_name";
    
    // Icon ve color alanları varsa ekle
    if ($pdo->query("SHOW COLUMNS FROM categories LIKE 'icon'")->rowCount() > 0) {
        $sql .= ", c.icon as category_icon";
    }
    if ($pdo->query("SHOW COLUMNS FROM categories LIKE 'color'")->rowCount() > 0) {
        $sql .= ", c.color as category_color";
    }
    
    $sql .= " FROM transactions t 
              JOIN categories c ON t.category_id = c.id 
              WHERE t.user_id = ?";
    $params = [$userId];
    
    // Filtreleri uygula
    if (!empty($filters['type'])) {
        $sql .= " AND t.type = ?";
        $params[] = $filters['type'];
    }
    
    if (!empty($filters['category_id'])) {
        $sql .= " AND t.category_id = ?";
        $params[] = $filters['category_id'];
    }
    
    if (!empty($filters['start_date'])) {
        $sql .= " AND t.transaction_date >= ?";
        $params[] = $filters['start_date'];
    }
    
    if (!empty($filters['end_date'])) {
        $sql .= " AND t.transaction_date <= ?";
        $params[] = $filters['end_date'];
    }
    
    $sql .= " ORDER BY t.transaction_date DESC, t.id DESC";
    
    // Limit ekle
    if (!empty($filters['limit'])) {
        $sql .= " LIMIT " . intval($filters['limit']);
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Toplam gelir/gider hesapla
function calculateTotals($userId, $type, $startDate = null, $endDate = null) {
    global $pdo;
    
    $sql = "SELECT COALESCE(SUM(amount), 0) as total 
            FROM transactions 
            WHERE user_id = ? AND type = ?";
    $params = [$userId, $type];
    
    if ($startDate) {
        $sql .= " AND transaction_date >= ?";
        $params[] = $startDate;
    }
    
    if ($endDate) {
        $sql .= " AND transaction_date <= ?";
        $params[] = $endDate;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    
    return (float)($result['total'] ?? 0);
}

// Para birimi sembolünü getir
function getCurrencySymbol($currency) {
    $symbols = [
        'TRY' => '₺',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£'
    ];
    
    return $symbols[$currency] ?? $currency;
}

// Formatlamayı güncelleyelim
function formatMoney($amount, $currency = 'TRY') {
    $symbols = [
        'TRY' => '₺',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£'
    ];
    
    $symbol = $symbols[$currency] ?? $currency;
    
    return $symbol . number_format($amount, 2, ',', '.');
}

// Kategori ağacını getir (alt kategorilerle birlikte)
function getCategoryTree($type = null, $userId = null) {
    global $pdo;
    
    $sql = "SELECT * FROM categories 
            WHERE (user_id IS NULL OR user_id = ?)";
    $params = [$userId];
    
    // parent_id kontrolü
    if ($pdo->query("SHOW COLUMNS FROM categories LIKE 'parent_id'")->rowCount() > 0) {
        $sql .= " AND parent_id IS NULL";
    }
    
    // is_active kontrolü
    if ($pdo->query("SHOW COLUMNS FROM categories LIKE 'is_active'")->rowCount() > 0) {
        $sql .= " AND is_active = TRUE";
    }
    
    if ($type) {
        $sql .= " AND type = ?";
        $params[] = $type;
    }
    
    $sql .= " ORDER BY name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $categories = $stmt->fetchAll();
    
    // Alt kategorileri getir
    if ($pdo->query("SHOW COLUMNS FROM categories LIKE 'parent_id'")->rowCount() > 0) {
        foreach ($categories as &$category) {
            $stmt = $pdo->prepare("
                SELECT * FROM categories 
                WHERE parent_id = ? 
                ORDER BY name
            ");
            $stmt->execute([$category['id']]);
            $category['subcategories'] = $stmt->fetchAll();
        }
    }
    
    return $categories;
}

// Kategori detaylarını getir
function getCategoryDetails($categoryId) {
    global $pdo;
    
    $sql = "SELECT c.*";
    
    // parent_id varsa parent category bilgilerini de getir
    if ($pdo->query("SHOW COLUMNS FROM categories LIKE 'parent_id'")->rowCount() > 0) {
        $sql .= ", p.name as parent_name";
    }
    
    $sql .= ", (SELECT COUNT(*) FROM transactions WHERE category_id = c.id) as transaction_count,
               (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE category_id = c.id AND type = 'income') as total_income,
               (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE category_id = c.id AND type = 'expense') as total_expense
        FROM categories c";
    
    // parent_id varsa LEFT JOIN yap
    if ($pdo->query("SHOW COLUMNS FROM categories LIKE 'parent_id'")->rowCount() > 0) {
        $sql .= " LEFT JOIN categories p ON c.parent_id = p.id";
    }
    
    $sql .= " WHERE c.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$categoryId]);
    return $stmt->fetch();
}

// CSRF token oluştur
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF token doğrula
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token;
}

// Güvenli bir şekilde dosya adı oluştur
function sanitizeFileName($fileName) {
    // Sadece alfanümerik, nokta, tire ve alt çizgi karakterlerini tut
    return preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
}

// Tarih formatını düzenle
function formatDate($date, $format = 'd.m.Y') {
    return date($format, strtotime($date));
}

// Toplam işlem sayısını getir
function getTotalTransactionCount($userId, $type = null) {
    global $pdo;
    
    $sql = "SELECT COUNT(*) as count FROM transactions WHERE user_id = ?";
    $params = [$userId];
    
    if ($type) {
        $sql .= " AND type = ?";
        $params[] = $type;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    
    return (int)($result['count'] ?? 0);
}

// Log kaydı oluştur
function logActivity($userId, $action, $details = null) {
    global $pdo;
    
    // Eğer log tablosu varsa
    if ($pdo->query("SHOW TABLES LIKE 'activity_logs'")->rowCount() > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }
}

// Hatırlatma oluştur
function createReminder($userId, $title, $date, $description = null) {
    global $pdo;
    
    // Eğer hatırlatma tablosu varsa
    if ($pdo->query("SHOW TABLES LIKE 'reminders'")->rowCount() > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO reminders (user_id, title, reminder_date, description)
            VALUES (?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $userId,
            $title,
            $date,
            $description
        ]);
    }
    
    return false;
}

// Kullanıcının ayarlarını getir
function getUserSettings($userId) {
    global $pdo;
    
    // Eğer ayarlar tablosu varsa
    if ($pdo->query("SHOW TABLES LIKE 'user_settings'")->rowCount() > 0) {
        $stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: [];
    }
    
    return [];
}

// Varsayılan ayarları getir
function getDefaultSettings() {
    return [
        'currency' => 'TRY',
        'date_format' => 'd.m.Y',
        'language' => 'tr',
        'theme' => 'light',
        'email_notifications' => true
    ];
    /**
 * TCMB'den güncel döviz kurlarını çeker
 * 
 * @return array Döviz kurları (USD, EUR, GBP) arşivi
 */
function getTCMBExchangeRates() {
    // Önbellek dosyası - günde bir kez güncellenmesi yeterli
    $cacheFile = __DIR__ . '/../cache/exchange_rates.json';
    $cacheDir = __DIR__ . '/../cache';
    
    // Önbellek klasörü yoksa oluştur
    if (!file_exists($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    // Önbellek dosyası var ve bugün güncellenmişse, önbellekten al
    if (file_exists($cacheFile) && date('Y-m-d') === date('Y-m-d', filemtime($cacheFile))) {
        $rates = json_decode(file_get_contents($cacheFile), true);
        if (!empty($rates)) {
            return $rates;
        }
    }
    
    // TCMB'den XML olarak doviz kurlarını çek
    $url = 'https://www.tcmb.gov.tr/kurlar/today.xml';
    $xml = simplexml_load_file($url);
    
    if ($xml === false) {
        // XML çekilemezse varsayılan değerleri kullan
        return [
            'USD' => ['buying' => 0, 'selling' => 0],
            'EUR' => ['buying' => 0, 'selling' => 0],
            'GBP' => ['buying' => 0, 'selling' => 0],
        ];
    }
    
    // XML'den döviz kurlarını al
    $rates = [];
    foreach ($xml->Currency as $currency) {
        $code = (string)$currency['CurrencyCode'];
        if (in_array($code, ['USD', 'EUR', 'GBP'])) {
            $rates[$code] = [
                'buying' => (float)str_replace(',', '.', $currency->BanknoteBuying),
                'selling' => (float)str_replace(',', '.', $currency->BanknoteSelling),
                'name' => (string)$currency->CurrencyName,
                'code' => $code,
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    // Önbelleğe kaydet
    file_put_contents($cacheFile, json_encode($rates));
    
    return $rates;
}

/**
 * TL cinsinden döviz karşılığını hesaplar
 * 
 * @param float $amount Döviz miktarı
 * @param string $currency Döviz kodu (USD, EUR, GBP)
 * @param bool $isBuying Alış kurunu mu, satış kurunu mu kullanacak
 * @return float TL karşılığı
 */
function convertToTRY($amount, $currency, $isBuying = true) {
    if ($currency === 'TRY') {
        return $amount;
    }
    
    $rates = getTCMBExchangeRates();
    
    if (!isset($rates[$currency])) {
        return 0;
    }
    
    $rate = $isBuying ? $rates[$currency]['buying'] : $rates[$currency]['selling'];
    
    return $amount * $rate;
}

/**
 * TL'den dövize çevirir
 * 
 * @param float $amount TL miktarı
 * @param string $currency Döviz kodu (USD, EUR, GBP)
 * @param bool $isBuying Alış kurunu mu, satış kurunu mu kullanacak
 * @return float Döviz karşılığı
 */
function convertFromTRY($amount, $currency, $isBuying = true) {
    if ($currency === 'TRY') {
        return $amount;
    }
    
    $rates = getTCMBExchangeRates();
    
    if (!isset($rates[$currency])) {
        return 0;
    }
    
    $rate = $isBuying ? $rates[$currency]['buying'] : $rates[$currency]['selling'];
    
    if ($rate == 0) {
        return 0;
    }
    
    return $amount / $rate;
}
}