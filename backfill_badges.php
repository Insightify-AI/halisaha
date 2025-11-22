<?php
/**
 * Backfill Badges Script
 * Bu script mevcut kullanıcıları kontrol eder ve şartları sağlayanları rozetlerini verir.
 * Ayrıca puan geçmişini kontrol edip toplam puanları günceller.
 */

require_once 'includes/db.php';
require_once 'includes/GamificationService.php';

echo "<h2>Rozet Backfill İşlemi Başlatılıyor...</h2>";

try {
    $gamification = new GamificationService($pdo);
    
    // Tüm müşterileri al
    $stmt = $pdo->query("SELECT kullanici_id FROM Kullanicilar WHERE rol = 'musteri'");
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $totalBadgesAwarded = 0;
    $userBadgeCounts = [];
    
    foreach ($users as $userId) {
        echo "<p>Kullanıcı $userId kontrol ediliyor...</p>";
        
        // Rozet kontrolü yap (yeni rozetler döndürülecek)
        $newBadges = $gamification->checkBadges($userId);
        
        if (!empty($newBadges)) {
            $count = count($newBadges);
            $totalBadgesAwarded += $count;
            $userBadgeCounts[$userId] = $count;
            
            echo "<div style='margin-left: 20px; color: green;'>
                    ✓ $count yeni rozet verildi: " . implode(', ', array_column($newBadges, 'rozet_adi')) . "
                  </div>";
        } else {
            echo "<div style='margin-left: 20px; color: gray;'>- Yeni rozet yok</div>";
        }
    }
    
    // Puanları güncelle (puan geçmişinden)
    echo "<hr><h3>Puan Güncellemeleri</h3>";
    $stmt = $pdo->query("
        UPDATE Kullanicilar k
        SET toplam_puan = (
            SELECT COALESCE(SUM(pg.puan), 0)
            FROM PuanGecmisi pg
            WHERE pg.kullanici_id = k.kullanici_id
        )
        WHERE k.rol = 'musteri'
    ");
    echo "<p style='color: blue;'>✓ Tüm kullanıcı puanları güncellendi.</p>";
    
    // Özet
    echo "<hr><h3>İşlem Özeti</h3>";
    echo "<ul>";
    echo "<li><strong>Kontrol edilen kullanıcı sayısı:</strong> " . count($users) . "</li>";
    echo "<li><strong>Toplam verilen rozet:</strong> $totalBadgesAwarded</li>";
    echo "<li><strong>Rozet alan kullanıcı sayısı:</strong> " . count($userBadgeCounts) . "</li>";
    echo "</ul>";
    
    if (!empty($userBadgeCounts)) {
        echo "<h4>Detaylar:</h4><ul>";
        foreach ($userBadgeCounts as $uid => $count) {
            echo "<li>Kullanıcı #$uid: $count rozet</li>";
        }
        echo "</ul>";
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3 style='color: #155724;'>✓ Backfill İşlemi Tamamlandı!</h3>";
    echo "<p>Tüm kullanıcılar kontrol edildi ve şartları sağlayanlar rozetlerini aldı.</p>";
    echo "</div>";
    
    echo "<br><a href='index.php' class='btn btn-primary' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Ana Sayfaya Dön</a>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<h3>❌ Hata Oluştu!</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h2, h3, h4 {
        color: #333;
    }
    p {
        margin: 5px 0;
    }
</style>
