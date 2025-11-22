<?php
require_once 'db.php';

/**
 * WheelService - Günlük şans çarkı yönetimi
 */
class WheelService {
    private $pdo;
    
    // Çark ödül olasılıkları (toplam 100%)
    private $rewardProbabilities = [
        ['type' => 'puan', 'value' => '10', 'weight' => 15, 'label' => '10 Puan'],
        ['type' => 'puan', 'value' => '25', 'weight' => 15, 'label' => '25 Puan'],
        ['type' => 'puan', 'value' => '50', 'weight' => 20, 'label' => '50 Puan'],
        ['type' => 'puan', 'value' => '75', 'weight' => 10, 'label' => '75 Puan'],
        ['type' => 'puan', 'value' => '100', 'weight' => 15, 'label' => '100 Puan'],
        ['type' => 'puan', 'value' => '200', 'weight' => 10, 'label' => '200 Puan'],
        ['type' => 'kupon', 'value' => 'INDIRIM10', 'weight' => 10, 'label' => '%10 İndirim'],
        ['type' => 'rozet', 'value' => 'lucky_star', 'weight' => 5, 'label' => 'Şanslı Rozet']
    ];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Bugün çark çevirme hakkı var mı?
     * @param int $userId
     * @return bool
     */
    public function canSpinToday($userId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM CarkCevirmeler 
            WHERE kullanici_id = ? AND cevrim_tarihi = CURDATE()
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() == 0;
    }
    
