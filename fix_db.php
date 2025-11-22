<?php
require_once 'includes/db.php';

function runSqlFile($pdo, $file) {
    if (!file_exists($file)) {
        echo "Dosya bulunamadı: $file<br>";
        return;
    }
    
    $sql = file_get_contents($file);
    
    try {
        $pdo->exec($sql);
        echo "Başarılı: $file çalıştırıldı.<br>";
    } catch (PDOException $e) {
        echo "Hata ($file): " . $e->getMessage() . "<br>";
    }
}

echo "<h2>Veritabanı Güncellemesi Başlatılıyor...</h2>";

// 1. Önce yorumlar tablosunu güncelle (Eksik kolonlar vs.)
runSqlFile($pdo, 'update_yorumlar_table.sql');

// 2. Sosyal ve Gamification tablolarını ekle
runSqlFile($pdo, 'social_gamification.sql');

echo "<h3>İşlem Tamamlandı.</h3>";
echo "<a href='index.php'>Anasayfaya Dön</a>";
?>
