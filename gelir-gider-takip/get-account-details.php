<?php
require_once 'includes/functions.php';
require_once 'includes/bank-functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$accountId = $_GET['id'] ?? 0;

// Hesap bilgilerini getir
$account = getBankAccountById($accountId, $userId);

// AJAX yanıtı olarak gönder
header('Content-Type: application/json');

if ($account) {
    echo json_encode([
        'success' => true,
        'currency' => $account['currency'],
        'account_name' => $account['account_name'],
        'bank_name' => $account['bank_name']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Hesap bulunamadı'
    ]);
}
?>