    /**
     * Çarkı çevir ve ödül ver
     * @param int $userId
     * @return array Sonuç (success, reward_type, reward_value, reward_label, message)
     */
    public function spinWheel($userId) {
        try {
            // Bugün zaten çevrilmiş mi kontrol et
            if (!$this->canSpinToday($userId)) {
                return [
                    'success' => false,
                    'message' => 'Bugün zaten çark çevirdiniz! Yarın tekrar gelin 🎡'
                ];
            }
            
            // Rastgele ödül seç
            $reward = $this->selectRandomReward();
            
            // Çark çevirme kaydı oluştur
            $stmt = $this->pdo->prepare("
                INSERT INTO CarkCevirmeler (kullanici_id, cevrim_tarihi, odul_tipi, odul_degeri)
                VALUES (?, CURDATE(), ?, ?)
            ");
            $stmt->execute([$userId, $reward['type'], $reward['value']]);
            
            // Ödülü ver
            $rewardResult = $this->giveReward($userId, $reward['type'], $reward['value']);
            
            // Quest güncellemesi (çark çevirme görevi)
            require_once 'QuestService.php';
            $questService = new QuestService($this->pdo);
            $questService->updateQuestProgress($userId, 'cark_cevir_5');
            
            // Çark çevirme rozeti kontrolü
            $this->checkSpinBadges($userId);
            
            return [
                'success' => true,
                'reward_type' => $reward['type'],
                'reward_value' => $reward['value'],
                'reward_label' => $reward['label'],
                'message' => $rewardResult['message'],
                'total_spins' => $this->getTotalSpins($userId)
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Bir hata oluştu: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Ağırlıklı rastgele ödül seçimi
     * @return array Seçilen ödül
     */
    private function selectRandomReward() {
        $totalWeight = array_sum(array_column($this->rewardProbabilities, 'weight'));
        $random = rand(1, $totalWeight);
        
        $currentWeight = 0;
        foreach ($this->rewardProbabilities as $reward) {
            $currentWeight += $reward['weight'];
            if ($random <= $currentWeight) {
                return $reward;
            }
        }
        
        // Fallback (olmamalı ama güvenlik için)
        return $this->rewardProbabilities[0];
    }
    
    /**
     * Ödülü kullanıcıya ver
     * @param int $userId
     * @param string $type 'puan', 'kupon', 'rozet'
     * @param string $value Ödül değeri
     * @return array Sonuç mesajı
     */
    private function giveReward($userId, $type, $value) {
        switch ($type) {
            case 'puan':
                $points = intval($value);
                
                // Puan ekle
                $stmt = $this->pdo->prepare("
                    INSERT INTO PuanGecmisi (kullanici_id, islem_tipi, puan, aciklama)
                    VALUES (?, 'cark', ?, 'Şans çarkından kazanıldı')
                ");
                $stmt->execute([$userId, $points]);
                
                $stmt = $this->pdo->prepare("
                    UPDATE Kullanicilar 
                    SET toplam_puan = toplam_puan + ? 
                    WHERE kullanici_id = ?
                ");
                $stmt->execute([$points, $userId]);
                
                return [
                    'message' => "🎉 Tebrikler! Çarktan {$points} puan kazandınız!"
                ];
                
            case 'kupon':
                // Kupon kodu ile kupon ID'sini bul
                $stmt = $this->pdo->prepare("SELECT kupon_id FROM Kuponlar WHERE kupon_kodu = ?");
                $stmt->execute([$value]);
                $kupon = $stmt->fetch();
                
                if ($kupon) {
                    // Kullanıcıya kuponu ver
                    try {
                        $stmt = $this->pdo->prepare("
                            INSERT INTO KullaniciKuponlari (kullanici_id, kupon_id)
                            VALUES (?, ?)
                        ");
                        $stmt->execute([$userId, $kupon['kupon_id']]);
                        
                        return [
                            'message' => "🎊 Harika! '{$value}' kuponunu kazandınız!"
                        ];
                    } catch (PDOException $e) {
                        // Zaten varsa puan alternatifi ver
                        return $this->giveReward($userId, 'puan', '50');
                    }
                }
                
                // Kupon bulunamazsa puan ver
                return $this->giveReward($userId, 'puan', '50');
                
            case 'rozet':
                // Rozet ver
                $stmt = $this->pdo->prepare("SELECT * FROM Rozetler WHERE rozet_kodu = ?");
                $stmt->execute([$value]);
                $badge = $stmt->fetch();
                
                if ($badge) {
                    try {
                        $stmt = $this->pdo->prepare("
                            INSERT INTO KullaniciRozetleri (kullanici_id, rozet_id)
                            VALUES (?, ?)
                        ");
                        $stmt->execute([$userId, $badge['rozet_id']]);
                        
                        return [
                            'message' => "⭐ İnanılmaz! '{$badge['rozet_adi']}' rozetini kazandınız!"
                        ];
                    } catch (PDOException $e) {
                        // Zaten varsa puan ver
                        return $this->giveReward($userId, 'puan', '100');
                    }
                }
                
                // Rozet bulunamazsa puan ver
                return $this->giveReward($userId, 'puan', '100');
                
            default:
                return ['message' => 'Bilinmeyen ödül tipi'];
        }
    }
    
    /**
     * Çark çevirme rozetlerini kontrol et
     */
    private function checkSpinBadges($userId) {
        $totalSpins = $this->getTotalSpins($userId);
        
        // 100 kez çark çevirme rozeti
        if ($totalSpins >= 100 && !$this->hasBadge($userId, 'cark_100')) {
            $this->awardBadge($userId, 'cark_100');
        }
    }
    
    /**
     * Toplam çark çevirme sayısı
     */
    private function getTotalSpins($userId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM CarkCevirmeler 
            WHERE kullanici_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
    
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
            } catch (PDOException $e) {
                // Zaten var
            }
        }
    }
    
    /**
     * Çark ödül seçeneklerini frontend için getir
     * @return array Ödül listesi
     */
    public function getWheelOptions() {
        return $this->rewardProbabilities;
    }
    
    /**
     * Kullanıcının çark geçmişini getir
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getSpinHistory($userId, $limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT cevrim_tarihi, odul_tipi, odul_degeri 
            FROM CarkCevirmeler 
            WHERE kullanici_id = ? 
            ORDER BY cevrim_tarihi DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
