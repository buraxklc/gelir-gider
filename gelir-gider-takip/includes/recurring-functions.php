<?php
require_once __DIR__ . '/db.php';

// Tekrarlayan işlemleri getir
function getRecurringTransactions($userId, $onlyActive = true) {
    global $pdo;
    
    $sql = "SELECT rt.*, c.name as category_name, c.icon as category_icon, 
                   c.color as category_color, ba.account_name as bank_account_name
            FROM recurring_transactions rt
            JOIN categories c ON rt.category_id = c.id
            LEFT JOIN bank_accounts ba ON rt.bank_account_id = ba.id
            WHERE rt.user_id = ?";
    
    if ($onlyActive) {
        $sql .= " AND rt.is_active = TRUE";
    }
    
    $sql .= " ORDER BY rt.next_occurrence ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Tek bir tekrarlayan işlemi getir
function getRecurringTransactionById($id, $userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT rt.*, c.name as category_name, ba.account_name as bank_account_name
        FROM recurring_transactions rt
        JOIN categories c ON rt.category_id = c.id
        LEFT JOIN bank_accounts ba ON rt.bank_account_id = ba.id
        WHERE rt.id = ? AND rt.user_id = ?
    ");
    $stmt->execute([$id, $userId]);
    return $stmt->fetch();
}

// Tekrarlayan işlem ekle
function addRecurringTransaction($userId, $data) {
    global $pdo;
    
    // Sonraki oluşma tarihini hesapla
    $nextOccurrence = calculateNextOccurrence(
        $data['start_date'],
        $data['frequency'],
        $data['frequency_interval'] ?? 1,
        $data['day_of_week'] ?? null,
        $data['day_of_month'] ?? null
    );
    
    $stmt = $pdo->prepare("
        INSERT INTO recurring_transactions 
        (user_id, category_id, bank_account_id, type, amount, description, 
         frequency, frequency_interval, day_of_week, day_of_month, 
         start_date, end_date, next_occurrence, notification_days) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $success = $stmt->execute([
        $userId,
        $data['category_id'],
        $data['bank_account_id'] ?? null,
        $data['type'],
        $data['amount'],
        $data['description'] ?? '',
        $data['frequency'],
        $data['frequency_interval'] ?? 1,
        $data['day_of_week'] ?? null,
        $data['day_of_month'] ?? null,
        $data['start_date'],
        $data['end_date'] ?? null,
        $nextOccurrence,
        $data['notification_days'] ?? 3
    ]);
    
    return $success ? $pdo->lastInsertId() : false;
}

// Sonraki oluşma tarihini hesapla
function calculateNextOccurrence($startDate, $frequency, $interval = 1, $dayOfWeek = null, $dayOfMonth = null) {
    $date = new DateTime($startDate);
    $today = new DateTime();
    
    // Eğer başlangıç tarihi gelecekte ise, o tarihi döndür
    if ($date > $today) {
        return $date->format('Y-m-d');
    }
    
    // Bugüne kadar olan tekrarları atla
    while ($date <= $today) {
        switch ($frequency) {
            case 'daily':
                $date->modify("+{$interval} days");
                break;
                
            case 'weekly':
                if ($dayOfWeek) {
                    $date->modify('next ' . getDayName($dayOfWeek));
                } else {
                    $date->modify("+{$interval} weeks");
                }
                break;
                
            case 'monthly':
                if ($dayOfMonth) {
                    $date->modify('first day of next month');
                    $date->setDate($date->format('Y'), $date->format('m'), min($dayOfMonth, $date->format('t')));
                } else {
                    $date->modify("+{$interval} months");
                }
                break;
                
            case 'yearly':
                $date->modify("+{$interval} years");
                break;
        }
    }
    
    return $date->format('Y-m-d');
}

// Gün ismini getir
function getDayName($dayNumber) {
    $days = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday'
    ];
    return $days[$dayNumber] ?? 'Monday';
}

// Tekrarlayan işlemleri işle (Cron job için)
function processRecurringTransactions() {
    global $pdo;
    
    $today = date('Y-m-d');
    
    // Bugün veya geçmişte olması gereken işlemleri getir
    $stmt = $pdo->prepare("
        SELECT * FROM recurring_transactions 
        WHERE is_active = TRUE 
        AND next_occurrence <= ?
        AND (end_date IS NULL OR end_date >= ?)
    ");
    $stmt->execute([$today, $today]);
    $recurringTransactions = $stmt->fetchAll();
    
    foreach ($recurringTransactions as $recurring) {
        try {
            $pdo->beginTransaction();
            
            // Normal işlem oluştur
            $stmt = $pdo->prepare("
                INSERT INTO transactions 
                (user_id, category_id, type, amount, description, transaction_date) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $recurring['user_id'],
                $recurring['category_id'],
                $recurring['type'],
                $recurring['amount'],
                $recurring['description'] . ' (Otomatik)',
                $recurring['next_occurrence']
            ]);
            
            $transactionId = $pdo->lastInsertId();
            
            // Banka hesabı varsa, banka işlemi de oluştur
            if ($recurring['bank_account_id']) {
                require_once __DIR__ . '/bank-functions.php';
                
                addBankTransaction($recurring['bank_account_id'], [
                    'transaction_id' => $transactionId,
                    'transaction_type' => $recurring['type'] == 'income' ? 'deposit' : 'withdrawal',
                    'amount' => $recurring['amount'],
                    'description' => $recurring['description'] . ' (Otomatik)',
                    'transaction_date' => $recurring['next_occurrence']
                ]);
            }
            
            // Sonraki oluşma tarihini güncelle
            $nextOccurrence = calculateNextOccurrence(
                $recurring['next_occurrence'],
                $recurring['frequency'],
                $recurring['frequency_interval'],
                $recurring['day_of_week'],
                $recurring['day_of_month']
            );
            
            $stmt = $pdo->prepare("
                UPDATE recurring_transactions 
                SET last_processed = ?, next_occurrence = ?
                WHERE id = ?
            ");
            $stmt->execute([$recurring['next_occurrence'], $nextOccurrence, $recurring['id']]);
            
            $pdo->commit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Recurring transaction error: " . $e->getMessage());
        }
    }
}

// Hatırlatıcıları kontrol et ve bildirim oluştur
function checkReminders() {
    global $pdo;
    
    // Yaklaşan tekrarlayan işlemleri kontrol et
    $stmt = $pdo->prepare("
        SELECT rt.*, c.name as category_name, u.username
        FROM recurring_transactions rt
        JOIN categories c ON rt.category_id = c.id
        JOIN users u ON rt.user_id = u.id
        WHERE rt.is_active = TRUE
        AND rt.next_occurrence BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL rt.notification_days DAY)
        AND rt.notification_days > 0
    ");
    $stmt->execute();
    $upcomingTransactions = $stmt->fetchAll();
    
    foreach ($upcomingTransactions as $transaction) {
        // Bu işlem için bugün bildirim oluşturulmuş mu kontrol et
        $stmt = $pdo->prepare("
            SELECT id FROM notifications 
            WHERE user_id = ? 
            AND related_table = 'recurring_transactions'
            AND related_id = ?
            AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute([$transaction['user_id'], $transaction['id']]);
        
        if (!$stmt->fetch()) {
            // Bildirim oluştur
            $daysUntil = ceil((strtotime($transaction['next_occurrence']) - strtotime(date('Y-m-d'))) / 86400);
            
            $title = $transaction['type'] == 'income' ? 'Yaklaşan Gelir' : 'Yaklaşan Ödeme';
            $message = sprintf(
                "%s: %s - %s (%d gün sonra)",
                $transaction['category_name'],
                $transaction['description'],
                formatMoney($transaction['amount']),
                $daysUntil
            );
            
            createNotification(
                $transaction['user_id'],
                'reminder',
                $title,
                $message,
                'recurring_transactions',
                $transaction['id']
            );
        }
    }
}

// Bildirim oluştur
function createNotification($userId, $type, $title, $message, $relatedTable = null, $relatedId = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO notifications 
        (user_id, type, title, message, related_table, related_id) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $userId,
        $type,
        $title,
        $message,
        $relatedTable,
        $relatedId
    ]);
}

// Kullanıcının bildirimlerini getir
function getUserNotifications($userId, $unreadOnly = false) {
    global $pdo;
    
    $sql = "SELECT * FROM notifications WHERE user_id = ?";
    
    if ($unreadOnly) {
        $sql .= " AND is_read = FALSE";
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Bildirimi okundu olarak işaretle
function markNotificationAsRead($notificationId, $userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = TRUE, read_at = NOW()
        WHERE id = ? AND user_id = ?
    ");
    
    return $stmt->execute([$notificationId, $userId]);
}

// Yaklaşan ödemeleri getir (Dashboard widget için)
function getUpcomingPayments($userId, $days = 7) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT rt.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
               DATEDIFF(rt.next_occurrence, CURDATE()) as days_until
        FROM recurring_transactions rt
        JOIN categories c ON rt.category_id = c.id
        WHERE rt.user_id = ?
        AND rt.is_active = TRUE
        AND rt.type = 'expense'
        AND rt.next_occurrence BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
        ORDER BY rt.next_occurrence ASC
    ");
    $stmt->execute([$userId, $days]);
    return $stmt->fetchAll();
}
?>