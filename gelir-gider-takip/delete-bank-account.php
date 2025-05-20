<?php
require_once 'includes/functions.php';
require_once 'includes/bank-functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$accountId = $_GET['id'] ?? 0;

// Hesap bilgilerini kontrol et
$account = getBankAccountById($accountId, $userId);

if ($account) {
    try {
        $pdo->beginTransaction();
        
        // Önce hesaba ait tüm işlemleri sil
        $stmt = $pdo->prepare("DELETE FROM bank_transactions WHERE account_id = ?");
        $stmt->execute([$accountId]);
        
        // Sonra hesabı sil
        $stmt = $pdo->prepare("DELETE FROM bank_accounts WHERE id = ? AND user_id = ?");
        $stmt->execute([$accountId, $userId]);
        
        $pdo->commit();
        
        $_SESSION['success_message'] = 'Banka hesabı başarıyla silindi.';
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = 'Hesap silinirken bir hata oluştu.';
    }
}

header('Location: bank-accounts.php');
exit;
?>