<?php
require_once 'includes/functions.php';
require_once 'includes/recurring-functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$notificationId = $_GET['id'] ?? 0;
$redirect = $_GET['redirect'] ?? 'dashboard.php';

markNotificationAsRead($notificationId, $userId);

// Geri dön
header('Location: ' . $redirect);
exit;
?>