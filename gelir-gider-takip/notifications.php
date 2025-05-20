<?php
require_once 'includes/functions.php';
require_once 'includes/recurring-functions.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Bildirimleri getir (okunmuş/okunmamış tümü)
$notifications = getUserNotifications($userId, false);

// Tüm bildirimleri okundu olarak işaretle
if (isset($_GET['mark_all_read']) && $_GET['mark_all_read'] == 1) {
    if (count($notifications) > 0) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                UPDATE notifications 
                SET is_read = TRUE, read_at = NOW()
                WHERE user_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$userId]);
            $pdo->commit();
            
            // Sayfayı yenile
            header('Location: notifications.php?marked=1');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
}

// Tüm bildirimleri sil
if (isset($_GET['delete_all']) && $_GET['delete_all'] == 1) {
    if (count($notifications) > 0) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
            $stmt->execute([$userId]);
            $pdo->commit();
            
            // Sayfayı yenile
            header('Location: notifications.php?deleted=1');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
}

$pageTitle = 'Bildirimler';
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
.notification-item {
    border-left: 4px solid transparent;
    transition: all 0.2s;
}
.notification-item:hover {
    background-color: #f3f4f6;
}
.notification-item.unread {
    border-left-color: #3b82f6;
    background-color: #eff6ff;
}
.notification-item.reminder {
    border-left-color: #3b82f6;
}
.notification-item.warning {
    border-left-color: #f59e0b;
}
.notification-item.overdue {
    border-left-color: #ef4444;
}
.notification-item.info {
    border-left-color: #10b981;
}
.badge-counter {
    position: relative;
    top: -8px;
    right: 5px;
}
</style>
<?php endif; ?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-bell"></i> Bildirimler
                        <?php 
                        $unreadCount = count(array_filter($notifications, function($n) { return !$n['is_read']; }));
                        if ($unreadCount > 0): 
                        ?>
                        <span class="badge bg-danger rounded-pill"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </h5>
                    <div>
                        <?php if (count($notifications) > 0): ?>
                        <a href="notifications.php?mark_all_read=1" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-check-all"></i> Tümünü Okundu İşaretle
                        </a>
                        <a href="notifications.php?delete_all=1" class="btn btn-sm btn-outline-danger" 
                           onclick="return confirm('Tüm bildirimleri silmek istediğinize emin misiniz?')">
                            <i class="bi bi-trash"></i> Tümünü Sil
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['marked']) && $_GET['marked'] == 1): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        Tüm bildirimler okundu olarak işaretlendi.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        Tüm bildirimler silindi.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (empty($notifications)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-bell-slash display-1 text-muted mb-3"></i>
                        <p class="text-muted mb-0">Henüz hiç bildirim bulunmuyor.</p>
                    </div>
                    <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($notifications as $notification): 
                            $iconClass = [
                                'reminder' => 'bi-clock text-info',
                                'overdue' => 'bi-exclamation-triangle text-danger',
                                'info' => 'bi-info-circle text-primary',
                                'warning' => 'bi-exclamation-diamond text-warning'
                            ][$notification['type']] ?? 'bi-bell text-secondary';
                        ?>
                        <div class="list-group-item notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?> <?php echo $notification['type']; ?>">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div>
                                    <div class="d-flex align-items-center">
                                        <i class="<?php echo $iconClass; ?> me-3 fs-4"></i>
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8'); ?></h6>
                                            <p class="mb-1"><?php echo htmlspecialchars($notification['message'], ENT_QUOTES, 'UTF-8'); ?></p>
                                            <small class="text-muted">
                                                <i class="bi bi-clock"></i> <?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?>
                                                <?php if ($notification['is_read']): ?>
                                                <span class="ms-2"><i class="bi bi-check2-all"></i> Okundu</span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <?php if (!$notification['is_read']): ?>
                                    <a href="mark-notification-read.php?id=<?php echo $notification['id']; ?>&redirect=notifications.php" 
                                       class="btn btn-sm btn-outline-primary" title="Okundu olarak işaretle">
                                        <i class="bi bi-check2"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="delete-notification.php?id=<?php echo $notification['id']; ?>&redirect=notifications.php" 
                                       class="btn btn-sm btn-outline-danger ms-1" title="Bildirimi sil"
                                       onclick="return confirm('Bu bildirimi silmek istediğinize emin misiniz?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>