<?php
require_once 'includes/functions.php';
require_once 'includes/bank-functions.php';
require_once 'includes/recurring-functions.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Bu ay için verileri getir
$currentMonth = date('Y-m');
$startDate = $currentMonth . '-01';
$endDate = date('Y-m-t', strtotime($startDate));

// Toplam değerleri hesapla
$totalIncome = calculateTotals($userId, 'income');
$totalExpense = calculateTotals($userId, 'expense');
$netBalance = $totalIncome - $totalExpense;

// Bu ay için değerler
$monthlyIncome = calculateTotals($userId, 'income', $startDate, $endDate);
$monthlyExpense = calculateTotals($userId, 'expense', $startDate, $endDate);
$monthlyBalance = $monthlyIncome - $monthlyExpense;

// Banka hesap varlıklarını al
$totalAssets = getTotalAssets($userId);

// Yaklaşan ödemeleri getir
// Fonksiyon yoksa, manuel sorgu ile çözelim
if (!function_exists('getUpcomingPayments')) {
    function getUpcomingPayments($userId, $days = 7) {
        global $pdo;
        
        $stmt = $pdo->prepare("
            SELECT rt.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
                   DATEDIFF(rt.next_occurrence, CURDATE()) as days_until
            FROM recurring_transactions rt
            JOIN categories c ON rt.category_id = c.id
            WHERE rt.user_id = ?
            AND rt.is_active = TRUE
            AND rt.type = 'expense'
            AND rt.next_occurrence BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY rt.next_occurrence ASC
        ");
        $stmt->execute([$userId, $days]);
        return $stmt->fetchAll();
    }
}

// Yaklaşan ödemeleri getir
$upcomingPayments = getUpcomingPayments($userId, 7);

// Okunmamış bildirimleri getir
// Fonksiyon yoksa, manuel sorgu ile çözelim
if (!function_exists('getUserNotifications')) {
    function getUserNotifications($userId, $unreadOnly = false) {
        global $pdo;
        
        $sql = "SELECT * FROM notifications WHERE user_id = ?";
        
        if ($unreadOnly) {
            $sql .= " AND is_read = FALSE";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT 20";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}

// Bildirimleri getir
$unreadNotifications = getUserNotifications($userId, true);

// Son 10 işlem
$recentTransactions = getTransactions($userId, ['limit' => 10]);

$pageTitle = 'Kontrol Paneli';
include 'includes/header.php';
?>

<h2 class="mb-4">Kontrol Paneli</h2>

<!-- Özet Kartları - 4 Kart Yan Yana -->
<div class="row mb-4">
    <!-- Gelir Kartı -->
    <div class="col-md-3">
        <div class="stat-card card text-white bg-success fade-in">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Toplam Gelir</h5>
                        <h2 class="card-text"><?= formatMoney($totalIncome) ?></h2>
                        <p class="card-text small">Bu ay: <?= formatMoney($monthlyIncome) ?></p>
                    </div>
                    <i class="bi bi-arrow-up-circle-fill icon"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gider Kartı -->
    <div class="col-md-3">
        <div class="stat-card card text-white bg-danger fade-in">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Toplam Gider</h5>
                        <h2 class="card-text"><?= formatMoney($totalExpense) ?></h2>
                        <p class="card-text small">Bu ay: <?= formatMoney($monthlyExpense) ?></p>
                    </div>
                    <i class="bi bi-arrow-down-circle-fill icon"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Net Bakiye Kartı -->
    <div class="col-md-3">
        <div class="stat-card card text-white bg-<?= $netBalance >= 0 ? 'info' : 'warning' ?> fade-in">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Net Bakiye</h5>
                        <h2 class="card-text"><?= formatMoney($netBalance) ?></h2>
                        <p class="card-text small">Bu ay: <?= formatMoney($monthlyBalance) ?></p>
                    </div>
                    <i class="bi bi-wallet-fill icon"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Varlık Kartı -->
    <div class="col-md-3">
        <div class="stat-card card text-white bg-primary fade-in">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Toplam Varlık</h5>
                        <h2 class="card-text"><?= formatMoney($totalAssets) ?></h2>
                        <p class="card-text small">Banka hesapları</p>
                    </div>
                    <i class="bi bi-bank icon"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Yaklaşan Ödemeler ve Bildirimler -->
<div class="row mb-4">
    <!-- Yaklaşan Ödemeler Widget -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-calendar-event"></i> Yaklaşan Ödemeler
                </h5>
                <a href="recurring-transactions.php" class="btn btn-sm btn-outline-primary">
                    Tümünü Gör
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($upcomingPayments)): ?>
                    <p class="text-muted text-center mb-0">
                        <i class="bi bi-calendar-check"></i> Yaklaşan ödeme bulunmuyor.
                    </p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($upcomingPayments, 0, 5) as $payment): ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">
                                        <?php if (isset($payment['category_icon']) && isset($payment['category_color'])): ?>
                                        <i class="<?= e($payment['category_icon']) ?>" 
                                           style="color: <?= e($payment['category_color']) ?>"></i>
                                        <?php endif; ?>
                                        <?= e($payment['description']) ?>
                                    </h6>
                                    <small class="text-muted">
                                        <?= e($payment['category_name']) ?> • 
                                        <?= date('d.m.Y', strtotime($payment['next_occurrence'])) ?>
                                        <?php if ($payment['days_until'] == 0): ?>
                                            <span class="badge bg-warning">Bugün</span>
                                        <?php elseif ($payment['days_until'] == 1): ?>
                                            <span class="badge bg-info">Yarın</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary"><?= $payment['days_until'] ?> gün</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div class="text-danger text-end">
                                    <strong><?= formatMoney($payment['amount']) ?></strong>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bildirimler Widget -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-bell"></i> Bildirimler
                    <?php if (count($unreadNotifications) > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?= count($unreadNotifications) ?></span>
                    <?php endif; ?>
                </h5>
                <a href="notifications.php" class="btn btn-sm btn-outline-primary">
                    Tümünü Gör
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($unreadNotifications)): ?>
                    <p class="text-muted text-center mb-0">
                        <i class="bi bi-bell-slash"></i> Okunmamış bildirim bulunmuyor.
                    </p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($unreadNotifications, 0, 5) as $notification): ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <?php
                                        $iconClass = [
                                            'reminder' => 'bi-clock text-info',
                                            'overdue' => 'bi-exclamation-triangle text-danger',
                                            'info' => 'bi-info-circle text-primary',
                                            'warning' => 'bi-exclamation-diamond text-warning'
                                        ][$notification['type']] ?? 'bi-bell text-secondary';
                                        ?>
                                        <i class="<?= $iconClass ?>"></i>
                                        <?= e($notification['title']) ?>
                                    </h6>
                                    <p class="mb-1 small"><?= e($notification['message']) ?></p>
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> 
                                        <?= date('d.m.Y H:i', strtotime($notification['created_at'])) ?>
                                    </small>
                                </div>
                                <a href="mark-notification-read.php?id=<?= $notification['id'] ?>" 
                                   class="btn btn-sm btn-light ms-2" title="Okundu olarak işaretle">
                                    <i class="bi bi-check2"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Hızlı İşlemler -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="bi bi-lightning"></i> Hızlı İşlemler
                </h5>
                <div class="d-flex flex-wrap gap-2">
                    <a href="add-transaction.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Yeni İşlem
                    </a>
                    <a href="add-bank-transaction.php" class="btn btn-outline-primary">
                        <i class="bi bi-bank2"></i> Banka İşlemi
                    </a>
                    <a href="add-recurring-transaction.php" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-repeat"></i> Tekrarlayan İşlem
                    </a>
                    <a href="bank-transfer.php" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left-right"></i> Transfer
                    </a>
                    <a href="reports.php" class="btn btn-outline-primary">
                        <i class="bi bi-graph-up"></i> Raporlar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Son İşlemler -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-clock-history"></i> Son İşlemler
        </h5>
        <a href="transactions.php" class="btn btn-sm btn-outline-primary">
            Tüm İşlemler
        </a>
    </div>
    <div class="card-body">
        <?php if (empty($recentTransactions)): ?>
            <div class="empty-state text-center py-5">
                <i class="bi bi-inbox display-4 text-muted mb-3"></i>
                <h5 class="text-muted">Henüz işlem bulunmuyor</h5>
                <p class="text-muted mb-4">İlk gelir veya gider işleminizi ekleyin.</p>
                <a href="add-transaction.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> İlk İşlemi Ekle
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Kategori</th>
                            <th>Açıklama</th>
                            <th>Tip</th>
                            <th class="text-end">Tutar</th>
                            <th class="text-end">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTransactions as $transaction): ?>
                        <tr>
                            <td>
                                <i class="bi bi-calendar3"></i>
                                <?= date('d.m.Y', strtotime($transaction['transaction_date'])) ?>
                            </td>
                            <td>
                                <?php if (isset($transaction['category_icon']) && isset($transaction['category_color'])): ?>
                                    <i class="<?= e($transaction['category_icon']) ?>" 
                                       style="color: <?= e($transaction['category_color']) ?>"></i>
                                <?php endif; ?>
                                <?= e($transaction['category_name'] ?? 'Kategori Yok') ?>
                            </td>
                            <td><?= e($transaction['description']) ?></td>
                            <td>
                                <span class="badge bg-<?= $transaction['type'] == 'income' ? 'success' : 'danger' ?>">
                                    <i class="bi bi-<?= $transaction['type'] == 'income' ? 'arrow-up' : 'arrow-down' ?>"></i>
                                    <?= $transaction['type'] == 'income' ? 'Gelir' : 'Gider' ?>
                                </span>
                            </td>
                            <td class="text-end <?= $transaction['type'] == 'income' ? 'text-success' : 'text-danger' ?>">
                                <strong>
                                    <?= $transaction['type'] == 'income' ? '+' : '-' ?>
                                    <?= formatMoney($transaction['amount']) ?>
                                </strong>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="edit-transaction.php?id=<?= $transaction['id'] ?>" 
                                       class="btn btn-outline-primary" title="Düzenle">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="delete-transaction.php?id=<?= $transaction['id'] ?>" 
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
            
            <div class="text-center mt-3">
                <a href="transactions.php" class="btn btn-outline-primary">
                    <i class="bi bi-list-ul"></i> Tüm İşlemleri Görüntüle
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>