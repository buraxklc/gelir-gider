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
        'day_of_week' => $_POST['day_of_week'] ?? null,
        'day_of_month' => $_POST['day_of_month'] ?? null,
        'start_date' => $_POST['start_date'] ?? '',
        'end_date' => $_POST['end_date'] ?? null,
        'notification_days' => intval($_POST['notification_days'] ?? 3)
    ];
    
    // Validasyon
    if (empty($data['type']) || empty($data['category_id']) || $data['amount'] <= 0 || 
        empty($data['frequency']) || empty($data['start_date'])) {
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
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-arrow-repeat"></i> Yeni Tekrarlayan İşlem
                </h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
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
                            <label for="bank_account_id" class="form-label">Banka Hesabı (Opsiyonel)</label>
                            <select class="form-select" id="bank_account_id" name="bank_account_id">
                                <option value="">Seçin</option>
                                <?php foreach ($bankAccounts as $account): ?>
                                <option value="<?= $account['id'] ?>">
                                    <?= e($account['bank_name']) ?> - <?= e($account['account_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3 mt-3">
                        <label for="description" class="form-label">Açıklama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="description" name="description" required>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="frequency" class="form-label">Tekrar Sıklığı <span class="text-danger">*</span></label>
                            <select class="form-select" id="frequency" name="frequency" required onchange="updateFrequencyOptions()">
                                <option value="">Seçin</option>
                                <option value="daily">Günlük</option>
                                <option value="weekly">Haftalık</option>
                                <option value="monthly">Aylık</option>
                                <option value="yearly">Yıllık</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="frequency_interval" class="form-label">Tekrar Aralığı</label>
                            <input type="number" class="form-control" id="frequency_interval" name="frequency_interval" 
                                   value="1" min="1">
                        </div>
                        
                        <div class="col-md-4" id="dayOptionsContainer" style="display: none;">
                            <!-- Gün seçenekleri buraya gelecek -->
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Başlangıç Tarihi <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">Bitiş Tarihi (Opsiyonel)</label>
                            <input type="date" class="form-control" id="end_date" name="end_date">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="notification_days" class="form-label">Hatırlatma (gün önce)</label>
                            <input type="number" class="form-control" id="notification_days" name="notification_days" 
                                   value="3" min="0">
                        </div>
                    </div>
                    
                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <a href="recurring-transactions.php" class="btn btn-secondary">İptal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Tekrarlayan İşlemi Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Yardım -->
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title">Tekrar Sıklığı Açıklamaları</h6>
                <ul class="small mb-0">
                    <li><strong>Günlük:</strong> Her gün veya belirtilen gün aralığında tekrarlar</li>
                    <li><strong>Haftalık:</strong> Her hafta belirtilen günde tekrarlar</li>
                    <li><strong>Aylık:</strong> Her ay belirtilen günde tekrarlar</li>
                    <li><strong>Yıllık:</strong> Her yıl aynı tarihte tekrarlar</li>
                </ul>
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

// Kategorileri güncelle devamı
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

function updateFrequencyOptions() {
   const frequency = document.getElementById('frequency').value;
   const dayOptionsContainer = document.getElementById('dayOptionsContainer');
   const intervalLabel = document.querySelector('label[for="frequency_interval"]');
   
   dayOptionsContainer.innerHTML = '';
   dayOptionsContainer.style.display = 'none';
   
   switch (frequency) {
       case 'daily':
           intervalLabel.textContent = 'Her kaç günde bir?';
           break;
           
       case 'weekly':
           intervalLabel.textContent = 'Her kaç haftada bir?';
           dayOptionsContainer.style.display = 'block';
           dayOptionsContainer.innerHTML = `
               <label for="day_of_week" class="form-label">Haftanın Günü</label>
               <select class="form-select" id="day_of_week" name="day_of_week">
                   <option value="1">Pazartesi</option>
                   <option value="2">Salı</option>
                   <option value="3">Çarşamba</option>
                   <option value="4">Perşembe</option>
                   <option value="5">Cuma</option>
                   <option value="6">Cumartesi</option>
                   <option value="7">Pazar</option>
               </select>
           `;
           break;
           
       case 'monthly':
           intervalLabel.textContent = 'Her kaç ayda bir?';
           dayOptionsContainer.style.display = 'block';
           dayOptionsContainer.innerHTML = `
               <label for="day_of_month" class="form-label">Ayın Günü</label>
               <input type="number" class="form-control" id="day_of_month" name="day_of_month" 
                      min="1" max="31" value="1">
           `;
           break;
           
       case 'yearly':
           intervalLabel.textContent = 'Her kaç yılda bir?';
           break;
           
       default:
           intervalLabel.textContent = 'Tekrar Aralığı';
   }
}
</script>

<?php include 'includes/footer.php'; ?>