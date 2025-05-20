<?php
// Güncelleme yönetimi için yardımcı fonksiyonlar
require_once __DIR__ . '/version.php';

/**
 * Kritik dosyaları koruma listesi
 * Bu dosyalar yedeklenecek ve güncelleme sonrası geri yüklenecek
 */
function getCriticalFiles() {
    return [
        'includes/config.php',
        'includes/db.php',
        'client_settings.php', 
        'custom_theme.css'
    ];
}

/**
 * Son sürümü kontrol eder
 * @return array Güncelleme bilgilerini içeren dizi
 */
function checkForUpdates() {
    $currentVersion = APP_VERSION;
    $updateServer = UPDATE_SERVER;
    
    // Son sürümü kontrol et
    $latestVersion = @file_get_contents($updateServer . 'latest-version.txt');
    if (!$latestVersion) {
        return [
            'update_available' => false,
            'error' => 'Güncelleme sunucusuna bağlanılamadı.'
        ];
    }
    
    $latestVersion = trim($latestVersion); // Boşlukları temizle
    
    if (version_compare($latestVersion, $currentVersion, '>')) {
        // Değişiklikleri al
        $changelog = @file_get_contents($updateServer . $latestVersion . '/changelog.txt');
        
        // Dosya listesini al
        $fileList = @file_get_contents($updateServer . $latestVersion . '/filelist.txt');
        $files = $fileList ? explode("\n", $fileList) : [];
        $files = array_filter($files); // Boş satırları temizle
        
        return [
            'update_available' => true,
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'changelog' => $changelog ?: 'Değişiklik bilgisi bulunamadı.',
            'files' => $files
        ];
    }
    
    return ['update_available' => false];
}

/**
 * Güncelleme öncesi sistem yedeği oluşturur
 * @param array $files Yedeklenecek dosyalar
 * @return string Yedek klasörünün yolu
 */
function createBackup($files = []) {
    $backupDir = __DIR__ . '/backups/' . date('Y-m-d_H-i-s');
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    // Tüm kritik dosyaları yedekle
    $criticalFiles = getCriticalFiles();
    foreach ($criticalFiles as $file) {
        $fullPath = __DIR__ . '/' . $file;
        if (file_exists($fullPath)) {
            // Klasör yapısını koru
            $backupPath = $backupDir . '/' . $file;
            $backupFolder = dirname($backupPath);
            
            if (!is_dir($backupFolder)) {
                mkdir($backupFolder, 0755, true);
            }
            
            copy($fullPath, $backupPath);
        }
    }
    
    // Güncellenecek dosyaları da yedekle
    foreach ($files as $file) {
        $fullPath = __DIR__ . '/' . $file;
        if (file_exists($fullPath)) {
            // Klasör yapısını koru
            $backupPath = $backupDir . '/' . $file;
            $backupFolder = dirname($backupPath);
            
            if (!is_dir($backupFolder)) {
                mkdir($backupFolder, 0755, true);
            }
            
            copy($fullPath, $backupPath);
        }
    }
    
    // Sürüm bilgisini kaydet
    file_put_contents($backupDir . '/version.txt', APP_VERSION);
    
    return $backupDir;
}

/**
 * Yedeklenen kritik dosyaları geri yükler
 * @param string $backupDir Yedek klasörünün yolu
 * @return bool Başarılı olup olmadığı
 */
function restoreCriticalFiles($backupDir) {
    $criticalFiles = getCriticalFiles();
    foreach ($criticalFiles as $file) {
        $backupPath = $backupDir . '/' . $file;
        $destPath = __DIR__ . '/' . $file;
        
        if (file_exists($backupPath)) {
            // Hedef klasörün var olduğundan emin ol
            $destFolder = dirname($destPath);
            if (!is_dir($destFolder)) {
                mkdir($destFolder, 0755, true);
            }
            
            copy($backupPath, $destPath);
        }
    }
    
    return true;
}

/**
 * Tek bir dosyayı günceller
 * @param string $file Dosya yolu
 * @param string $version Güncelleme sürümü
 * @return bool Başarılı olup olmadığı
 */
function updateSingleFile($file, $version) {
    $updateServer = UPDATE_SERVER;
    $fileUrl = $updateServer . $version . '/files/' . $file;
    
    $targetPath = __DIR__ . '/' . $file;
    $targetDir = dirname($targetPath);
    
    // Klasörün var olduğundan emin ol
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Yeni dosyayı indir
    $newContent = @file_get_contents($fileUrl);
    if ($newContent !== false) {
        file_put_contents($targetPath, $newContent);
        return true;
    }
    
    return false;
}

/**
 * Sürüm dosyasını günceller
 * @param string $version Yeni sürüm numarası
 * @return bool Başarılı olup olmadığı
 */
function updateVersionFile($version) {
    $versionFile = __DIR__ . '/version.php';
    $content = file_get_contents($versionFile);
    
    // Sürüm numarasını değiştir
    $content = preg_replace(
        "/define\('APP_VERSION',\s*'(.*)'\);/",
        "define('APP_VERSION', '$version');",
        $content
    );
    
    return file_put_contents($versionFile, $content);
}

/**
 * Veritabanı güncellemelerini çalıştırır
 * @param string $version Yeni sürüm numarası
 * @return bool Başarılı olup olmadığı
 */
function runDatabaseUpdates($version) {
    // Güncelleme dosyasını indir
    $updateServer = UPDATE_SERVER;
    $dbUpdateUrl = $updateServer . $version . '/database.php';
    $updatePath = __DIR__ . '/temp_db_update.php';
    
    // Dosyayı indir
    $content = @file_get_contents($dbUpdateUrl);
    if ($content) {
        file_put_contents($updatePath, $content);
        
        // Dosyayı yükle ve çalıştır
        include_once $updatePath;
        if (function_exists('applyDatabaseUpdates')) {
            $result = applyDatabaseUpdates();
            
            // Geçici dosyayı sil
            @unlink($updatePath);
            
            return $result;
        }
        
        // Geçici dosyayı sil
        @unlink($updatePath);
    }
    
    return true; // Güncelleme yoksa başarılı kabul et
}

/**
 * Yönetici yetkisini kontrol eder
 * Burada kendi yetkilendirme sisteminizi kullanın
 */
function isAdmin() {
    // Bu kısmı kendi yetkilendirme sisteminize göre değiştirin
    if (isset($_SESSION['user_id'])) {
        // Örneğin, yönetici kullanıcı ID'si 1 mi kontrolü
        return $_SESSION['user_id'] == 1;
    }
    return false;
}