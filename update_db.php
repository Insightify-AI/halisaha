<?php
require_once 'includes/db.php';

echo "<h1>Veritabanı Güncelleme Aracı</h1>";

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Güncellemeler başlıyor...<br>";

    // 1. sp_AdminTumRezervasyonlar
    $sql1 = "DROP PROCEDURE IF EXISTS sp_AdminTumRezervasyonlar;
    CREATE PROCEDURE sp_AdminTumRezervasyonlar()
    BEGIN
        SELECT r.*, 
               m.ad as musteri_ad, m.soyad as musteri_soyad, m.telefon as musteri_telefon,
               t.tesis_adi, s.saha_adi,
               sb.baslangic_saati, sb.bitis_saati
        FROM Rezervasyonlar r
        JOIN Kullanicilar m ON r.musteri_id = m.kullanici_id
        JOIN Sahalar s ON r.saha_id = s.saha_id
        JOIN Tesisler t ON s.tesis_id = t.tesis_id
        JOIN SaatBloklari sb ON r.saat_id = sb.saat_id
        ORDER BY r.tarih DESC, sb.baslangic_saati DESC;
    END;";
    $pdo->exec($sql1);
    echo "sp_AdminTumRezervasyonlar oluşturuldu.<br>";

    // 2. sp_AdminRezervasyonDurumGuncelle
    $sql2 = "DROP PROCEDURE IF EXISTS sp_AdminRezervasyonDurumGuncelle;
    CREATE PROCEDURE sp_AdminRezervasyonDurumGuncelle(IN p_rezervasyon_id INT, IN p_yeni_durum VARCHAR(20))
    BEGIN
        UPDATE Rezervasyonlar 
        SET durum = p_yeni_durum 
        WHERE rezervasyon_id = p_rezervasyon_id;
    END;";
    $pdo->exec($sql2);
    echo "sp_AdminRezervasyonDurumGuncelle oluşturuldu.<br>";

    // 3. sp_AdminKullanicilariGetir
    $sql3 = "DROP PROCEDURE IF EXISTS sp_AdminKullanicilariGetir;
    CREATE PROCEDURE sp_AdminKullanicilariGetir()
    BEGIN
        SELECT * FROM Kullanicilar ORDER BY kayit_tarihi DESC;
    END;";
    $pdo->exec($sql3);
    echo "sp_AdminKullanicilariGetir oluşturuldu.<br>";

    // 4. sp_AdminKullaniciSil
    $sql4 = "DROP PROCEDURE IF EXISTS sp_AdminKullaniciSil;
    CREATE PROCEDURE sp_AdminKullaniciSil(IN p_kullanici_id INT)
    BEGIN
        DELETE FROM Kullanicilar WHERE kullanici_id = p_kullanici_id;
    END;";
    $pdo->exec($sql4);
    echo "sp_AdminKullaniciSil oluşturuldu.<br>";

    // 5. sp_AdminSonHareketler
    $sql5 = "DROP PROCEDURE IF EXISTS sp_AdminSonHareketler;
    CREATE PROCEDURE sp_AdminSonHareketler()
    BEGIN
        SELECT r.tarih, r.durum, 
               m.ad as musteri_ad, m.soyad as musteri_soyad,
               t.tesis_adi
        FROM Rezervasyonlar r
        JOIN Kullanicilar m ON r.musteri_id = m.kullanici_id
        JOIN Sahalar s ON r.saha_id = s.saha_id
        JOIN Tesisler t ON s.tesis_id = t.tesis_id
        ORDER BY r.rezervasyon_id DESC
        LIMIT 5;
    END;";
    $pdo->exec($sql5);
    echo "sp_AdminSonHareketler oluşturuldu.<br>";

    // 6. sp_AdminGrafikSehir
    $sql6 = "DROP PROCEDURE IF EXISTS sp_AdminGrafikSehir;
    CREATE PROCEDURE sp_AdminGrafikSehir()
    BEGIN
        SELECT s.sehir_adi, COUNT(r.rezervasyon_id) as toplam
        FROM Rezervasyonlar r
        JOIN Sahalar sa ON r.saha_id = sa.saha_id
        JOIN Tesisler t ON sa.tesis_id = t.tesis_id
        JOIN Ilceler i ON t.ilce_id = i.ilce_id
        JOIN Sehirler s ON i.sehir_id = s.sehir_id
        GROUP BY s.sehir_adi;
    END;";
    $pdo->exec($sql6);
    echo "sp_AdminGrafikSehir oluşturuldu.<br>";

    // 7. sp_AdminGrafikAylik
    $sql7 = "DROP PROCEDURE IF EXISTS sp_AdminGrafikAylik;
    CREATE PROCEDURE sp_AdminGrafikAylik()
    BEGIN
        SELECT MONTH(r.tarih) as ay, COUNT(r.rezervasyon_id) as toplam
        FROM Rezervasyonlar r
        WHERE YEAR(r.tarih) = YEAR(CURDATE())
        GROUP BY MONTH(r.tarih)
        ORDER BY ay ASC;
    END;";
    $pdo->exec($sql7);
    echo "sp_AdminGrafikAylik oluşturuldu.<br>";

    echo "<h3 style='color: green;'>Tüm güncellemeler başarıyla tamamlandı!</h3>";
    echo "<a href='admin_panel.php'>Admin Paneline Dön</a>";

} catch (PDOException $e) {
    echo "<h3 style='color: red;'>Hata oluştu: " . $e->getMessage() . "</h3>";
}
?>
