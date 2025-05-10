<?php
require_once __DIR__ . '/db.php';

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

// Kategorileri getir - GÜNCELLEME
function getCategories($type = null, $userId = null) {
    global $pdo;
    
    $sql = "SELECT * FROM categories WHERE (user_id IS NULL OR user_id = ?)";
    $params = [$userId];
    
    // is_active kontrolü (eğer tablo yapınızda bu alan varsa)
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

// İşlemleri getir - GÜNCELLEME
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

// Toplam gelir/gider hesapla - DÜZELTME
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

// XSS koruması
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Para formatı
function formatMoney($amount) {
    return '₺' . number_format($amount, 2, ',', '.');
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
?>