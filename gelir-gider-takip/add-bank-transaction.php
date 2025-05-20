<?php
require_once 'includes/functions.php';
require_once 'includes/bank-functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Hesap ID'sini al
$accountId = $_GET['account'] ?? $_POST['account_id'] ?? 0;

// Kullanıcının banka hesaplarını getir
$accounts = getBankAccounts($userId);

// Eğer hesap ID varsa, doğrula
$selectedAccount = null; // Değişkeni başlat
if ($accountId) {
    $selectedAccount = getBankAccountById($accountId, $userId);
    if (!$selectedAccount) {
        $accountId = 0;
        $selectedAccount = null; // Hesap bulunamazsa null yap
    }
}

// Hesabın para birimini belirle
$currency = ($selectedAccount ? $selectedAccount['currency'] : 'TRY');
$currencySymbol = getCurrencySymbol($currency);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'transaction_type' => $_POST['transaction_type'] ?? '',
        'amount' => floatval($_POST['amount'] ?? 0),
        'description' => trim($_POST['description'] ?? ''),
        'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d H:i:s'),
        'reference_number' => trim($_POST['reference_number'] ?? '')
    ];
    
    $accountId = $_POST['account_id'] ?? 0;
    
    // Validasyon
    if (!$accountId || !$data['transaction_type'] || $data['amount'] <= 0) {
        $error = 'Lütfen tüm zorunlu alanları doldurun.';
    } else {
        // İşlemi ekle
        if (addBankTransaction($accountId, $data)) {
            $_SESSION['success_message'] = 'Banka işlemi başarıyla eklendi.';
            header('Location: bank-transactions.php?id=' . $accountId);
            exit;
        } else {
            $error = 'İşlem eklenirken bir hata oluştu.';
        }
    }
}

$pageTitle = 'Banka İşlemi Ekle';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-plus-circle"></i> Banka İşlemi Ekle
                </h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="account_id" class="form-label">Banka Hesabı <span class="text-danger">*</span></label>
                            <select class="form-select" id="account_id" name="account_id" required>
                                <option value="">Hesap seçin</option>
                                <?php foreach ($accounts as $account): ?>
                                <option value="<?= $account['id'] ?>" 
                                        data-currency="<?= e($account['currency']) ?>"
                                        <?= $accountId == $account['id'] ? 'selected' : '' ?>>
                                    <?= e($account['bank_name']) ?> - <?= e($account['account_name']) ?>
                                    (<?= formatMoney($account['current_balance'], $account['currency']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="transaction_type" class="form-label">İşlem Tipi <span class="text-danger">*</span></label>
                            <select class="form-select" id="transaction_type" name="transaction_type" required>
                                <option value="">Seçin</option>
                                <option value="deposit">Para Yatırma</option>
                                <option value="withdrawal">Para Çekme</option>
                                <option value="fee">Banka Ücreti</option>
                                <option value="interest">Faiz</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label for="amount" class="form-label">Tutar <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" id="currency-symbol"><?= $currencySymbol ?></span>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       step="0.01" min="0.01" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="transaction_date" class="form-label">İşlem Tarihi</label>
                            <input type="datetime-local" class="form-control" id="transaction_date" name="transaction_date" 
                                   value="<?= date('Y-m-d\TH:i') ?>">
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label for="reference_number" class="form-label">Referans Numarası</label>
                            <input type="text" class="form-control" id="reference_number" name="reference_number" 
                                   placeholder="Opsiyonel">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="description" class="form-label">Açıklama</label>
                            <input type="text" class="form-control" id="description" name="description" 
                                   placeholder="İşlem açıklaması">
                        </div>
                    </div>
                    
                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <a href="bank-accounts.php" class="btn btn-secondary">İptal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> İşlemi Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Yardım -->
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title">İşlem Tipleri</h6>
                <ul class="small mb-0">
                    <li><strong>Para Yatırma:</strong> Hesaba para ekleme</li>
                    <li><strong>Para Çekme:</strong> Hesaptan para çıkarma</li>
                    <li><strong>Banka Ücreti:</strong> Banka işlem ücretleri, aidat vb.</li>
                    <li><strong>Faiz:</strong> Hesaba yansıyan faiz gelirleri</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Hesap değiştiğinde para birimi sembolünü güncelle
document.getElementById('account_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption.value) {
        const currency = selectedOption.getAttribute('data-currency');
        updateCurrencySymbol(currency);
    }
});

// Para birimi sembolünü güncelleme işlevi
function updateCurrencySymbol(currency) {
    const currencySymbolMap = {
        'TRY': '₺',
        'USD': '$',
        'EUR': '€',
        'GBP': '£'
    };
    
    const symbol = currencySymbolMap[currency] || currency;
    document.getElementById('currency-symbol').textContent = symbol;
}

// Sayfa yüklendiğinde başlangıç para birimini ayarla
document.addEventListener('DOMContentLoaded', function() {
    const accountSelect = document.getElementById('account_id');
    if (accountSelect.value) {
        const selectedOption = accountSelect.options[accountSelect.selectedIndex];
        const currency = selectedOption.getAttribute('data-currency');
        updateCurrencySymbol(currency);
    }
});
</script>

<?php include 'includes/footer.php'; ?>