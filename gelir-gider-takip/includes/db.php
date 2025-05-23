<?php
// Türkiye saat dilimini ayarla - EN ÜST SATIR
date_default_timezone_set('Europe/Istanbul');

// Veritabanı bağlantı ayarları
define('DB_HOST', 'localhost');
define('DB_NAME', 'gelir_gider_db');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // MySQL'i Türkiye saatine ayarla
    $pdo->exec("SET time_zone = '+03:00'");
    
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// Session başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>