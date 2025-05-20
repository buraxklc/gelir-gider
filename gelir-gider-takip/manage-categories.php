<?php
require_once 'includes/functions.php';
requireLogin();

$error = '';
$success = '';

// Yeni kategori ekleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? '';
    $icon = $_POST['icon'] ?? 'bi-folder';
    $color = $_POST['color'] ?? '#000000';
    $parent_id = $_POST['parent_id'] ?: null;
    
    if ($name && $type) {
        // Aynı isimde kategori var mı kontrol et
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND user_id = ? AND type = ?");
        $stmt->execute([$name, $_SESSION['user_id'], $type]);
        
        if ($stmt->fetch()) {
            $error = 'Bu isimde bir kategori zaten mevcut!';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO categories (name, type, icon, color, parent_id, user_id) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$name, $type, $icon, $color, $parent_id, $_SESSION['user_id']])) {
                $success = 'Kategori başarıyla eklendi!';
            } else {
                $error = 'Kategori eklenirken bir hata oluştu!';
            }
        }
    } else {
        $error = 'Lütfen tüm alanları doldurun!';
    }
}

// Kategori güncelleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_category'])) {
    $id = $_POST['category_id'];
    $name = trim($_POST['name'] ?? '');
    $icon = $_POST['icon'] ?? 'bi-folder';
    $color = $_POST['color'] ?? '#000000';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $stmt = $pdo->prepare("
        UPDATE categories 
        SET name = ?, icon = ?, color = ?, is_active = ?
        WHERE id = ? AND (user_id = ? OR user_id IS NULL)
    ");
    
    if ($stmt->execute([$name, $icon, $color, $is_active, $id, $_SESSION['user_id']])) {
        $success = 'Kategori başarıyla güncellendi!';
    } else {
        $error = 'Kategori güncellenirken bir hata oluştu!';
    }
}

// Kategori silme
if (isset($_GET['delete'])) {
    $categoryId = $_GET['delete'];
    
    // Bu kategoriye ait işlem var mı kontrol et
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM transactions WHERE category_id = ?");
    $stmt->execute([$categoryId]);
    $count = $stmt->fetch()['count'];
    
    if ($count > 0) {
        $error = 'Bu kategoride ' . $count . ' adet işlem bulunuyor. Önce bu işlemleri silin veya başka bir kategoriye taşıyın!';
    } else {
        // Sadece kullanıcının kendi kategorilerini silebilir
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$categoryId, $_SESSION['user_id']])) {
            $success = 'Kategori başarıyla silindi!';
        } else {
            $error = 'Kategori silinemedi! Sistem kategorilerini silemezsiniz.';
        }
    }
}

// Kategorileri getir
$stmt = $pdo->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM transactions t WHERE t.category_id = c.id) as transaction_count,
           (SELECT COUNT(*) FROM categories sub WHERE sub.parent_id = c.id) as subcategory_count
    FROM categories c
    WHERE c.user_id = ? OR c.user_id IS NULL
    ORDER BY c.type, c.user_id DESC, c.name
");
$stmt->execute([$_SESSION['user_id']]);
$categories = $stmt->fetchAll();

// Ana kategorileri ayır (alt kategori için)
$mainCategories = array_filter($categories, function($cat) {
    return $cat['parent_id'] === null;
});

$pageTitle = 'Kategori Yönetimi';
include 'includes/header.php';
?>


<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= e($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= e($success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Kategori Listesi -->
<div class="row">
    <?php 
    $groupedCategories = [];
    foreach ($categories as $category) {
        $groupedCategories[$category['type']][] = $category;
    }
    ?>
    
    <?php foreach (['income' => 'Gelir Kategorileri', 'expense' => 'Gider Kategorileri'] as $type => $title): ?>
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-<?= $type == 'income' ? 'graph-up-arrow text-success' : 'graph-down-arrow text-danger' ?>"></i> 
                    <?= $title ?> 
                    <span class="badge bg-secondary ms-2"><?= isset($groupedCategories[$type]) ? count($groupedCategories[$type]) : 0 ?></span>
                </h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal" data-type="<?= $type ?>">
                    <i class="bi bi-plus"></i>
                </button>
            </div>
            <div class="card-body p-0">
                <?php if (!isset($groupedCategories[$type]) || empty($groupedCategories[$type])): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-folder text-muted display-4"></i>
                        <p class="text-muted mt-2">Bu tipte henüz kategori bulunmuyor.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($groupedCategories[$type] as $category): ?>
                        <div class="list-group-item category-item d-flex justify-content-between align-items-center p-3">
                            <div class="d-flex align-items-center">
                                <div class="category-icon me-3" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background-color: <?= htmlspecialchars($category['color']); ?>; color: white; border-radius: 8px;">
                                    <i class="<?= htmlspecialchars($category['icon']); ?>"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold"><?= htmlspecialchars($category['name']); ?></h6>
                                    <div>
                                        <?php if ($category['parent_id']): ?>
                                            <span class="badge bg-secondary me-1">Alt Kategori</span>
                                        <?php endif; ?>
                                        <?php if (!$category['is_active']): ?>
                                            <span class="badge bg-warning text-dark me-1">Pasif</span>
                                        <?php endif; ?>
                                        <?php if ($category['user_id'] === null): ?>
                                            <span class="badge bg-info me-1">Sistem</span>
                                        <?php endif; ?>
                                        <span class="badge bg-light text-dark border"><?= isset($category['transaction_count']) ? $category['transaction_count'] : 0 ?> işlem</span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <?php if ($category['user_id'] !== null): ?>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editCategoryModal"
                                            data-category='<?= json_encode($category); ?>'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="?delete=<?= $category['id']; ?>" 
                                       class="btn btn-outline-danger"
                                       onclick="return confirm('Bu kategoriyi silmek istediğinize emin misiniz?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                                <?php else: ?>
                                <span class="text-muted small">Sistem Kategorisi</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<style>
.category-item {
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}
.category-item:hover {
    background-color: #f8f9fa;
    transform: translateX(3px);
}
.category-icon {
    transition: all 0.2s ease;
}
.category-item:hover .category-icon {
    transform: scale(1.1);
}
</style>

<script>
// Kategori ekleme modalında tipi otomatik seç
document.addEventListener('DOMContentLoaded', function() {
    const addCategoryModal = document.getElementById('addCategoryModal');
    if (addCategoryModal) {
        addCategoryModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const type = button.getAttribute('data-type');
            if (type) {
                document.getElementById('type').value = type;
                updateCategories(); // Eğer böyle bir fonksiyonunuz varsa
            }
        });
    }
});
</script>

