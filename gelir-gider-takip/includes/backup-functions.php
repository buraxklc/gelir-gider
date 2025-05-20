<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/bank-functions.php';
require_once __DIR__ . '/recurring-functions.php';

/**
 * Kullanıcının tüm verilerini JSON formatında dışa aktar
 */
function exportUserData($userId) {
    global $pdo;
    
    // Kullanıcı bilgilerini al
    $user = getUserById($userId);
    if (!$user) {
        return false;
    }
    
    $backup = [
        'backup_info' => [
            'version' => '1.0',
            'created_at' => date('Y-m-d H:i:s'),
            'user_id' => $userId,
            'username' => $user['username'],
            'export_type' => 'full_backup'
        ],
        'user_profile' => [
            'username' => $user['username'],
            'email' => $user['email'],
            'created_at' => $user['created_at']
        ],
        'categories' => exportUserCategories($userId),
        'transactions' => exportUserTransactions($userId),
        'bank_accounts' => exportUserBankAccounts($userId),
        'bank_transactions' => exportUserBankTransactions($userId),
        'recurring_transactions' => exportUserRecurringTransactions($userId),
        'notifications' => exportUserNotifications($userId)
    ];
    
    return json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

/**
 * Kullanıcının kategorilerini getir (sistem + kullanıcı kategorileri)
 */
function exportUserCategories($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT id, name, type, icon, color, parent_id, user_id, is_active, created_at 
        FROM categories 
        WHERE user_id = ? OR user_id IS NULL
        ORDER BY user_id ASC, id ASC
    ");
    $stmt->execute([$userId]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug için
    error_log("Categories exported: " . count($categories));
    
    return $categories;
}

/**
 * Kullanıcının işlemlerini getir
 */
function exportUserTransactions($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT t.*, c.name as category_name, c.type as category_type
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ?
        ORDER BY t.transaction_date DESC, t.id DESC
    ");
    $stmt->execute([$userId]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug için
    error_log("Transactions exported: " . count($transactions));
    
    return $transactions;
}

/**
 * Kullanıcının banka hesaplarını getir
 */
function exportUserBankAccounts($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM bank_accounts 
        WHERE user_id = ?
        ORDER BY id
    ");
    $stmt->execute([$userId]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug için
    error_log("Bank accounts exported: " . count($accounts));
    
    return $accounts;
}

/**
 * Kullanıcının banka işlemlerini getir
 */
function exportUserBankTransactions($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT bt.*, ba.account_name, ba.bank_name
        FROM bank_transactions bt
        JOIN bank_accounts ba ON bt.account_id = ba.id
        WHERE ba.user_id = ?
        ORDER BY bt.transaction_date DESC, bt.id DESC
    ");
    $stmt->execute([$userId]);
    $bankTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug için
    error_log("Bank transactions exported: " . count($bankTransactions));
    
    return $bankTransactions;
}

/**
 * Kullanıcının tekrarlayan işlemlerini getir
 */
function exportUserRecurringTransactions($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT rt.*, c.name as category_name, c.type as category_type, ba.account_name
        FROM recurring_transactions rt
        LEFT JOIN categories c ON rt.category_id = c.id
        LEFT JOIN bank_accounts ba ON rt.bank_account_id = ba.id
        WHERE rt.user_id = ?
        ORDER BY rt.id
    ");
    $stmt->execute([$userId]);
    $recurring = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug için
    error_log("Recurring transactions exported: " . count($recurring));
    
    return $recurring;
}

/**
 * Kullanıcının bildirimlerini getir
 */
function exportUserNotifications($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug için
    error_log("Notifications exported: " . count($notifications));
    
    return $notifications;
}

/**
 * JSON dosyasından kullanıcı verilerini içe aktar
 */
function importUserData($userId, $jsonData) {
    global $pdo;
    
    try {
        $data = json_decode($jsonData, true);
        
        if (!$data || !isset($data['backup_info'])) {
            throw new Exception('Geçersiz yedek dosyası formatı');
        }
        
        // Backup versiyonunu kontrol et
        if (!isset($data['backup_info']['version']) || $data['backup_info']['version'] !== '1.0') {
            throw new Exception('Desteklenmeyen yedek dosyası versiyonu');
        }
        
        $pdo->beginTransaction();
        
        $importResults = [];
        
        // Kategorileri içe aktar
        $categoryMapping = [];
        if (!empty($data['categories'])) {
            $categoryMapping = importCategories($userId, $data['categories']);
            $importResults[] = "Kategoriler: " . count($categoryMapping) . " adet";
        }
        
        // Banka hesaplarını içe aktar
        $accountMapping = [];
        if (!empty($data['bank_accounts'])) {
            $accountMapping = importBankAccounts($userId, $data['bank_accounts']);
            $importResults[] = "Banka hesapları: " . count($accountMapping) . " adet";
        }
        
        // İşlemleri içe aktar
        $importedTransactions = 0;
        if (!empty($data['transactions'])) {
            $importedTransactions = importTransactions($userId, $data['transactions'], $categoryMapping);
            $importResults[] = "İşlemler: " . $importedTransactions . " adet";
        }
        
        // Banka işlemlerini içe aktar
        $importedBankTransactions = 0;
        if (!empty($data['bank_transactions'])) {
            $importedBankTransactions = importBankTransactionsData($userId, $data['bank_transactions'], $accountMapping);
            $importResults[] = "Banka işlemleri: " . $importedBankTransactions . " adet";
        }
        
        // Tekrarlayan işlemleri içe aktar
        $importedRecurring = 0;
        if (!empty($data['recurring_transactions'])) {
            $importedRecurring = importRecurringTransactionsData($userId, $data['recurring_transactions'], $categoryMapping, $accountMapping);
            $importResults[] = "Tekrarlayan işlemler: " . $importedRecurring . " adet";
        }
        
        // Bildirimleri içe aktar
        $importedNotifications = 0;
        if (!empty($data['notifications'])) {
            $importedNotifications = importNotifications($userId, $data['notifications']);
            $importResults[] = "Bildirimler: " . $importedNotifications . " adet";
        }
        
        $pdo->commit();
        
        // Debug log
        error_log("Import completed: " . implode(", ", $importResults));
        
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Import error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Kategorileri içe aktar
 */
function importCategories($userId, $categories) {
    global $pdo;
    
    $categoryMapping = []; // Eski ID -> Yeni ID mapping
    $imported = 0;
    
    foreach ($categories as $category) {
        // Sistem kategorilerini atla
        if (!isset($category['user_id']) || $category['user_id'] === null) {
            continue;
        }
        
        // Aynı isimde kategori var mı kontrol et
        $stmt = $pdo->prepare("
            SELECT id FROM categories 
            WHERE name = ? AND type = ? AND user_id = ?
        ");
        $stmt->execute([$category['name'], $category['type'], $userId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Mevcut kategoriyi kullan
            $categoryMapping[$category['id']] = $existing['id'];
            continue;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO categories (name, type, icon, color, parent_id, user_id, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $category['name'],
            $category['type'],
            $category['icon'] ?? 'bi-folder',
            $category['color'] ?? '#000000',
            null, // Parent ID'yi daha sonra güncelleme
            $userId,
            $category['is_active'] ?? 1
        ]);
        
        if ($result) {
            $newId = $pdo->lastInsertId();
            $categoryMapping[$category['id']] = $newId;
            $imported++;
        }
    }
    
    return $categoryMapping;
}

/**
 * İşlemleri içe aktar - TIMESTAMP ile garantili çözüm
 */
function importTransactions($userId, $transactions, $categoryMapping = []) {
    global $pdo;
    
    $imported = 0;
    
    // İşlemleri tarihe göre sırala (eski -> yeni)
    usort($transactions, function($a, $b) {
        return strtotime($a['transaction_date']) - strtotime($b['transaction_date']);
    });
    
    // Base timestamp: şuandan itibaren
    $baseTime = time();
    $counter = 0;
    
    foreach ($transactions as $transaction) {
        $categoryId = null;
        
        // Kategori ID'sini bul
        if (isset($categoryMapping[$transaction['category_id']])) {
            $categoryId = $categoryMapping[$transaction['category_id']];
        } else {
            $stmt = $pdo->prepare("
                SELECT id FROM categories 
                WHERE name = ? AND type = ? AND (user_id = ? OR user_id IS NULL)
                LIMIT 1
            ");
            $stmt->execute([
                $transaction['category_name'], 
                $transaction['category_type'] ?? $transaction['type'], 
                $userId
            ]);
            $category = $stmt->fetch();
            
            if ($category) {
                $categoryId = $category['id'];
            }
        }
        
        if (!$categoryId) {
            continue;
        }
        
        // Yeni tarih: her işlem 1 saniye sonraya
        $newTimestamp = $baseTime + $counter;
        $newDate = date('Y-m-d H:i:s', $newTimestamp);
        
        $stmt = $pdo->prepare("
            INSERT INTO transactions (user_id, category_id, type, amount, description, transaction_date)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $userId,
            $categoryId,
            $transaction['type'],
            $transaction['amount'],
            $transaction['description'] ?? '',
            $newDate  // YENİ TARİH KULLAN
        ]);
        
        if ($result) {
            $imported++;
            $counter++; // Her işlem 1 saniye sonraya
        }
    }
    
    return $imported;
}
/**
 * Banka hesaplarını içe aktar
 */
function importBankAccounts($userId, $bankAccounts) {
    global $pdo;
    
    $accountMapping = [];
    $imported = 0;
    
    foreach ($bankAccounts as $account) {
        // Aynı hesap var mı kontrol et
        $stmt = $pdo->prepare("
            SELECT id FROM bank_accounts 
            WHERE user_id = ? AND bank_name = ? AND account_name = ?
            LIMIT 1
        ");
        $stmt->execute([$userId, $account['bank_name'], $account['account_name']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Mevcut hesabı kullan
            $accountMapping[$account['id']] = $existing['id'];
            continue;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO bank_accounts (
                user_id, bank_name, account_name, account_number, iban, 
                account_type, currency, current_balance, initial_balance, 
                color, icon, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $userId,
            $account['bank_name'],
            $account['account_name'],
            $account['account_number'] ?? '',
            $account['iban'] ?? '',
            $account['account_type'] ?? 'checking',
            $account['currency'] ?? 'TRY',
            $account['current_balance'] ?? 0,
            $account['initial_balance'] ?? 0,
            $account['color'] ?? '#4F46E5',
            $account['icon'] ?? 'bi-bank',
            $account['is_active'] ?? 1
        ]);
        
        if ($result) {
            $newId = $pdo->lastInsertId();
            $accountMapping[$account['id']] = $newId;
            $imported++;
        }
    }
    
    return $accountMapping;
}

/**
 * Banka işlemlerini içe aktar
 */
function importBankTransactionsData($userId, $bankTransactions, $accountMapping = []) {
    global $pdo;
    
    $imported = 0;
    
    foreach ($bankTransactions as $transaction) {
        $accountId = null;
        
        // Önce mapping'de ara
        if (isset($accountMapping[$transaction['account_id']])) {
            $accountId = $accountMapping[$transaction['account_id']];
        } else {
            // Hesap ID'sini bul (account_name ve bank_name'e göre)
            $stmt = $pdo->prepare("
                SELECT id FROM bank_accounts 
                WHERE account_name = ? AND bank_name = ? AND user_id = ?
                LIMIT 1
            ");
            $stmt->execute([$transaction['account_name'], $transaction['bank_name'], $userId]);
            $account = $stmt->fetch();
            
            if ($account) {
                $accountId = $account['id'];
            }
        }
        
        if (!$accountId) {
            continue; // Hesap bulunamazsa işlemi atla
        }
        
        // Aynı işlem var mı kontrol et
        $stmt = $pdo->prepare("
            SELECT id FROM bank_transactions 
            WHERE account_id = ? AND amount = ? AND transaction_date = ? AND transaction_type = ?
            LIMIT 1
        ");
        $stmt->execute([$accountId, $transaction['amount'], $transaction['transaction_date'], $transaction['transaction_type']]);
        if ($stmt->fetch()) {
            continue; // Aynı işlem varsa atla
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO bank_transactions (
                account_id, transaction_type, amount, balance_after, 
                description, transaction_date, reference_number
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $accountId,
            $transaction['transaction_type'],
            $transaction['amount'],
            $transaction['balance_after'] ?? 0,
            $transaction['description'] ?? '',
            $transaction['transaction_date'],
            $transaction['reference_number'] ?? ''
        ]);
        
        if ($result) {
            $imported++;
        }
    }
    
    return $imported;
}

/**
 * Tekrarlayan işlemleri içe aktar
 */
function importRecurringTransactionsData($userId, $recurringTransactions, $categoryMapping = [], $accountMapping = []) {
    global $pdo;
    
    $imported = 0;
    
    foreach ($recurringTransactions as $transaction) {
        $categoryId = null;
        $accountId = null;
        
        // Kategori ID'sini bul
        if (isset($categoryMapping[$transaction['category_id']])) {
            $categoryId = $categoryMapping[$transaction['category_id']];
        } else {
            $stmt = $pdo->prepare("
                SELECT id FROM categories 
                WHERE name = ? AND type = ? AND (user_id = ? OR user_id IS NULL)
                LIMIT 1
            ");
            $stmt->execute([
                $transaction['category_name'], 
                $transaction['category_type'] ?? $transaction['type'], 
                $userId
            ]);
            $category = $stmt->fetch();
            
            if ($category) {
                $categoryId = $category['id'];
            }
        }
        
        // Banka hesabı ID'sini bul (varsa)
        if (!empty($transaction['bank_account_id'])) {
            if (isset($accountMapping[$transaction['bank_account_id']])) {
                $accountId = $accountMapping[$transaction['bank_account_id']];
            } else if (!empty($transaction['account_name'])) {
                $stmt = $pdo->prepare("
                    SELECT id FROM bank_accounts 
                    WHERE account_name = ? AND user_id = ?
                    LIMIT 1
                ");
                $stmt->execute([$transaction['account_name'], $userId]);
                $account = $stmt->fetch();
                
                if ($account) {
                    $accountId = $account['id'];
                }
            }
        }
        
        if (!$categoryId) {
            continue; // Kategori bulunamazsa işlemi atla
        }
        
        // Aynı tekrarlayan işlem var mı kontrol et
        $stmt = $pdo->prepare("
            SELECT id FROM recurring_transactions 
            WHERE user_id = ? AND category_id = ? AND amount = ? AND description = ?
            LIMIT 1
        ");
        $stmt->execute([$userId, $categoryId, $transaction['amount'], $transaction['description']]);
        if ($stmt->fetch()) {
            continue; // Aynı işlem varsa atla
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO recurring_transactions (
                user_id, category_id, bank_account_id, type, amount, description,
                frequency, frequency_interval, day_of_week, day_of_month,
                start_date, end_date, next_occurrence, notification_days, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $userId,
            $categoryId,
            $accountId,
            $transaction['type'],
            $transaction['amount'],
            $transaction['description'] ?? '',
            $transaction['frequency'],
            $transaction['frequency_interval'] ?? 1,
            $transaction['day_of_week'] ?? null,
            $transaction['day_of_month'] ?? null,
            $transaction['start_date'],
            $transaction['end_date'] ?? null,
            $transaction['next_occurrence'],
            $transaction['notification_days'] ?? 3,
            $transaction['is_active'] ?? 1
        ]);
        
        if ($result) {
            $imported++;
        }
    }
    
    return $imported;
}

/**
 * Bildirimleri içe aktar
 */
function importNotifications($userId, $notifications) {
    global $pdo;
    
    $imported = 0;
    
    foreach ($notifications as $notification) {
        // Aynı bildirim var mı kontrol et
        $stmt = $pdo->prepare("
            SELECT id FROM notifications 
            WHERE user_id = ? AND title = ? AND created_at = ?
            LIMIT 1
        ");
        $stmt->execute([$userId, $notification['title'], $notification['created_at']]);
        if ($stmt->fetch()) {
            continue; // Aynı bildirim varsa atla
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO notifications (
                user_id, type, title, message, related_table, related_id,
                is_read, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $userId,
            $notification['type'] ?? 'info',
            $notification['title'],
            $notification['message'],
            $notification['related_table'] ?? null,
            $notification['related_id'] ?? null,
            $notification['is_read'] ?? 0,
            $notification['created_at']
        ]);
        
        if ($result) {
            $imported++;
        }
    }
    
    return $imported;
}

/**
 * Kullanıcının mevcut verilerini sil (dikkatli kullanın!)
 */
function deleteUserData($userId) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Önce bağımlılıkları sil
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        $stmt = $pdo->prepare("DELETE FROM recurring_transactions WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        $stmt = $pdo->prepare("
            DELETE bt FROM bank_transactions bt 
            JOIN bank_accounts ba ON bt.account_id = ba.id 
            WHERE ba.user_id = ?
        ");
        $stmt->execute([$userId]);
        
        $stmt = $pdo->prepare("
            DELETE btr FROM bank_transfers btr
            JOIN bank_accounts ba1 ON btr.from_account_id = ba1.id
            WHERE ba1.user_id = ?
        ");
        $stmt->execute([$userId]);
        
        $stmt = $pdo->prepare("
            DELETE btr FROM bank_transfers btr
            JOIN bank_accounts ba2 ON btr.to_account_id = ba2.id
            WHERE ba2.user_id = ?
        ");
        $stmt->execute([$userId]);
        
        $stmt = $pdo->prepare("DELETE FROM bank_accounts WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        $stmt = $pdo->prepare("DELETE FROM categories WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?>