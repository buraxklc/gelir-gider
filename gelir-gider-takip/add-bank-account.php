<?php
require_once 'includes/functions.php';
require_once 'includes/bank-functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

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
        'initial_balance' => floatval($_POST['initial_balance'] ?? 0),
        'color' => $_POST['color'] ?? '#4F46E5',
        'icon' => $_POST['icon'] ?? 'bi-bank'
    ];
    
    // Validasyon
    if (empty($data['bank_name']) || empty($data['account_name'])) {
        $error = 'Lütfen zorunlu alanları doldurun.';
    } else {
        // Hesabı ekle
        $accountId = addBankAccount($userId, $data);
        
        if ($accountId) {
            // Başlangıç bakiyesi varsa ilk işlem olarak ekle
            if ($data['initial_balance'] != 0) {
                addBankTransaction($accountId, [
                    'transaction_type' => 'deposit',
                    'amount' => abs($data['initial_balance']),
                    'description' => 'Başlangıç bakiyesi',
                    'transaction_date' => date('Y-m-d H:i:s')
                ]);
            }
            
            $_SESSION['success_message'] = 'Banka hesabı başarıyla eklendi.';
            header('Location: bank-accounts.php');
            exit;
        } else {
            $error = 'Hesap eklenirken bir hata oluştu.';
        }
    }
}

$pageTitle = 'Yeni Banka Hesabı';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-bank"></i> Yeni Banka Hesabı Ekle
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
                                <option value="<?= e($bank['short_name']) ?>" data-color="<?= e($bank['color']) ?>">
                                    <?= e($bank['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="account_name" class="form-label">Hesap Adı <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="account_name" name="account_name" 
                                   placeholder="örn: Maaş Hesabı" required>
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label for="account_number" class="form-label">Hesap Numarası</label>
                            <input type="text" class="form-control" id="account_number" name="account_number" 
                                   placeholder="Opsiyonel">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="iban" class="form-label">IBAN</label>
                            <input type="text" class="form-control" id="iban" name="iban" 
                                   placeholder="TR00 0000 0000 0000 0000 0000 00" maxlength="34">
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label for="account_type" class="form-label">Hesap Türü</label>
                            <select class="form-select" id="account_type" name="account_type">
                                <option value="checking">Vadesiz Hesap</option>
                                <option value="savings">Vadeli Hesap</option>
                                <option value="investment">Yatırım Hesabı</option>
                                <option value="credit">Kredi Hesabı</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="currency" class="form-label">Para Birimi</label>
                            <select class="form-select" id="currency" name="currency">
                                <option value="TRY">TRY - Türk Lirası</option>
                                <option value="USD">USD - Amerikan Doları</option>
                                <option value="EUR">EUR - Euro</option>
                                <option value="GBP">GBP - İngiliz Sterlini</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="initial_balance" class="form-label">Başlangıç Bakiyesi</label>
                            <div class="input-group">
                                <span class="input-group-text">₺</span>
                                <input type="number" class="form-control" id="initial_balance" name="initial_balance" 
                                       step="0.01" value="0.00">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label for="icon" class="form-label">İkon</label>
                            <select class="form-select" id="icon" name="icon">
                                <option value="bi-bank">🏦 Banka</option>
                                <option value="bi-credit-card">💳 Kredi Kartı</option>
                                <option value="bi-piggy-bank">🐷 Kumbara</option>
                                <option value="bi-wallet2">👛 Cüzdan</option>
                                <option value="bi-cash-stack">💵 Nakit</option>
                                <option value="bi-currency-dollar">💲 Dolar</option>
                                <option value="bi-currency-euro">💶 Euro</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="color" class="form-label">Renk</label>
                            <input type="color" class="form-control" id="color" name="color" value="#4F46E5">
                        </div>
                    </div>
                    
                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <a href="bank-accounts.php" class="btn btn-secondary">İptal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Hesabı Ekle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>