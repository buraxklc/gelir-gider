<?php
require_once 'includes/functions.php';
require_once 'includes/bank-functions.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Success mesajını kontrol et
$success = '';
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Banka hesaplarını getir
$bankAccounts = getBankAccounts($userId);

// Para birimlerine göre toplam varlıkları hesapla
$totalAssetsByCurrency = [];
$availableCurrencies = ['TRY', 'USD', 'EUR', 'GBP']; // Desteklenen para birimleri

foreach ($availableCurrencies as $currencyCode) {
    $totalAssetsByCurrency[$currencyCode] = 0;
}

// Her hesabın bakiyesini ilgili para birimi toplamına ekle
foreach ($bankAccounts as $account) {
    $currency = $account['currency'];
    // Eğer desteklenen bir para birimi ise toplamı güncelle
    if (isset($totalAssetsByCurrency[$currency])) {
        $totalAssetsByCurrency[$currency] += $account['current_balance'];
    }
}

// Boş para birimlerini kaldır (bakiyesi sıfır olan)
foreach ($totalAssetsByCurrency as $currency => $total) {
    if ($total <= 0) {
        unset($totalAssetsByCurrency[$currency]);
    }
}

// İlgili para birimlerinde toplam varlıklar var mı?
$hasCurrencyAssets = !empty($totalAssetsByCurrency);

// TCMB Döviz kurlarını al
$exchangeRates = getTCMBExchangeRates();

// TL cinsinden toplam varlık hesapla
$totalAssetsInTRY = 0;

foreach ($totalAssetsByCurrency as $currency => $total) {
    $totalAssetsInTRY += convertToTRY($total, $currency);
}

