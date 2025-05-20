<?php
require_once 'includes/functions.php';
require_once 'includes/bank-functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Kullanıcının banka hesaplarını getir
$accounts = getBankAccounts($userId);

// En az 2 hesap olmalı
if (count($accounts) < 2) {
    header('Location: bank-accounts.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fromAccountId = $_POST['from_account_id'] ?? 0;
    $toAccountId = $_POST['to_account_id'] ?? 0;
    $amount = floatval($_POST['amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    
    // Validasyon
    if (!$fromAccountId || !$toAccountId || $amount <= 0) {
        $error = 'Lütfen tüm zorunlu alanları doldurun.';
    } elseif ($fromAccountId == $toAccountId) {
        $error = 'Kaynak ve hedef hesap aynı olamaz.';
    } else {
        // Kaynak hesap bakiyesini kontrol et
        $fromAccount = getBankAccountById($fromAccountId, $userId);
        if ($fromAccount['current_balance'] < $amount) {
            $error = 'Yetersiz bakiye.';
        } else {
            // Transfer işlemini gerçekleştir
            if (bankTransfer($fromAccountId, $toAccountId, $amount, $description)) {
                $_SESSION['success_message'] = 'Transfer işlemi başarıyla gerçekleştirildi.';
                header('Location: bank-accounts.php');
                exit;
            } else {
                $error = 'Transfer işlemi sırasında bir hata oluştu.';
            }
        }
    }
}

$pageTitle = 'Hesaplar Arası Transfer';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-arrow-left-right"></i> Hesaplar Arası Transfer
                </h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="from_account_id" class="form-label">Kaynak Hesap <span class="text-danger">*</span></label>
                            <select class="form-select" id="from_account_id" name="from_account_id" required>
                                <option value="">Seçin</option>
                                <?php foreach ($accounts as $account): ?>
                                <option value="<?= $account['id'] ?>">
                                    <?= e($account['bank_name']) ?> - <?= e($account['account_name']) ?>
                                    (<?= formatMoney($account['current_balance']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="to_account_id" class="form-label">Hedef Hesap <span class="text-danger">*</span></label>
                            <select class="form-select" id="to_account_id" name="to_account_id" required>
                                <option value="">Seçin</option>
                                <?php foreach ($accounts as $account): ?>
                                <option value="<?= $account['id'] ?>">
                                    <?= e($account['bank_name']) ?> - <?= e($account['account_name']) ?>
                                    (<?= formatMoney($account['current_balance']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label for="amount" class="form-label">Transfer Tutarı <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₺</span>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       step="0.01" min="0.01" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="description" class="form-label">Açıklama</label>
                            <input type="text" class="form-control" id="description" name="description" 
                                   placeholder="Transfer açıklaması">
                        </div>
                    </div>
                    
                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <a href="bank-accounts.php" class="btn btn-secondary">İptal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Transfer Yap
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Transfer Özeti -->
        <div class="card mt-3" id="transferSummary" style="display: none;">
            <div class="card-body">
                <h6 class="card-title">Transfer Özeti</h6>
                <div id="summaryContent"></div>
            </div>
        </div>
    </div>
</div>

<script>
// Transfer önizleme
document.addEventListener('DOMContentLoaded', function() {
    const fromSelect = document.getElementById('from_account_id');
    const toSelect = document.getElementById('to_account_id');
    const amountInput = document.getElementById('amount');
    const summaryDiv = document.getElementById('transferSummary');
    const summaryContent = document.getElementById('summaryContent');
    
    function updateSummary() {
        const fromOption = fromSelect.options[fromSelect.selectedIndex];
        const toOption = toSelect.options[toSelect.selectedIndex];
        const amount = parseFloat(amountInput.value) || 0;
        
        if (fromSelect.value && toSelect.value && amount > 0) {
            summaryDiv.style.display = 'block';
            summaryContent.innerHTML = `
                <p><strong>Kaynak:</strong> ${fromOption.text}</p>
                <p><strong>Hedef:</strong> ${toOption.text}</p>
                <p><strong>Tutar:</strong> ₺${amount.toFixed(2)}</p>
            `;
        } else {
            summaryDiv.style.display = 'none';
        }
    }
    
    fromSelect.addEventListener('change', updateSummary);
    toSelect.addEventListener('change', updateSummary);
    amountInput.addEventListener('input', updateSummary);
});
</script>

<?php include 'includes/footer.php'; ?>