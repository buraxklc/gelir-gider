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

$pageTitle = 'Profil';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Profil Bilgileri</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Kullanıcı Adı</label>
                    <input type="text" class="form-control" value="<?= e($user['username']) ?>" readonly>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">E-posta</label>
                    <input type="email" class="form-control" value="<?= e($user['email']) ?>" readonly>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Kayıt Tarihi</label>
                    <input type="text" class="form-control" value="<?= date('d.m.Y H:i', strtotime($user['created_at'])) ?>" readonly>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Şifre Değiştir</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mevcut Şifre</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Yeni Şifre</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Yeni Şifre (Tekrar)</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Şifreyi Değiştir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>