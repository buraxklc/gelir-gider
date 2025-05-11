<?php
require_once 'includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Filtreleri al
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$type = $_GET['type'] ?? '';
$categoryId = $_GET['category'] ?? '';

// Toplam gelir ve gider
$totalIncome = calculateTotals($userId, 'income', $startDate, $endDate);
$totalExpense = calculateTotals($userId, 'expense', $startDate, $endDate);
$netBalance = $totalIncome - $totalExpense;

// Kategori bazlı özet
$sql = "SELECT 
            c.name as category_name,
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

// Kategoriler
$categories = getCategories(null, $userId);

$pageTitle = 'Raporlar';
include 'includes/header.php';
?>

<h2 class="mb-4">Raporlar</h2>

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
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary d-block w-100">Filtrele</button>
            </div>
        </form>
    </div>
</div>

<!-- Özet Kartları -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title">Gelir</h5>
                <h3 class="card-text"><?= formatMoney($totalIncome) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <h5 class="card-title">Gider</h5>
                <h3 class="card-text"><?= formatMoney($totalExpense) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-<?= $netBalance >= 0 ? 'info' : 'warning' ?>">
            <div class="card-body">
                <h5 class="card-title">Net</h5>
                <h3 class="card-text"><?= formatMoney($netBalance) ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Kategori Bazlı Dağılım -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Kategori Bazlı Dağılım</h5>
            </div>
            <div class="card-body">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Aylık Trend -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Aylık Trend</h5>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Kategori Detayları -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Kategori Detayları</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th>Tip</th>
                        <th class="text-end">İşlem Sayısı</th>
                        <th class="text-end">Toplam Tutar</th>
                        <th class="text-end">Ortalama</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categoryStats as $stat): ?>
                    <tr>
                        <td><?= e($stat['category_name']) ?></td>
                        <td>
                            <span class="badge bg-<?= $stat['type'] == 'income' ? 'success' : 'danger' ?>">
                                <?= $stat['type'] == 'income' ? 'Gelir' : 'Gider' ?>
                            </span>
                        </td>
                        <td class="text-end"><?= $stat['transaction_count'] ?></td>
                        <td class="text-end"><?= formatMoney($stat['total_amount']) ?></td>
                        <td class="text-end"><?= formatMoney($stat['total_amount'] / $stat['transaction_count']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Kategori grafiği
const categoryData = <?= json_encode($categoryStats) ?>;
const categoryLabels = categoryData.map(item => item.category_name);
const categoryValues = categoryData.map(item => item.total_amount);
const categoryColors = categoryData.map(item => 
    item.type === 'income' ? 'rgba(75, 192, 192, 0.8)' : 'rgba(255, 99, 132, 0.8)'
);

new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: categoryLabels,
        datasets: [{
            data: categoryValues,
            backgroundColor: categoryColors
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
            }
        }
    }
});

// Aylık trend grafiği
const monthlyData = <?= json_encode($monthlyStats) ?>;
const months = [...new Set(monthlyData.map(item => item.month))];
const incomeData = [];
const expenseData = [];

months.forEach(month => {
    const monthIncome = monthlyData.find(item => item.month === month && item.type === 'income');
    const monthExpense = monthlyData.find(item => item.month === month && item.type === 'expense');
    
    incomeData.push(monthIncome ? monthIncome.total : 0);
    expenseData.push(monthExpense ? monthExpense.total : 0);
});

new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: months.map(month => {
            const [year, monthNum] = month.split('-');
            return new Date(year, monthNum - 1).toLocaleDateString('tr-TR', { month: 'short', year: 'numeric' });
        }),
        datasets: [
            {
                label: 'Gelir',
                data: incomeData,
                backgroundColor: 'rgba(75, 192, 192, 0.8)',
            },
            {
                label: 'Gider',
                data: expenseData,
                backgroundColor: 'rgba(255, 99, 132, 0.8)',
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
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>