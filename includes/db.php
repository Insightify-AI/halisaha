<?php
// Session'ı başlat (eğer başlatılmamışsa)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Veritabanı Ayarları
$host = 'localhost';
$dbname = 'halisaha_db';
$username = 'root';  // AMPPS/XAMPP varsayılan kullanıcı adı
$password = 'mysql'; // AMPPS varsayılan şifresi "mysql"dir. Boş ise '' yap.

try {
    // PDO Bağlantısını Oluştur
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Hataları göster
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Verileri dizi olarak çek
        PDO::ATTR_EMULATE_PREPARES   => false,                  // SQL Enjeksiyon koruması
    ];

    $pdo = new PDO($dsn, $username, $password, $options);

    // 1. ZAMAN DİLİMİ AYARLARI (Türkiye Saati)
    date_default_timezone_set('Europe/Istanbul');
    $pdo->exec("SET time_zone = '+03:00'");
    $pdo->exec("SET NAMES 'utf8mb4'");

    // 2. OTOMATİK TAMAMLAMA (Süresi geçen rezervasyonları güncelle)
    // Onaylı olup, tarihi ve bitiş saati geçmiş olanları 'tamamlandi' yap
    $sql = "UPDATE Rezervasyonlar r
            JOIN SaatBloklari sb ON r.saat_id = sb.saat_id
            SET r.durum = 'tamamlandi'
            WHERE r.durum = 'onaylandi'
            AND TIMESTAMP(r.tarih, sb.bitis_saati) < NOW()";
    
    $pdo->exec($sql);

    // Bağlantı başarılıysa sessizce devam et (Ekrana bir şey yazdırma)

} catch (PDOException $e) {
    // Hata varsa kullanıcıya göster
    die("Veritabanı Bağlantı Hatası: " . $e->getMessage());
}
?>