<?php
require_once 'includes/db.php';

echo "<h1>Veritabanı Düzeltme Aracı</h1>";

try {
    // 1. Tekrar eden özellik isimlerini bul
    $sql = "SELECT ozellik_adi, COUNT(*) as sayi 
            FROM Ozellikler 
            GROUP BY ozellik_adi 
            HAVING sayi > 1";
    $duplicates = $pdo->query($sql)->fetchAll();

    if (count($duplicates) == 0) {
        echo "<div style='color: green;'>Tekrar eden özellik bulunamadı. Veritabanı temiz.</div>";
    } else {
        echo "Found " . count($duplicates) . " duplicate features.<br>";

        $pdo->beginTransaction();

        foreach ($duplicates as $dup) {
            $ad = $dup['ozellik_adi'];
            echo "Processing: <strong>$ad</strong><br>";

            // Bu isme sahip tüm ID'leri al
            $stmt = $pdo->prepare("SELECT ozellik_id FROM Ozellikler WHERE ozellik_adi = ? ORDER BY ozellik_id ASC");
            $stmt->execute([$ad]);
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // İlk ID'yi ana (master) olarak kabul et
            $master_id = $ids[0];
            echo " - Master ID: $master_id<br>";

            // Diğer ID'leri döngüye al
            for ($i = 1; $i < count($ids); $i++) {
                $duplicate_id = $ids[$i];
                echo " - Merging Duplicate ID: $duplicate_id -> Master ID: $master_id... ";

                // 1. İlişkileri Güncelle: Eski ID'yi kullananları Master ID'ye yönlendir
                // Ancak Master ID zaten varsa (UNIQUE key hatası olmasın diye) IGNORE veya kontrol gerekebilir.
                // Basitçe UPDATE yapalım, hata verirse (zaten varsa) eskiyi silelim.
                
                $updateSql = "UPDATE IGNORE TesisOzellikIliski SET ozellik_id = ? WHERE ozellik_id = ?";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([$master_id, $duplicate_id]);
                
                // UPDATE IGNORE sonrası hala eski ID'ye sahip satırlar varsa (çakışma nedeniyle güncellenemeyenler),
                // bunlar zaten master_id'ye sahip bir kaydın olduğu tesislerdir. Bunları silebiliriz.
                $deleteRelSql = "DELETE FROM TesisOzellikIliski WHERE ozellik_id = ?";
                $deleteRelStmt = $pdo->prepare($deleteRelSql);
                $deleteRelStmt->execute([$duplicate_id]);

                // 2. Özelliği Sil
                $deleteFeatSql = "DELETE FROM Ozellikler WHERE ozellik_id = ?";
                $deleteFeatStmt = $pdo->prepare($deleteFeatSql);
                $deleteFeatStmt->execute([$duplicate_id]);

                echo "OK.<br>";
            }
        }

        // 3. Gelecekte tekrar olmaması için UNIQUE index ekle
        // Önce index var mı kontrol etmeyelim, try-catch içinde hata verirse zaten vardır.
        try {
            $pdo->exec("ALTER TABLE Ozellikler ADD UNIQUE INDEX unique_ozellik_adi (ozellik_adi)");
            echo "<strong>Başarılı:</strong> 'ozellik_adi' sütununa UNIQUE index eklendi.<br>";
        } catch (PDOException $e) {
            echo "Bilgi: Unique index zaten var veya eklenemedi (" . $e->getMessage() . ")<br>";
        }

        $pdo->commit();
        echo "<h3 style='color: green;'>Tüm düzeltmeler başarıyla tamamlandı!</h3>";
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<h3 style='color: red;'>Hata oluştu: " . $e->getMessage() . "</h3>";
}
?>
<br>
<a href="index.php">Anasayfaya Dön</a>
