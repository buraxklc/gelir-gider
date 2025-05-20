<?php
require_once 'includes/functions.php';
require_once 'includes/recurring-functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$id = $_GET['id'] ?? 0;

// İşlemi getir ve kontrol et
$transaction = getRecurringTransactionById($id, $userId);

if ($transaction) {
    // Durumu değiştir
    $newStatus = !$transaction['is_active'];
    
    $stmt = $pdo->prepare("
        UPDATE recurring_transactions 
        SET is_active = ? 
        WHERE id = ? AND user_id = ?
    ");
    
    if ($stmt->execute([$newStatus, $id, $userId])) {
        $_SESSION['success_message'] = $newStatus ? 
            'Tekrarlayan işlem aktifleştirildi.' : 
            'Tekrarlayan işlem duraklatıldı.';
    }
}

header('Location: recurring-transactions.php');
exit;
?>