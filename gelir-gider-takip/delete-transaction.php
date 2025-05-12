<?php
require_once 'includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$transactionId = $_GET['id'] ?? '';
$redirect = $_GET['redirect'] ?? 'dashboard.php';

if ($transactionId) {
    // İşlemin kullanıcıya ait olduğunu kontrol et
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->execute([$transactionId, $userId]);
}

// Yönlendir
header('Location: ' . $redirect);
exit;
?>