<?php
require_once 'includes/db.php';
session_start();

// Sadece POST isteği ile gelindiyse çalış
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['kullanici_id'])) {
    
    $musteri_id = $_SESSION['kullanici_id']; // Sadece Müşteriler rezervasyon yapabilir
    $saha_id = $_POST['saha_id'];
    $saat_id = $_POST['saat_id'];
    $tarih = $_POST['tarih'];
    $fiyat = $_POST['fiyat']; // Gerçek projede fiyatı backend'den tekrar sorgulamak daha güvenlidir ama şimdilik OK.

    try {
        // YENİ SİSTEM: Bakiye kontrolü ve ödeme ile rezervasyon oluştur
        $stmt = $pdo->prepare("CALL sp_RezervasyonOlusturVeOde(?, ?, ?, ?, ?)");
        $stmt->execute([
            $musteri_id,
            $saha_id,
            $saat_id,
            $tarih,
            $fiyat
        ]);
        
        $result = $stmt->fetch();
        $stmt->closeCursor();
        
        if ($result && $result['status'] == 'SUCCESS') {
            // BAŞARILI - Rezervasyon oluşturuldu ve ödeme yapıldı
            $yeni_bakiye = number_format($result['yeni_bakiye'], 2);
            $odenen = number_format($result['odenen_tutar'], 2);
            
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
                    <p class='lead'>Talebiniz tesis sahibine iletildi.</p>
                    <div class='alert alert-info mt-3'>
                        <i class='fas fa-info-circle me-2'></i>
                        <strong>{$odenen}₺</strong> bakiyenizden çekildi.<br>
                        <small>Yeni bakiyeniz: <strong>{$yeni_bakiye}₺</strong></small>
                    </div>
                    <p class='text-muted'><small>Onay durumunu profilinden takip edebilirsin.</small></p>
                    <a href='profil.php' class='btn btn-primary btn-lg mt-3 me-2'>Profilim</a>
                    <a href='index.php' class='btn btn-outline-secondary btn-lg mt-3'>Anasayfa</a>
                </div>
            </body>
            </html>";
            exit;
        }

    } catch (PDOException $e) {
        // HATA YÖNETİMİ
        
        // Yetersiz bakiye hatası
        if (strpos($e->getMessage(), 'Yetersiz bakiye') !== false) {
            echo "<!DOCTYPE html>
            <html lang='tr'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Yetersiz Bakiye</title>
                <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
                <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
            </head>
            <body class='bg-light d-flex align-items-center justify-content-center' style='height: 100vh;'>
                <div class='text-center'>
                    <div class='mb-4'>
                        <i class='fas fa-wallet fa-5x text-warning'></i>
                    </div>
                    <h1 class='text-warning fw-bold'>Yetersiz Bakiye!</h1>
                    <p class='lead'>Rezervasyon yapmak için cüzdanınızda yeterli bakiye bulunmuyor.</p>
                    <div class='alert alert-warning mt-3'>
                        <i class='fas fa-exclamation-triangle me-2'></i>
                        Gerekli tutar: <strong>{$fiyat}₺</strong>
                    </div>
                    <p class='text-muted'>Lütfen cüzdanınıza para yükleyip tekrar deneyin.</p>
                    <a href='cuzdan.php' class='btn btn-warning btn-lg mt-3 me-2'>
                        <i class='fas fa-plus-circle me-2'></i>Bakiye Yükle
                    </a>
                    <a href='javascript:history.back()' class='btn btn-outline-secondary btn-lg mt-3'>Geri Dön</a>
                </div>
            </body>
            </html>";
            exit;
        }
        
        // Duplicate Entry hatası (Aynı saatte başkası almışsa)
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