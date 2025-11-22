<?php
require_once 'includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['kullanici_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

$kullanici_id = $_SESSION['kullanici_id'];
$action = $_POST['action'] ?? '';

try {
    if ($action === 'bakiye_yukle') {
        $tutar = floatval($_POST['tutar']);
        
        if ($tutar <= 0) {
            throw new Exception("Geçersiz tutar.");
        }

        // Bonus Hesaplama
        $bonus = 0;
        if ($tutar >= 1000) {
            $bonus = $tutar * 0.20; // %20 Bonus
        } elseif ($tutar >= 500) {
            $bonus = $tutar * 0.15; // %15 Bonus
        } elseif ($tutar >= 250) {
            $bonus = $tutar * 0.12; // %12 Bonus
        } elseif ($tutar >= 100) {
            $bonus = $tutar * 0.10; // %10 Bonus
        }

        $toplam_yuklenecek = $tutar + $bonus;

        $pdo->beginTransaction();

        // Bakiyeyi Güncelle
        $stmt = $pdo->prepare("UPDATE Kullanicilar SET bakiye = bakiye + ? WHERE kullanici_id = ?");
        $stmt->execute([$toplam_yuklenecek, $kullanici_id]);

        // Hareket Kaydı - Yükleme
        $stmt = $pdo->prepare("INSERT INTO CuzdanHareketleri (kullanici_id, islem_tipi, tutar, aciklama) VALUES (?, 'yukleme', ?, ?)");
        $stmt->execute([$kullanici_id, $tutar, "Kredi Kartı ile Yükleme"]);

        // Hareket Kaydı - Bonus (Varsa)
        if ($bonus > 0) {
            $stmt = $pdo->prepare("INSERT INTO CuzdanHareketleri (kullanici_id, islem_tipi, tutar, aciklama) VALUES (?, 'bonus', ?, ?)");
            $stmt->execute([$kullanici_id, $bonus, "Yükleme Bonusu"]);
        }

        $pdo->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Bakiye başarıyla yüklendi!',
            'yeni_bakiye' => number_format($toplam_yuklenecek, 2),
            'bonus' => number_format($bonus, 2)
        ]);

    } elseif ($action === 'sanal_kart_olustur') {
        // Sanal Kart Kontrolü
        $stmt = $pdo->prepare("SELECT * FROM SanalKartlar WHERE kullanici_id = ?");
        $stmt->execute([$kullanici_id]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Zaten bir sanal kartınız var.");
        }

        // Rastgele Kart Bilgileri Oluştur
        $kart_no = '5' . rand(100, 999) . ' ' . rand(1000, 9999) . ' ' . rand(1000, 9999) . ' ' . rand(1000, 9999);
        $son_kullanim = date('m/y', strtotime('+5 years'));
        $cvv = rand(100, 999);

        $stmt = $pdo->prepare("INSERT INTO SanalKartlar (kullanici_id, kart_numarasi, son_kullanma_tarihi, cvv) VALUES (?, ?, ?, ?)");
        $stmt->execute([$kullanici_id, $kart_no, $son_kullanim, $cvv]);

        // İlk Kart Bonusu (Opsiyonel)
        $ilk_bonus = 50.00;
        $stmt = $pdo->prepare("UPDATE Kullanicilar SET bakiye = bakiye + ? WHERE kullanici_id = ?");
        $stmt->execute([$ilk_bonus, $kullanici_id]);

        $stmt = $pdo->prepare("INSERT INTO CuzdanHareketleri (kullanici_id, islem_tipi, tutar, aciklama) VALUES (?, 'bonus', ?, ?)");
        $stmt->execute([$kullanici_id, $ilk_bonus, "Sanal Kart Oluşturma Bonusu"]);

        echo json_encode(['success' => true, 'message' => 'Sanal kartınız oluşturuldu ve 50₺ bonus yüklendi!']);

    } else {
        throw new Exception("Geçersiz işlem.");
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
