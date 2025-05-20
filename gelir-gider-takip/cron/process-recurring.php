<?php
// Türkiye saat dilimini ayarla - EN ÜST SATIR
date_default_timezone_set('Europe/Istanbul');

require_once '../includes/functions.php';
require_once '../includes/recurring-functions.php';

// Debug için
echo "=== RECURRING TRANSACTIONS DEBUG ===<br>";
echo "Current PHP Date: " . date('Y-m-d H:i:s') . "<br>";

$today = date('Y-m-d');

// Tüm kullanıcılar için tekrarlayan işlemleri getir
$stmt = $pdo->prepare("
    SELECT * FROM recurring_transactions 
    WHERE is_active = 1 
    AND next_occurrence <= ?
");
$stmt->execute([$today]);
$recurringTransactions = $stmt->fetchAll();

echo "Found " . count($recurringTransactions) . " transactions to process<br><br>";

foreach ($recurringTransactions as $recurring) {
    try {
        $pdo->beginTransaction();
        
        // 1. Normal işlem oluştur
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
        
        // 2. Banka işlemi oluştur (varsa)
        if ($recurring['bank_account_id']) {
            // Banka işlemi kodu...
        }
        
        // 3. Sonraki tarihi güncelle
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
        echo "✓ Transaction processed successfully!<br>";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "✗ ERROR: " . $e->getMessage() . "<br>";
    }
}

echo "=== PROCESS COMPLETED ===<br>";
?>