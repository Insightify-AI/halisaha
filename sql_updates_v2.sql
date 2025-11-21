-- 1. FAVORİLER TABLOSU
CREATE TABLE IF NOT EXISTS Favoriler (
    favori_id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id INT NOT NULL,
    tesis_id INT NOT NULL,
    ekleme_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favori (kullanici_id, tesis_id),
    FOREIGN KEY (kullanici_id) REFERENCES Kullanicilar(kullanici_id) ON DELETE CASCADE,
    FOREIGN KEY (tesis_id) REFERENCES Tesisler(tesis_id) ON DELETE CASCADE
);

-- 2. ÖZELLİKLER TABLOSU (Master Data)
CREATE TABLE IF NOT EXISTS Ozellikler (
    ozellik_id INT AUTO_INCREMENT PRIMARY KEY,
    ozellik_adi VARCHAR(50) NOT NULL,
    ikon_kodu VARCHAR(50) NOT NULL,
    UNIQUE KEY unique_ozellik_adi (ozellik_adi)
);

-- Varsayılan Özellikleri Ekle (IGNORE ile çakışmaları atla)
INSERT IGNORE INTO Ozellikler (ozellik_adi, ikon_kodu) VALUES 
('Otopark', 'fa-parking'),
('Duş', 'fa-shower'),
('Soyunma Odası', 'fa-tshirt'),
('Kafeterya', 'fa-coffee'),
('WiFi', 'fa-wifi'),
('Kredi Kartı', 'fa-credit-card'),
('Tribün', 'fa-users'),
('Kamera Kaydı', 'fa-video');

-- 3. TESİS - ÖZELLİK İLİŞKİ TABLOSU
CREATE TABLE IF NOT EXISTS TesisOzellikIliski (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tesis_id INT NOT NULL,
    ozellik_id INT NOT NULL,
    FOREIGN KEY (tesis_id) REFERENCES Tesisler(tesis_id) ON DELETE CASCADE,
    FOREIGN KEY (ozellik_id) REFERENCES Ozellikler(ozellik_id) ON DELETE CASCADE
);

-- 4. STORED PROCEDURE: Favori Ekle/Çıkar
DELIMITER //
DROP PROCEDURE IF EXISTS sp_FavoriToggle //
CREATE PROCEDURE sp_FavoriToggle(IN p_kullanici_id INT, IN p_tesis_id INT)
BEGIN
    IF EXISTS (SELECT 1 FROM Favoriler WHERE kullanici_id = p_kullanici_id AND tesis_id = p_tesis_id) THEN
        DELETE FROM Favoriler WHERE kullanici_id = p_kullanici_id AND tesis_id = p_tesis_id;
        SELECT 'silindi' as islem;
    ELSE
        INSERT INTO Favoriler (kullanici_id, tesis_id) VALUES (p_kullanici_id, p_tesis_id);
        SELECT 'eklendi' as islem;
    END IF;
END //
DELIMITER ;

-- 5. STORED PROCEDURE: Kullanıcının Favorilerini Getir (DÜZELTİLMİŞ)
DELIMITER //
DROP PROCEDURE IF EXISTS sp_KullaniciFavorileri //
CREATE PROCEDURE sp_KullaniciFavorileri(IN p_kullanici_id INT)
BEGIN
    SELECT T.*, S.sehir_adi, I.ilce_adi
    FROM Favoriler F
    JOIN Tesisler T ON F.tesis_id = T.tesis_id
    JOIN Ilceler I ON T.ilce_id = I.ilce_id
    JOIN Sehirler S ON I.sehir_id = S.sehir_id
    WHERE F.kullanici_id = p_kullanici_id
    ORDER BY F.ekleme_tarihi DESC;
END //
DELIMITER ;

-- 6. STORED PROCEDURE: Rezervasyon İptal Et
DELIMITER //
DROP PROCEDURE IF EXISTS sp_RezervasyonIptal //
CREATE PROCEDURE sp_RezervasyonIptal(IN p_rezervasyon_id INT, IN p_kullanici_id INT)
BEGIN
    UPDATE Rezervasyonlar 
    SET durum = 'iptal' 
    WHERE rezervasyon_id = p_rezervasyon_id 
    AND musteri_id = (SELECT rol_id FROM Kullanicilar WHERE kullanici_id = p_kullanici_id AND rol = 'musteri')
    AND durum = 'onay_bekliyor'; 
END //
DELIMITER ;

-- 7. STORED PROCEDURE: Tesis Özelliklerini Getir
DELIMITER //
DROP PROCEDURE IF EXISTS sp_TesisOzellikleriGetir //
CREATE PROCEDURE sp_TesisOzellikleriGetir(IN p_tesis_id INT)
BEGIN
    SELECT O.ozellik_adi, O.ikon_kodu
    FROM TesisOzellikIliski T
    JOIN Ozellikler O ON T.ozellik_id = O.ozellik_id
    WHERE T.tesis_id = p_tesis_id;
END //
DELIMITER ;

-- 8. STORED PROCEDURE: Tesis Özellik Bağla
DELIMITER //
DROP PROCEDURE IF EXISTS sp_TesisOzellikBagla //
CREATE PROCEDURE sp_TesisOzellikBagla(IN p_tesis_id INT, IN p_ozellik_id INT)
BEGIN
    INSERT INTO TesisOzellikIliski (tesis_id, ozellik_id) VALUES (p_tesis_id, p_ozellik_id);
END //
DELIMITER ;

-- 9. STORED PROCEDURE: Admin Tüm Rezervasyonları Getir (YENİ)
DELIMITER //
DROP PROCEDURE IF EXISTS sp_AdminTumRezervasyonlar //
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
END //
DELIMITER ;

-- 10. STORED PROCEDURE: Admin Rezervasyon Durum Güncelle
DELIMITER //
DROP PROCEDURE IF EXISTS sp_AdminRezervasyonDurumGuncelle //
CREATE PROCEDURE sp_AdminRezervasyonDurumGuncelle(IN p_rezervasyon_id INT, IN p_yeni_durum VARCHAR(20))
BEGIN
    UPDATE Rezervasyonlar 
    SET durum = p_yeni_durum 
    WHERE rezervasyon_id = p_rezervasyon_id;
END //
DELIMITER ;
