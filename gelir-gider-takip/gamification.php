<?php
require_once 'includes/functions.php';
require_once 'includes/gamification.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Kullanıcının oyun istatistiklerini al
$gameStats = getUserGameStats($userId);
$nextLevelPoints = pointsToNextLevel($gameStats['points']);
$levelProgress = (($gameStats['points'] % 1000) / 1000) * 100;

// Başarıları kontrol et
$newAchievements = checkAchievements($userId);
$userAchievements = getUserAchievements($userId);

// Aktif challenge'ları getir
$activeChallenges = getActiveChallenges();

// Challenge'a katılma
if (isset($_POST['join_challenge'])) {
    $challengeId = $_POST['challenge_id'];
    joinChallenge($userId, $challengeId);
    header('Location: gamification.php');
    exit;
}

// Liderlik tablosu
$leaderboard = getLeaderboard(10);

$pageTitle = 'Oyunlaştırma';
include 'includes/header.php';
?>

<!-- Yeni başarılar modalı -->
<?php if (!empty($newAchievements)): ?>
<div class="modal fade show" id="newAchievementModal" tabindex="-1" style="display: block;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">🎉 Yeni Başarı Kazandınız!</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <?php foreach ($newAchievements as $achievement): ?>
                <div class="mb-3">
                    <i class="<?= e($achievement['icon']) ?> display-1 text-warning"></i>
                    <h4 class="mt-3"><?= e($achievement['name']) ?></h4>
                    <p class="text-muted"><?= e($achievement['description']) ?></p>
                    <p class="fw-bold">+<?= $achievement['points'] ?> Puan</p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container-fluid">
    <h2 class="mb-4">Oyunlaştırma Dashboard</h2>
    
    <!-- Kullanıcı Profili ve Puan -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="avatar-lg bg-white rounded-circle d-flex align-items-center justify-content-center">
                                <span class="text-primary fw-bold display-6"><?= substr($_SESSION['username'], 0, 1) ?></span>
                            </div>
                        </div>
                        <div class="col">
                            <h4 class="mb-1"><?= e($_SESSION['username']) ?></h4>
                            <p class="mb-0">Seviye <?= $gameStats['level'] ?> • <?= number_format($gameStats['points']) ?> Puan</p>
                        </div>
                        <div class="col-md-6">
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar bg-warning" style="width: <?= $levelProgress ?>%">
                                    <?= round($levelProgress) ?>%
                                </div>
                            </div>
                            <small>Sonraki seviyeye <?= $nextLevelPoints ?> puan kaldı</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Başarılar -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">🏆 Başarılar</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php
                        $allAchievements = $pdo->query("SELECT * FROM achievements ORDER BY points ASC")->fetchAll();
                        foreach ($allAchievements as $achievement):
                            $earned = false;
                            $earnedDate = null;
                            foreach ($userAchievements as $userAch) {
                                if ($userAch['id'] == $achievement['id']) {
                                    $earned = true;
                                    $earnedDate = $userAch['earned_at'];
                                    break;
                                }
                            }
                        ?>
                        <div class="col-6 col-md-4">
                            <div class="text-center <?= !$earned ? 'opacity-50' : '' ?>">
                                <div class="achievement-badge mb-2">
                                    <i class="<?= e($achievement['icon']) ?> display-6 <?= $earned ? 'text-' . 
                                        ($achievement['badge_type'] == 'gold' ? 'warning' : 
                                        ($achievement['badge_type'] == 'silver' ? 'secondary' : 
                                        ($achievement['badge_type'] == 'platinum' ? 'info' : 'success'))) : 'text-muted' ?>"></i>
                                </div>
                                <p class="small mb-0"><?= e($achievement['name']) ?></p>
                                <?php if ($earned): ?>
                                <small class="text-muted d-block"><?= date('d.m.Y', strtotime($earnedDate)) ?></small>
                                <?php else: ?>
                                <small class="text-muted d-block"><?= $achievement['points'] ?> puan</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Aktif Challenge'lar -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">🎯 Aktif Görevler</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($activeChallenges as $challenge): 
                        $userProgress = getUserChallengeProgress($userId, $challenge['id']);
                        $hasJoined = $userProgress !== false;
                        
                        if ($hasJoined) {
                            updateChallengeProgress($userId, $challenge['id']);
                            $userProgress = getUserChallengeProgress($userId, $challenge['id']);
                        }
                        
                        $progressPercentage = $hasJoined ? 
                            min(100, ($userProgress['progress'] / $challenge['target_value']) * 100) : 0;
                    ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <strong><?= e($challenge['name']) ?></strong>
                            <span class="badge bg-primary"><?= $challenge['reward_points'] ?> puan</span>
                        </div>
                        <p class="small text-muted mb-2"><?= e($challenge['description']) ?></p>
                        
                        <?php if ($hasJoined): ?>
                            <div class="progress mb-2">
                                <div class="progress-bar" style="width: <?= $progressPercentage ?>%">
                                    <?= round($progressPercentage) ?>%
                                </div>
                            </div>
                            <small class="text-muted">
                                İlerleme: <?= number_format($userProgress['progress'], 2) ?> / <?= number_format($challenge['target_value'], 2) ?>
                            </small>
                            <?php if ($userProgress['is_completed']): ?>
                            <div class="text-success small mt-1">
                                <i class="bi bi-check-circle-fill"></i> Tamamlandı!
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <form method="POST" class="mt-2">
                                <input type="hidden" name="challenge_id" value="<?= $challenge['id'] ?>">
                                <button type="submit" name="join_challenge" class="btn btn-sm btn-primary">
                                    Göreve Katıl
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <small class="text-muted d-block mt-1">
                            Bitiş: <?= date('d.m.Y', strtotime($challenge['end_date'])) ?>
                        </small>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($activeChallenges)): ?>
                    <p class="text-muted text-center">Şu anda aktif görev bulunmuyor.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Liderlik Tablosu -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">👑 Liderlik Tablosu</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Kullanıcı</th>
                                    <th class="text-end">Puan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leaderboard as $index => $user): ?>
                                <tr <?= $user['username'] == $_SESSION['username'] ? 'class="table-active"' : '' ?>>
                                    <td>
                                        <?php if ($index < 3): ?>
                                        <span class="badge bg-<?= $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'danger') ?>">
                                            <?= $index + 1 ?>
                                        </span>
                                        <?php else: ?>
                                        <?= $index + 1 ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= e($user['username']) ?>
                                        <small class="text-muted d-block">Seviye <?= $user['level'] ?></small>
                                    </td>
                                    <td class="text-end">
                                        <?= number_format($user['points']) ?>
                                        <small class="text-muted d-block"><?= $user['achievement_count'] ?> başarı</small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-lg {
    width: 80px;
    height: 80px;
}

.achievement-badge {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.progress {
    background-color: #e9ecef;
}
</style>

<script>
// Yeni başarı modalını göster
<?php if (!empty($newAchievements)): ?>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('newAchievementModal'));
    modal.show();
});
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>