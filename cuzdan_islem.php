<?php
require_once 'includes/db.php';

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
            $bonus = $tutar * 0.20;
        } elseif ($tutar >= 500) {
            $bonus = $tutar * 0.15;
        } elseif ($tutar >= 250) {
            $bonus = $tutar * 0.12;
        } elseif ($tutar >= 100) {
            $bonus = $tutar * 0.10;
        }

        $toplam_yuklenecek = $tutar + $bonus;

        // Transaction Başlat
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

        // DEĞİŞİKLİKLERİ ONAYLA (Veritabanına İşle)
        $pdo->commit();

        // Güncel bakiyeyi çek
        $stmt = $pdo->prepare("SELECT bakiye FROM Kullanicilar WHERE kullanici_id = ?");
        $stmt->execute([$kullanici_id]);
        $guncel_bakiye = $stmt->fetchColumn();

        $yeni_bakiye_fmt = number_format($guncel_bakiye, 2);
        $bonus_fmt = number_format($bonus, 2);
        $tarih_fmt = date('d.m.Y H:i');

        // Yeni işlem satırı HTML
        $new_row_html = '
        <tr class="table-success">
            <td class="ps-4">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle p-2 me-3 bg-success-subtle">
                        <i class="fas fa-arrow-down text-success"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-capitalize">yukleme</div>
                        <small class="text-muted">Kredi Kartı ile Yükleme</small>
                    </div>
                </div>
            </td>
            <td class="text-muted small">' . $tarih_fmt . '</td>
            <td class="text-end pe-4">
                <span class="fw-bold text-success">+' . number_format($tutar, 2) . ' ₺</span>
            </td>
        </tr>';

        if ($bonus > 0) {
            $new_row_html = '
            <tr class="table-warning">
                <td class="ps-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle p-2 me-3 bg-warning-subtle">
                            <i class="fas fa-gift text-warning"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-capitalize">bonus</div>
                            <small class="text-muted">Yükleme Bonusu</small>
                        </div>
                    </div>
                </td>
                <td class="text-muted small">' . $tarih_fmt . '</td>
                <td class="text-end pe-4">
                    <span class="fw-bold text-success">+' . $bonus_fmt . ' ₺</span>
                </td>
            </tr>' . $new_row_html;
        }

        echo json_encode([
            'success' => true, 
            'message' => 'Bakiye başarıyla yüklendi!',
            'yeni_bakiye' => $yeni_bakiye_fmt,
            'bonus' => $bonus_fmt,
            'new_row_html' => $new_row_html
        ]);

    } elseif ($action === 'sanal_kart_olustur') {

        // DÜZELTME 1: 'id' yerine 'kart_id' kullanıldı
        $stmt = $pdo->prepare("SELECT kart_id FROM SanalKartlar WHERE kullanici_id = ?");
        $stmt->execute([$kullanici_id]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Zaten bir sanal kartınız var.");
        }

        // İYİLEŞTİRME: İşlem bütünlüğü için Transaction başlatıldı
        $pdo->beginTransaction();

        $kart_no = '5' . rand(100, 999) . ' ' . rand(1000, 9999) . ' ' . rand(1000, 9999) . ' ' . rand(1000, 9999);
        $son_kullanim = date('m/y', strtotime('+5 years'));
        $cvv = rand(100, 999);

        // Kartı Oluştur
        $stmt = $pdo->prepare("INSERT INTO SanalKartlar (kullanici_id, kart_numarasi, son_kullanma_tarihi, cvv) VALUES (?, ?, ?, ?)");
        $stmt->execute([$kullanici_id, $kart_no, $son_kullanim, $cvv]);

        // İlk Kart Bonusu
        $ilk_bonus = 50.00;
        $stmt = $pdo->prepare("UPDATE Kullanicilar SET bakiye = bakiye + ? WHERE kullanici_id = ?");
        $stmt->execute([$ilk_bonus, $kullanici_id]);

        // Bonus Logu
        $stmt = $pdo->prepare("INSERT INTO CuzdanHareketleri (kullanici_id, islem_tipi, tutar, aciklama) VALUES (?, 'bonus', ?, ?)");
        $stmt->execute([$kullanici_id, $ilk_bonus, "Sanal Kart Oluşturma Bonusu"]);

        // İYİLEŞTİRME: İşlemleri onayla
        $pdo->commit();

        // Güncel bakiyeyi çek (Ekranda göstermek için)
        $stmt = $pdo->prepare("SELECT bakiye FROM Kullanicilar WHERE kullanici_id = ?");
        $stmt->execute([$kullanici_id]);
        $guncel_bakiye = $stmt->fetchColumn();

        $tarih_fmt = date('d.m.Y H:i');
        
        $new_row_html = '
        <tr class="table-warning">
            <td class="ps-4">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle p-2 me-3 bg-warning-subtle">
                        <i class="fas fa-gift text-warning"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-capitalize">bonus</div>
                        <small class="text-muted">Sanal Kart Oluşturma Bonusu</small>
                    </div>
                </div>
            </td>
            <td class="text-muted small">' . $tarih_fmt . '</td>
            <td class="text-end pe-4">
                <span class="fw-bold text-success">+' . number_format($ilk_bonus, 2) . ' ₺</span>
            </td>
        </tr>';

        echo json_encode([
            'success' => true, 
            'message' => 'Sanal kartınız oluşturuldu ve 50₺ bonus yüklendi!',
            'kart_no' => $kart_no,
            'son_kullanim' => $son_kullanim,
            'yeni_bakiye' => number_format($guncel_bakiye, 2),
            'new_row_html' => $new_row_html
        ]);

    } else {
        throw new Exception("Geçersiz işlem.");
    }

} catch (Exception $e) {
    // Hata durumunda yapılan işlemleri geri al
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
