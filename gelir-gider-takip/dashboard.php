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
$upcomingPayments = getUpcomingPayments($userId, 7);

// Okunmamış bildirimleri getir
$unreadNotifications = getUserNotifications($userId, true);

// Son 5 işlem
$recentTransactions = getTransactions($userId, ['limit' => 5]);

$pageTitle = 'Kontrol Paneli';
include 'includes/header.php';
?>

<!-- Custom Dashboard Styles -->
<style>
.dashboard-header {
    margin-bottom: 2rem;
    position: relative;
}

.dashboard-title {
    font-size: 1.75rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.dashboard-subtitle {
    font-size: 1rem;
    color: #6b7280;
    font-weight: 400;
}

.stat-card {
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.3s;
    height: 100%;
    border: none;
    position: relative;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
}

.stat-card .icon-bg {
    position: absolute;
    top: -15px;
    right: -15px;
    font-size: 8rem;
    opacity: 0.15;
    transform: rotate(10deg);
}

.stat-icon {
    height: 45px;
    width: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    margin-bottom: 15px;
    font-size: 1.5rem;
}

.stat-title {
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: rgba(255, 255, 255, 0.8);
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    letter-spacing: -0.025em;
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
}

.stat-change {
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    font-weight: 500;
}

.stat-change i {
    margin-right: 0.25rem;
}

.widget-card {
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(0, 0, 0, 0.05);
    margin-bottom: 1.5rem;
}

.widget-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.widget-title {
    font-weight: 600;
    font-size: 1.125rem;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
}

.widget-title i {
    margin-right: 0.5rem;
    opacity: 0.7;
}

.widget-body {
    padding: 1.25rem 1.5rem;
    background-color: white;
}

.quick-action-btn {
    border-radius: 12px;
    padding: 0.875rem 1.25rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.quick-action-btn:hover {
    transform: translateY(-2px);
}

.transaction-item {
    padding: 1rem 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.transaction-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.transaction-item:first-child {
    padding-top: 0;
}

.transaction-details {
    display: flex;
    align-items: center;
}

.transaction-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.transaction-info h6 {
    margin: 0;
    font-weight: 600;
    font-size: 0.95rem;
}

.transaction-info p {
    margin: 0;
    font-size: 0.8125rem;
    color: #6b7280;
}

.transaction-amount {
    font-weight: 600;
    text-align: right;
}

.transaction-date {
    font-size: 0.75rem;
    color: #6b7280;
}

.upcoming-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.upcoming-item:last-child {
    border-bottom: none;
}

.upcoming-details {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
}

.upcoming-title {
    font-weight: 600;
    font-size: 0.95rem;
    margin: 0;
    display: flex;
    align-items: center;
}

.upcoming-category {
    font-size: 0.8125rem;
    color: #6b7280;
}

.upcoming-date {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
}

.notification-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-title {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 0.25rem;
    display: flex;
    align-items: center;
}

.notification-title i {
    margin-right: 0.5rem;
}

.notification-date {
    font-size: 0.75rem;
    color: #6b7280;
}

.empty-state {
    padding: 1.5rem;
    text-align: center;
}

.empty-state i {
    font-size: 2.5rem;
    color: #d1d5db;
    margin-bottom: 0.75rem;
}

.empty-state h6 {
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #6b7280;
    margin-bottom: 1rem;
    font-size: 0.9375rem;
}

@media (max-width: 767.98px) {
    .dashboard-header {
        text-align: center;
        margin-bottom: 1.5rem;
    }
    
    .stat-card {
        margin-bottom: 1rem;
    }
}
</style>

<div class="dashboard-header">
    <h1 class="dashboard-title">Merhaba, <?= e($_SESSION['username']) ?></h1>
    <p class="dashboard-subtitle"><?= date('d F Y', strtotime('now')) ?> • <?= date('l') ?></p>
</div>

<!-- İstatistik Kartları -->
<div class="row">
    <!-- Gelir Kartı -->
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="stat-card text-white bg-success h-100">
            <div class="card-body">
                <i class="bi bi-arrow-up-circle-fill icon-bg"></i>
                <div class="stat-icon bg-white text-success">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <h6 class="stat-title">Toplam Gelir</h6>
                <h3 class="stat-value"><?= formatMoney($totalIncome) ?></h3>
                <div class="stat-change">
                    <span>Bu ay: <?= formatMoney($monthlyIncome) ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gider Kartı -->
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="stat-card text-white bg-danger h-100">
            <div class="card-body">
                <i class="bi bi-arrow-down-circle-fill icon-bg"></i>
                <div class="stat-icon bg-white text-danger">
                    <i class="bi bi-bag"></i>
                </div>
                <h6 class="stat-title">Toplam Gider</h6>
                <h3 class="stat-value"><?= formatMoney($totalExpense) ?></h3>
                <div class="stat-change">
                    <span>Bu ay: <?= formatMoney($monthlyExpense) ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Net Bakiye Kartı -->
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="stat-card text-white bg-<?= $netBalance >= 0 ? 'info' : 'warning' ?> h-100">
            <div class="card-body">
                <i class="bi bi-wallet-fill icon-bg"></i>
                <div class="stat-icon bg-white text-<?= $netBalance >= 0 ? 'info' : 'warning' ?>">
                    <i class="bi bi-wallet2"></i>
                </div>
                <h6 class="stat-title">Net Bakiye</h6>
                <h3 class="stat-value"><?= formatMoney($netBalance) ?></h3>
                <div class="stat-change">
                    <span>Bu ay: <?= formatMoney($monthlyBalance) ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Varlık Kartı -->
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="stat-card text-white bg-primary h-100">
            <div class="card-body">
                <i class="bi bi-bank2 icon-bg"></i>
                <div class="stat-icon bg-white text-primary">
                    <i class="bi bi-bank"></i>
                </div>
                <h6 class="stat-title">Toplam Varlık</h6>
                <h3 class="stat-value"><?= formatMoney($totalAssets) ?></h3>
                <div class="stat-change">
                    <span>Banka hesapları</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Sol Sütun - Son İşlemler ve Hızlı İşlemler -->
    <div class="col-lg-6">
        <!-- Son İşlemler -->
        <div class="widget-card">
            <div class="widget-header">
                <h5 class="widget-title">
                    <i class="bi bi-clock-history"></i> Son İşlemler
                </h5>
                <a href="transactions.php" class="btn btn-sm btn-outline-primary">
                    Tümünü Gör
                </a>
            </div>
            <div class="widget-body">
                <?php if (empty($recentTransactions)): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h6>Henüz işlem bulunmuyor</h6>
                    <p>İlk gelir veya gider işleminizi ekleyin.</p>
                    <a href="add-transaction.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> İlk İşlemi Ekle
                    </a>
                </div>
                <?php else: ?>
                <div class="transactions-list">
                    <?php foreach ($recentTransactions as $transaction): ?>
                    <div class="transaction-item">
                        <div class="transaction-details">
                            <div class="transaction-icon" style="background-color: <?= e($transaction['category_color'] ?? '#6b7280') ?>; color: white;">
                                <i class="<?= e($transaction['category_icon'] ?? 'bi-tag') ?>"></i>
                            </div>
                            <div class="transaction-info">
                                <h6><?= e($transaction['description'] ?: $transaction['category_name']) ?></h6>
                                <p><?= date('d M Y', strtotime($transaction['transaction_date'])) ?></p>
                            </div>
                        </div>
                        <div class="transaction-amount">
                            <div class="<?= $transaction['type'] == 'income' ? 'text-success' : 'text-danger' ?>">
                                <?= $transaction['type'] == 'income' ? '+' : '-' ?><?= formatMoney($transaction['amount']) ?>
                            </div>
                            <div class="transaction-date">
                                <?= $transaction['type'] == 'income' ? 'Gelir' : 'Gider' ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Hızlı İşlemler -->
        <div class="widget-card mt-4">
            <div class="widget-header">
                <h5 class="widget-title">
                    <i class="bi bi-lightning"></i> Hızlı İşlemler
                </h5>
            </div>
            <div class="widget-body">
                <div class="row g-3">
                    <div class="col-6">
                        <a href="add-transaction.php" class="quick-action-btn btn btn-primary d-block w-100">
                            <i class="bi bi-plus-circle"></i> Yeni İşlem
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="add-bank-transaction.php" class="quick-action-btn btn btn-outline-primary d-block w-100">
                            <i class="bi bi-bank"></i> Banka İşlemi
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="add-recurring-transaction.php" class="quick-action-btn btn btn-outline-primary d-block w-100">
                            <i class="bi bi-arrow-repeat"></i> Tekrarlayan
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="reports.php" class="quick-action-btn btn btn-outline-primary d-block w-100">
                            <i class="bi bi-graph-up"></i> Raporlar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sağ Sütun - Yaklaşan Ödemeler ve Bildirimler -->
    <div class="col-lg-6">
        <!-- Yaklaşan Ödemeler -->
        <div class="widget-card">
            <div class="widget-header">
                <h5 class="widget-title">
                    <i class="bi bi-calendar-event"></i> Yaklaşan Ödemeler
                </h5>
                <a href="recurring-transactions.php" class="btn btn-sm btn-outline-primary">
                    Tümünü Gör
                </a>
            </div>
            <div class="widget-body">
                <?php if (empty($upcomingPayments)): ?>
                <div class="empty-state">
                    <i class="bi bi-calendar-check"></i>
                    <h6>Yaklaşan ödeme bulunmuyor</h6>
                    <p>Tekrarlayan işlemlerinizi ekleyin ve takip edin.</p>
                    <a href="add-recurring-transaction.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> Ekle
                    </a>
                </div>
                <?php else: ?>
                <div class="upcoming-list">
                    <?php foreach (array_slice($upcomingPayments, 0, 3) as $payment): ?>
                    <div class="upcoming-item">
                        <div class="upcoming-details">
                            <h6 class="upcoming-title">
                                <?php if (isset($payment['category_icon']) && isset($payment['category_color'])): ?>
                                <i class="<?= e($payment['category_icon']) ?>" style="color: <?= e($payment['category_color']) ?>"></i>
                                <?php endif; ?>
                                <?= e($payment['description']) ?>
                            </h6>
                            <div class="text-danger"><?= formatMoney($payment['amount']) ?></div>
                        </div>
                        <div class="upcoming-details">
                            <div class="upcoming-category"><?= e($payment['category_name']) ?></div>
                            <div class="upcoming-date">
                                <i class="bi bi-calendar"></i> 
                                <?= date('d M', strtotime($payment['next_occurrence'])) ?>
                                <?php if ($payment['days_until'] == 0): ?>
                                    <span class="badge bg-warning text-dark">Bugün</span>
                                <?php elseif ($payment['days_until'] == 1): ?>
                                    <span class="badge bg-info text-white">Yarın</span>
                                <?php else: ?>
                                    <span class="badge bg-primary"><?= $payment['days_until'] ?> gün</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Bildirimler -->
        <div class="widget-card mt-4">
            <div class="widget-header">
                <h5 class="widget-title">
                    <i class="bi bi-bell"></i> Bildirimler
                    <?php if (count($unreadNotifications) > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-2"><?= count($unreadNotifications) ?></span>
                    <?php endif; ?>
                </h5>
                <a href="notifications.php" class="btn btn-sm btn-outline-primary">
                    Tümünü Gör
                </a>
            </div>
            <div class="widget-body">
                <?php if (empty($unreadNotifications)): ?>
                <div class="empty-state">
                    <i class="bi bi-bell-slash"></i>
                    <h6>Okunmamış bildiriminiz yok</h6>
                    <p>Tüm bildirimleriniz güncel.</p>
                </div>
                <?php else: ?>
                <div class="notifications-list">
                    <?php foreach (array_slice($unreadNotifications, 0, 3) as $notification): ?>
                    <div class="notification-item">
                        <h6 class="notification-title">
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
                        <p class="notification-message mb-1"><?= e($notification['message']) ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="notification-date">
                                <i class="bi bi-clock"></i> 
                                <?= date('d M H:i', strtotime($notification['created_at'])) ?>
                            </span>
                            <a href="mark-notification-read.php?id=<?= $notification['id'] ?>" class="btn btn-sm btn-light">
                                <i class="bi bi-check2"></i> Okundu
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

<?php include 'includes/footer.php'; ?>