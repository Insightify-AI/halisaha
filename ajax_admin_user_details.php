<?php
require_once 'includes/db.php';

// GÜVENLİK - Admin tüm kullanıcıların bilgilerini görebilir
if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim. Sadece admin kullanıcılar bu bilgilere erişebilir.']);
    exit;
}

// Kullanıcı ID kontrolü
if (!isset($_GET['kullanici_id'])) {
    echo json_encode(['success' => false, 'message' => 'Kullanıcı ID bulunamadı']);
    exit;
}

$kullanici_id = $_GET['kullanici_id'];

// Admin her kullanıcının bilgilerini görebilir

try {
    // Kullanıcı Temel Bilgileri
    $stmt = $pdo->prepare("
        SELECT 
            k.*,
            DATE_FORMAT(k.kayit_tarihi, '%d.%m.%Y %H:%i') as kayit_tarihi_fmt,
            COALESCE(k.bakiye, 0) as bakiye
        FROM Kullanicilar k
        WHERE k.kullanici_id = ?
    ");
    $stmt->execute([$kullanici_id]);
    $kullanici = $stmt->fetch();
    
    if (!$kullanici) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
        exit;
    }
    
    // Rezervasyon İstatistikleri
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as toplam_rezervasyon,
            SUM(CASE WHEN durum = 'tamamlandi' THEN 1 ELSE 0 END) as tamamlanan,
            SUM(CASE WHEN durum = 'onaylandi' THEN 1 ELSE 0 END) as onaylanan,
            SUM(CASE WHEN durum = 'onay_bekliyor' THEN 1 ELSE 0 END) as bekleyen,
            SUM(CASE WHEN durum = 'iptal' THEN 1 ELSE 0 END) as iptal
        FROM Rezervasyonlar
        WHERE musteri_id = ?
    ");
    $stmt->execute([$kullanici_id]);
    $rezervasyon_stats = $stmt->fetch();
    
    // Toplam Harcama (Odemeler tablosu rezervasyon üzerinden erişilir)
    $stmt = $pdo->prepare("
        SELECT SUM(o.tutar) as toplam_harcama
        FROM Odemeler o
        JOIN Rezervasyonlar r ON o.rezervasyon_id = r.rezervasyon_id
        WHERE r.musteri_id = ? AND o.durum = 'basarili'
    ");
    $stmt->execute([$kullanici_id]);
    $harcama = $stmt->fetch();
    
    // Favoriler
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as favori_sayisi
        FROM Favoriler
        WHERE kullanici_id = ?
    ");
    $stmt->execute([$kullanici_id]);
    $favori = $stmt->fetch();
    
    // Yorumlar (Yorumlar tablosunda musteri_id kullanılıyor)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as yorum_sayisi
        FROM Yorumlar
        WHERE musteri_id = ?
    ");
    $stmt->execute([$kullanici_id]);
    $yorum = $stmt->fetch();
    
    // Kullanıcının Tesisleri (Eğer tesis sahibiyse)
    $tesisler = [];
    if ($kullanici['rol'] == 'tesis_sahibi') {
        $stmt = $pdo->prepare("
            SELECT 
                t.tesis_id,
                t.tesis_adi as ad,
                s.sehir_adi as sehir,
                t.onay_durumu as durum
            FROM Tesisler t
            JOIN Ilceler i ON t.ilce_id = i.ilce_id
            JOIN Sehirler s ON i.sehir_id = s.sehir_id
            WHERE t.tesis_sahibi_id = ?
        ");
        $stmt->execute([$kullanici_id]);
        $tesisler = $stmt->fetchAll();
    }
    
    // Son Aktiviteler
    $stmt = $pdo->prepare("
        SELECT 
            'Rezervasyon' as tip,
            r.tarih,
            CONCAT(t.tesis_adi, ' - ', DATE_FORMAT(r.tarih, '%d.%m.%Y'), ' ', TIME_FORMAT(sb.baslangic_saati, '%H:%i')) as detay,
            r.durum
        FROM Rezervasyonlar r
        JOIN Sahalar s ON r.saha_id = s.saha_id
        JOIN Tesisler t ON s.tesis_id = t.tesis_id
        JOIN SaatBloklari sb ON r.saat_id = sb.saat_id
        WHERE r.musteri_id = ?
        ORDER BY r.tarih DESC, sb.baslangic_saati DESC
        LIMIT 5
    ");
    $stmt->execute([$kullanici_id]);
    $aktiviteler = $stmt->fetchAll();
    
    // Rozet Bilgileri
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as rozet_sayisi
        FROM KullaniciRozetleri
        WHERE kullanici_id = ?
    ");
    $stmt->execute([$kullanici_id]);
    $rozet = $stmt->fetch();
    
    // Görev İstatistikleri
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as toplam_gorev,
            SUM(CASE WHEN tamamlandi = 1 THEN 1 ELSE 0 END) as tamamlanan_gorev
        FROM KullaniciQuestleri
        WHERE kullanici_id = ?
    ");
    $stmt->execute([$kullanici_id]);
    $gorev = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'kullanici' => $kullanici,
        'istatistikler' => [
            'rezervasyon' => $rezervasyon_stats,
            'harcama' => $harcama['toplam_harcama'] ?? 0,
            'favori' => $favori['favori_sayisi'] ?? 0,
            'yorum' => $yorum['yorum_sayisi'] ?? 0,
            'rozet' => $rozet['rozet_sayisi'] ?? 0,
            'gorev' => $gorev
        ],
        'tesisler' => $tesisler,
        'aktiviteler' => $aktiviteler
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>
