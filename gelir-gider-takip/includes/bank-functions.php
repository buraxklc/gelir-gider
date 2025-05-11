<?php
require_once __DIR__ . '/db.php';

// Kullanıcının banka hesaplarını getir
function getBankAccounts($userId, $onlyActive = true) {
    global $pdo;
    
    $sql = "SELECT ba.*, b.name as bank_full_name, b.color as bank_color 
            FROM bank_accounts ba
            LEFT JOIN banks b ON ba.bank_name = b.short_name
            WHERE ba.user_id = ?";
    
    if ($onlyActive) {
        $sql .= " AND ba.is_active = TRUE";
    }
    
    $sql .= " ORDER BY ba.account_name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Tek bir banka hesabını getir
function getBankAccountById($accountId, $userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT ba.*, b.name as bank_full_name, b.color as bank_color 
        FROM bank_accounts ba
        LEFT JOIN banks b ON ba.bank_name = b.short_name
        WHERE ba.id = ? AND ba.user_id = ?
    ");
    $stmt->execute([$accountId, $userId]);
    return $stmt->fetch();
}

// Banka hesabı ekle
function addBankAccount($userId, $data) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO bank_accounts 
        (user_id, bank_name, account_name, account_number, iban, account_type, 
         currency, initial_balance, current_balance, color, icon) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $success = $stmt->execute([
        $userId,
        $data['bank_name'],
        $data['account_name'],
        $data['account_number'] ?? null,
        $data['iban'] ?? null,
        $data['account_type'] ?? 'checking',
        $data['currency'] ?? 'TRY',
        $data['initial_balance'] ?? 0,
        $data['initial_balance'] ?? 0, // current_balance başlangıçta initial_balance'a eşit
        $data['color'] ?? '#4F46E5',
        $data['icon'] ?? 'bi-bank'
    ]);
    
    return $success ? $pdo->lastInsertId() : false;
}

// Banka hesabını güncelle
function updateBankAccount($accountId, $userId, $data) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE bank_accounts 
        SET bank_name = ?, account_name = ?, account_number = ?, iban = ?, 
            account_type = ?, currency = ?, color = ?, icon = ?
        WHERE id = ? AND user_id = ?
    ");
    
    return $stmt->execute([
        $data['bank_name'],
        $data['account_name'],
        $data['account_number'] ?? null,
        $data['iban'] ?? null,
        $data['account_type'] ?? 'checking',
        $data['currency'] ?? 'TRY',
        $data['color'] ?? '#4F46E5',
        $data['icon'] ?? 'bi-bank',
        $accountId,
        $userId
    ]);
}

// Hesap bakiyesini güncelle
function updateAccountBalance($accountId, $newBalance) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE bank_accounts 
        SET current_balance = ? 
        WHERE id = ?
    ");
    
    return $stmt->execute([$newBalance, $accountId]);
}

// Banka işlemi ekle
function addBankTransaction($accountId, $data) {
    global $pdo;
    
    // Mevcut bakiyeyi al
    $stmt = $pdo->prepare("SELECT current_balance FROM bank_accounts WHERE id = ?");
    $stmt->execute([$accountId]);
    $currentBalance = $stmt->fetch()['current_balance'];
    
    // Yeni bakiyeyi hesapla
    $newBalance = $currentBalance;
    if (in_array($data['transaction_type'], ['deposit', 'transfer_in', 'interest'])) {
        $newBalance += $data['amount'];
    } else {
        $newBalance -= $data['amount'];
    }
    
    // İşlemi kaydet
    $stmt = $pdo->prepare("
        INSERT INTO bank_transactions 
        (account_id, transaction_id, transaction_type, amount, balance_after, 
         description, transaction_date, reference_number) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $success = $stmt->execute([
        $accountId,
        $data['transaction_id'] ?? null,
        $data['transaction_type'],
        $data['amount'],
        $newBalance,
        $data['description'] ?? null,
        $data['transaction_date'] ?? date('Y-m-d H:i:s'),
        $data['reference_number'] ?? null
    ]);
    
    if ($success) {
        // Hesap bakiyesini güncelle
        updateAccountBalance($accountId, $newBalance);
    }
    
    return $success;
}

// Hesap hareketlerini getir
function getBankTransactions($accountId, $filters = []) {
    global $pdo;
    
    $sql = "SELECT * FROM bank_transactions WHERE account_id = ?";
    $params = [$accountId];
    
    if (!empty($filters['start_date'])) {
        $sql .= " AND transaction_date >= ?";
        $params[] = $filters['start_date'];
    }
    
    if (!empty($filters['end_date'])) {
        $sql .= " AND transaction_date <= ?";
        $params[] = $filters['end_date'];
    }
    
    if (!empty($filters['type'])) {
        $sql .= " AND transaction_type = ?";
        $params[] = $filters['type'];
    }
    
    $sql .= " ORDER BY transaction_date DESC, id DESC";
    
    if (!empty($filters['limit'])) {
        $sql .= " LIMIT " . intval($filters['limit']);
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Hesaplar arası transfer
function bankTransfer($fromAccountId, $toAccountId, $amount, $description = '') {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Transfer kaydını oluştur
        $stmt = $pdo->prepare("
            INSERT INTO bank_transfers 
            (from_account_id, to_account_id, amount, description) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$fromAccountId, $toAccountId, $amount, $description]);
        $transferId = $pdo->lastInsertId();
        
        // Gönderen hesap işlemi
        addBankTransaction($fromAccountId, [
            'transaction_type' => 'transfer_out',
            'amount' => $amount,
            'description' => "Transfer: " . $description,
            'reference_number' => "TRF-" . $transferId
        ]);
        
        // Alıcı hesap işlemi
        addBankTransaction($toAccountId, [
            'transaction_type' => 'transfer_in',
            'amount' => $amount,
            'description' => "Transfer: " . $description,
            'reference_number' => "TRF-" . $transferId
        ]);
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

// Toplam varlıkları hesapla
function getTotalAssets($userId, $currency = 'TRY') {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT SUM(current_balance) as total 
        FROM bank_accounts 
        WHERE user_id = ? AND currency = ? AND is_active = TRUE
    ");
    $stmt->execute([$userId, $currency]);
    $result = $stmt->fetch();
    
    return $result['total'] ?? 0;
}

// Bankaları getir
function getBanks() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM banks WHERE is_active = TRUE ORDER BY name");
    return $stmt->fetchAll();
}

// Hesap özeti
function getAccountSummary($accountId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_transactions,
            SUM(CASE WHEN transaction_type IN ('deposit', 'transfer_in', 'interest') THEN amount ELSE 0 END) as total_deposits,
            SUM(CASE WHEN transaction_type IN ('withdrawal', 'transfer_out', 'fee') THEN amount ELSE 0 END) as total_withdrawals,
            MIN(transaction_date) as first_transaction,
            MAX(transaction_date) as last_transaction
        FROM bank_transactions
        WHERE account_id = ?
    ");
    $stmt->execute([$accountId]);
    return $stmt->fetch();
}
?>