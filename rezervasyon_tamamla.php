<?php
require_once 'includes/db.php';
session_start();

// Sadece POST isteği ile gelindiyse çalış
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['kullanici_id'])) {
    
    $musteri_id = $_SESSION['rol_id']; // Musteriler tablosundaki ID (Login.php'de ayarlamıştık)
    $saha_id = $_POST['saha_id'];
    $saat_id = $_POST['saat_id'];
    $tarih = $_POST['tarih'];
    $fiyat = $_POST['fiyat']; // Gerçek projede fiyatı backend'den tekrar sorgulamak daha güvenlidir ama şimdilik OK.

    try {
        // 1. REZERVASYONU KAYDET
        // Insert işlemi
        $sql = "INSERT INTO Rezervasyonlar (musteri_id, saha_id, saat_id, tarih, durum) 
                VALUES (:musteri, :saha, :saat, :tarih, 'onay_bekliyor')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':musteri' => $musteri_id,
            ':saha' => $saha_id,
            ':saat' => $saat_id,
            ':tarih' => $tarih
        ]);
        
        $rezervasyon_id = $pdo->lastInsertId();

        // 2. ÖDEME KAYDI OLUŞTUR (Bekliyor statüsünde)
        // 1-1 İlişki gereği ödeme tablosuna da kayıt atıyoruz
        $sqlOdeme = "INSERT INTO Odemeler (rezervasyon_id, tutar, odeme_yontemi, durum) 
                     VALUES (:rid, :tutar, 'nakit', 'bekliyor')";
        $stmtOdeme = $pdo->prepare($sqlOdeme);
        $stmtOdeme->execute([
            ':rid' => $rezervasyon_id,
            ':tutar' => $fiyat
        ]);

        // 3. BAŞARILI SAYFASINA YÖNLENDİR
        // Kullanıcıyı profilindeki rezervasyonlarım sekmesine veya bir teşekkür sayfasına atabiliriz.
        // Biz basit bir "Başarılı" mesajı gösterip Profile atalım.
        echo "<!DOCTYPE html>
        <html lang='tr'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>İşlem Başarılı</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
        </head>
        <body class='bg-light d-flex align-items-center justify-content-center' style='height: 100vh;'>
            <div class='text-center'>
                <div class='mb-4'>
                    <img src='https://cdn-icons-png.flaticon.com/512/190/190411.png' width='100'>
                </div>
                <h1 class='text-success fw-bold'>Rezervasyon Başarılı!</h1>
                <p class='lead'>Talebiniz tesis sahibine iletildi. Onay durumunu profilinden takip edebilirsin.</p>
                <a href='index.php' class='btn btn-primary btn-lg mt-3'>Anasayfaya Dön</a>
            </div>
        </body>
        </html>";
        exit;

    } catch (PDOException $e) {
        // Eğer "Duplicate Entry" hatası alırsak (Aynı saatte başkası almışsa)
        if ($e->getCode() == 23000) {
            echo "<h3>HATA: Üzgünüz, seçtiğiniz saat az önce başkası tarafından alındı!</h3>";
            echo "<a href='javascript:history.back()'>Geri Dön ve Başka Saat Seç</a>";
        } else {
            echo "Sistem Hatası: " . $e->getMessage();
        }
    }

} else {
    header("Location: index.php"); // Sayfaya direkt girmeye çalışanı kov
}
?>