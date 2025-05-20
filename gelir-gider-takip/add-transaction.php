<?php
require_once 'includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Kategorileri getir
$incomeCategories = getCategoryTree('income', $userId);
$expenseCategories = getCategoryTree('expense', $userId);

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF koruması için token kontrolü (opsiyonel)
    if (!isset($_SESSION['form_token']) || $_SESSION['form_token'] !== $_POST['form_token']) {
        // Token oluştur
        $_SESSION['form_token'] = bin2hex(random_bytes(32));
    } else {
        // Form verilerini al
        $type = trim($_POST['type'] ?? '');
        $category_id = trim($_POST['category_id'] ?? '');
        $amount = trim($_POST['amount'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $transaction_date = trim($_POST['transaction_date'] ?? '');
        
        // Validasyon
        $errors = [];
        
        if (empty($type) || !in_array($type, ['income', 'expense'])) {
            $errors[] = 'Geçerli bir işlem tipi seçin.';
        }
        
        if (empty($category_id) || !is_numeric($category_id)) {
            $errors[] = 'Geçerli bir kategori seçin.';
        }
        
        if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
            $errors[] = 'Geçerli bir tutar girin.';
        }
        
        if (empty($transaction_date)) {
            $errors[] = 'Tarih seçin.';
        }
        
        // Hata yoksa işlemi kaydet
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                // İşlemi ekle
                $stmt = $pdo->prepare("
                    INSERT INTO transactions (user_id, category_id, type, amount, description, transaction_date, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $success = $stmt->execute([
                    $userId,
                    $category_id,
                    $type,
                    $amount,
                    $description,
                    $transaction_date
                ]);
                
                if ($success) {
                    $lastInsertId = $pdo->lastInsertId();
                    
                
                    
                    $pdo->commit();
                    
                    // Başarı mesajı
                    $success = 'İşlem başarıyla eklendi!';
                    if (isset($achievementMessage)) {
                        $success .= $achievementMessage;
                    }
                    
                    // Formu temizle
                    $_POST = [];
                } else {
                    throw new Exception('İşlem eklenirken bir hata oluştu.');
                }
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = $e->getMessage();
                error_log("Transaction error: " . $e->getMessage());
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

// CSRF token oluştur
if (!isset($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
}

$pageTitle = 'Yeni İşlem Ekle';
include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-plus-circle"></i> Yeni İşlem Ekle
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle"></i> <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?= e($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="form_token" value="<?= $_SESSION['form_token'] ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="type" class="form-label">İşlem Tipi <span class="text-danger">*</span></label>
                                <select class="form-select" id="type" name="type" required onchange="updateCategories()">
                                    <option value="">Seçin</option>
                                    <option value="income" <?= ($_POST['type'] ?? '') == 'income' ? 'selected' : '' ?>>
                                        <i class="bi bi-arrow-up-circle text-success"></i> Gelir
                                    </option>
                                    <option value="expense" <?= ($_POST['type'] ?? '') == 'expense' ? 'selected' : '' ?>>
                                        <i class="bi bi-arrow-down-circle text-danger"></i> Gider
                                    </option>
                                </select>
                                <div class="invalid-feedback">Lütfen işlem tipi seçin.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Önce işlem tipi seçin</option>
                                </select>
                                <div class="invalid-feedback">Lütfen kategori seçin.</div>
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label for="amount" class="form-label">Tutar <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₺</span>
                                    <input type="number" class="form-control" id="amount" name="amount" 
                                           value="<?= e($_POST['amount'] ?? '') ?>" 
                                           step="0.01" min="0.01" required
                                           placeholder="0.00">
                                    <div class="invalid-feedback">Lütfen geçerli bir tutar girin.</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="transaction_date" class="form-label">Tarih <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="transaction_date" name="transaction_date" 
                                       value="<?= e($_POST['transaction_date'] ?? date('Y-m-d')) ?>" 
                                       max="<?= date('Y-m-d') ?>" required>
                                <div class="invalid-feedback">Lütfen tarih seçin.</div>
                            </div>
                        </div>
                        
                        <div class="mb-3 mt-3">
                            <label for="description" class="form-label">Açıklama</label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="İşlem hakkında detay ekleyin (opsiyonel)"><?= e($_POST['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> İptal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> İşlemi Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Yardım kartı -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="bi bi-info-circle"></i> Yardım
                    </h6>
                    <ul class="small mb-0">
                        <li>İşlem tipini seçtikten sonra kategori listesi güncellenecektir.</li>
                        <li>Tutar alanına ondalıklı sayı girebilirsiniz (örn: 123.45).</li>
                        <li>Açıklama alanı opsiyoneldir, boş bırakabilirsiniz.</li>
                        <li><span class="text-danger">*</span> işaretli alanlar zorunludur.</li>
                    </ul>
                </div>
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
            
            // Kategori adını göster
            let categoryName = category.name;
            
            // İkon varsa ekle
            if (category.icon) {
                categoryName = category.name;
            }
            
            option.textContent = categoryName;
            
            // Renk varsa uygula
            if (category.color) {
                option.style.color = category.color;
            }
            
            categorySelect.appendChild(option);
            
            // Alt kategorileri ekle
            if (category.subcategories && category.subcategories.length > 0) {
                category.subcategories.forEach(subcat => {
                    const subOption = document.createElement('option');
                    subOption.value = subcat.id;
                    subOption.textContent = '  └ ' + subcat.name;
                    
                    if (subcat.color) {
                        subOption.style.color = subcat.color;
                    }
                    
                    categorySelect.appendChild(subOption);
                });
            }
        });
    }
}

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    // Form validasyonu
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Eğer tip seçiliyse kategorileri güncelle
    const selectedType = '<?= $_POST['type'] ?? '' ?>';
    const selectedCategory = '<?= $_POST['category_id'] ?? '' ?>';
    
    if (selectedType) {
        updateCategories();
        if (selectedCategory) {
            document.getElementById('category_id').value = selectedCategory;
        }
    }
    
    // Tutar formatlaması
    const amountInput = document.getElementById('amount');
    amountInput.addEventListener('blur', function() {
        if (this.value) {
            this.value = parseFloat(this.value).toFixed(2);
        }
    });
    
    // Enter tuşu ile form gönderme
    document.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
            const form = document.querySelector('form');
            if (form.checkValidity()) {
                form.submit();
            } else {
                form.classList.add('was-validated');
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>