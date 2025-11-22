<?php
require_once 'db.php';

/**
 * QuestService - Günlük ve haftalık görev (quest) yönetimi
 */
class QuestService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Kullanıcının günlük görevlerini getir
     * @param int $userId
     * @return array Görevler ve ilerleme bilgileri
     */
    public function getDailyQuests($userId) {
        return $this->getQuests($userId, 'gunluk');
    }
    
    /**
     * Kullanıcının haftalık görevlerini getir
     * @param int $userId
     * @return array Görevler ve ilerleme bilgileri
     */
    public function getWeeklyQuests($userId) {
        return $this->getQuests($userId, 'haftalik');
    }
    
    /**
     * Görevleri getir (günlük veya haftalık)
     */
    /**
     * Görevleri getir (günlük veya haftalık)
     */
    private function getQuests($userId, $type) {
        // Günlük görevler için Ymd (örn: 20231025), Haftalık için oW (örn: 202343)
        $donemKodu = ($type == 'gunluk') ? date('Ymd') : date('oW');
        
        $stmt = $this->pdo->prepare("
            SELECT 
                q.quest_id,
                q.quest_kodu,
                q.baslik,
                q.aciklama,
                q.hedef_sayi,
                q.odul_puan,
                q.ikon,
                COALESCE(kq.ilerleme, 0) as ilerleme,
                COALESCE(kq.tamamlandi, 0) as tamamlandi,
                kq.tamamlanma_tarihi
            FROM Questler q
            LEFT JOIN KullaniciQuestleri kq ON q.quest_id = kq.quest_id 
                AND kq.kullanici_id = ? 
                AND kq.hafta_numarasi = ?
            WHERE q.quest_tipi = ? AND q.aktif = 1
            ORDER BY q.sira ASC
        ");
        $stmt->execute([$userId, $donemKodu, $type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Quest ilerlemesini güncelle
     * @param int $userId
     * @param string $questCode Quest kodu (örn: 'favori_ekle_3', 'yorum_yap_1')
     * @param int $increment Artış miktarı (varsayılan 1)
     * @return array Güncelleme sonucu ve tamamlandı mı bilgisi
     */
    public function updateQuestProgress($userId, $questCode, $increment = 1) {
        try {
            // Quest bilgisini al
            $stmt = $this->pdo->prepare("SELECT * FROM Questler WHERE quest_kodu = ? AND aktif = 1");
            $stmt->execute([$questCode]);
            $quest = $stmt->fetch();
            
            if (!$quest) {
                return ['success' => false, 'message' => 'Quest bulunamadı'];
            }
            
            $questId = $quest['quest_id'];
            $hedefSayi = $quest['hedef_sayi'];
            $odul = $quest['odul_puan'];
            
            // Quest tipine göre dönem kodunu belirle
            $donemKodu = ($quest['quest_tipi'] == 'gunluk') ? date('Ymd') : date('oW');
            
            // Mevcut ilerlemeyi al
            $stmt = $this->pdo->prepare("
                SELECT * FROM KullaniciQuestleri 
                WHERE kullanici_id = ? AND quest_id = ? AND hafta_numarasi = ?
            ");
            $stmt->execute([$userId, $questId, $donemKodu]);
            $progress = $stmt->fetch();
            
            if (!$progress) {
                // İlk kez yapılıyor, yeni kayıt oluştur
                $yeniIlerleme = min($increment, $hedefSayi);
                $tamamlandi = ($yeniIlerleme >= $hedefSayi);
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO KullaniciQuestleri 
                    (kullanici_id, quest_id, ilerleme, tamamlandi, tamamlanma_tarihi, hafta_numarasi)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $userId, 
                    $questId, 
                    $yeniIlerleme, 
                    (int)$tamamlandi,
                    $tamamlandi ? date('Y-m-d H:i:s') : null,
                    $donemKodu
                ]);
                
                // Tamamlandıysa ödül ver
                if ($tamamlandi) {
                    $this->giveQuestReward($userId, $questCode, $odul);
                    return [
                        'success' => true,
                        'completed' => true,
                        'quest_baslik' => $quest['baslik'],
                        'odul' => $odul,
                        'message' => "🎉 '{$quest['baslik']}' görevini tamamladınız! +{$odul} puan!"
                    ];
                }
                
                return [
                    'success' => true,
                    'completed' => false,
                    'ilerleme' => $yeniIlerleme,
                    'hedef' => $hedefSayi
                ];
                
            } else {
                // Zaten tamamlanmışsa artık puan verme
                if ($progress['tamamlandi']) {
                    return ['success' => true, 'completed' => true, 'message' => 'Görev zaten tamamlanmış'];
                }
                
                // İlerlemeyi güncelle
                $yeniIlerleme = min($progress['ilerleme'] + $increment, $hedefSayi);
                $tamamlandi = ($yeniIlerleme >= $hedefSayi);
                
                $stmt = $this->pdo->prepare("
                    UPDATE KullaniciQuestleri 
                    SET ilerleme = ?, 
                        tamamlandi = ?,
                        tamamlanma_tarihi = ?
                    WHERE kullanici_id = ? AND quest_id = ? AND hafta_numarasi = ?
                ");
                $stmt->execute([
                    $yeniIlerleme,
                    (int)$tamamlandi,
                    $tamamlandi ? date('Y-m-d H:i:s') : null,
                    $userId,
                    $questId,
                    $donemKodu
                ]);
                
                // Yeni tamamlandıysa ödül ver
                if ($tamamlandi) {
                    $this->giveQuestReward($userId, $questCode, $odul);
                    
                    // Quest master rozeti için kontrol
                    $this->checkQuestMasterBadge($userId);
                    
                    return [
                        'success' => true,
                        'completed' => true,
                        'quest_baslik' => $quest['baslik'],
                        'odul' => $odul,
                        'message' => "🎊 '{$quest['baslik']}' görevini tamamladınız! +{$odul} puan kazandınız!"
                    ];
                }
                
                return [
                    'success' => true,
                    'completed' => false,
                    'ilerleme' => $yeniIlerleme,
                    'hedef' => $hedefSayi,
                    'message' => "İlerleme: {$yeniIlerleme}/{$hedefSayi}"
                ];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Hata: ' . $e->getMessage()];
        }
    }
    
    /**
     * Quest ödülünü ver
     */
    private function giveQuestReward($userId, $questCode, $points) {
        // Puan ekle
        $stmt = $this->pdo->prepare("
            INSERT INTO PuanGecmisi (kullanici_id, islem_tipi, puan, aciklama)
            VALUES (?, 'quest', ?, ?)
        ");
        $stmt->execute([$userId, $points, "Quest tamamlandı: {$questCode}"]);
        
        $stmt = $this->pdo->prepare("
            UPDATE Kullanicilar 
            SET toplam_puan = toplam_puan + ? 
            WHERE kullanici_id = ?
        ");
        $stmt->execute([$points, $userId]);
    }
    
    /**
     * Quest master rozeti kontrolü
     */
    private function checkQuestMasterBadge($userId) {
        // Toplam tamamlanan quest sayısı
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM KullaniciQuestleri 
            WHERE kullanici_id = ? AND tamamlandi = 1
        ");
        $stmt->execute([$userId]);
        $totalCompleted = $stmt->fetchColumn();
        
        require_once 'GamificationService.php';
        $gamification = new GamificationService($this->pdo);
        
        // 10 quest
        if ($totalCompleted >= 10 && !$this->hasBadge($userId, 'quest_master_10')) {
            $this->awardBadge($userId, 'quest_master_10');
        }
        
        // 50 quest
        if ($totalCompleted >= 50 && !$this->hasBadge($userId, 'quest_master_50')) {
            $this->awardBadge($userId, 'quest_master_50');
        }
        
        // 100 quest
        if ($totalCompleted >= 100 && !$this->hasBadge($userId, 'quest_master_100')) {
            $this->awardBadge($userId, 'quest_master_100');
        }
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
                return true;
            } catch (PDOException $e) {
                return false;
            }
        }
        return false;
    }
    
    /**
     * Günlük questleri sıfırla (Cron job için)
     */
    public function resetDailyQuests() {
        // Günlük questlerin bir önceki günkü kayıtlarını temizle
        // (Hafta numarası değişmediği için eski günlük kayıtları silebiliriz)
        $stmt = $this->pdo->prepare("
            DELETE kq FROM KullaniciQuestleri kq
            JOIN Questler q ON kq.quest_id = q.quest_id
            WHERE q.quest_tipi = 'gunluk' 
            AND kq.hafta_numarasi < ?
        ");
        $stmt->execute([date('Ymd')]);
        
        return ['success' => true, 'message' => 'Günlük questler sıfırlandı'];
    }
    
    /**
     * Haftalık questleri sıfırla (Cron job için)
     */
    public function resetWeeklyQuests() {
        // Önceki haftaların kayıtlarını temizle
        $stmt = $this->pdo->prepare("
            DELETE kq FROM KullaniciQuestleri kq
            JOIN Questler q ON kq.quest_id = q.quest_id
            WHERE q.quest_tipi = 'haftalik' 
            AND kq.hafta_numarasi < ?
        ");
        $stmt->execute([date('oW')]);
        
        return ['success' => true, 'message' => 'Haftalık questler sıfırlandı'];
    }
    
    /**
     * Kullanıcının quest tamamlama istatistikleri
     */
    public function getUserQuestStats($userId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as toplam_tamamlanan,
                SUM(q.odul_puan) as toplam_puan_kazanildi
            FROM KullaniciQuestleri kq
            JOIN Questler q ON kq.quest_id = q.quest_id
            WHERE kq.kullanici_id = ? AND kq.tamamlandi = 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
