<?php
require_once 'includes/functions.php';
require_once 'includes/bank-functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$accountId = $_GET['id'] ?? 0;

// Tarihleri al
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // Ay başı
$endDate = $_GET['end_date'] ?? date('Y-m-t'); // Ay sonu

// Hesap bilgilerini kontrol et
$account = getBankAccountById($accountId, $userId);
if (!$account) {
    header('Location: bank-accounts.php');
    exit;
}

// Hesap hareketlerini getir
$transactions = getBankTransactions($accountId, [
    'start_date' => $startDate,
    'end_date' => $endDate
]);

// Hesap özeti istatistiklerini hesapla
$totalDeposits = 0;
$totalWithdrawals = 0;
$totalFees = 0;
$totalInterest = 0;
$transactionCount = count($transactions);

foreach ($transactions as $transaction) {
    switch ($transaction['transaction_type']) {
        case 'deposit':
        case 'transfer_in':
            $totalDeposits += $transaction['amount'];
            break;
        case 'withdrawal':
        case 'transfer_out':
            $totalWithdrawals += $transaction['amount'];
            break;
        case 'fee':
            $totalFees += $transaction['amount'];
            break;
        case 'interest':
            $totalInterest += $transaction['amount'];
            break;
    }
}

// Dönem başı ve sonu bakiyesini hesapla
$startBalance = 0;
$endBalance = $account['current_balance'];

// En eski tarihli işlemi bul
if (!empty($transactions)) {
    // Sıralamayı ters çevir
    $tempTransactions = $transactions;
    usort($tempTransactions, function($a, $b) {
        return strtotime($a['transaction_date']) - strtotime($b['transaction_date']);
    });
    
    $firstTransaction = $tempTransactions[0];
    $lastTransaction = $tempTransactions[count($tempTransactions) - 1];
    
    // Dönem sonu bakiyesi, son işlemin bakiyesi
    $endBalance = $lastTransaction['balance_after'];
    
    // Dönem başı bakiyesi, ilk işlemden önceki bakiye
    $startBalance = $firstTransaction['balance_after'] - getTransactionAmount($firstTransaction);
}

$pageTitle = $account['account_name'] . ' - Hesap Özeti';
include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><?= e($account['account_name']) ?> - Hesap Özeti</h2>
        <p class="text-muted mb-0"><?= e($account['bank_name']) ?></p>
    </div>
    <div>
        <a href="bank-transactions.php?id=<?= $accountId ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> İşlemlere Dön
        </a>
        <a href="#" class="btn btn-primary" onclick="window.print(); return false;">
            <i class="bi bi-printer"></i> Yazdır
        </a>
    </div>
</div>

<!-- Tarih Aralığı Seçimi -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="id" value="<?= $accountId ?>">
            
            <div class="col-md-4">
                <label for="start_date" class="form-label">Başlangıç Tarihi</label>
                <input type="date" class="form-control" id="start_date" name="start_date" 
                       value="<?= e($startDate) ?>" required>
            </div>
            
            <div class="col-md-4">
                <label for="end_date" class="form-label">Bitiş Tarihi</label>
                <input type="date" class="form-control" id="end_date" name="end_date" 
                       value="<?= e($endDate) ?>" required>
            </div>
            
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary">Özeti Göster</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Hesap Bilgileri -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Hesap Bilgileri</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <th>Banka:</th>
                        <td><?= e($account['bank_name']) ?></td>
                    </tr>
                    <tr>
                        <th>Hesap Adı:</th>
                        <td><?= e($account['account_name']) ?></td>
                    </tr>
                    <tr>
                        <th>Hesap No:</th>
                        <td><?= e($account['account_number'] ?: 'Belirtilmemiş') ?></td>
                    </tr>
                    <tr>
                        <th>IBAN:</th>
                        <td><?= e($account['iban'] ?: 'Belirtilmemiş') ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <th>Hesap Türü:</th>
                        <td>
                            <?php
                            $typeLabels = [
                                'checking' => 'Vadesiz Hesap',
                                'savings' => 'Vadeli Hesap',
                                'investment' => 'Yatırım Hesabı',
                                'credit' => 'Kredi Hesabı'
                            ];
                            echo $typeLabels[$account['account_type']] ?? $account['account_type'];
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Para Birimi:</th>
                        <td><?= e($account['currency']) ?></td>
                    </tr>
                    <tr>
                        <th>Dönem:</th>
                        <td><?= date('d.m.Y', strtotime($startDate)) ?> - <?= date('d.m.Y', strtotime($endDate)) ?></td>
                    </tr>
                    <tr>
                        <th>Mevcut Bakiye:</th>
                        <td><strong><?= formatMoney($account['current_balance']) ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Hesap Özeti -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Hesap Özeti</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table">
                    <tr class="table-light">
                        <th>Dönem Başı Bakiye</th>
                        <td class="text-end"><?= formatMoney($startBalance) ?></td>
                    </tr>
                    <tr>
                        <th>Toplam Para Yatırma</th>
                        <td class="text-end text-success">+ <?= formatMoney($totalDeposits) ?></td>
                    </tr>
                    <tr>
                        <th>Toplam Para Çekme</th>
                        <td class="text-end text-danger">- <?= formatMoney($totalWithdrawals) ?></td>
                    </tr>
                    <tr>
                        <th>Toplam Banka Ücreti</th>
                        <td class="text-end text-danger">- <?= formatMoney($totalFees) ?></td>
                    </tr>
                    <tr>
                        <th>Toplam Faiz</th>
                        <td class="text-end text-success">+ <?= formatMoney($totalInterest) ?></td>
                    </tr>
                    <tr class="table-light">
                        <th>Dönem Sonu Bakiye</th>
                        <td class="text-end"><strong><?= formatMoney($endBalance) ?></strong></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title">İşlem Özeti</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Toplam İşlem Sayısı
                                <span class="badge bg-primary rounded-pill"><?= $transactionCount ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Para Yatırma İşlemi
                                <span class="badge bg-success rounded-pill"><?= count(array_filter($transactions, function($t) { return in_array($t['transaction_type'], ['deposit', 'transfer_in']); })) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Para Çekme İşlemi
                                <span class="badge bg-danger rounded-pill"><?= count(array_filter($transactions, function($t) { return in_array($t['transaction_type'], ['withdrawal', 'transfer_out']); })) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Banka Ücreti
                                <span class="badge bg-secondary rounded-pill"><?= count(array_filter($transactions, function($t) { return $t['transaction_type'] == 'fee'; })) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Faiz İşlemi
                                <span class="badge bg-info rounded-pill"><?= count(array_filter($transactions, function($t) { return $t['transaction_type'] == 'interest'; })) ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- İşlem Listesi -->
