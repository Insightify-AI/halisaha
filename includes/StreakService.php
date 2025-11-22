<?php
require_once 'db.php';

/**
 * StreakService - Günlük check-in ve streak (ardışık giriş) yönetimi
 */
class StreakService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Günlük check-in işlemi
     * @param int $userId Kullanıcı ID
     * @return array Sonuç (success, streak, puan, bonus_earned, new_badges)
     */
    public function checkIn($userId) {
        try {
            // Stored procedure kullanarak check-in yap
            $stmt = $this->pdo->prepare("CALL sp_CheckInYap(?)");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            if (!$result) {
                return [
                    'success' => false,
                    'message' => 'Check-in işlemi başarısız oldu.'
                ];
            }
            
            $streak = $result['streak'];
            $puan = $result['puan_kazanildi'];
            $bonusEarned = $puan > 5; // Normal 5 puandan fazlaysa bonus var
            
            // Streak milestone kontrolü ve rozet verme
            $newBadges = $this->checkStreakMilestones($userId, $streak);
            
            // Quest sistemine bildir (günlük check-in görevi için)
            require_once 'QuestService.php';
            $questService = new QuestService($this->pdo);
            $questService->updateQuestProgress($userId, 'gunluk_checkin');
            
            return [
                'success' => true,
                'message' => "🎉 Günlük check-in tamamlandı! +{$puan} puan kazandınız!",
                'streak' => $streak,
                'puan' => $puan,
                'bonus_earned' => $bonusEarned,
                'new_badges' => $newBadges
            ];
            
        } catch (PDOException $e) {
            // "Bugün zaten check-in yaptınız" hatası
            if (strpos($e->getMessage(), 'zaten check-in') !== false) {
                return [
                    'success' => false,
                    'message' => 'Bugün zaten check-in yaptınız! Yarın tekrar gelin 😊'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Bir hata oluştu: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Kullanıcının mevcut streak bilgisini getir
     * @param int $userId
     * @return array (mevcut_streak, en_uzun_streak, son_checkin)
     */
    public function getCurrentStreak($userId) {
        $stmt = $this->pdo->prepare("
            SELECT mevcut_streak, en_uzun_streak, son_checkin_tarihi 
            FROM StreakTakibi 
            WHERE kullanici_id = ?
        ");
        $stmt->execute([$userId]);
        $streak = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$streak) {
            return [
                'mevcut_streak' => 0,
                'en_uzun_streak' => 0,
                'son_checkin' => null
            ];
        }
        
        return $streak;
    }
    
    /**
     * Streak milestone kontrolü ve rozet verme
     * @param int $userId
     * @param int $streak Güncel streak değeri
     * @return array Yeni kazanılan rozetler
     */
    private function checkStreakMilestones($userId, $streak) {
        require_once 'GamificationService.php';
        $gamification = new GamificationService($this->pdo);
        $newBadges = [];
        
        // 7 gün streak
        if ($streak == 7 && !$this->hasBadge($userId, '7_gun_streak')) {
            $badgeInfo = $this->awardBadge($userId, '7_gun_streak');
            if ($badgeInfo) {
                $newBadges[] = $badgeInfo;
            }
        }
        
        // 30 gün streak
        if ($streak == 30 && !$this->hasBadge($userId, '30_gun_streak')) {
            $badgeInfo = $this->awardBadge($userId, '30_gun_streak');
            if ($badgeInfo) {
                $newBadges[] = $badgeInfo;
            }
        }
        
        // 365 gün streak
        if ($streak == 365 && !$this->hasBadge($userId, '365_gun_streak')) {
            $badgeInfo = $this->awardBadge($userId, '365_gun_streak');
            if ($badgeInfo) {
                $newBadges[] = $badgeInfo;
            }
        }
        
        return $newBadges;
    }
    
    /**
     * Kullanıcının rozetinin olup olmadığını kontrol et
     */
    private function hasBadge($userId, $badgeCode) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM KullaniciRozetleri kr
            JOIN Rozetler r ON kr.rozet_id = r.rozet_id
            WHERE kr.kullanici_id = ? AND r.rozet_kodu = ?
        ");
        $stmt->execute([$userId, $badgeCode]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Rozet ver
     */
    private function awardBadge($userId, $badgeCode) {
        $stmt = $this->pdo->prepare("SELECT * FROM Rozetler WHERE rozet_kodu = ?");
        $stmt->execute([$badgeCode]);
        $badge = $stmt->fetch();
        
        if ($badge) {
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO KullaniciRozetleri (kullanici_id, rozet_id) 
                    VALUES (?, ?)
                ");
                $stmt->execute([$userId, $badge['rozet_id']]);
                
                return [
                    'rozet_adi' => $badge['rozet_adi'],
                    'aciklama' => $badge['aciklama'],
                    'ikon' => $badge['ikon']
                ];
            } catch (PDOException $e) {
                // Zaten varsa hata verme
                return null;
            }
        }
        
        return null;
    }
    
    /**
     * Check-in geçmişini getir
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getCheckInHistory($userId, $limit = 30) {
        $stmt = $this->pdo->prepare("
            SELECT checkin_tarihi, puan_kazanildi 
            FROM GunlukCheckinler 
            WHERE kullanici_id = ? 
            ORDER BY checkin_tarihi DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Bugün check-in yapılmış mı?
     * @param int $userId
     * @return bool
     */
    public function hasCheckedInToday($userId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM GunlukCheckinler 
            WHERE kullanici_id = ? AND checkin_tarihi = CURDATE()
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Bu ay toplam check-in sayısı
     * @param int $userId
     * @return int
     */
    public function getMonthlyCheckIns($userId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM GunlukCheckinler 
            WHERE kullanici_id = ? 
            AND YEAR(checkin_tarihi) = YEAR(CURDATE())
            AND MONTH(checkin_tarihi) = MONTH(CURDATE())
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
}
?>
