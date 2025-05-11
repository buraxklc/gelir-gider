<?php
require_once 'includes/functions.php';
require_once 'includes/recurring-functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$notificationId = $_GET['id'] ?? 0;

markNotificationAsRead($notificationId, $userId);

// Geri dön
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
exit;
?>