<?php
require_once 'includes/functions.php';
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

// Son 10 işlem
$recentTransactions = getTransactions($userId, ['limit' => 10]);

// Debug için - eğer değerler 0 geliyorsa
error_log("User ID: " . $userId);
error_log("Total Income: " . $totalIncome);
error_log("Total Expense: " . $totalExpense);
error_log("Monthly Income: " . $monthlyIncome);
error_log("Monthly Expense: " . $monthlyExpense);

$pageTitle = 'Kontrol Paneli';
include 'includes/header.php';
?>

<h2 class="mb-4">Kontrol Paneli</h2>

<!-- Özet Kartları -->
<div class="dashboard-grid mb-4">
    <div class="stat-card card text-white bg-success fade-in">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title">Toplam Gelir</h5>
                    <h2 class="card-text" data-counter="<?= $totalIncome ?>" data-format="currency">₺0</h2>
                    <p class="card-text small">Bu ay: <span data-counter="<?= $monthlyIncome ?>" data-format="currency">₺0</span></p>
                </div>
                <i class="bi bi-arrow-up-circle-fill icon"></i>
            </div>
        </div>
    </div>
    
    <div class="stat-card card text-white bg-danger fade-in">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title">Toplam Gider</h5>
                    <h2 class="card-text" data-counter="<?= $totalExpense ?>" data-format="currency">₺0</h2>
                    <p class="card-text small">Bu ay: <span data-counter="<?= $monthlyExpense ?>" data-format="currency">₺0</span></p>
                </div>
                <i class="bi bi-arrow-down-circle-fill icon"></i>
            </div>
        </div>
    </div>
    
    <div class="stat-card card text-white bg-<?= $netBalance >= 0 ? 'info' : 'warning' ?> fade-in">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title">Net Bakiye</h5>
                    <h2 class="card-text" data-counter="<?= $netBalance ?>" data-format="currency">₺0</h2>
                    <p class="card-text small">Bu ay: <span data-counter="<?= $monthlyBalance ?>" data-format="currency">₺0</span></p>
                </div>
                <i class="bi bi-wallet-fill icon"></i>
            </div>
        </div>
    </div>
</div>

<!-- Son İşlemler -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Son İşlemler</h5>
        <a href="add-transaction.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Yeni İşlem
        </a>
    </div>
    <div class="card-body">
        <?php if (empty($recentTransactions)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h5>Henüz işlem bulunmuyor</h5>
                <p>İlk gelir veya gider işleminizi ekleyin.</p>
                <a href="add-transaction.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> İşlem Ekle
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
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
                        <tr data-type="<?= $transaction['type'] ?>" data-amount="<?= $transaction['amount'] ?>">
                            <td><?= date('d.m.Y', strtotime($transaction['transaction_date'])) ?></td>
                            <td>
                                <?php if (isset($transaction['category_icon'])): ?>
                                    <i class="<?= e($transaction['category_icon']) ?>" 
                                       style="color: <?= e($transaction['category_color'] ?? '#000000') ?>"></i>
                                <?php endif; ?>
                                <?= e($transaction['category_name']) ?>
                            </td>
                            <td><?= e($transaction['description']) ?></td>
                            <td>
                                <span class="badge bg-<?= $transaction['type'] == 'income' ? 'success' : 'danger' ?>">
                                    <?= $transaction['type'] == 'income' ? 'Gelir' : 'Gider' ?>
                                </span>
                            </td>
                            <td class="text-end <?= $transaction['type'] == 'income' ? 'text-success' : 'text-danger' ?>">
                                <?= $transaction['type'] == 'income' ? '+' : '-' ?>
                                <?= formatMoney($transaction['amount']) ?>
                            </td>
                            <td class="text-end">
                                <a href="edit-transaction.php?id=<?= $transaction['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="delete-transaction.php?id=<?= $transaction['id'] ?>" 
                                   class="btn btn-sm btn-danger btn-delete"
                                   data-item-name="bu işlemi">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>