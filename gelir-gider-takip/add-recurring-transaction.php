<?php
require_once 'includes/functions.php';
require_once 'includes/recurring-functions.php';
require_once 'includes/bank-functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Kategorileri getir
$incomeCategories = getCategories('income', $userId);
$expenseCategories = getCategories('expense', $userId);

// Banka hesaplarını getir
$bankAccounts = getBankAccounts($userId);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'type' => $_POST['type'] ?? '',
        'category_id' => $_POST['category_id'] ?? '',
        'bank_account_id' => $_POST['bank_account_id'] ?? null,
        'amount' => floatval($_POST['amount'] ?? 0),
        'description' => trim($_POST['description'] ?? ''),
        'frequency' => $_POST['frequency'] ?? '',
        'frequency_interval' => intval($_POST['frequency_interval'] ?? 1),
        'start_date' => $_POST['start_date'] ?? '',
        'end_date' => $_POST['end_date'] ?? null,
        'notification_days' => intval($_POST['notification_days'] ?? 3)
    ];
    
    // Validasyon
    if (empty($data['type']) || empty($data['category_id']) || $data['amount'] <= 0 || 
        empty($data['frequency']) || empty($data['start_date']) || empty($data['description'])) {
        $error = 'Lütfen zorunlu alanları doldurun.';
    } else {
        // Tekrarlayan işlemi ekle
        if (addRecurringTransaction($userId, $data)) {
            $_SESSION['success_message'] = 'Tekrarlayan işlem başarıyla eklendi.';
            header('Location: recurring-transactions.php');
            exit;
        } else {
            $error = 'İşlem eklenirken bir hata oluştu.';
        }
    }
}

$pageTitle = 'Yeni Tekrarlayan İşlem';
include 'includes/header.php';

// CSS dosyası bulunamadıysa geçici CSS ekle
if (!file_exists('assets/css/style.css')):
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
<style>
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    background-color: #f9fafb;
    color: #1f2937;
    line-height: 1.5;
}
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem;
}
.card {
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid rgba(0,0,0,0.05);
    margin-bottom: 1rem;
}
.card-header {
    background-color: rgba(249, 250, 251, 0.8);
    border-bottom: 1px solid rgba(0,0,0,0.05);
}
.btn-primary {
    background-color: #4f46e5;
    border-color: #4338ca;
}
.btn-primary:hover {
    background-color: #4338ca;
}
.btn-secondary {
    background-color: #6b7280;
    border-color: #4b5563;
}
.btn-secondary:hover {
    background-color: #4b5563;
}
.form-control, .form-select {
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
}
.form-control:focus, .form-select:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
}
.form-label {
    font-weight: 500;
    color: #4b5563;
}
.text-danger {
    color: #dc2626 !important;
}
.alert-danger {
    background-color: #fee2e2;
    border-color: #fecaca;
    color: #b91c1c;
}
</style>
<?php endif; ?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-arrow-repeat"></i> Yeni Tekrarlayan İşlem
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="type" class="form-label">İşlem Tipi <span class="text-danger">*</span></label>
                                <select class="form-select" id="type" name="type" required onchange="updateCategories()">
                                    <option value="">Seçin</option>
                                    <option value="income">Gelir</option>
                                    <option value="expense">Gider</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Önce işlem tipi seçin</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label for="amount" class="form-label">Tutar <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₺</span>
                                    <input type="number" class="form-control" id="amount" name="amount" 
                                           step="0.01" min="0.01" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="description" class="form-label">Açıklama <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="description" name="description" required>
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                               <label for="bank_account_id" class="form-label">Banka Hesabı <span class="text-danger">*</span></label>
                               <select class="form-select" id="bank_account_id" name="bank_account_id" required>
                                <option value="">Seçin</option>
                                <?php foreach ($bankAccounts as $account): ?>
                                <option value="<?= $account['id'] ?>">
                                    <?= e($account['bank_name']) ?> - <?= e($account['account_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            </div>
                            <div class="col-md-6">
                                <label for="frequency" class="form-label">Tekrar Sıklığı <span class="text-danger">*</span></label>
                                <select class="form-select" id="frequency" name="frequency" required>
                                    <option value="">Seçin</option>
                                    <option value="daily">Günlük</option>
                                    <option value="weekly">Haftalık</option>
                                    <option value="monthly">Aylık</option>
                                    <option value="yearly">Yıllık</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <label for="frequency_interval" class="form-label">Tekrar Aralığı</label>
                                <input type="number" class="form-control" id="frequency_interval" name="frequency_interval" 
                                       value="1" min="1">
                                <small class="text-muted">Kaç zamanda bir tekrarlansın?</small>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="start_date" class="form-label">Başlangıç Tarihi <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="end_date" class="form-label">Bitiş Tarihi (Opsiyonel)</label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-12">
                                <label for="notification_days" class="form-label">Hatırlatma (gün önce)</label>
                                <input type="number" class="form-control" id="notification_days" name="notification_days" 
                                       value="3" min="0">
                                <small class="text-muted">0 = hatırlatma yapma</small>
                            </div>
                        </div>
                        
                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <a href="recurring-transactions.php" class="btn btn-secondary">İptal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Kaydet
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
                        <li><strong>Günlük:</strong> Her gün veya belirtilen gün aralığında tekrarlar</li>
                        <li><strong>Haftalık:</strong> Her hafta aynı günde tekrarlar</li>
                        <li><strong>Aylık:</strong> Her ay aynı günde tekrarlar</li>
                        <li><strong>Yıllık:</strong> Her yıl aynı tarihte tekrarlar</li>
                        <li><strong>Tekrar Aralığı:</strong> Örneğin, "2" seçerseniz her 2 günde/haftada/ayda/yılda bir tekrarlar</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Kategorileri JSON olarak hazırla
const categories = {
    income: <?php echo json_encode($incomeCategories); ?>,
    expense: <?php echo json_encode($expenseCategories); ?>
};

// Kategorileri güncelle
function updateCategories() {
    const type = document.getElementById('type').value;
    const categorySelect = document.getElementById('category_id');
    
    categorySelect.innerHTML = '<option value="">Kategori seçin</option>';
    
    if (type && categories[type]) {
        categories[type].forEach(category => {
            const option = document.createElement('option');
            option.value = category.id;
            option.textContent = category.name;
            categorySelect.appendChild(option);
        });
    }
}

// Form doğrulama
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>

<?php include 'includes/footer.php'; ?>