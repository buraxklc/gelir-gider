<?php
require_once 'includes/functions.php';
require_once 'includes/bank-functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$accountId = $_GET['id'] ?? 0;

// Hesap bilgilerini kontrol et
$account = getBankAccountById($accountId, $userId);
if (!$account) {
    header('Location: bank-accounts.php');
    exit;
}

// Filtreleri al
$filters = [
    'start_date' => $_GET['start_date'] ?? null,
    'end_date' => $_GET['end_date'] ?? null,
    'type' => $_GET['type'] ?? null
];

// Hesap hareketlerini getir
$transactions = getBankTransactions($accountId, $filters);

// Hesap özeti
$summary = getAccountSummary($accountId);

$pageTitle = $account['account_name'] . ' - Hesap Hareketleri';
include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><?= e($account['account_name']) ?></h2>
        <p class="text-muted mb-0"><?= e($account['bank_name']) ?></p>
    </div>
    <div>
        <a href="add-bank-transaction.php?account=<?= $accountId ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> İşlem Ekle
        </a>
        <a href="bank-accounts.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Geri
        </a>
    </div>
</div>

<!-- Hesap Özeti -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Mevcut Bakiye</h6>
                <h3 class="mb-0"><?= formatMoney($account['current_balance']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title">Toplam Yatırım</h6>
                <h3 class="mb-0"><?= formatMoney($summary['total_deposits']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h6 class="card-title">Toplam Çekim</h6>
                <h3 class="mb-0"><?= formatMoney($summary['total_withdrawals']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6 class="card-title">İşlem Sayısı</h6>
                <h3 class="mb-0"><?= $summary['total_transactions'] ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Filtreler -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="id" value="<?= $accountId ?>">
            
            <div class="col-md-3">
                <label for="start_date" class="form-label">Başlangıç Tarihi</label>
                <input type="date" class="form-control" id="start_date" name="start_date" 
                       value="<?= e($filters['start_date']) ?>">
            </div>
            
            <div class="col-md-3">
                <label for="end_date" class="form-label">Bitiş Tarihi</label>
                <input type="date" class="form-control" id="end_date" name="end_date" 
                       value="<?= e($filters['end_date']) ?>">
            </div>
            
            <div class="col-md-3">
                <label for="type" class="form-label">İşlem Tipi</label>
                <select class="form-select" id="type" name="type">
                    <option value="">Tümü</option>
                    <option value="deposit" <?= $filters['type'] == 'deposit' ? 'selected' : '' ?>>Para Yatırma</option>
                    <option value="withdrawal" <?= $filters['type'] == 'withdrawal' ? 'selected' : '' ?>>Para Çekme</option>
                    <option value="transfer_in" <?= $filters['type'] == 'transfer_in' ? 'selected' : '' ?>>Gelen Transfer</option>
                    <option value="transfer_out" <?= $filters['type'] == 'transfer_out' ? 'selected' : '' ?>>Giden Transfer</option>
                    <option value="fee" <?= $filters['type'] == 'fee' ? 'selected' : '' ?>>Banka Ücreti</option>
                    <option value="interest" <?= $filters['type'] == 'interest' ? 'selected' : '' ?>>Faiz</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary">Filtrele</button>
                    <a href="?id=<?= $accountId ?>" class="btn btn-secondary">Temizle</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Hesap Hareketleri -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Hesap Hareketleri</h5>
    </div>
    <div class="card-body">
        <?php if (empty($transactions)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted mb-3"></i>
                <p class="text-muted">Henüz hesap hareketi bulunmuyor.</p>
                <a href="add-bank-transaction.php?account=<?= $accountId ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> İlk İşlemi Ekle
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>İşlem Tipi</th>
                            <th>Açıklama</th>
                            <th class="text-end">Tutar</th>
                            <th class="text-end">Bakiye</th>
                            <th>Referans</th>
                            <th>İşlemler</th>
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
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="edit-bank-transaction.php?id=<?= $transaction['id'] ?>" class="btn btn-outline-primary" title="Düzenle">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="delete-bank-transaction.php?id=<?= $transaction['id'] ?>&account=<?= $accountId ?>" 
                                       class="btn btn-outline-danger" 
                                       onclick="return confirm('Bu işlemi silmek istediğinize emin misiniz?')" 
                                       title="Sil">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if (count($transactions) > 20): ?>
            <div class="d-flex justify-content-center mt-4">
                <nav aria-label="Sayfalama">
                    <ul class="pagination">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Önceki</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Sonraki</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Hızlı İşlemler -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Hızlı İşlemler</h5>
    </div>
    <div class="card-body">
        <div class="d-flex gap-2">
            <a href="add-bank-transaction.php?account=<?= $accountId ?>&type=deposit" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Para Yatır
            </a>
            <a href="add-bank-transaction.php?account=<?= $accountId ?>&type=withdrawal" class="btn btn-danger">
                <i class="bi bi-dash-circle"></i> Para Çek
            </a>
            <a href="bank-transfer.php?from=<?= $accountId ?>" class="btn btn-primary">
                <i class="bi bi-arrow-left-right"></i> Transfer Yap
            </a>
            <a href="bank-statement.php?id=<?= $accountId ?>" class="btn btn-info">
                <i class="bi bi-file-earmark-text"></i> Hesap Özeti
            </a>
        </div>
    </div>
</div>

<script>
// İşlem sırası ve filtreleme için aktif seçimi göster
document.addEventListener('DOMContentLoaded', function() {
    // Filtreleme formunun gönderilmesi
    const filterForm = document.querySelector('form');
    filterForm.addEventListener('submit', function(e) {
        // Boş filtreleri temizle
        const inputs = this.querySelectorAll('input, select');
        inputs.forEach(input => {
            if (input.value === '' && input.name !== 'id') {
                input.disabled = true;
            }
        });
    });
    
    // Tarihleri JS ile formatlama
    const dates = document.querySelectorAll('td:first-child');
    dates.forEach(cell => {
        const dateObj = new Date(cell.textContent);
        if (!isNaN(dateObj)) {
            const options = { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            cell.textContent = dateObj.toLocaleDateString('tr-TR', options);
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>