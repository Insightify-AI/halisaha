<?php
require_once 'includes/db.php';
require_once 'includes/QuestService.php';

echo "Engagement verileri başlatılıyor...<br>";

try {
    $questService = new QuestService($pdo);
    
    // 1. Tüm Müşterileri Çek
    $stmt = $pdo->query("SELECT kullanici_id, ad, soyad FROM Kullanicilar WHERE rol = 'musteri'");
    $users = $stmt->fetchAll();
    
    echo "Toplam " . count($users) . " müşteri bulundu.<br>";
    
    foreach ($users as $user) {
        $userId = $user['kullanici_id'];
        echo "Kullanıcı işleniyor: " . $user['ad'] . " " . $user['soyad'] . "... ";
        
        // 2. Streak Kaydı Oluştur (Yoksa)
        $stmtStreak = $pdo->prepare("INSERT IGNORE INTO StreakTakibi (kullanici_id, mevcut_streak, en_uzun_streak, son_checkin_tarihi) VALUES (?, 0, 0, NULL)");
        $stmtStreak->execute([$userId]);
        
        // 3. Günlük Questleri Ata
        // QuestService::getDailyQuests fonksiyonu zaten questleri kontrol edip yoksa atıyor.
        // Bu yüzden sadece çağırmamız yeterli.
        $questService->getDailyQuests($userId);
        
        // 4. Haftalık Questleri Ata
        $questService->getWeeklyQuests($userId);
        
        echo "OK.<br>";
    }
    
    echo "<hr><strong>İşlem Başarıyla Tamamlandı!</strong><br>";
    echo "Tüm kullanıcılar için streak kayıtları açıldı ve bugünün görevleri atandı.";
    
} catch (PDOException $e) {
    echo "<br><strong>HATA:</strong> " . $e->getMessage();
}
?>