$pageTitle = 'Banka Hesapları';
include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Banka Hesapları</h2>
    <a href="add-bank-account.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Yeni Hesap Ekle
    </a>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= e($success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Döviz Kurları - Minimal ve Mobil Uyumlu -->
<?php if (!empty($exchangeRates)): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0 d-flex align-items-center">
            <i class="bi bi-currency-exchange me-2"></i> Güncel Kurlar
            <span class="badge bg-light text-dark ms-auto small">
                <?= date('d.m.Y', strtotime($exchangeRates['USD']['updated_at'])) ?>
            </span>
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="row g-0">
            <?php foreach (['USD', 'EUR', 'GBP'] as $code): 
                if (isset($exchangeRates[$code])):
                    $rate = $exchangeRates[$code];
                    $symbol = ['USD' => '$', 'EUR' => '€', 'GBP' => '£'][$code] ?? $code;
            ?>
            <div class="col-4 p-2 text-center border-end border-bottom">
                <div class="currency-box">
                    <div class="currency-symbol"><?= $symbol ?></div>
                    <div class="currency-code"><?= $code ?></div>
                    <div class="currency-rate"><?= number_format($rate['buying'], 2) ?> ₺</div>
                </div>
            </div>
            <?php endif; endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Toplam Varlık Özeti (TL Cinsinden) -->
<div class="card mb-4 bg-primary text-white">
    <div class="card-body">
        <h5 class="card-title">Toplam Varlık (TL Karşılığı)</h5>
        <h2 class="mb-0"><?= formatMoney($totalAssetsInTRY) ?> ₺</h2>
        <small>Tüm hesapların TL karşılığı</small>
    </div>
</div>

<!-- Para Birimlerine Göre Toplam Varlık Kartları -->
<?php if ($hasCurrencyAssets): ?>
<div class="row mb-4">
    <?php foreach ($totalAssetsByCurrency as $currency => $total): ?>
    <div class="col-md-<?= count($totalAssetsByCurrency) > 2 ? '4' : '6' ?> mb-3">
        <div class="card <?= $currency == 'TRY' ? 'bg-success' : 'bg-info' ?> text-white">
            <div class="card-body">
                <h5 class="card-title">Toplam <?= e($currency) ?></h5>
                <h2 class="mb-0"><?= formatMoney($total, $currency) ?></h2>
                
                <?php if ($currency != 'TRY'): ?>
                <small>≈ <?= formatMoney(convertToTRY($total, $currency)) ?> ₺</small>
                <?php else: ?>
                <small>TL hesaplar toplamı</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Hesaplar Grid -->
<div class="row">
    <?php if (empty($bankAccounts)): ?>
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-bank display-1 text-muted mb-3"></i>
                <h5>Henüz banka hesabı eklemediniz</h5>
                <p class="text-muted">Banka hesaplarınızı ekleyerek varlıklarınızı takip edebilirsiniz.</p>
                <a href="add-bank-account.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> İlk Hesabı Ekle
                </a>
            </div>
        </div>
    </div>
    <?php else: ?>
        <?php foreach ($bankAccounts as $account): 
            // Hesabın para birimi bilgilerini al
            $currency = $account['currency'];
            $currencySymbol = getCurrencySymbol($currency);
        ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 hover-scale" style="border-left: 4px solid <?= e($account['color']) ?>;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="card-title mb-1">
                                <i class="<?= e($account['icon']) ?>" style="color: <?= e($account['color']) ?>;"></i>
                                <?= e($account['account_name']) ?>
                            </h5>
                            <p class="text-muted mb-0"><?= e($account['bank_name']) ?> (<?= e($currency) ?>)</p>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="bank-transactions.php?id=<?= $account['id'] ?>">
                                        <i class="bi bi-list-ul"></i> Hareketler
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="add-bank-transaction.php?account=<?= $account['id'] ?>">
                                        <i class="bi bi-plus"></i> İşlem Ekle
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="edit-bank-account.php?id=<?= $account['id'] ?>">
                                        <i class="bi bi-pencil"></i> Düzenle
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger" href="delete-bank-account.php?id=<?= $account['id'] ?>" 
                                       onclick="return confirm('Bu hesabı silmek istediğinize emin misiniz?')">
                                        <i class="bi bi-trash"></i> Sil
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Mevcut Bakiye</small>
                        <h3 class="mb-0 <?= $account['current_balance'] >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= formatMoney($account['current_balance'], $currency) ?>
                        </h3>
                        <?php if ($currency != 'TRY'): ?>
                        <small class="text-muted">≈ <?= formatMoney(convertToTRY($account['current_balance'], $currency)) ?> ₺</small>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($account['account_number']): ?>
                    <div class="mb-2">
                        <small class="text-muted">Hesap No:</small>
                        <small><?= e($account['account_number']) ?></small>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($account['iban']): ?>
                    <div class="mb-2">
                        <small class="text-muted">IBAN:</small>
                        <small><?= e($account['iban']) ?></small>
                    </div>
                    <?php endif; ?>
                    
                    <div class="text-muted small">
                        <i class="bi bi-tag"></i> 
                        <?php
                        $typeLabels = [
                            'checking' => 'Vadesiz Hesap',
                            'savings' => 'Vadeli Hesap',
                            'investment' => 'Yatırım Hesabı',
                            'credit' => 'Kredi Hesabı'
                        ];
                        echo $typeLabels[$account['account_type']] ?? $account['account_type'];
                        ?>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <div class="d-grid gap-2">
                        <a href="bank-transactions.php?id=<?= $account['id'] ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-eye"></i> Hesap Detayları
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Hızlı İşlemler -->
<?php if (!empty($bankAccounts)): ?>
<div class="row mt-4">
    <div class="col-12">
        <h4>Hızlı İşlemler</h4>
        <div class="d-flex gap-2 flex-wrap">
            <a href="add-bank-transaction.php" class="btn btn-outline-primary">
                <i class="bi bi-plus-circle"></i> Para Yatır/Çek
            </a>
            <?php if (count($bankAccounts) > 1): ?>
            <a href="bank-transfer.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left-right"></i> Hesaplar Arası Transfer
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.hover-scale {
    transition: transform 0.3s ease;
}
.hover-scale:hover {
    transform: translateY(-5px);
}
</style>
<style>
.currency-box {
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.2s;
}
.currency-box:hover {
    background-color: #f8f9fa;
}
.currency-symbol {
    font-size: 1.25rem;
    font-weight: bold;
    color: #4F46E5;
}
.currency-code {
    font-size: 0.75rem;
    color: #6B7280;
    margin-bottom: 2px;
}
.currency-rate {
    font-weight: 600;
    font-size: 0.875rem;
}
@media (max-width: 576px) {
    .currency-symbol {
        font-size: 1rem;
    }
    .currency-rate {
        font-size: 0.8rem;
    }
}
</style>


<?php include 'includes/footer.php'; ?>