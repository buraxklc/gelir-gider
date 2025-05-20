<?php
require_once 'includes/functions.php';
require_once 'includes/recurring-functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$id = $_GET['id'] ?? 0;

// İşlemi kontrol et
$transaction = getRecurringTransactionById($id, $userId);

if ($transaction) {
    $stmt = $pdo->prepare("DELETE FROM recurring_transactions WHERE id = ? AND user_id = ?");
    
    if ($stmt->execute([$id, $userId])) {
        $_SESSION['success_message'] = 'Tekrarlayan işlem silindi.';
    }
}

header('Location: recurring-transactions.php');
exit;
?>