<?php
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

    // Bağlantı başarılıysa sessizce devam et (Ekrana bir şey yazdırma)

} catch (PDOException $e) {
    // Hata varsa kullanıcıya göster
    die("Veritabanı Bağlantı Hatası: " . $e->getMessage());
}
?>