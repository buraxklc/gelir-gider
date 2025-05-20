<?php
require_once 'includes/functions.php';
require_once 'includes/backup-functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Dışa aktarma işlemi
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $jsonData = exportUserData($userId);
    
    if ($jsonData) {
        $filename = 'kasapro_yedek_' . $_SESSION['username'] . '_' . date('Y-m-d_H-i-s') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($jsonData));
        
        echo $jsonData;
        exit;
    } else {
        $error = 'Veri dışa aktarma işlemi başarısız!';
    }
}

// İçe aktarma işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
    if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
        $uploadedFile = $_FILES['backup_file'];
        
        // Dosya kontrolü
        if ($uploadedFile['size'] > 10 * 1024 * 1024) { // 10MB limit
            $error = 'Dosya boyutu çok büyük! Maksimum 10MB olabilir.';
        } elseif (pathinfo($uploadedFile['name'], PATHINFO_EXTENSION) !== 'json') {
            $error = 'Sadece JSON dosyaları kabul edilir!';
        } else {
            $jsonData = file_get_contents($uploadedFile['tmp_name']);
            
            if ($jsonData === false) {
                $error = 'Dosya okunamadı!';
            } else {
                try {
                    $overwriteData = isset($_POST['overwrite_data']);
                    
                    if ($overwriteData) {
                        // Mevcut verileri sil
                        deleteUserData($userId);
                    }
                    
                    if (importUserData($userId, $jsonData)) {
                        $success = 'Veriler başarıyla içe aktarıldı!';
                    } else {
                        $error = 'Veri içe aktarma işlemi başarısız!';
                    }
                } catch (Exception $e) {
                    $error = 'İçe aktarma hatası: ' . $e->getMessage();
                }
            }
        }
    } else {
        $error = 'Lütfen geçerli bir dosya seçin!';
    }
}

$pageTitle = 'Veri Yedekleme';
include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h2 class="mb-4">
                <i class="bi bi-shield-check"></i> Veri Yedekleme Sistemi
            </h2>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-circle"></i> <?= e($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?= e($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Dışa Aktarma Kartı -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-download"></i> Veri Dışa Aktarma
                    </h5>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        Tüm verilerinizi JSON formatında indirin. Bu dosya ile verilerinizi başka bir sisteme 
                        aktarabilir veya güvenli bir yerde saklayabilirsiniz.
                    </p>
                    
                    <div class="bg-light p-3 rounded mb-3">
                        <h6>Dışa aktarılacak veriler:</h6>
                        <ul class="mb-0">
                            <li>✅ Profil bilgileriniz</li>
                            <li>✅ Tüm kategoriler</li>
                            <li>✅ Tüm işlemler</li>
                            <li>✅ Banka hesapları</li>
                            <li>✅ Banka işlemleri</li>
                            <li>✅ Tekrarlayan işlemler</li>
                            <li>✅ Bildirimler</li>
                        </ul>
                    </div>
                    
                    <a href="backup.php?action=export" class="btn btn-primary btn-lg">
                        <i class="bi bi-download"></i> Verilerimi İndir
                    </a>
                </div>
            </div>

            <!-- İçe Aktarma Kartı -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-upload"></i> Veri İçe Aktarma
                    </h5>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        Daha önce dışa aktardığınız JSON dosyasını yükleyerek verilerinizi geri yükleyin.
                    </p>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Dikkat:</strong> Bu işlem geri alınamaz! Mevcut verilerinizi değiştirmek istiyorsanız 
                        önce yedek almayı unutmayın.
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="backup_file" class="form-label">Yedek Dosyası</label>
                            <input type="file" class="form-control" id="backup_file" name="backup_file" 
                                   accept=".json" required>
                            <div class="form-text">Sadece JSON dosyaları kabul edilir. Maksimum 10MB.</div>
                            <div class="invalid-feedback">Lütfen geçerli bir JSON dosyası seçin.</div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="overwrite_data" name="overwrite_data">
                            <label class="form-check-label" for="overwrite_data">
                                <strong>Mevcut verileri sil ve değiştir</strong>
                                <br><small class="text-muted">
                                    Bu seçeneği işaretlerseniz, mevcut tüm verileriniz silinir ve yedek dosyasındaki 
                                    verilerle değiştirilir. İşaretlenmezse veriler mevcut verilere eklenir.
                                </small>
                            </label>
                        </div>
                        
                        <button type="submit" name="import" class="btn btn-danger btn-lg" 
                                onclick="return confirm('Bu işlemi gerçekleştirmek istediğinize emin misiniz?')">
                            <i class="bi bi-upload"></i> Verileri İçe Aktar
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Yardım Kartı -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-question-circle"></i> Nasıl Kullanılır?
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">🔽 Dışa Aktarma</h6>
                            <p class="small">Tüm verilerinizi JSON formatında bir dosyaya kaydeder. Bu dosyayı güvenli bir yerde 
                            saklayarak verilerinizi koruyabilirsiniz.</p>
                            
                            <h6 class="text-info">🔼 İçe Aktarma</h6>
                            <p class="small">Daha önce dışa aktardığınız dosyayı sisteme yükleyerek verilerinizi geri getirir.</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success">💡 Ne Zaman Kullanılır?</h6>
                            <ul class="small">
                                <li>🏠 Hosting değiştirirken</li>
                                <li>🛡️ Veri kaybına karşı yedekleme</li>
                                <li>🔄 Farklı sistemler arasında veri taşıma</li>
                                <li>🧪 Test amacıyla veri kopyalama</li>
                            </ul>
                            
                            <div class="alert alert-info alert-sm">
                                <small><strong>💡 İpucu:</strong> Yedek dosyalarınızı güvenli bir yerde saklayın!</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validasyonu
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Dosya seçildiğinde bilgi göster
    const fileInput = document.getElementById('backup_file');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                console.log(`Seçilen dosya: ${file.name} (${fileSize} MB)`);
            }
        });
    }
});
</script>

<style>
.alert-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

.card {
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-lg {
    padding: 0.75rem 2rem;
    font-size: 1.125rem;
}

.bg-light {
    background-color: #f8f9fa !important;
}

.list-unstyled li {
    margin-bottom: 0.25rem;
}
</style>

<?php include 'includes/footer.php'; ?>