<div class="card print-break">
    <div class="card-header">
        <h5 class="mb-0">Hesap Hareketleri</h5>
    </div>
    <div class="card-body">
        <?php if (empty($transactions)): ?>
            <div class="alert alert-info">
                Seçilen tarih aralığında işlem bulunamadı.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>İşlem Tipi</th>
                            <th>Açıklama</th>
                            <th class="text-end">Tutar</th>
                            <th class="text-end">Bakiye</th>
                            <th>Referans</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?= date('d.m.Y H:i', strtotime($transaction['transaction_date'])) ?></td>
                            <td>
                                <?php
                                $typeLabels = [
                                    'deposit' => '<span class="badge bg-success">Para Yatırma</span>',
                                    'withdrawal' => '<span class="badge bg-danger">Para Çekme</span>',
                                    'transfer_in' => '<span class="badge bg-info">Gelen Transfer</span>',
                                    'transfer_out' => '<span class="badge bg-warning">Giden Transfer</span>',
                                    'fee' => '<span class="badge bg-secondary">Banka Ücreti</span>',
                                    'interest' => '<span class="badge bg-primary">Faiz</span>'
                                ];
                                echo $typeLabels[$transaction['transaction_type']] ?? $transaction['transaction_type'];
                                ?>
                            </td>
                            <td><?= e($transaction['description']) ?></td>
                            <td class="text-end">
                                <?php
                                $isCredit = in_array($transaction['transaction_type'], ['deposit', 'transfer_in', 'interest']);
                                $class = $isCredit ? 'text-success' : 'text-danger';
                                $sign = $isCredit ? '+' : '-';
                                ?>
                                <span class="<?= $class ?>"><?= $sign ?><?= formatMoney($transaction['amount']) ?></span>
                            </td>
                            <td class="text-end"><?= formatMoney($transaction['balance_after']) ?></td>
                            <td><?= e($transaction['reference_number']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Yazdırma stilleri -->
<style media="print">
    @page {
        size: A4;
        margin: 1cm;
    }
    
    body {
        font-size: 12pt;
    }
    
    .btn, .navbar, .no-print {
        display: none !important;
    }
    
    .card {
        border: 1px solid #ddd;
        margin-bottom: 20px;
        break-inside: avoid;
    }
    
    .card-header {
        background-color: #f7f7f7;
        font-weight: bold;
        padding: 10px;
    }
    
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .table th, .table td {
        border: 1px solid #ddd;
        padding: 8px;
    }
    
    .print-break {
        page-break-before: always;
    }
    
    .badge {
        padding: 3px 6px;
        border-radius: 3px;
        font-weight: normal;
        color: #fff !important;
    }
    
    .bg-success {
        background-color: #28a745 !important;
    }
    
    .bg-danger {
        background-color: #dc3545 !important;
    }
    
    .bg-info {
        background-color: #17a2b8 !important;
    }
    
    .bg-warning {
        background-color: #ffc107 !important;
    }
    
    .bg-secondary {
        background-color: #6c757d !important;
    }
    
    .bg-primary {
        background-color: #007bff !important;
    }
    
    .text-success {
        color: #28a745 !important;
    }
    
    .text-danger {
        color: #dc3545 !important;
    }
    
    .text-muted {
        color: #6c757d !important;
    }
</style>

<?php 
// bank-functions.php içinde eklenecek yardımcı fonksiyon
function getTransactionAmount($transaction) {
    if (in_array($transaction['transaction_type'], ['deposit', 'transfer_in', 'interest'])) {
        return $transaction['amount'];
    } else {
        return -$transaction['amount'];
    }
}
?>

<?php include 'includes/footer.php'; ?>