<!-- Yeni Kategori Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Kategori Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="add_category" value="1">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Kategori Adı</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="type" class="form-label">Tip</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="">Seçin</option>
                            <option value="income">Gelir</option>
                            <option value="expense">Gider</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Ana Kategori (Opsiyonel)</label>
                        <select class="form-select" id="parent_id" name="parent_id">
                            <option value="">Ana Kategori</option>
                            <?php foreach ($mainCategories as $mainCat): ?>
                            <option value="<?= $mainCat['id'] ?>" data-type="<?= $mainCat['type'] ?>">
                                <?= e($mainCat['name']) ?> (<?= $mainCat['type'] == 'income' ? 'Gelir' : 'Gider' ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="icon" class="form-label">İkon</label>
                        <select class="form-select" id="icon" name="icon" required>
                            <option value="bi-folder">📁 Klasör</option>
                            <option value="bi-cash">💵 Nakit</option>
                            <option value="bi-credit-card">💳 Kredi Kartı</option>
                            <option value="bi-cart">🛒 Market</option>
                            <option value="bi-house">🏠 Ev</option>
                            <option value="bi-car-front">🚗 Araba</option>
                            <option value="bi-bus-front">🚌 Ulaşım</option>
                            <option value="bi-cup-straw">🥤 Yeme-İçme</option>
                            <option value="bi-gift">🎁 Hediye</option>
                            <option value="bi-heart">❤️ Sağlık</option>
                            <option value="bi-book">📚 Eğitim</option>
                            <option value="bi-controller">🎮 Eğlence</option>
                            <option value="bi-airplane">✈️ Seyahat</option>
                            <option value="bi-briefcase">💼 İş</option>
                            <option value="bi-tools">🔧 Tamirat</option>
                            <option value="bi-tags">🏷️ Diğer</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="color" class="form-label">Renk</label>
                        <input type="color" class="form-control" id="color" name="color" value="#000000">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Kategori Düzenleme Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kategori Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="update_category" value="1">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Kategori Adı</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_icon" class="form-label">İkon</label>
                        <select class="form-select" id="edit_icon" name="icon" required>
                            <option value="bi-folder">📁 Klasör</option>
                            <option value="bi-cash">💵 Nakit</option>
                            <option value="bi-credit-card">💳 Kredi Kartı</option>
                            <option value="bi-cart">🛒 Market</option>
                            <option value="bi-house">🏠 Ev</option>
                            <option value="bi-car-front">🚗 Araba</option>
                            <option value="bi-bus-front">🚌 Ulaşım</option>
                            <option value="bi-cup-straw">🥤 Yeme-İçme</option>
                            <option value="bi-gift">🎁 Hediye</option>
                            <option value="bi-heart">❤️ Sağlık</option>
                            <option value="bi-book">📚 Eğitim</option>
                            <option value="bi-controller">🎮 Eğlence</option>
                            <option value="bi-airplane">✈️ Seyahat</option>
                            <option value="bi-briefcase">💼 İş</option>
                            <option value="bi-tools">🔧 Tamirat</option>
                            <option value="bi-tags">🏷️ Diğer</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_color" class="form-label">Renk</label>
                        <input type="color" class="form-control" id="edit_color" name="color">
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" checked>
                            <label class="form-check-label" for="edit_is_active">
                                Aktif
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Düzenleme modalını doldur
document.getElementById('editCategoryModal').addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const category = JSON.parse(button.getAttribute('data-category'));
    
    document.getElementById('edit_category_id').value = category.id;
    document.getElementById('edit_name').value = category.name;
    document.getElementById('edit_icon').value = category.icon;
    document.getElementById('edit_color').value = category.color;
    document.getElementById('edit_is_active').checked = category.is_active == 1;
});

// Ana kategori seçiminde tip kontrolü
document.getElementById('type').addEventListener('change', function() {
    const selectedType = this.value;
    const parentSelect = document.getElementById('parent_id');
    const options = parentSelect.querySelectorAll('option');
    
    options.forEach(option => {
        if (option.value && option.dataset.type !== selectedType) {
            option.style.display = 'none';
        } else {
            option.style.display = '';
        }
    });
    
    // Seçili değer uygun değilse sıfırla
    if (parentSelect.value && parentSelect.selectedOptions[0].dataset.type !== selectedType) {
        parentSelect.value = '';
    }
});
</script>

<?php include 'includes/footer.php'; ?>