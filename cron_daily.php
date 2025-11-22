<?php
require_once 'includes/db.php';
require_once 'includes/QuestService.php';

// Log dosyasını ayarla
$logFile = 'logs/cron_daily.log';
function logMessage($message) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

try {
    logMessage("Günlük cron job başladı.");
    
    $questService = new QuestService($pdo);
    
    // 1. Günlük Questleri Sıfırla
    $questService->resetDailyQuests();
    logMessage("Günlük questler sıfırlandı.");
    
    // 2. Çark Çevirme Haklarını Sıfırla
    // Bu işlem için ayrı bir tablo veya sütun kullanıyorsak burada sıfırlamalıyız.
    // Şu anki yapıda CarkCevirmeler tablosuna tarih bazlı insert yapıyoruz, 
    // bu yüzden "hak sıfırlama" yerine "yeni gün kontrolü" zaten kod içinde yapılıyor.
    // Ancak performans için eski logları temizleyebiliriz.
    
    // 3. Streak Kontrolü (Opsiyonel: Dün girmeyenlerin streak'ini sıfırla)
    // StreakService::checkStreakStatus() gibi bir metod eklenebilir.
    // Şu anki yapıda kullanıcı giriş yaptığında kontrol ediliyor.
    
    logMessage("Günlük cron job başarıyla tamamlandı.");
    
} catch (Exception $e) {
    logMessage("HATA: " . $e->getMessage());
}
?>
