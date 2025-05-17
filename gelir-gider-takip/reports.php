<?php
require_once 'includes/functions.php';
require_once 'includes/bank-functions.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Filtreleri al
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // Bu ayın başlangıcı
$endDate = $_GET['end_date'] ?? date('Y-m-t'); // Bu ayın sonu
$type = $_GET['type'] ?? '';
$categoryId = $_GET['category'] ?? '';

// Toplam değerleri hesapla
$totalIncome = calculateTotals($userId, 'income', $startDate, $endDate);
$totalExpense = calculateTotals($userId, 'expense', $startDate, $endDate);
$netBalance = $totalIncome - $totalExpense;

// Kategorileri getir
$categories = getCategories(null, $userId);

// Kategori bazlı özet
$sql = "SELECT 
            c.name as category_name,
            c.icon as category_icon,
            c.color as category_color,
            t.type,
            SUM(t.amount) as total_amount,
            COUNT(*) as transaction_count
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? 
        AND t.transaction_date BETWEEN ? AND ?";

$params = [$userId, $startDate, $endDate];

if ($type) {
    $sql .= " AND t.type = ?";
    $params[] = $type;
}

if ($categoryId) {
    $sql .= " AND c.id = ?";
    $params[] = $categoryId;
}

$sql .= " GROUP BY c.id, t.type ORDER BY total_amount DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categoryStats = $stmt->fetchAll();

// Aylık istatistikler
$sql = "SELECT 
            DATE_FORMAT(transaction_date, '%Y-%m') as month,
            type,
            SUM(amount) as total
        FROM transactions
        WHERE user_id = ?
        AND transaction_date BETWEEN ? AND ?
        GROUP BY month, type
        ORDER BY month";

$stmt = $pdo->prepare($sql);
$stmt->execute([$userId, $startDate, $endDate]);
$monthlyStats = $stmt->fetchAll();

// Aylık verileri hazırla
$months = [];
$incomeData = [];
$expenseData = [];

foreach ($monthlyStats as $stat) {
    if (!in_array($stat['month'], $months)) {
        $months[] = $stat['month'];
    }
    
    if ($stat['type'] == 'income') {
        $incomeData[$stat['month']] = $stat['total'];
    } else {
        $expenseData[$stat['month']] = $stat['total'];
    }
}

// Eksik ayları 0 olarak doldur
foreach ($months as $month) {
    if (!isset($incomeData[$month])) {
        $incomeData[$month] = 0;
    }
    if (!isset($expenseData[$month])) {
        $expenseData[$month] = 0;
    }
}

$pageTitle = 'Raporlar';
include 'includes/header.php';
?>

<h2 class="mb-4">Finansal Raporlar</h2>

<!-- Filtreler -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Başlangıç Tarihi</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e($startDate) ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">Bitiş Tarihi</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= e($endDate) ?>">
            </div>
            <div class="col-md-3">
                <label for="type" class="form-label">İşlem Tipi</label>
                <select class="form-select" id="type" name="type">
                    <option value="">Tümü</option>
                    <option value="income" <?= $type == 'income' ? 'selected' : '' ?>>Gelir</option>
                    <option value="expense" <?= $type == 'expense' ? 'selected' : '' ?>>Gider</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="category" class="form-label">Kategori</label>
                <select class="form-select" id="category" name="category">
                    <option value="">Tümü</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Filtrele</button>
                <a href="reports.php" class="btn btn-secondary">Sıfırla</a>
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
                <h2 class="card-text"><?= formatMoney($totalIncome) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <h5 class="card-title">Toplam Gider</h5>
                <h2 class="card-text"><?= formatMoney($totalExpense) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white <?= $netBalance >= 0 ? 'bg-info' : 'bg-warning' ?>">
            <div class="card-body">
                <h5 class="card-title">Net Bakiye</h5>
                <h2 class="card-text"><?= formatMoney($netBalance) ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Aylık Trend -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Aylık Gelir/Gider Trendi</h5>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Kategori Dağılımı -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Kategori Dağılımı</h5>
            </div>
            <div class="card-body">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Kategori Bazlı Özet -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Kategori Bazlı Özet</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th>İşlem Tipi</th>
                        <th>İşlem Sayısı</th>
                        <th class="text-end">Toplam Tutar</th>
                        <th class="text-end">Yüzde</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categoryStats as $stat): ?>
                    <tr>
                        <td>
                            <?php if (!empty($stat['category_icon'])): ?>
                            <i class="<?= e($stat['category_icon']) ?>" style="color: <?= e($stat['category_color']) ?>"></i>
                            <?php endif; ?>
                            <?= e($stat['category_name']) ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $stat['type'] == 'income' ? 'success' : 'danger' ?>">
                                <?= $stat['type'] == 'income' ? 'Gelir' : 'Gider' ?>
                            </span>
                        </td>
                        <td><?= $stat['transaction_count'] ?></td>
                        <td class="text-end"><?= formatMoney($stat['total_amount']) ?></td>
                        <td class="text-end">
                            <?php
                            $total = $stat['type'] == 'income' ? $totalIncome : $totalExpense;
                            echo $total > 0 ? round(($stat['total_amount'] / $total) * 100, 1) . '%' : '0%';
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($categoryStats)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-3">Seçili filtrelere göre işlem bulunamadı.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Aylık trend grafiği
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map(function($month) {
                list($year, $monthNum) = explode('-', $month);
                return date('M Y', strtotime("$year-$monthNum-01"));
            }, $months)) ?>,
            datasets: [
                {
                    label: 'Gelir',
                    data: <?= json_encode(array_values($incomeData)) ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: '#28a745',
                    borderWidth: 1
                },
                {
                    label: 'Gider',
                    data: <?= json_encode(array_values($expenseData)) ?>,
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: '#dc3545',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₺' + value.toLocaleString('tr-TR');
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ₺' + context.parsed.y.toLocaleString('tr-TR');
                        }
                    }
                }
            }
        }
    });
    
    // Kategori dağılım grafiği
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    
    // Kategorileri ve renkleri hazırla
    const categoryNames = [];
    const categoryAmounts = [];
    const categoryColors = [];
    
    <?php
    // En çok 10 kategori göster
    $counter = 0;
    foreach ($categoryStats as $stat):
        if ($counter++ < 10):
    ?>
    categoryNames.push('<?= addslashes($stat['category_name']) ?> (<?= $stat['type'] == 'income' ? 'Gelir' : 'Gider' ?>)');
    categoryAmounts.push(<?= $stat['total_amount'] ?>);
    categoryColors.push('<?= !empty($stat['category_color']) ? $stat['category_color'] : ($stat['type'] == 'income' ? '#28a745' : '#dc3545') ?>');
    <?php 
        endif;
    endforeach; 
    ?>
    
    new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: categoryNames,
            datasets: [{
                data: categoryAmounts,
                backgroundColor: categoryColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return '₺' + value.toLocaleString('tr-TR') + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>