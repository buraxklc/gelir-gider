<?php
require_once 'includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$transactionId = $_GET['id'] ?? '';

if ($transactionId) {
    // İşlemin kullanıcıya ait olduğunu kontrol et
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->execute([$transactionId, $userId]);
}

// Dashboard'a yönlendir
header('Location: dashboard.php');
exit;
?>