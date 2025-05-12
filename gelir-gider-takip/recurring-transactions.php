<?php
require_once 'includes/functions.php';
require_once 'includes/recurring-functions.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Tekrarlayan işlemleri getir
$recurringTransactions = getRecurringTransactions($userId);

$pageTitle = 'Tekrarlayan İşlemler';
include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Tekrarlayan İşlemler</h2>
    <a href="add-recurring-transaction.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Yeni Tekrarlayan İşlem
    </a>
</div>

<?php if (isset($_SESSION['success_message'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<div class="row">
    <?php if (empty($recurringTransactions)): ?>
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-arrow-repeat display-1 text-muted mb-3"></i>
                <h5>Henüz tekrarlayan işlem eklemediniz</h5>
                <p class="text-muted">Düzenli gelir ve giderlerinizi otomatik olarak takip edin.</p>
                <a href="add-recurring-transaction.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> İlk Tekrarlayan İşlemi Ekle
                </a>
            </div>
        </div>
    </div>
    <?php else: ?>
        <?php foreach ($recurringTransactions as $transaction): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="card-title mb-1">
                                <?php if (isset($transaction['category_icon']) && isset($transaction['category_color'])): ?>
                                <i class="<?php echo htmlspecialchars($transaction['category_icon'], ENT_QUOTES, 'UTF-8'); ?>" 
                                   style="color: <?php echo htmlspecialchars($transaction['category_color'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($transaction['description'], ENT_QUOTES, 'UTF-8'); ?>
                            </h5>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($transaction['category_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="edit-recurring-transaction.php?id=<?php echo $transaction['id']; ?>">
                                        <i class="bi bi-pencil"></i> Düzenle
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="toggle-recurring-transaction.php?id=<?php echo $transaction['id']; ?>">
                                        <i class="bi bi-pause-circle"></i> 
                                        <?php echo $transaction['is_active'] ? 'Duraklat' : 'Aktifleştir'; ?>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="delete-recurring-transaction.php?id=<?php echo $transaction['id']; ?>"
                                       onclick="return confirm('Bu tekrarlayan işlemi silmek istediğinize emin misiniz?')">
                                        <i class="bi bi-trash"></i> Sil
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <h4 class="mb-0 <?php echo $transaction['type'] == 'income' ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $transaction['type'] == 'income' ? '+' : '-'; ?>
                            <?php echo formatMoney($transaction['amount']); ?>
                        </h4>
                        <small class="text-muted">
                            <?php
                            $frequencyLabels = [
                                'daily' => 'Günlük',
                                'weekly' => 'Haftalık',
                                'monthly' => 'Aylık',
                                'yearly' => 'Yıllık'
                            ];
                            echo $frequencyLabels[$transaction['frequency']] ?? $transaction['frequency'];
                            ?>
                        </small>
                    </div>
                    
                    <div class="mb-2">
                        <small class="text-muted d-block">Sonraki Ödeme:</small>
                        <strong><?php echo date('d.m.Y', strtotime($transaction['next_occurrence'])); ?></strong>
                        <?php
                        $daysUntil = ceil((strtotime($transaction['next_occurrence']) - time()) / 86400);
                        if ($daysUntil == 0): ?>
                            <span class="badge bg-warning">Bugün</span>
                        <?php elseif ($daysUntil == 1): ?>
                            <span class="badge bg-info">Yarın</span>
                        <?php elseif ($daysUntil <= 7): ?>
                            <span class="badge bg-primary"><?php echo $daysUntil; ?> gün sonra</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (isset($transaction['bank_account_name']) && $transaction['bank_account_name']): ?>
                    <div class="mb-2">
                        <small class="text-muted">Banka Hesabı:</small>
                        <small><?php echo htmlspecialchars($transaction['bank_account_name'], ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!$transaction['is_active']): ?>
                    <div class="alert alert-warning mb-0 py-1 px-2">
                        <small><i class="bi bi-pause-circle"></i> Duraklatıldı</small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>