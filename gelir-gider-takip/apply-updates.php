<?php
// Oturum başlat
session_start();

// Fonksiyonları dahil et
require_once 'includes/functions.php';
require_once 'update-functions.php';

// Yetki kontrolü
requireLogin();

// Yönetici değilse, ana sayfaya yönlendir
if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Güncelleme Uygulama';
include 'includes/header.php'; // Kendi header dosyanızı ekleyin

$result = [
    'success' => false,
    'message' => '',
    'updated_files' => [],
    'failed_files' => [],
    'version' => ''
];

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $version = $_POST['version'] ?? '';
    
    if (empty($version)) {
        $result['message'] = 'Sürüm bilgisi bulunamadı!';
    } else {
        // Hangi dosyaları güncelleyeceğiz?
        if (isset($_POST['update_all'])) {
            // Tüm dosyaları güncelle
            $updates = checkForUpdates();
            $filesToUpdate = $updates['files'];
        } else {
            // Seçili dosyaları güncelle
            $filesToUpdate = $_POST['files'] ?? [];
        }
        
        if (empty($filesToUpdate)) {
            $result['message'] = 'Güncellenecek dosya seçilmedi!';
        } else {
            // Güncelleme öncesi yedek al
            $backupDir = createBackup($filesToUpdate);
            
            // Dosyaları güncelle
            foreach ($filesToUpdate as $file) {
                if (empty($file)) continue;
                
                if (updateSingleFile($file, $version)) {
                    $result['updated_files'][] = $file;
                } else {
                    $result['failed_files'][] = $file;
                }
            }
            
            // Kritik dosyaları geri yükle
            restoreCriticalFiles($backupDir);
            
            // Tüm dosyalar güncellendiyse, sürüm numarasını güncelle
            if (isset($_POST['update_all']) && empty($result['failed_files'])) {
                if (updateVersionFile($version)) {
                    $result['version'] = $version;
                }
                
                // Veritabanı güncellemesi varsa çalıştır
                runDatabaseUpdates($version);
            }
            
            $result['success'] = true;
            $result['message'] = 'Güncelleme tamamlandı!';
        }
    }
}

?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-cloud-upload"></i> Güncelleme Sonucu</h5>
                </div>
                <div class="card-body">
                    <?php if ($result['message']): ?>
                        <div class="alert alert-<?= $result['success'] ? 'success' : 'danger' ?>">
                            <h5>
                                <i class="bi bi-<?= $result['success'] ? 'check-circle' : 'exclamation-triangle' ?>"></i> 
                                <?= $result['message'] ?>
                            </h5>
                            
                            <?php if ($result['version']): ?>
                                <p>Sistem sürümü başarıyla <strong><?= $result['version'] ?></strong> olarak güncellendi.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($result['updated_files'])): ?>
                        <div class="mt-4">
                            <h6><i class="bi bi-check-circle text-success"></i> Güncellenen Dosyalar (<?= count($result['updated_files']) ?>)</h6>
                            <ul class="list-group">
                                <?php foreach ($result['updated_files'] as $file): ?>
                                    <li class="list-group-item"><?= $file ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($result['failed_files'])): ?>
                        <div class="mt-4">
                            <h6><i class="bi bi-x-circle text-danger"></i> Güncellenemeyen Dosyalar (<?= count($result['failed_files']) ?>)</h6>
                            <ul class="list-group">
                                <?php foreach ($result['failed_files'] as $file): ?>
                                    <li class="list-group-item"><?= $file ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="update-system.php" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> Güncelleme Sayfasına Dön
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-house"></i> Ana Sayfaya Dön
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; // Kendi footer dosyanızı ekleyin ?>