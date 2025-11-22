<?php
require_once 'includes/db.php';
require_once 'includes/QuestService.php';
require_once 'includes/GamificationService.php';

// Log dosyasını ayarla
$logFile = 'logs/cron_weekly.log';
function logMessage($message) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

try {
    logMessage("Haftalık cron job başladı.");
    
    $questService = new QuestService($pdo);
    $gamification = new GamificationService($pdo);
    
    // 1. Haftalık Questleri Sıfırla
    $questService->resetWeeklyQuests();
    logMessage("Haftalık questler sıfırlandı.");
    
    // 2. Haftalık Liderlik Tablosunu Arşivle ve Sıfırla
    // Bu adımda:
    // a) Şu anki liderleri bul
    // b) İlk 3'e rozet/ödül ver
    // c) PuanGecmisi tablosundaki 'haftalik_puan' flag'ini sıfırla veya yeni hafta başlat
    
    // NOT: Şu anki yapıda v_HaftalikPuanlar view'ı son 7 günü baz alıyor.
    // Eğer sabit bir "Hafta" mantığı (Pazartesi-Pazar) istiyorsak,
    // PuanGecmisi tablosuna 'hafta_id' ekleyip her hafta artırmamız gerekirdi.
    // Şimdilik dinamik son 7 gün kullanıldığı için sıfırlamaya gerek yok.
    // Ancak "Haftanın Lideri" rozetini dağıtmak için bu scripti Pazar gecesi çalıştırabiliriz.
    
    $stmt = $pdo->prepare("SELECT * FROM v_HaftalikPuanlar ORDER BY haftalik_puan DESC LIMIT 3");
    $stmt->execute();
    $leaders = $stmt->fetchAll();
    
    if ($leaders) {
        foreach ($leaders as $index => $leader) {
            $userId = $leader['kullanici_id'];
            $rank = $index + 1;
            
            // Liderlik rozeti ver
            if ($rank == 1) {
                $gamification->awardBadge($userId, 'haftalik_lider');
                // Bonus puan ver
                $gamification->addPoints($userId, 'haftalik_odul', 500, 'Haftalık Liderlik Ödülü');
                logMessage("Kullanıcı $userId haftalık lider oldu. Ödüller verildi.");
            }
        }
    }
    
    logMessage("Haftalık cron job başarıyla tamamlandı.");
    
} catch (Exception $e) {
    logMessage("HATA: " . $e->getMessage());
}
?>
