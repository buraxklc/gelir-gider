<?php
require_once 'includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// İşlem ID'sini al
$transactionId = $_GET['id'] ?? '';

if (!$transactionId) {
    header('Location: dashboard.php');
    exit;
}

// İşlemi getir
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
$stmt->execute([$transactionId, $userId]);
$transaction = $stmt->fetch();

if (!$transaction) {
    header('Location: dashboard.php');
    exit;
}

// Kategorileri getir
$incomeCategories = getCategories('income', $userId);
$expenseCategories = getCategories('expense', $userId);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['type'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $description = $_POST['description'] ?? '';
    $transaction_date = $_POST['transaction_date'] ?? '';
    
    // Validasyon
    if (!$type || !$category_id || !$amount || !$transaction_date) {
        $error = 'Lütfen zorunlu alanları doldurun!';
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $error = 'Geçerli bir tutar girin!';
    } else {
        // İşlemi güncelle
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET category_id = ?, type = ?, amount = ?, description = ?, transaction_date = ?
            WHERE id = ? AND user_id = ?
        ");
        
        if ($stmt->execute([$category_id, $type, $amount, $description, $transaction_date, $transactionId, $userId])) {
            $success = 'İşlem başarıyla güncellendi!';
            // Güncel veriyi getir
            $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
            $stmt->execute([$transactionId, $userId]);
            $transaction = $stmt->fetch();
        } else {
            $error = 'İşlem güncellenirken bir hata oluştu!';
        }
    }
}

$pageTitle = 'İşlem Düzenle';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">İşlem Düzenle</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="type" class="form-label">İşlem Tipi</label>
                                <select class="form-select" id="type" name="type" required onchange="updateCategories()">
                                    <option value="">Seçin</option>
                                    <option value="income" <?= $transaction['type'] == 'income' ? 'selected' : '' ?>>Gelir</option>
                                    <option value="expense" <?= $transaction['type'] == 'expense' ? 'selected' : '' ?>>Gider</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Kategori</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Kategori seçin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Tutar</label>
                                <div class="input-group">
                                    <span class="input-group-text">₺</span>
                                    <input type="number" class="form-control" id="amount" name="amount" 
                                           value="<?= e($transaction['amount']) ?>" step="0.01" min="0.01" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="transaction_date" class="form-label">Tarih</label>
                                <input type="date" class="form-control" id="transaction_date" name="transaction_date" 
                                       value="<?= e($transaction['transaction_date']) ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= e($transaction['description']) ?></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="dashboard.php" class="btn btn-secondary">İptal</a>
                        <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Kategorileri JSON olarak hazırla
const categories = {
    income: <?= json_encode($incomeCategories) ?>,
    expense: <?= json_encode($expenseCategories) ?>
};

function updateCategories() {
    const type = document.getElementById('type').value;
    const categorySelect = document.getElementById('category_id');
    
    // Kategori select'ini temizle
    categorySelect.innerHTML = '<option value="">Kategori seçin</option>';
    
    if (type && categories[type]) {
        categories[type].forEach(category => {
            const option = document.createElement('option');
            option.value = category.id;
            
            // İkon ve renk ekle
            const icon = category.icon ? category.icon.replace('bi-', '') : '';
            option.innerHTML = `${icon} ${category.name}`;
            option.style.color = category.color;
            
            categorySelect.appendChild(option);
            
            // Alt kategorileri ekle
            if (category.subcategories) {
                category.subcategories.forEach(subcat => {
                    const subOption = document.createElement('option');
                    subOption.value = subcat.id;
                    subOption.innerHTML = `&nbsp;&nbsp;&nbsp;└ ${subcat.name}`;
                    subOption.style.color = subcat.color;
                    categorySelect.appendChild(subOption);
                });
            }
        });
    }
}

// Sayfa yüklendiğinde çalıştır
document.addEventListener('DOMContentLoaded', function() {
    updateCategories();
});
</script>

<?php include 'includes/footer.php'; ?>