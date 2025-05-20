<?php
require_once 'includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Filtreleri al
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$type = $_GET['type'] ?? '';
$categoryId = $_GET['category_id'] ?? '';
$search = $_GET['search'] ?? '';

// Filtreleri uygula
$filters = [
    'start_date' => $startDate,
    'end_date' => $endDate,
    'type' => $type,
    'category_id' => $categoryId,
    'search' => $search
];

// Kategorileri getir (filtre için)
$categories = getCategories(null, $userId);

// Filtrelenmiş işlemleri getir
$transactions = getTransactions($userId, $filters);

// Toplam değerleri hesapla - HATA DÜZELTİLDİ
$filteredIncome = 0;
$filteredExpense = 0;

foreach ($transactions as $transaction) {
    if ($transaction['type'] == 'income') {
        $filteredIncome += $transaction['amount'];
    } else {
        $filteredExpense += $transaction['amount'];
    }
}

$filteredNet = $filteredIncome - $filteredExpense;

$pageTitle = 'Tüm İşlemler';
include 'includes/header.php';

// CSS dosyası bulunamadıysa geçici CSS ekle
if (!file_exists('assets/css/style.css')):
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
<style>
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background-color: #f9fafb;
    color: #1f2937;
}
.card {
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid rgba(0,0,0,0.05);
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
.table-hover tbody tr:hover {
    background-color: #f9fafb;
}
.text-success { color: #10b981 !important; }
.text-danger { color: #ef4444 !important; }
.bg-success { background-color: #10b981 !important; }
.bg-danger { background-color: #ef4444 !important; }
.badge { font-weight: 500; padding: 0.35em 0.65em; }

/* Arama kutusu stilleri */
.search-box {
    position: relative;
}

.search-box input {
    padding-left: 16px;
    padding-right: 45px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 16px;
    height: 48px;
    transition: all 0.2s ease;
}

.search-box input:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    outline: none;
}

.search-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    font-size: 18px;
    pointer-events: none;
}

.badge.bg-info {
    background-color: #3b82f6 !important;
}

@media (max-width: 768px) {
    .search-box input {
        font-size: 16px;
    }
}

.btn-outline-secondary:hover {
    background-color: #6b7280;
    border-color: #6b7280;
    color: white;
}
</style>
<?php endif; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Tüm İşlemler</h2>
        <a href="add-transaction.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Yeni İşlem
        </a>
    </div>
    
    <!-- Filtreler ve Arama -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <!-- Arama kutusu -->
                <div class="col-md-12 mb-3">
                    <div class="search-box position-relative">
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="İşlem açıklaması veya kategori adında ara...">
                        <i class="bi bi-search search-icon"></i>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Başlangıç Tarihi</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">Bitiş Tarihi</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                </div>
                <div class="col-md-2">
                    <label for="type" class="form-label">İşlem Tipi</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">Tümü</option>
                        <option value="income" <?php echo $type == 'income' ? 'selected' : ''; ?>>Gelir</option>
                        <option value="expense" <?php echo $type == 'expense' ? 'selected' : ''; ?>>Gider</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="category_id" class="form-label">Kategori</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">Tümü</option>
                        <optgroup label="Gelir Kategorileri">
                            <?php foreach ($categories as $category): ?>
                                <?php if ($category['type'] == 'income'): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $categoryId == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Gider Kategorileri">
                            <?php foreach ($categories as $category): ?>
                                <?php if ($category['type'] == 'expense'): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $categoryId == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block w-100">
                        <i class="bi bi-funnel"></i>
                    </button>
                </div>
                
                <!-- Filtreleri temizle butonu -->
                <div class="col-12">
                    <a href="transactions.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle"></i> Filtreleri Temizle
                    </a>
                    <?php if ($search): ?>
                    <span class="badge bg-info ms-2">
                        <i class="bi bi-search"></i> "<?php echo htmlspecialchars($search); ?>" için sonuçlar
                    </span>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Özet Kartları -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Toplam Gelir</h5>
                    <h3 class="card-text"><?php echo formatMoney($filteredIncome); ?></h3>
                    <p class="card-text">Filtrelenmiş sonuçlar</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h5 class="card-title">Toplam Gider</h5>
                    <h3 class="card-text"><?php echo formatMoney($filteredExpense); ?></h3>
                    <p class="card-text">Filtrelenmiş sonuçlar</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-<?php echo $filteredNet >= 0 ? 'info' : 'warning'; ?>">
                <div class="card-body">
                    <h5 class="card-title">Net</h5>
                    <h3 class="card-text"><?php echo formatMoney($filteredNet); ?></h3>
                    <p class="card-text">Filtrelenmiş sonuçlar</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- İşlemler Tablosu -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($transactions)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted mb-3"></i>
                    <h5 class="text-muted">Gösterilecek işlem bulunamadı</h5>
                    <p class="text-muted mb-4">Filtreleri değiştirerek veya yeni işlem ekleyerek başlayabilirsiniz.</p>
                    <a href="add-transaction.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Yeni İşlem Ekle
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
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <i class="bi bi-calendar3"></i>
                                    <?php echo date('d.m.Y', strtotime($transaction['transaction_date'])); ?>
                                </td>
                                <td>
                                    <?php if (isset($transaction['category_icon']) && isset($transaction['category_color'])): ?>
                                        <i class="<?php echo htmlspecialchars($transaction['category_icon']); ?>" 
                                           style="color: <?php echo htmlspecialchars($transaction['category_color']); ?>"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($transaction['category_name'] ?? 'Kategori Yok'); ?>
                                </td>
                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $transaction['type'] == 'income' ? 'success' : 'danger'; ?>">
                                        <i class="bi bi-<?php echo $transaction['type'] == 'income' ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                        <?php echo $transaction['type'] == 'income' ? 'Gelir' : 'Gider'; ?>
                                    </span>
                                </td>
                                <td class="text-end <?php echo $transaction['type'] == 'income' ? 'text-success' : 'text-danger'; ?>">
                                    <strong>
                                        <?php echo $transaction['type'] == 'income' ? '+' : '-'; ?>
                                        <?php echo formatMoney($transaction['amount']); ?>
                                    </strong>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit-transaction.php?id=<?php echo $transaction['id']; ?>" 
                                           class="btn btn-outline-primary" title="Düzenle">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete-transaction.php?id=<?php echo $transaction['id']; ?>&redirect=transactions.php" 
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
                
                <!-- İşlem sayısı bilgisi -->
                <div class="mt-3 text-muted small">
                    Toplam <?php echo count($transactions); ?> işlem gösteriliyor.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Dışa Aktarma -->
    <div class="mt-3">
        <div class="dropdown d-inline-block">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown">
                <i class="bi bi-download"></i> Dışa Aktar
            </button>
            <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                <li><a class="dropdown-item" href="export.php?format=csv&type=<?php echo $type; ?>&category_id=<?php echo $categoryId; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&search=<?php echo urlencode($search); ?>">CSV</a></li>
                <li><a class="dropdown-item" href="export.php?format=excel&type=<?php echo $type; ?>&category_id=<?php echo $categoryId; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&search=<?php echo urlencode($search); ?>">Excel</a></li>
                <li><a class="dropdown-item" href="export.php?format=pdf&type=<?php echo $type; ?>&category_id=<?php echo $categoryId; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&search=<?php echo urlencode($search); ?>">PDF</a></li>
            </ul>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary ms-2">
            <i class="bi bi-arrow-left"></i> Kontrol Paneline Dön
        </a>
    </div>
</div>

<script>
// Gerçek zamanlı arama (debounce ile)
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    let searchTimeout;
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.trim();
            
            // 500ms bekle, sonra arama yap
            searchTimeout = setTimeout(() => {
                if (searchTerm.length >= 2 || searchTerm.length === 0) {
                    // Mevcut URL'i al ve search parametresini güncelle
                    const url = new URL(window.location);
                    if (searchTerm) {
                        url.searchParams.set('search', searchTerm);
                    } else {
                        url.searchParams.delete('search');
                    }
                    
                    // Sayfayı yenile
                    window.location.href = url.toString();
                }
            }, 500);
        });
        
        // Enter tuşuna basıldığında hemen ara
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                
                const url = new URL(window.location);
                const searchTerm = this.value.trim();
                
                if (searchTerm) {
                    url.searchParams.set('search', searchTerm);
                } else {
                    url.searchParams.delete('search');
                }
                
                window.location.href = url.toString();
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>