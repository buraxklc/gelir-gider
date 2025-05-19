<?php
require_once 'includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$user = getUserById($userId);
$error = '';
$success = '';

// Şifre değiştirme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
   $currentPassword = $_POST['current_password'] ?? '';
   $newPassword = $_POST['new_password'] ?? '';
   $confirmPassword = $_POST['confirm_password'] ?? '';
   
   if (!password_verify($currentPassword, $user['password'])) {
       $error = 'Mevcut şifre hatalı!';
   } elseif ($newPassword !== $confirmPassword) {
       $error = 'Yeni şifreler eşleşmiyor!';
   } elseif (strlen($newPassword) < 6) {
       $error = 'Yeni şifre en az 6 karakter olmalıdır!';
   } else {
       $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
       $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
       
       if ($stmt->execute([$hashedPassword, $userId])) {
           $success = 'Şifre başarıyla değiştirildi!';
       } else {
           $error = 'Şifre değiştirirken bir hata oluştu!';
       }
   }
}

// Profil bilgisi güncelleme 
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
   $email = trim($_POST['email'] ?? '');
   
   // E-posta kontrolü (başka kullanıcı kullanıyor mu)
   if ($email != $user['email']) {
       $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
       $stmt->execute([$email, $userId]);
       if ($stmt->fetch()) {
           $error = 'Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor!';
       }
   }
   
   if (!$error) {
       $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
       if ($stmt->execute([$email, $userId])) {
           $success = 'Profil bilgileriniz başarıyla güncellendi!';
           // Güncel kullanıcı bilgilerini al
           $user = getUserById($userId);
       } else {
           $error = 'Profil güncellenirken bir hata oluştu!';
       }
   }
}

// Tema değiştirme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_theme'])) {
   $darkMode = isset($_POST['dark_mode']) ? 1 : 0;
   
   if (updateUserTheme($userId, $darkMode)) {
       $success = 'Tema tercihiniz başarıyla güncellendi!';
       // Kullanıcı bilgilerini yenile
       $user = getUserById($userId);
   } else {
       $error = 'Tema güncellenirken bir hata oluştu!';
   }
}

$pageTitle = 'Profil';
include 'includes/header.php';
?>

