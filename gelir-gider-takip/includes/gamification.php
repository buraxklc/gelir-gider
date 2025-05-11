<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Kullanıcının puan ve seviyesini getir
function getUserGameStats($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM user_points WHERE user_id = ?");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();
    
    if (!$stats) {
        // İlk kez giren kullanıcı için kayıt oluştur
        $stmt = $pdo->prepare("INSERT INTO user_points (user_id, points, level, total_earned) VALUES (?, 0, 1, 0)");
        $stmt->execute([$userId]);
        
        return [
            'points' => 0,
            'level' => 1,
            'total_earned' => 0
        ];
    }
    
    return $stats;
}

// Seviye hesaplama
function calculateLevel($points) {
    // Her 1000 puan = 1 seviye
    return floor($points / 1000) + 1;
}

// Bir sonraki seviyeye kaç puan kaldı
function pointsToNextLevel($currentPoints) {
    $currentLevel = calculateLevel($currentPoints);
    $nextLevelPoints = $currentLevel * 1000;
    return $nextLevelPoints - $currentPoints;
}

// Puan ekle
function addPoints($userId, $points, $reason = '') {
    global $pdo;
    
    try {
        // Mevcut puanı al veya yeni kayıt oluştur
        $stmt = $pdo->prepare("
            INSERT INTO user_points (user_id, points, level, total_earned) 
            VALUES (?, ?, 1, ?)
            ON DUPLICATE KEY UPDATE 
            points = points + ?, 
            total_earned = total_earned + ?,
            level = FLOOR((points + ?) / 1000) + 1
        ");
        $stmt->execute([$userId, $points, $points, $points, $points, $points]);
        
        // Güncel bilgileri getir
        $stmt = $pdo->prepare("SELECT points, level FROM user_points WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return [
            'points' => $result['points'] ?? $points,
            'level' => $result['level'] ?? 1,
            'added' => $points
        ];
    } catch (Exception $e) {
        error_log("Error adding points: " . $e->getMessage());
        return false;
    }
}

// Kullanıcının başarılarını getir
function getUserAchievements($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT a.*, ua.earned_at
        FROM achievements a
        JOIN user_achievements ua ON a.id = ua.achievement_id
        WHERE ua.user_id = ?
        ORDER BY ua.earned_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Başarıları kontrol et ve ödüllendir
function checkAchievements($userId) {
    global $pdo;
    
    $earnedAchievements = [];
    
    // Tüm başarıları getir
    $stmt = $pdo->prepare("
        SELECT a.* 
        FROM achievements a
        LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
        WHERE ua.id IS NULL
    ");
    $stmt->execute([$userId]);
    $achievements = $stmt->fetchAll();
    
    foreach ($achievements as $achievement) {
        $earned = false;
        
        switch ($achievement['criteria_type']) {
            case 'transaction_count':
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id = ?");
                $stmt->execute([$userId]);
                $count = $stmt->fetch()['count'];
                $earned = $count >= $achievement['criteria_value'];
                break;
                
            case 'savings_amount':
                $totalIncome = calculateTotals($userId, 'income');
                $totalExpense = calculateTotals($userId, 'expense');
                $savings = $totalIncome - $totalExpense;
                $earned = $savings >= $achievement['criteria_value'];
                break;
                
            case 'category_count':
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM categories WHERE user_id = ?");
                $stmt->execute([$userId]);
                $count = $stmt->fetch()['count'];
                $earned = $count >= $achievement['criteria_value'];
                break;
        }
        
        if ($earned) {
            // Başarıyı kaydet
            $stmt = $pdo->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?, ?)");
            $stmt->execute([$userId, $achievement['id']]);
            
            // Eğer yeni eklendiyse
            if ($pdo->lastInsertId() > 0) {
                // Puan ekle
                addPoints($userId, $achievement['points'], 'achievement_' . $achievement['id']);
                $earnedAchievements[] = $achievement;
            }
        }
    }
    
    return $earnedAchievements;
}

// Aktif challenge'ları getir
function getActiveChallenges() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM challenges 
        WHERE is_active = TRUE 
        AND start_date <= CURDATE() 
        AND end_date >= CURDATE()
        ORDER BY end_date ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Kullanıcının challenge ilerlemesini getir
function getUserChallengeProgress($userId, $challengeId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM user_challenges 
        WHERE user_id = ? AND challenge_id = ?
    ");
    $stmt->execute([$userId, $challengeId]);
    return $stmt->fetch();
}

// Challenge'a katıl
function joinChallenge($userId, $challengeId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO user_challenges (user_id, challenge_id) 
        VALUES (?, ?)
    ");
    return $stmt->execute([$userId, $challengeId]);
}

// Challenge ilerlemesini güncelle
function updateChallengeProgress($userId, $challengeId) {
    global $pdo;
    
    // Önce kullanıcının challenge'a katılıp katılmadığını kontrol et
    $stmt = $pdo->prepare("SELECT * FROM user_challenges WHERE user_id = ? AND challenge_id = ?");
    $stmt->execute([$userId, $challengeId]);
    $userChallenge = $stmt->fetch();
    
    if (!$userChallenge) {
        return false;
    }
    
    // Challenge detaylarını al
    $stmt = $pdo->prepare("SELECT * FROM challenges WHERE id = ?");
    $stmt->execute([$challengeId]);
    $challenge = $stmt->fetch();
    
    if (!$challenge) return false;
    
    $progress = 0;
    
    switch ($challenge['target_type']) {
        case 'save_amount':
            // Belirlenen tarih aralığında yapılan birikim
            $stmt = $pdo->prepare("
                SELECT 
                    COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) -
                    COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as savings
                FROM transactions 
                WHERE user_id = ? 
                AND transaction_date BETWEEN ? AND ?
            ");
            $stmt->execute([$userId, $challenge['start_date'], $challenge['end_date']]);
            $result = $stmt->fetch();
            $progress = max(0, $result['savings'] ?? 0);
            break;
            
        case 'no_expense_days':
            // Harcama yapılmayan gün sayısı
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT transaction_date) as days
                FROM transactions
                WHERE user_id = ? 
                AND type = 'expense'
                AND transaction_date BETWEEN ? AND ?
            ");
            $stmt->execute([$userId, $challenge['start_date'], $challenge['end_date']]);
            $expenseDays = $stmt->fetch()['days'] ?? 0;
            
            // Toplam gün sayısı
            $start = new DateTime($challenge['start_date']);
            $end = new DateTime($challenge['end_date']);
            $totalDays = $end->diff($start)->days + 1;
            $progress = max(0, $totalDays - $expenseDays);
            break;
            
        case 'transaction_count':
            // İşlem sayısı
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count
                FROM transactions
                WHERE user_id = ? 
                AND transaction_date BETWEEN ? AND ?
            ");
            $stmt->execute([$userId, $challenge['start_date'], $challenge['end_date']]);
            $progress = $stmt->fetch()['count'] ?? 0;
            break;
    }
    
    // İlerlemeyi güncelle
    $isCompleted = $progress >= $challenge['target_value'];
    
    $stmt = $pdo->prepare("
        UPDATE user_challenges 
        SET progress = ?, 
            is_completed = ?,
            completed_at = CASE 
                WHEN ? = TRUE AND is_completed = FALSE THEN NOW()
                ELSE completed_at 
            END
        WHERE user_id = ? AND challenge_id = ?
    ");
    $stmt->execute([$progress, $isCompleted, $isCompleted, $userId, $challengeId]);
    
    // Tamamlandıysa ve daha önce tamamlanmamışsa puan ekle
    if ($isCompleted && !$userChallenge['is_completed']) {
        addPoints($userId, $challenge['reward_points'], 'challenge_' . $challengeId);
    }
    
    return $progress;
}

// Liderlik tablosu
function getLeaderboard($limit = 10) {
    global $pdo;
    
    $limit = intval($limit);
    
    $stmt = $pdo->prepare("
        SELECT u.username, up.points, up.level, 
               (SELECT COUNT(*) FROM user_achievements WHERE user_id = u.id) as achievement_count
        FROM user_points up
        JOIN users u ON up.user_id = u.id
        ORDER BY up.points DESC
        LIMIT " . $limit
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

// Test için bazı challenge'lar ekleyelim
function createSampleChallenges() {
    global $pdo;
    
    try {
        // Mevcut challenge'ları kontrol et
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM challenges WHERE is_active = TRUE");
        $count = $stmt->fetch()['count'];
        
        if ($count == 0) {
            // Örnek challenge'lar ekle
            $challenges = [
                [
                    'name' => 'Haftalık Tasarruf',
                    'description' => 'Bu hafta 500 TL biriktirin',
                    'start_date' => date('Y-m-d'),
                    'end_date' => date('Y-m-d', strtotime('+7 days')),
                    'challenge_type' => 'weekly',
                    'target_type' => 'save_amount',
                    'target_value' => 500,
                    'reward_points' => 100
                ],
                [
                    'name' => 'İşlem Ustası',
                    'description' => 'Bu ay 20 işlem ekleyin',
                    'start_date' => date('Y-m-01'),
                    'end_date' => date('Y-m-t'),
                    'challenge_type' => 'monthly',
                    'target_type' => 'transaction_count',
                    'target_value' => 20,
                    'reward_points' => 200
                ],
                [
                    'name' => 'Harcama Kontrolü',
                    'description' => '3 gün harcama yapmayın',
                    'start_date' => date('Y-m-d'),
                    'end_date' => date('Y-m-d', strtotime('+7 days')),
                    'challenge_type' => 'weekly',
                    'target_type' => 'no_expense_days',
                    'target_value' => 3,
                    'reward_points' => 75
                ]
            ];
            
            foreach ($challenges as $challenge) {
                $stmt = $pdo->prepare("
                    INSERT INTO challenges (name, description, start_date, end_date, challenge_type, target_type, target_value, reward_points)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $challenge['name'],
                    $challenge['description'],
                    $challenge['start_date'],
                    $challenge['end_date'],
                    $challenge['challenge_type'],
                    $challenge['target_type'],
                    $challenge['target_value'],
                    $challenge['reward_points']
                ]);
            }
        }
    } catch (Exception $e) {
        error_log("Error creating sample challenges: " . $e->getMessage());
    }
}

// Örnek challenge'ları oluştur
createSampleChallenges();
?>