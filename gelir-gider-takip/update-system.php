<?php
// Oturum başlat
session_start();

// Fonksiyonları dahil et
require_once 'includes/functions.php';
require_once 'update-functions.php';

// Yetki kontrolü - Kendi yetkilendirme sisteminize göre değiştirin
requireLogin(); // Kendi giriş kontrol fonksiyonunuz

// Yönetici değilse, ana sayfaya yönlendir
if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Sistem Güncellemeleri';
include 'includes/header.php'; // Kendi header dosyanızı ekleyin
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-cloud-download"></i> Sistem Güncellemeleri</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6>Mevcut Sürüm: <span class="badge bg-primary"><?= APP_VERSION ?></span></h6>
                            <p class="text-muted mb-0"><?= APP_NAME ?></p>
                        </div>
                        <button class="btn btn-primary" id="checkUpdatesBtn">
                            <i class="bi bi-arrow-repeat"></i> Güncellemeleri Kontrol Et
                        </button>
                    </div>
                    
                    <div id="updatesResult">
                        <!-- Sonuçlar buraya eklenecek -->
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Güncelleme kontrolü yapmak için butona tıklayın.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkBtn = document.getElementById('checkUpdatesBtn');
    const resultDiv = document.getElementById('updatesResult');
    
    checkBtn.addEventListener('click', function() {
        // Butonun görünümünü yükleniyor olarak değiştir
        checkBtn.disabled = true;
        checkBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Kontrol Ediliyor...';
        
        // AJAX isteği gönder
        fetch('check-updates-ajax.php')
            .then(response => response.json())
            .then(data => {
                // Butonun görünümünü eski haline getir
                checkBtn.disabled = false;
                checkBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Güncellemeleri Kontrol Et';
                
                // Sonuçları göster
                if (data.update_available) {
                    let files = '';
                    if (data.files && data.files.length > 0) {
                        files = '<div class="mt-3">';
                        files += '<h6>Güncellenecek Dosyalar:</h6>';
                        files += '<form method="post" action="apply-updates.php">';
                        files += '<ul class="list-group mb-3">';
                        
                        data.files.forEach(file => {
                            files += `<li class="list-group-item">
                                <div class="form-check">
                                    <input class="form-check-input file-checkbox" type="checkbox" name="files[]" value="${file}" id="file_${file.replace(/[^a-z0-9]/gi, '_')}">
                                    <label class="form-check-label" for="file_${file.replace(/[^a-z0-9]/gi, '_')}">${file}</label>
                                </div>
                            </li>`;
                        });
                        
                        files += '</ul>';
                        files += '<div class="d-flex justify-content-between">';
                        files += '<div><button type="button" id="selectAllBtn" class="btn btn-sm btn-outline-primary">Tümünü Seç</button> ';
                        files += '<button type="button" id="deselectAllBtn" class="btn btn-sm btn-outline-secondary">Seçimi Kaldır</button></div>';
                        files += '<div>';
                        files += '<input type="hidden" name="version" value="' + data.latest_version + '">';
                        files += '<button type="submit" name="update_selected" class="btn btn-success me-2">Seçili Dosyaları Güncelle</button>';
                        files += '<button type="submit" name="update_all" class="btn btn-primary">Tüm Dosyaları Güncelle</button>';
                        files += '</div>';
                        files += '</div>';
                        files += '</form>';
                        files += '</div>';
                    }
                    
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h5><i class="bi bi-check-circle"></i> Yeni Sürüm Bulundu: ${data.latest_version}</h5>
                            <p>Şu anki sürüm: ${data.current_version}</p>
                            <hr>
                            <h6>Değişiklikler:</h6>
                            <pre class="bg-light p-3 rounded">${data.changelog}</pre>
                        </div>
                        ${files}
                    `;
                    
                    // Seçim butonlarının çalışması
                    document.getElementById('selectAllBtn')?.addEventListener('click', function() {
                        document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = true);
                    });
                    
                    document.getElementById('deselectAllBtn')?.addEventListener('click', function() {
                        document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = false);
                    });
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-info">
                            <h5><i class="bi bi-info-circle"></i> Sisteminiz Güncel</h5>
                            <p>Şu anki sürüm: ${APP_VERSION}</p>
                            ${data.error ? '<div class="mt-2 text-danger"><i class="bi bi-exclamation-triangle"></i> ' + data.error + '</div>' : ''}
                        </div>
                    `;
                }
            })
            .catch(error => {
                // Hata durumunda
                checkBtn.disabled = false;
                checkBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Güncellemeleri Kontrol Et';
                
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> Bir hata oluştu: ${error.message}
                    </div>
                `;
            });
    });
});
</script>

<?php include 'includes/footer.php'; // Kendi footer dosyanızı ekleyin ?>