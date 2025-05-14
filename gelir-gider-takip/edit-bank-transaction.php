<?php
require_once 'includes/functions.php';
require_once 'includes/bank-functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// İşlem ID'sini al
$transactionId = $_GET['id'] ?? 0;

// İşlem bilgilerini getir
$transaction = getBankTransactionById($transactionId);

// İşlem bulunamadı veya kullanıcının değilse
if (!$transaction || !isUserBankAccount($transaction['account_id'], $userId)) {
    header('Location: bank-accounts.php');
    exit;
}

// Hesap bilgilerini getir
$account = getBankAccountById($transaction['account_id'], $userId);

// Para birimi bilgisini al
$currency = $account['currency'];
$currencySymbol = getCurrencySymbol($currency);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'transaction_type' => $_POST['transaction_type'] ?? '',
        'amount' => floatval($_POST['amount'] ?? 0),
        'description' => trim($_POST['description'] ?? ''),
        'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d H:i:s'),
        'reference_number' => trim($_POST['reference_number'] ?? '')
    ];
    
    // Validasyon
    if (!$data['transaction_type'] || $data['amount'] <= 0) {
        $error = 'Lütfen tüm zorunlu alanları doldurun.';
    } else {
        // İşlem türüne göre hesap bakiyesini güncelle
        $oldAmount = $transaction['amount'];
        $oldType = $transaction['transaction_type'];
        
        // Eski işlemin etkisini geri al
        $adjustedBalance = $account['current_balance'];
        if (in_array($oldType, ['deposit', 'transfer_in', 'interest'])) {
            $adjustedBalance -= $oldAmount;
        } else {
            $adjustedBalance += $oldAmount;
        }
        
        // Yeni işlemin etkisini uygula
        if (in_array($data['transaction_type'], ['deposit', 'transfer_in', 'interest'])) {
            $adjustedBalance += $data['amount'];
        } else {
            $adjustedBalance -= $data['amount'];
        }
        
        // İşlem ve hesap bakiyesini güncelle
        if (updateBankTransaction($transactionId, $data, $adjustedBalance)) {
            // Hesap bakiyesini güncelle
            updateAccountBalance($transaction['account_id'], $adjustedBalance);
            
            $success = 'İşlem başarıyla güncellendi.';
            
            // Form tekrar submit edilmesin diye
            if (!$error) {
                header('Location: bank-transactions.php?id=' . $transaction['account_id']);
                exit;
            }
        } else {
            $error = 'İşlem güncellenirken bir hata oluştu.';
        }
    }
}

$pageTitle = 'Banka İşlemini Düzenle';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-pencil"></i> Banka İşlemini Düzenle
                </h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="account_id" class="form-label">Banka Hesabı</label>
                            <input type="text" class="form-control" value="<?= e($account['bank_name'] . ' - ' . $account['account_name']) ?> (<?= e($currency) ?>)" readonly>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="transaction_type" class="form-label">İşlem Tipi <span class="text-danger">*</span></label>
                            <select class="form-select" id="transaction_type" name="transaction_type" required>
                                <option value="">Seçin</option>
                                <option value="deposit" <?= $transaction['transaction_type'] == 'deposit' ? 'selected' : '' ?>>Para Yatırma</option>
                                <option value="withdrawal" <?= $transaction['transaction_type'] == 'withdrawal' ? 'selected' : '' ?>>Para Çekme</option>
                                <option value="fee" <?= $transaction['transaction_type'] == 'fee' ? 'selected' : '' ?>>Banka Ücreti</option>
                                <option value="interest" <?= $transaction['transaction_type'] == 'interest' ? 'selected' : '' ?>>Faiz</option>
                                <option value="transfer_in" <?= $transaction['transaction_type'] == 'transfer_in' ? 'selected' : '' ?>>Gelen Transfer</option>
                                <option value="transfer_out" <?= $transaction['transaction_type'] == 'transfer_out' ? 'selected' : '' ?>>Giden Transfer</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label for="amount" class="form-label">Tutar <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><?= $currencySymbol ?></span>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       step="0.01" min="0.01" required value="<?= e($transaction['amount']) ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="transaction_date" class="form-label">İşlem Tarihi</label>
                            <input type="datetime-local" class="form-control" id="transaction_date" name="transaction_date" 
                                   value="<?= date('Y-m-d\TH:i', strtotime($transaction['transaction_date'])) ?>">
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label for="reference_number" class="form-label">Referans Numarası</label>
                            <input type="text" class="form-control" id="reference_number" name="reference_number" 
                                   value="<?= e($transaction['reference_number']) ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="description" class="form-label">Açıklama</label>
                            <input type="text" class="form-control" id="description" name="description" 
                                   value="<?= e($transaction['description']) ?>">
                        </div>
                    </div>
                    
                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <a href="bank-transactions.php?id=<?= $transaction['account_id'] ?>" class="btn btn-secondary">İptal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Değişiklikleri Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Uyarı -->
        <div class="card mt-3">
            <div class="card-body">
                <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle"></i> <strong>Dikkat:</strong> İşlemi düzenlediğinizde hesap bakiyesi otomatik olarak güncellenecektir.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// İşlem tipine göre alan görünürlüğünü ayarla
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('transaction_type');
    const amountInput = document.getElementById('amount');
    
    typeSelect.addEventListener('change', function() {
        const type = this.value;
        // Transfer işlemlerinde bazı alanlar kısıtlanabilir
        if (type === 'transfer_in' || type === 'transfer_out') {
            // Örneğin, transfer işlemlerinde açıklama zorunlu olabilir
            document.getElementById('description').required = true;
        } else {
            document.getElementById('description').required = false;
        }
    });
    
    // Sayfa yüklendiğinde de kontrol et
    typeSelect.dispatchEvent(new Event('change'));
});
</script>

<?php include 'includes/footer.php'; ?>