<div class="container py-5">
   <div class="row justify-content-center">
       <div class="col-lg-10">
           <!-- Profil Kartı -->
           <div class="card shadow-sm border-0 overflow-hidden mb-4">
               <div class="profile-header bg-primary text-white p-4">
                   <div class="d-flex align-items-center position-relative">
                       <div class="profile-avatar bg-white text-primary d-flex align-items-center justify-content-center rounded-circle">
                           <i class="bi bi-person-fill fs-1"></i>
                       </div>
                       <div class="ms-3">
                           <h4 class="mb-1 fw-bold"><?= e($user['username']) ?></h4>
                           <p class="mb-0 opacity-75">
                               <i class="bi bi-envelope me-2"></i><?= e($user['email']) ?>
                           </p>
                       </div>
                   </div>
               </div>
               <div class="card-body p-4">
                   <div class="row">
                       <div class="col-md-6 mb-3">
                           <div class="card border-0 bg-light h-100">
                               <div class="card-body">
                                   <h6 class="text-muted mb-3">Hesap Bilgileri</h6>
                                   <p class="mb-1"><strong>Kullanıcı Adı:</strong> <?= e($user['username']) ?></p>
                                   <p class="mb-1"><strong>E-posta:</strong> <?= e($user['email']) ?></p>
                                   <p class="mb-0"><strong>Kayıt Tarihi:</strong> <?= date('d.m.Y', strtotime($user['created_at'])) ?></p>
                               </div>
                           </div>
                       </div>
                       <div class="col-md-6 mb-3">
                           <div class="card border-0 bg-light h-100">
                               <div class="card-body">
                                   <h6 class="text-muted mb-3">İstatistikler</h6>
                                   <p class="mb-1"><strong>Toplam İşlem:</strong> <?= getTotalTransactionCount($userId) ?></p>
                                   <p class="mb-1"><strong>Gelir İşlemleri:</strong> <?= getTotalTransactionCount($userId, 'income') ?></p>
                                   <p class="mb-0"><strong>Gider İşlemleri:</strong> <?= getTotalTransactionCount($userId, 'expense') ?></p>
                               </div>
                           </div>
                       </div>
                   </div>
               </div>
           </div>

           <!-- Tabs ve İçerik -->
           <div class="card shadow-sm border-0">
               <div class="card-header bg-white p-0 border-0">
                   <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                       <li class="nav-item" role="presentation">
                           <button class="nav-link active px-4 py-3" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-pane" type="button" role="tab">
                               <i class="bi bi-person me-2"></i>Profil Bilgileri
                           </button>
                       </li>
                       <li class="nav-item" role="presentation">
                           <button class="nav-link px-4 py-3" id="password-tab" data-bs-toggle="tab" data-bs-target="#password-pane" type="button" role="tab">
                               <i class="bi bi-shield-lock me-2"></i>Şifre Değiştir
                           </button>
                       </li>
                       <li class="nav-item" role="presentation">
                           <button class="nav-link px-4 py-3" id="theme-tab" data-bs-toggle="tab" data-bs-target="#theme-pane" type="button" role="tab">
                               <i class="bi bi-palette me-2"></i>Tema Ayarları
                           </button>
                       </li>
                   </ul>
               </div>
               <div class="card-body p-4">
                   <?php if ($error): ?>
                   <div class="alert alert-danger alert-dismissible fade show">
                       <i class="bi bi-exclamation-circle me-2"></i><?= e($error) ?>
                       <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                   </div>
                   <?php endif; ?>
                   
                   <?php if ($success): ?>
                   <div class="alert alert-success alert-dismissible fade show">
                       <i class="bi bi-check-circle me-2"></i><?= e($success) ?>
                       <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                   </div>
                   <?php endif; ?>

                   <div class="tab-content" id="profileTabsContent">
                       <!-- Profil Bilgileri Tab -->
                       <div class="tab-pane fade show active" id="profile-pane" role="tabpanel" tabindex="0">
                           <form method="POST" class="needs-validation" novalidate>
                               <input type="hidden" name="update_profile" value="1">
                               
                               <div class="mb-4">
                                   <label for="username" class="form-label">Kullanıcı Adı</label>
                                   <input type="text" class="form-control bg-light" id="username" value="<?= e($user['username']) ?>" readonly>
                                   <div class="form-text">Kullanıcı adınız değiştirilemez.</div>
                               </div>
                               
                               <div class="mb-4">
                                   <label for="email" class="form-label">E-posta Adresi</label>
                                   <input type="email" class="form-control" id="email" name="email" value="<?= e($user['email']) ?>" required>
                                   <div class="invalid-feedback">Lütfen geçerli bir e-posta adresi girin.</div>
                               </div>
                               
                               <button type="submit" class="btn btn-primary px-4">
                                   <i class="bi bi-save me-2"></i>Değişiklikleri Kaydet
                               </button>
                           </form>
                       </div>

                       <!-- Şifre Değiştir Tab -->
                       <div class="tab-pane fade" id="password-pane" role="tabpanel" tabindex="0">
                           <form method="POST" class="needs-validation" novalidate>
                               <input type="hidden" name="change_password" value="1">
                               
                               <div class="mb-4">
                                   <label for="current_password" class="form-label">Mevcut Şifre</label>
                                   <div class="input-group">
                                       <input type="password" class="form-control" id="current_password" name="current_password" required>
                                       <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                                           <i class="bi bi-eye"></i>
                                       </button>
                                   </div>
                                   <div class="invalid-feedback">Lütfen mevcut şifrenizi girin.</div>
                               </div>
                               
                               <div class="mb-4">
                                   <label for="new_password" class="form-label">Yeni Şifre</label>
                                   <div class="input-group">
                                       <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required>
                                       <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                           <i class="bi bi-eye"></i>
                                       </button>
                                   </div>
                                   <div class="invalid-feedback">Yeni şifre en az 6 karakter olmalıdır.</div>
                               </div>
                               
                               <div class="mb-4">
                                   <label for="confirm_password" class="form-label">Yeni Şifre (Tekrar)</label>
                                   <div class="input-group">
                                       <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                       <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                           <i class="bi bi-eye"></i>
                                       </button>
                                   </div>
                                   <div class="invalid-feedback">Şifreler eşleşmelidir.</div>
                               </div>
                               
                               <button type="submit" class="btn btn-primary px-4">
                                   <i class="bi bi-shield-check me-2"></i>Şifreyi Değiştir
                               </button>
                           </form>
                       </div>

                       <!-- Tema Ayarları Tab -->
                       <div class="tab-pane fade" id="theme-pane" role="tabpanel" tabindex="0">
                           <form method="POST" class="needs-validation" novalidate>
                               <input type="hidden" name="change_theme" value="1">
                               
                               <div class="mb-4">
                                   <h6 class="mb-3">Görünüm Tercihi</h6>
                                   <div class="form-check form-switch">
                                       <input class="form-check-input" type="checkbox" id="dark_mode" name="dark_mode" 
                                              <?= getUserTheme($userId) ? 'checked' : '' ?>>
                                       <label class="form-check-label" for="dark_mode">
                                           <i class="bi bi-moon-stars me-2"></i>Karanlık Mod
                                       </label>
                                       <div class="form-text">Karanlık modu etkinleştirerek gözlerinizi koruyun ve daha rahat bir deneyim yaşayın.</div>
                                   </div>
                               </div>
                               
                               <div class="theme-preview mb-4">
                                   <h6 class="mb-3">Önizleme</h6>
                                   <div class="row">
                                       <div class="col-md-6 mb-3">
                                           <div class="preview-card light-preview">
                                               <div class="preview-header">
                                                   <i class="bi bi-sun me-2"></i>Açık Tema
                                               </div>
                                               <div class="preview-body">
                                                   <div class="preview-text">Örnek metin içeriği</div>
                                                   <div class="preview-button">Örnek Buton</div>
                                                   <div class="preview-card-small">
                                                       <div class="preview-card-content">Kart Örneği</div>
                                                   </div>
                                               </div>
                                           </div>
                                       </div>
                                       <div class="col-md-6 mb-3">
                                           <div class="preview-card dark-preview">
                                               <div class="preview-header">
                                                   <i class="bi bi-moon me-2"></i>Karanlık Tema
                                               </div>
                                               <div class="preview-body">
                                                   <div class="preview-text">Örnek metin içeriği</div>
                                                   <div class="preview-button">Örnek Buton</div>
                                                   <div class="preview-card-small">
                                                       <div class="preview-card-content">Kart Örneği</div>
                                                   </div>
                                               </div>
                                           </div>
                                       </div>
                                   </div>
                               </div>
                               
                               <div class="theme-benefits mb-4">
                                   <h6 class="mb-3">Karanlık Mod Avantajları</h6>
                                   <div class="row">
                                       <div class="col-md-6">
                                           <ul class="list-unstyled">
                                               <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Göz yorgunluğunu azaltır</li>
                                               <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Gece kullanımı için uygundur</li>
                                           </ul>
                                       </div>
                                       <div class="col-md-6">
                                           <ul class="list-unstyled">
                                               <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Modern görünüm sağlar</li>
                                               <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Batarya tasarrufu yapar</li>
                                           </ul>
                                       </div>
                                   </div>
                               </div>
                               
                               <button type="submit" class="btn btn-primary px-4">
                                   <i class="bi bi-palette me-2"></i>Tema Ayarlarını Kaydet
                               </button>
                           </form>
                       </div>
                   </div>
               </div>
           </div>
           
           <!-- Hesap Çıkış kartı -->
           <div class="card mt-4 border-danger shadow-sm">
               <div class="card-body p-4">
                   <div class="d-flex justify-content-between align-items-center">
                       <div>
                           <h5 class="text-danger mb-1">Hesaptan Çıkış Yap</h5>
                           <p class="text-muted mb-0">Tüm cihazlardan çıkış yaparak oturumunuzu sonlandırın</p>
                       </div>
                       <a href="logout.php" class="btn btn-danger">
                           <i class="bi bi-box-arrow-right me-2"></i>Çıkış Yap
                       </a>
                   </div>
               </div>
           </div>
       </div>
   </div>
