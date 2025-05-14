<?php
require_once 'includes/functions.php';
require_once 'includes/bank-functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Hesap ID'sini al
$accountId = $_GET['id'] ?? 0;

// Hesap bilgilerini getir
$account = getBankAccountById($accountId, $userId);

// Hesap bulunamazsa veya kullanıcıya ait değilse
if (!$account) {
    header('Location: bank-accounts.php');
    exit;
}

// Bankaları getir
$banks = getBanks();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'bank_name' => trim($_POST['bank_name'] ?? ''),
        'account_name' => trim($_POST['account_name'] ?? ''),
        'account_number' => trim($_POST['account_number'] ?? ''),
        'iban' => trim($_POST['iban'] ?? ''),
        'account_type' => $_POST['account_type'] ?? 'checking',
        'currency' => $_POST['currency'] ?? 'TRY',
        'color' => $_POST['color'] ?? '#4F46E5',
        'icon' => $_POST['icon'] ?? 'bi-bank'
    ];
    
    // Validasyon
    if (empty($data['bank_name']) || empty($data['account_name'])) {
        $error = 'Lütfen zorunlu alanları doldurun.';
    } else {
        // Hesabı güncelle
        if (updateBankAccount($accountId, $userId, $data)) {
            $_SESSION['success_message'] = 'Banka hesabı başarıyla güncellendi.';
            header('Location: bank-accounts.php');
            exit;
        } else {
            $error = 'Hesap güncellenirken bir hata oluştu.';
        }
    }
}

$pageTitle = 'Banka Hesabını Düzenle';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-pencil"></i> Banka Hesabını Düzenle
                </h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="bank_name" class="form-label">Banka <span class="text-danger">*</span></label>
                            <select class="form-select" id="bank_name" name="bank_name" required>
                                <option value="">Banka seçin</option>
                                <?php foreach ($banks as $bank): ?>
                                <option value="<?= e($bank['short_name']) ?>" 
                                        data-color="<?= e($bank['color']) ?>"
                                        <?= $bank['short_name'] == $account['bank_name'] ? 'selected' : '' ?>>
                                    <?= e($bank['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="account_name" class="form-label">Hesap Adı <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="account_name" name="account_name" 
                                   value="<?= e($account['account_name']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label for="account_number" class="form-label">Hesap Numarası</label>
                            <input type="text" class="form-control" id="account_number" name="account_number" 
                                   value="<?= e($account['account_number']) ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="iban" class="form-label">IBAN</label>
                            <input type="text" class="form-control" id="iban" name="iban" 
                                   value="<?= e($account['iban']) ?>" maxlength="34">
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label for="account_type" class="form-label">Hesap Türü</label>
                            <select class="form-select" id="account_type" name="account_type">
                                <option value="checking" <?= $account['account_type'] == 'checking' ? 'selected' : '' ?>>Vadesiz Hesap</option>
                                <option value="savings" <?= $account['account_type'] == 'savings' ? 'selected' : '' ?>>Vadeli Hesap</option>
                                <option value="investment" <?= $account['account_type'] == 'investment' ? 'selected' : '' ?>>Yatırım Hesabı</option>
                                <option value="credit" <?= $account['account_type'] == 'credit' ? 'selected' : '' ?>>Kredi Hesabı</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="currency" class="form-label">Para Birimi</label>
                            <select class="form-select" id="currency" name="currency">
                                <option value="TRY" <?= $account['currency'] == 'TRY' ? 'selected' : '' ?>>TRY - Türk Lirası</option>
                                <option value="USD" <?= $account['currency'] == 'USD' ? 'selected' : '' ?>>USD - Amerikan Doları</option>
                                <option value="EUR" <?= $account['currency'] == 'EUR' ? 'selected' : '' ?>>EUR - Euro</option>
                                <option value="GBP" <?= $account['currency'] == 'GBP' ? 'selected' : '' ?>>GBP - İngiliz Sterlini</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Mevcut Bakiye</label>
                            <div class="form-control bg-light">
                                <?= formatMoney($account['current_balance']) ?>
                            </div>
                            <small class="text-muted">Bakiye, hesap hareketleri ile güncellenir</small>
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label for="icon" class="form-label">İkon</label>
                            <select class="form-select" id="icon" name="icon">
                                <option value="bi-bank" <?= $account['icon'] == 'bi-bank' ? 'selected' : '' ?>>🏦 Banka</option>
                                <option value="bi-credit-card" <?= $account['icon'] == 'bi-credit-card' ? 'selected' : '' ?>>💳 Kredi Kartı</option>
                                <option value="bi-piggy-bank" <?= $account['icon'] == 'bi-piggy-bank' ? 'selected' : '' ?>>🐷 Kumbara</option>
                                <option value="bi-wallet2" <?= $account['icon'] == 'bi-wallet2' ? 'selected' : '' ?>>👛 Cüzdan</option>
                                <option value="bi-cash-stack" <?= $account['icon'] == 'bi-cash-stack' ? 'selected' : '' ?>>💵 Nakit</option>
                                <option value="bi-currency-dollar" <?= $account['icon'] == 'bi-currency-dollar' ? 'selected' : '' ?>>💲 Dolar</option>
                                <option value="bi-currency-euro" <?= $account['icon'] == 'bi-currency-euro' ? 'selected' : '' ?>>💶 Euro</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="color" class="form-label">Renk</label>
                            <input type="color" class="form-control" id="color" name="color" 
                                   value="<?= e($account['color']) ?>">
                        </div>
                    </div>
                    
                    <div class="mt-4 d-flex justify-content-between">
                        <div>
                            <a href="bank-accounts.php" class="btn btn-secondary">İptal</a>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Değişiklikleri Kaydet
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Hesap Bilgileri -->
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title">Hesap Bilgileri</h6>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Oluşturulma Tarihi:</strong><br>
                        <?= date('d.m.Y H:i', strtotime($account['created_at'])) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Son Güncelleme:</strong><br>
                        <?= date('d.m.Y H:i', strtotime($account['updated_at'])) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Banka seçildiğinde rengi otomatik güncelle
document.getElementById('bank_name').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const color = selectedOption.getAttribute('data-color');
    if (color) {
        document.getElementById('color').value = color;
    }
});
</script>

<?php include 'includes/footer.php'; ?>