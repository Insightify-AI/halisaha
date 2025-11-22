<?php
require_once 'db.php';

class GamificationService {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Puan Ekleme
    public function addPoints($userId, $actionType, $points, $description) {
        try {
            // 1. Puan Geçmişine Ekle
            $stmt = $this->pdo->prepare("INSERT INTO PuanGecmisi (kullanici_id, islem_tipi, puan, aciklama) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $actionType, $points, $description]);

            // 2. Kullanıcı Toplam Puanını Güncelle
            $stmt = $this->pdo->prepare("UPDATE Kullanicilar SET toplam_puan = toplam_puan + ? WHERE kullanici_id = ?");
            $stmt->execute([$points, $userId]);

            // 3. Rozet Kontrolü Yap
            $this->checkBadges($userId);

            return true;
        } catch (PDOException $e) {
            // Log error
            return false;
        }
    }

    // Rozet Kontrolü ve Verme
    public function checkBadges($userId) {
        $newBadges = [];

        // 1. İlk Yorum Rozeti
        if (!$this->hasBadge($userId, 'ilk_yorum')) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM Yorumlar WHERE musteri_id = ?");
            $stmt->execute([$userId]);
            if ($stmt->fetchColumn() >= 1) {
                $this->awardBadge($userId, 'ilk_yorum');
                $newBadges[] = 'İlk Yorum';
            }
        }

        // 2. 5 Rezervasyon Rozeti
        if (!$this->hasBadge($userId, '5_rezervasyon')) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM Rezervasyonlar WHERE musteri_id = ? AND durum = 'tamamlandi'");
            $stmt->execute([$userId]);
            if ($stmt->fetchColumn() >= 5) {
                $this->awardBadge($userId, '5_rezervasyon');
                $newBadges[] = '5 Rezervasyon';
            }
        }

        // 3. Sadık Müşteri (10 Rezervasyon)
        if (!$this->hasBadge($userId, 'sadik_musteri')) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM Rezervasyonlar WHERE musteri_id = ? AND durum = 'tamamlandi'");
            $stmt->execute([$userId]);
            if ($stmt->fetchColumn() >= 10) {
                $this->awardBadge($userId, 'sadik_musteri');
                $newBadges[] = 'Sadık Müşteri';
            }
        }

        return $newBadges;
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
        // Rozet ID'sini bul
        $stmt = $this->pdo->prepare("SELECT rozet_id FROM Rozetler WHERE rozet_kodu = ?");
        $stmt->execute([$badgeCode]);
        $rozetId = $stmt->fetchColumn();

        if ($rozetId) {
            $stmt = $this->pdo->prepare("INSERT INTO KullaniciRozetleri (kullanici_id, rozet_id) VALUES (?, ?)");
            $stmt->execute([$userId, $rozetId]);
            
            // Rozet kazanma bildirimi (Opsiyonel: Bildirim tablosu varsa oraya eklenebilir)
        }
    }

    // Liderlik Tablosu
    public function getLeaderboard($limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT ad, soyad, toplam_puan, 
            (SELECT COUNT(*) FROM Rezervasyonlar WHERE musteri_id = k.kullanici_id AND durum = 'tamamlandi') as rezervasyon_sayisi
            FROM Kullanicilar k
            WHERE rol = 'musteri'
            ORDER BY toplam_puan DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Kupon Kullanımı
    public function redeemCoupon($userId, $couponId) {
        // 1. Kupon ve Puan Kontrolü
        $stmt = $this->pdo->prepare("SELECT * FROM Kuponlar WHERE kupon_id = ?");
        $stmt->execute([$couponId]);
        $coupon = $stmt->fetch();

        if (!$coupon) return ['success' => false, 'message' => 'Kupon bulunamadı.'];

        $stmt = $this->pdo->prepare("SELECT toplam_puan FROM Kullanicilar WHERE kullanici_id = ?");
        $stmt->execute([$userId]);
        $userPoints = $stmt->fetchColumn();

        if ($userPoints < $coupon['gerekli_puan']) {
            return ['success' => false, 'message' => 'Yetersiz puan.'];
        }

        // 2. Puan Düş ve Kuponu Ver
        try {
            $this->pdo->beginTransaction();

            // Puan düş
            $this->addPoints($userId, 'kupon_alimi', -$coupon['gerekli_puan'], $coupon['kupon_kodu'] . ' kuponu alındı.');

            // Kuponu kullanıcıya tanımla
            $stmt = $this->pdo->prepare("INSERT INTO KullaniciKuponlari (kullanici_id, kupon_id) VALUES (?, ?)");
            $stmt->execute([$userId, $couponId]);

            $this->pdo->commit();
            return ['success' => true, 'message' => 'Kupon başarıyla alındı!'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Bir hata oluştu.'];
        }
    }
}
?>
