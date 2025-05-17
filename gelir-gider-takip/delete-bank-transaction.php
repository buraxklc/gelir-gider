<?php
require_once 'includes/functions.php';
require_once 'includes/bank-functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$transactionId = $_GET['id'] ?? 0;
$accountId = $_GET['account'] ?? 0;

// İşlem bilgilerini getir
$transaction = getBankTransactionById($transactionId);

// İşlem bulunamadı veya kullanıcının değilse
if (!$transaction || !isUserBankAccount($transaction['account_id'], $userId)) {
    header('Location: bank-accounts.php');
    exit;
}

// Hesap ID'sini al
$accountId = $transaction['account_id'];

// Hesap bilgilerini getir
$account = getBankAccountById($accountId, $userId);

try {
    $pdo->beginTransaction();
    
    // İşlemi silerken hesap bakiyesini güncelle
    $newBalance = $account['current_balance'];
    
    // İşlemin tipine göre bakiyeyi güncelle
    if (in_array($transaction['transaction_type'], ['deposit', 'transfer_in', 'interest'])) {
        // Para yatırma işlemiyse bakiyeden düş
        $newBalance -= $transaction['amount'];
    } else {
        // Para çekme işlemiyse bakiyeye ekle
        $newBalance += $transaction['amount'];
    }
    
    // İşlemi sil
    $stmt = $pdo->prepare("DELETE FROM bank_transactions WHERE id = ?");
    $stmt->execute([$transactionId]);
    
    // Hesap bakiyesini güncelle
    updateAccountBalance($accountId, $newBalance);
    
    $pdo->commit();
    
    $_SESSION['success_message'] = 'Banka işlemi başarıyla silindi.';
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = 'İşlem silinirken bir hata oluştu: ' . $e->getMessage();
}

// İşlem tamamlandığında banka hareketleri sayfasına yönlendir
header('Location: bank-transactions.php?id=' . $accountId);
exit;
?>