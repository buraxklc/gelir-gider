<?php
// Oturum başlat
session_start();

// Fonksiyonları dahil et
require_once 'includes/functions.php';
require_once 'update-functions.php';

// Yetki kontrolü
requireLogin();

// Yönetici değilse, hata döndür
if (!isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Yetkisiz erişim']);
    exit;
}

// JSON başlığı
header('Content-Type: application/json');

// Güncelleme kontrolü yap ve JSON döndür
echo json_encode(checkForUpdates());