</div>

<style>
/* Profil Sayfası Özel Stilleri */
.profile-avatar {
   width: 80px;
   height: 80px;
}

.nav-tabs .nav-link {
   color: #6c757d;
   border: none;
   border-bottom: 3px solid transparent;
   border-radius: 0;
   font-weight: 500;
}

.nav-tabs .nav-link.active {
   color: #4F46E5;
   border-bottom-color: #4F46E5;
   background-color: transparent;
}

.nav-tabs .nav-link:hover:not(.active) {
   border-bottom-color: #dee2e6;
}

.card {
   border-radius: 12px;
}

.toggle-password {
   border-top-right-radius: 8px;
   border-bottom-right-radius: 8px;
}

.profile-header {
   position: relative;
   overflow: hidden;
   border-top-left-radius: 12px;
   border-top-right-radius: 12px;
}

.profile-header::before {
   content: '';
   position: absolute;
   top: 0;
   left: 0;
   right: 0;
   bottom: 0;
   background: linear-gradient(135deg, #4F46E5 0%, #2D3A8C 100%);
   z-index: -2;
}

.profile-header::after {
   content: '';
   position: absolute;
   top: 0;
   left: 0;
   right: 0;
   bottom: 0;
   background-image: 
       linear-gradient(transparent 20px, rgba(255, 255, 255, 0.05) 20px, rgba(255, 255, 255, 0.05) 21px, transparent 21px),
       linear-gradient(transparent 40px, rgba(255, 255, 255, 0.05) 40px, rgba(255, 255, 255, 0.05) 41px, transparent 41px),
       linear-gradient(transparent 60px, rgba(255, 255, 255, 0.05) 60px, rgba(255, 255, 255, 0.05) 61px, transparent 61px);
   transform: perspective(100px) rotateX(1deg) scale(1.2);
   z-index: -1;
}

.profile-avatar {
   background-color: #fff;
   border-radius: 50%;
   display: flex;
   align-items: center;
   justify-content: center;
   color: #4F46E5;
   font-size: 40px;
   box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
   transition: all 0.3s ease;
}

.profile-avatar:hover {
   transform: scale(1.05);
   box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
}

/* Tema Önizleme Stilleri - GÜNCELLENMIŞ */
.preview-card {
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #e5e7eb;
    transition: all 0.3s ease;
    min-height: 200px;
    position: relative;
}

.preview-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* Açık tema önizlemesi - her durumda görünür */
.light-preview {
    background: #ffffff !important;
    color: #1f2937 !important;
    border-color: #e5e7eb !important;
}

.light-preview .preview-header {
    background: #f8f9fa !important;
    padding: 1rem;
    font-weight: 600;
    border-bottom: 1px solid #e5e7eb !important;
    display: flex;
    align-items: center;
    color: #1f2937 !important;
}

.light-preview .preview-body {
    padding: 1.5rem;
    background: #ffffff !important;
    color: #1f2937 !important;
}

.light-preview .preview-text {
    color: #6b7280 !important;
    margin-bottom: 1rem;
    font-size: 14px;
}

.light-preview .preview-button {
    background: #4f46e5 !important;
    color: white !important;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    display: inline-block;
    margin-bottom: 1rem;
    font-size: 14px;
    font-weight: 500;
}

.light-preview .preview-card-small {
    background: #f1f5f9 !important;
    border-radius: 8px;
    padding: 0.75rem;
    border: 1px solid #e2e8f0 !important;
}

.light-preview .preview-card-content {
    font-size: 13px;
    color: #475569 !important;
}

/* Karanlık tema önizlemesi */
.dark-preview {
    background: #1e1e1e !important;
    color: #e5e5e5 !important;
    border-color: #404040 !important;
}

.dark-preview .preview-header {
    background: #2d2d2d !important;
    padding: 1rem;
    font-weight: 600;
    border-bottom: 1px solid #404040 !important;
    display: flex;
    align-items: center;
    color: #e5e5e5 !important;
}

.dark-preview .preview-body {
    padding: 1.5rem;
    background: #1e1e1e !important;
    color: #e5e5e5 !important;
}

.dark-preview .preview-text {
    color: #a0a0a0 !important;
    margin-bottom: 1rem;
    font-size: 14px;
}

.dark-preview .preview-button {
    background: #4f46e5 !important;
    color: white !important;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    display: inline-block;
    margin-bottom: 1rem;
    font-size: 14px;
    font-weight: 500;
}

.dark-preview .preview-card-small {
    background: #3a3a3a !important;
    border-radius: 8px;
    padding: 0.75rem;
    border: 1px solid #404040 !important;
}

.dark-preview .preview-card-content {
    font-size: 13px;
    color: #d1d5db !important;
}
</style>

<script>
// Form Validasyon
document.addEventListener('DOMContentLoaded', function() {
   // Şifre alanları eşleşme kontrolü
   const newPassword = document.getElementById('new_password');
   const confirmPassword = document.getElementById('confirm_password');
   
   if (newPassword && confirmPassword) {
       confirmPassword.addEventListener('input', function() {
           if (this.value !== newPassword.value) {
               this.setCustomValidity('Şifreler eşleşmiyor');
           } else {
               this.setCustomValidity('');
           }
       });
   }
   
   // Şifre gösterme/gizleme
   const toggleButtons = document.querySelectorAll('.toggle-password');
   toggleButtons.forEach(button => {
       button.addEventListener('click', function() {
           const targetId = this.getAttribute('data-target');
           const passwordInput = document.getElementById(targetId);
           
           if (passwordInput.type === 'password') {
               passwordInput.type = 'text';
               this.innerHTML = '<i class="bi bi-eye-slash"></i>';
           } else {
               passwordInput.type = 'password';
               this.innerHTML = '<i class="bi bi-eye"></i>';
           }
       });
   });
   
   // Bootstrap Form Validation
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
   
   // Tema değişikliğini anında önizleme
   const themeToggle = document.getElementById('dark_mode');
   if (themeToggle) {
       themeToggle.addEventListener('change', function() {
           // Sayfayı yeniden yüklemeden tema önizlemesi
           document.documentElement.setAttribute('data-theme', this.checked ? 'dark' : 'light');
       });
   }
   
   // Tab hatırlama - URL hash'i veya localStorage kullanarak
   const hash = window.location.hash;
   if (hash) {
       const tab = document.querySelector(`[data-bs-target="${hash}"]`);
       if (tab) {
           const tabTrigger = new bootstrap.Tab(tab);
           tabTrigger.show();
       }
   }
   
   // Tab değişince URL hash'i güncelleme
   const tabs = document.querySelectorAll('[data-bs-toggle="tab"]');
   tabs.forEach(tab => {
       tab.addEventListener('shown.bs.tab', function(event) {
           const targetId = event.target.getAttribute('data-bs-target');
           window.location.hash = targetId;
       });
   });
});
</script>

<?php include 'includes/footer.php'; ?>