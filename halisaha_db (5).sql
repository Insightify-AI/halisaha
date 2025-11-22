-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost
-- Üretim Zamanı: 22 Kas 2025, 19:22:39
-- Sunucu sürümü: 8.0.43
-- PHP Sürümü: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `halisaha_db`
--

DELIMITER $$
--
-- Yordamlar
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AdminBekleyenTesisler` ()   BEGIN
    SELECT t.*, k.ad, k.soyad, k.telefon as sahip_telefon, i.ilce_adi, s.sehir_adi
    FROM Tesisler t
    JOIN Kullanicilar k ON t.tesis_sahibi_id = k.kullanici_id
    JOIN Ilceler i ON t.ilce_id = i.ilce_id
    JOIN Sehirler s ON i.sehir_id = s.sehir_id
    WHERE t.onay_durumu = 0
    ORDER BY t.tesis_id DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AdminGrafikAylik` ()   BEGIN
        SELECT MONTH(r.tarih) as ay, COUNT(r.rezervasyon_id) as toplam
        FROM Rezervasyonlar r
        WHERE YEAR(r.tarih) = YEAR(CURDATE())
        GROUP BY MONTH(r.tarih)
        ORDER BY ay ASC;
    END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AdminGrafikSehir` ()   BEGIN
        SELECT s.sehir_adi, COUNT(r.rezervasyon_id) as toplam
        FROM Rezervasyonlar r
        JOIN Sahalar sa ON r.saha_id = sa.saha_id
        JOIN Tesisler t ON sa.tesis_id = t.tesis_id
        JOIN Ilceler i ON t.ilce_id = i.ilce_id
        JOIN Sehirler s ON i.sehir_id = s.sehir_id
        GROUP BY s.sehir_adi;
    END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AdminIstatistik` ()   BEGIN
    SELECT 
        (SELECT COUNT(*) FROM Kullanicilar WHERE rol != 'admin') as toplam_kullanici,
        (SELECT COUNT(*) FROM Tesisler) as toplam_tesis,
        (SELECT COUNT(*) FROM Tesisler WHERE onay_durumu = 0) as bekleyen_tesis,
        (SELECT IFNULL(SUM(tutar), 0) FROM Odemeler WHERE durum = 'basarili' OR durum = 'bekliyor') as toplam_ciro;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AdminKasaDurumu` ()   BEGIN
    SELECT 
        (SELECT bakiye FROM Kullanicilar WHERE kullanici_id = 1) as guncel_kasa,
        
        (SELECT IFNULL(SUM(tutar), 0) FROM CuzdanHareketleri 
         WHERE kullanici_id = 1 AND islem_tipi = 'komisyon') as toplam_komisyon,
         
        (SELECT IFNULL(SUM(tutar), 0) FROM CuzdanHareketleri 
         WHERE kullanici_id = 1 AND islem_tipi = 'komisyon' 
         AND MONTH(tarih) = MONTH(CURRENT_DATE) AND YEAR(tarih) = YEAR(CURRENT_DATE)) as bu_ay_komisyon;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AdminKullanicilariGetir` ()   BEGIN
        SELECT * FROM Kullanicilar ORDER BY kayit_tarihi DESC;
    END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AdminKullaniciSil` (IN `p_kullanici_id` INT)   BEGIN
    
    IF p_kullanici_id = 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'S??per Admin silinemez!';
    END IF;

    DELETE FROM Kullanicilar WHERE kullanici_id = p_kullanici_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AdminRapor` ()   BEGIN
    SELECT 
        s.sehir_adi, 
        t.tesis_adi, 
        t.ortalama_puan, 
        COUNT(r.rezervasyon_id) as toplam_rezervasyon
    FROM Tesisler t
    JOIN Ilceler i ON t.ilce_id = i.ilce_id
    JOIN Sehirler s ON i.sehir_id = s.sehir_id
    LEFT JOIN Sahalar sh ON t.tesis_id = sh.tesis_id
    LEFT JOIN Rezervasyonlar r ON sh.saha_id = r.saha_id
    GROUP BY t.tesis_id
    ORDER BY s.sehir_adi ASC, t.ortalama_puan DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AdminRezervasyonDurumGuncelle` (IN `p_rezervasyon_id` INT, IN `p_yeni_durum` VARCHAR(20))   BEGIN
        UPDATE Rezervasyonlar 
        SET durum = p_yeni_durum 
        WHERE rezervasyon_id = p_rezervasyon_id;
    END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AdminRolGuncelle` (IN `p_hedef_kullanici_id` INT, IN `p_yeni_rol` VARCHAR(20))   BEGIN
    DECLARE v_eski_rol VARCHAR(20);
    
    
    IF p_hedef_kullanici_id = 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'S??per Admin yetkisi de??i??tirilemez!';
    END IF;
    
    
    SELECT rol INTO v_eski_rol FROM Kullanicilar WHERE kullanici_id = p_hedef_kullanici_id;
    
    
    IF v_eski_rol != p_yeni_rol THEN
        
        
        UPDATE Kullanicilar SET rol = p_yeni_rol WHERE kullanici_id = p_hedef_kullanici_id;
        
        
        IF p_yeni_rol = 'tesis_sahibi' THEN
            INSERT IGNORE INTO TesisSahipleri (sahip_id) VALUES (p_hedef_kullanici_id);
            
        ELSEIF p_yeni_rol = 'admin' THEN
            INSERT IGNORE INTO Adminler (admin_id, yetki_seviyesi) VALUES (p_hedef_kullanici_id, 1);
            
        ELSEIF p_yeni_rol = 'musteri' THEN
            INSERT IGNORE INTO Musteriler (musteri_id) VALUES (p_hedef_kullanici_id);
        END IF;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AdminSonHareketler` ()   BEGIN
    SELECT r.olusturma_tarihi as tarih, r.durum, 
           m.ad as musteri_ad, m.soyad as musteri_soyad,
           t.tesis_adi
    FROM Rezervasyonlar r
    JOIN Kullanicilar m ON r.musteri_id = m.kullanici_id
    JOIN Sahalar s ON r.saha_id = s.saha_id
    JOIN Tesisler t ON s.tesis_id = t.tesis_id
    ORDER BY r.olusturma_tarihi DESC
    LIMIT 10;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AdminTesisDurumGuncelle` (IN `p_tesis_id` INT, IN `p_durum` TINYINT)   BEGIN
    UPDATE Tesisler SET onay_durumu = p_durum WHERE tesis_id = p_tesis_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AdminTumRezervasyonlar` ()   BEGIN
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
    END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_CheckInYap` (IN `p_kullanici_id` INT)   BEGIN
    DECLARE v_bugun DATE;
    DECLARE v_son_checkin DATE;
    DECLARE v_mevcut_streak INT;
    DECLARE v_yeni_streak INT;
    DECLARE v_puan INT;
    DECLARE v_checkin_var INT;
    
    SET v_bugun = CURDATE();
    SET v_puan = 5; -- Varsayılan check-in puanı
    
    -- Bugün zaten check-in yapılmış mı?
    SELECT COUNT(*) INTO v_checkin_var 
    FROM GunlukCheckinler 
    WHERE kullanici_id = p_kullanici_id AND checkin_tarihi = v_bugun;
    
    IF v_checkin_var > 0 THEN
        -- Zaten yapılmışsa mevcut durumu döndür ve çık (HATA VERME)
        SELECT mevcut_streak as streak, 0 as puan_kazanildi
        FROM StreakTakibi
        WHERE kullanici_id = p_kullanici_id;
    ELSE
        -- StreakTakibi tablosunda kayıt var mı?
        SELECT COALESCE(son_checkin_tarihi, DATE_SUB(v_bugun, INTERVAL 2 DAY)), 
               COALESCE(mevcut_streak, 0)
        INTO v_son_checkin, v_mevcut_streak
        FROM StreakTakibi 
        WHERE kullanici_id = p_kullanici_id;
        
        -- Streak hesaplama
        IF v_son_checkin = DATE_SUB(v_bugun, INTERVAL 1 DAY) THEN
            -- Dün check-in yapılmış, streak devam ediyor
            SET v_yeni_streak = v_mevcut_streak + 1;
        ELSEIF v_son_checkin = v_bugun THEN
            -- Bugün zaten yapılmış (yukarıdaki kontrolden kaçarsa diye güvenlik)
            SET v_yeni_streak = v_mevcut_streak;
        ELSE
            -- Streak kırıldı
            SET v_yeni_streak = 1;
        END IF;
        
        -- Milestone bonusları
        IF v_yeni_streak = 7 THEN
            SET v_puan = v_puan + 50; -- 7 gün bonusu
        ELSEIF v_yeni_streak = 30 THEN
            SET v_puan = v_puan + 200; -- 30 gün bonusu
        ELSEIF v_yeni_streak = 365 THEN
            SET v_puan = v_puan + 1000; -- 1 yıl bonusu
        END IF;
        
        -- Check-in kaydı ekle
        INSERT INTO GunlukCheckinler (kullanici_id, checkin_tarihi, puan_kazanildi)
        VALUES (p_kullanici_id, v_bugun, v_puan);
        
        -- StreakTakibi güncelle veya ekle
        INSERT INTO StreakTakibi (kullanici_id, mevcut_streak, en_uzun_streak, son_checkin_tarihi)
        VALUES (p_kullanici_id, v_yeni_streak, v_yeni_streak, v_bugun)
        ON DUPLICATE KEY UPDATE
            mevcut_streak = v_yeni_streak,
            en_uzun_streak = GREATEST(en_uzun_streak, v_yeni_streak),
            son_checkin_tarihi = v_bugun;
        
        -- Puan ekle
        INSERT INTO PuanGecmisi (kullanici_id, islem_tipi, puan, aciklama)
        VALUES (p_kullanici_id, 'checkin', v_puan, CONCAT('Günlük check-in (', v_yeni_streak, ' gün streak)'));
        
        UPDATE Kullanicilar 
        SET toplam_puan = toplam_puan + v_puan 
        WHERE kullanici_id = p_kullanici_id;
        
        -- Sonuçları döndür
        SELECT v_yeni_streak as streak, v_puan as puan_kazanildi;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_FavoriToggle` (IN `p_kullanici_id` INT, IN `p_tesis_id` INT)   BEGIN
    IF EXISTS (SELECT 1 FROM Favoriler WHERE kullanici_id = p_kullanici_id AND tesis_id = p_tesis_id) THEN
        DELETE FROM Favoriler WHERE kullanici_id = p_kullanici_id AND tesis_id = p_tesis_id;
        SELECT 'silindi' as islem;
    ELSE
        INSERT INTO Favoriler (kullanici_id, tesis_id) VALUES (p_kullanici_id, p_tesis_id);
        SELECT 'eklendi' as islem;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KullaniciFavorileri` (IN `p_kullanici_id` INT)   BEGIN
    SELECT T.*, S.sehir_adi, I.ilce_adi
    FROM Favoriler F
    JOIN Tesisler T ON F.tesis_id = T.tesis_id
    JOIN Ilceler I ON T.ilce_id = I.ilce_id
    JOIN Sehirler S ON I.sehir_id = S.sehir_id
    WHERE F.kullanici_id = p_kullanici_id
    ORDER BY F.ekleme_tarihi DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KullaniciGiris` (IN `p_eposta` VARCHAR(150))   BEGIN
    -- E-postaya göre kullanıcıyı getir
    SELECT 
        k.kullanici_id, 
        k.sifre, 
        k.ad, 
        k.soyad, 
        k.rol,
        m.musteri_id, -- Eğer müşteri ise ID döner, değilse NULL
        t.sahip_id,   -- Eğer tesis sahibi ise ID döner
        a.admin_id    -- Eğer admin ise ID döner
    FROM Kullanicilar k
    LEFT JOIN Musteriler m ON k.kullanici_id = m.musteri_id
    LEFT JOIN TesisSahipleri t ON k.kullanici_id = t.sahip_id
    LEFT JOIN Adminler a ON k.kullanici_id = a.admin_id
    WHERE k.eposta = p_eposta;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_MusaitlikKontrol` (IN `p_saha_id` INT, IN `p_tarih` DATE)   BEGIN
    -- O gün o sahada alınmış rezervasyonların saatlerini getirir
    SELECT 
        sb.saat_id, 
        sb.baslangic_saati, 
        sb.bitis_saati
    FROM Rezervasyonlar r
    JOIN SaatBloklari sb ON r.saat_id = sb.saat_id
    WHERE r.saha_id = p_saha_id 
      AND r.tarih = p_tarih 
      AND r.durum != 'iptal'; -- İptal edilenler dolu sayılmaz
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_MusteriKayit` (IN `p_ad` VARCHAR(100), IN `p_soyad` VARCHAR(100), IN `p_eposta` VARCHAR(150), IN `p_sifre` VARCHAR(255), IN `p_telefon` VARCHAR(20), IN `p_cinsiyet` ENUM('E','K','Belirtilmemis'), IN `p_dogum_tarihi` DATE)   BEGIN
    -- Hata oluşursa işlemi geri al (Rollback)
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL; -- Hatayı PHP'ye fırlat
    END;

    START TRANSACTION;

        -- 1. Kullanıcılar tablosuna ekle
        INSERT INTO Kullanicilar (ad, soyad, eposta, sifre, telefon, rol) 
        VALUES (p_ad, p_soyad, p_eposta, p_sifre, p_telefon, 'musteri');

        -- 2. Eklenen kullanıcının ID'sini al
        SET @yeni_id = LAST_INSERT_ID();

        -- 3. Müşteriler tablosuna ekle
        INSERT INTO Musteriler (musteri_id, cinsiyet, dogum_tarihi) 
        VALUES (@yeni_id, p_cinsiyet, p_dogum_tarihi);

    COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_MusteriRezervasyonlari` (IN `p_musteri_id` INT)   BEGIN
    SELECT r.*, s.saha_adi, t.tesis_id, t.tesis_adi, sb.baslangic_saati, sb.bitis_saati,
           o.durum as odeme_durumu, o.tutar
    FROM Rezervasyonlar r
    JOIN Sahalar s ON r.saha_id = s.saha_id
    JOIN Tesisler t ON s.tesis_id = t.tesis_id
    JOIN SaatBloklari sb ON r.saat_id = sb.saat_id
    LEFT JOIN Odemeler o ON r.rezervasyon_id = o.rezervasyon_id
    WHERE r.musteri_id = p_musteri_id
    ORDER BY r.tarih DESC, sb.baslangic_saati DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_RezervasyonIptal` (IN `p_rezervasyon_id` INT, IN `p_kullanici_id` INT)   BEGIN
    UPDATE Rezervasyonlar 
    SET durum = 'iptal' 
    WHERE rezervasyon_id = p_rezervasyon_id 
    AND musteri_id = (SELECT rol_id FROM Kullanicilar WHERE kullanici_id = p_kullanici_id AND rol = 'musteri')
    AND durum = 'onay_bekliyor'; 
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_RezervasyonOlustur` (IN `p_musteri_id` INT, IN `p_saha_id` INT, IN `p_saat_id` INT, IN `p_tarih` DATE)   BEGIN
    INSERT INTO Rezervasyonlar (musteri_id, saha_id, saat_id, tarih, durum) 
    VALUES (p_musteri_id, p_saha_id, p_saat_id, p_tarih, 'onay_bekliyor');
    
    SELECT LAST_INSERT_ID() as rezervasyon_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_RezervasyonOnayla` (IN `p_rezervasyon_id` INT, IN `p_onaylayan_sahip_id` INT)   BEGIN
    DECLARE v_tutar DECIMAL(10,2);
    DECLARE v_tesis_sahibi_id INT;
    DECLARE v_sahip_kazanc DECIMAL(10,2);
    DECLARE v_sistem_komisyon DECIMAL(10,2);
    DECLARE v_musteri_id INT;
    DECLARE v_saha_id INT;
    
    -- Transaction başlat
    START TRANSACTION;
    
    -- Rezervasyon bilgilerini çek
    SELECT r.tutar, s.tesis_id, r.musteri_id, r.saha_id
    INTO v_tutar, v_tesis_sahibi_id, v_musteri_id, v_saha_id
    FROM Rezervasyonlar r
    JOIN Sahalar s ON r.saha_id = s.saha_id
    JOIN Tesisler t ON s.tesis_id = t.tesis_id
    WHERE r.rezervasyon_id = p_rezervasyon_id;
    
    -- Kontrol: Bu tesis bu sahibe ait mi?
    IF v_tesis_sahibi_id != p_onaylayan_sahip_id THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Bu rezervasyon size ait değil!';
    END IF;
    
    -- Komisyon hesapla
    SET v_sahip_kazanc = v_tutar * 0.95;  -- %95 Tesis Sahibine
    SET v_sistem_komisyon = v_tutar * 0.05;  -- %5 Sisteme
    
    -- Rezervasyon durumunu güncelle
    UPDATE Rezervasyonlar 
    SET durum = 'onaylandi' 
    WHERE rezervasyon_id = p_rezervasyon_id;
    
    -- 1. Tesis sahibinin bakiyesini güncelle
    UPDATE Kullanicilar 
    SET bakiye = bakiye + v_sahip_kazanc 
    WHERE kullanici_id = (SELECT kullanici_id FROM TesisSahipleri WHERE sahip_id = v_tesis_sahibi_id);
    
    -- 2. SİSTEM KASASINI GÜNCELLE (Süper Admin ID: 1)
    UPDATE Kullanicilar 
    SET bakiye = bakiye + v_sistem_komisyon 
    WHERE kullanici_id = 1;

    -- Log: Tesis Sahibi
    INSERT INTO CuzdanHareketleri (kullanici_id, islem_tipi, tutar, aciklama, tarih)
    VALUES (
        (SELECT kullanici_id FROM TesisSahipleri WHERE sahip_id = v_tesis_sahibi_id),
        'kazanc',
        v_sahip_kazanc,
        CONCAT('Rezerv #', p_rezervasyon_id, ' onayı - Kazanç (%95)'),
        NOW()
    );
    
    -- Log: Sistem Komisyonu
    INSERT INTO CuzdanHareketleri (kullanici_id, islem_tipi, tutar, aciklama, tarih)
    VALUES (
        1, -- Süper Admin
        'komisyon',
        v_sistem_komisyon,
        CONCAT('Rezerv #', p_rezervasyon_id, ' komisyonu (%5)'),
        NOW()
    );
    
    COMMIT;
    
    SELECT 'SUCCESS' AS status, v_sahip_kazanc AS kazanc, v_sistem_komisyon AS komisyon;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_SahaDetayGetir` (IN `p_saha_id` INT)   BEGIN
    SELECT s.*, t.tesis_adi, se.sehir_adi, i.ilce_adi 
    FROM Sahalar s 
    JOIN Tesisler t ON s.tesis_id = t.tesis_id 
    JOIN Ilceler i ON t.ilce_id = i.ilce_id
    JOIN Sehirler se ON i.sehir_id = se.sehir_id
    WHERE s.saha_id = p_saha_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_SahaEkle` (IN `p_tesis_id` INT, IN `p_saha_adi` VARCHAR(100), IN `p_zemin_tipi` ENUM('suni_cim','dogal_cim','hali','parke'), IN `p_kapasite` INT, IN `p_fiyat` DECIMAL(10,2))   BEGIN
    INSERT INTO Sahalar (tesis_id, saha_adi, zemin_tipi, kapasite, fiyat_saatlik) 
    VALUES (p_tesis_id, p_saha_adi, p_zemin_tipi, p_kapasite, p_fiyat);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_SahalariGetir` (IN `p_tesis_id` INT)   BEGIN
    SELECT * FROM Sahalar WHERE tesis_id = p_tesis_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_SahipGelenRezervasyonlar` (IN `p_sahip_id` INT)   BEGIN
    SELECT r.rezervasyon_id, r.tarih, r.durum, 
           m.ad, m.soyad, m.telefon, 
           t.tesis_adi, s.saha_adi,
           sb.baslangic_saati, sb.bitis_saati,
           o.tutar
    FROM Rezervasyonlar r
    JOIN Sahalar s ON r.saha_id = s.saha_id
    JOIN Tesisler t ON s.tesis_id = t.tesis_id
    JOIN SaatBloklari sb ON r.saat_id = sb.saat_id
    JOIN Kullanicilar m ON r.musteri_id = m.kullanici_id 
    LEFT JOIN Odemeler o ON r.rezervasyon_id = o.rezervasyon_id
    WHERE t.tesis_sahibi_id = p_sahip_id 
    ORDER BY r.olusturma_tarihi DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_SahipTesisleriGetir` (IN `p_sahip_id` INT)   BEGIN
    SELECT * FROM Tesisler WHERE tesis_sahibi_id = p_sahip_id ORDER BY tesis_id DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_TesisArama` (IN `p_sehir_id` INT, IN `p_ilce_id` INT, IN `p_ozellik_ids` TEXT, IN `p_ozellik_count` INT)   BEGIN
    -- Temel Sorgu
    SET @sql = 'SELECT t.*, i.ilce_adi, s.sehir_adi, (SELECT MIN(fiyat_saatlik) FROM Sahalar WHERE tesis_id = t.tesis_id) as baslangic_fiyat FROM Tesisler t JOIN Ilceler i ON t.ilce_id = i.ilce_id JOIN Sehirler s ON i.sehir_id = s.sehir_id WHERE t.onay_durumu = 1';

    -- Şehir Filtresi Varsa
    IF p_sehir_id > 0 THEN
        SET @sql = CONCAT(@sql, ' AND s.sehir_id = ', p_sehir_id);
    END IF;

    -- İlçe Filtresi Varsa
    IF p_ilce_id > 0 THEN
        SET @sql = CONCAT(@sql, ' AND t.ilce_id = ', p_ilce_id);
    END IF;

    -- Özellik Filtresi Varsa (En Zor Kısım)
    -- Dinamik olarak IN (1,3,5) ve HAVING COUNT = 3 ekliyoruz
    IF p_ozellik_count > 0 THEN
        SET @sql = CONCAT(@sql, ' AND t.tesis_id IN (SELECT tesis_id FROM TesisOzellikleri WHERE ozellik_id IN (', p_ozellik_ids, ') GROUP BY tesis_id HAVING COUNT(DISTINCT ozellik_id) = ', p_ozellik_count, ')');
    END IF;

    -- Oluşturulan SQL'i Çalıştır
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_TesisDetayGetir` (IN `p_tesis_id` INT)   BEGIN
    SELECT t.*, i.ilce_adi, s.sehir_adi, ts.ad AS sahip_ad, ts.soyad AS sahip_soyad
    FROM Tesisler t
    JOIN Ilceler i ON t.ilce_id = i.ilce_id
    JOIN Sehirler s ON i.sehir_id = s.sehir_id
    JOIN Kullanicilar ts ON t.tesis_sahibi_id = ts.kullanici_id
    WHERE t.tesis_id = p_tesis_id AND t.onay_durumu = 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_TesisEkle` (IN `p_sahip_id` INT, IN `p_ilce_id` INT, IN `p_ad` VARCHAR(200), IN `p_adres` TEXT, IN `p_telefon` VARCHAR(20), IN `p_aciklama` TEXT, IN `p_resim` VARCHAR(255))   BEGIN
    INSERT INTO Tesisler (tesis_sahibi_id, ilce_id, tesis_adi, adres, telefon, aciklama, kapak_resmi, onay_durumu)
    VALUES (p_sahip_id, p_ilce_id, p_ad, p_adres, p_telefon, p_aciklama, p_resim, 0); -- 0: Onay Bekliyor
    
    -- Eklenen ID'yi geri döndür
    SELECT LAST_INSERT_ID() as yeni_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_TesisleriListeleByIlce` (IN `p_ilce_id` INT)   BEGIN
    SELECT 
        t.tesis_id, 
        t.tesis_adi, 
        t.kapak_resmi, 
        t.adres,
        i.ilce_adi,
        s.sehir_adi,
        MIN(sh.fiyat_saatlik) as baslangic_fiyati -- En ucuz sahanın fiyatı
    FROM Tesisler t
    JOIN Ilceler i ON t.ilce_id = i.ilce_id
    JOIN Sehirler s ON i.sehir_id = s.sehir_id
    LEFT JOIN Sahalar sh ON t.tesis_id = sh.tesis_id
    WHERE t.ilce_id = p_ilce_id AND t.onay_durumu = 1
    GROUP BY t.tesis_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_TesisOzellikBagla` (IN `p_tesis_id` INT, IN `p_ozellik_id` INT)   BEGIN
    INSERT INTO TesisOzellikIliski (tesis_id, ozellik_id) VALUES (p_tesis_id, p_ozellik_id);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_TesisOzellikleriGetir` (IN `p_tesis_id` INT)   BEGIN
    SELECT O.ozellik_adi, O.ikon_kodu
    FROM TesisOzellikIliski T
    JOIN Ozellikler O ON T.ozellik_id = O.ozellik_id
    WHERE T.tesis_id = p_tesis_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_TesisYorumlariGetir` (IN `p_tesis_id` INT)   BEGIN
    SELECT y.*, m.ad, m.soyad 
    FROM Yorumlar y 
    JOIN Musteriler mu ON y.musteri_id = mu.musteri_id
    JOIN Kullanicilar m ON mu.musteri_id = m.kullanici_id
    WHERE y.tesis_id = p_tesis_id 
    ORDER BY y.tarih DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_VitrinTesisleriGetir` ()   BEGIN
    SELECT t.tesis_id, t.tesis_adi, t.kapak_resmi, i.ilce_adi, s.sehir_adi, t.onay_durumu
    FROM Tesisler t 
    JOIN Ilceler i ON t.ilce_id = i.ilce_id 
    JOIN Sehirler s ON i.sehir_id = s.sehir_id 
    WHERE t.onay_durumu = 1 
    ORDER BY t.tesis_id DESC LIMIT 6;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_YorumEkle` (IN `p_musteri_id` INT, IN `p_tesis_id` INT, IN `p_puan` TINYINT, IN `p_yorum` TEXT)   BEGIN
    -- 1. Yorumu Ekle
    INSERT INTO Yorumlar (musteri_id, tesis_id, puan, yorum_metni) 
    VALUES (p_musteri_id, p_tesis_id, p_puan, p_yorum);
    
    -- 2. Ortalamayı Hesapla ve Tesisi Güncelle
    UPDATE Tesisler 
    SET ortalama_puan = (SELECT AVG(puan) FROM Yorumlar WHERE tesis_id = p_tesis_id)
    WHERE tesis_id = p_tesis_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `adminler`
--

CREATE TABLE `adminler` (
  `admin_id` int NOT NULL,
  `yetki_seviyesi` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `adminler`
--

INSERT INTO `adminler` (`admin_id`, `yetki_seviyesi`) VALUES
(1, 5);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `carkcevirmeler`
--

CREATE TABLE `carkcevirmeler` (
  `cevrim_id` int NOT NULL,
  `kullanici_id` int NOT NULL,
  `cevrim_tarihi` date NOT NULL,
  `odul_tipi` enum('puan','kupon','rozet') COLLATE utf8mb4_unicode_ci NOT NULL,
  `odul_degeri` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `olusturma_zamani` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `carkcevirmeler`
--

INSERT INTO `carkcevirmeler` (`cevrim_id`, `kullanici_id`, `cevrim_tarihi`, `odul_tipi`, `odul_degeri`, `olusturma_zamani`) VALUES
(1, 6, '2025-11-22', 'puan', '10', '2025-11-22 12:06:44'),
(2, 4, '2025-11-22', 'puan', '100', '2025-11-22 13:00:29');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `cuzdanhareketleri`
--

CREATE TABLE `cuzdanhareketleri` (
  `hareket_id` int NOT NULL,
  `kullanici_id` int NOT NULL,
  `islem_tipi` enum('yukleme','harcama','bonus','iade','cashback') COLLATE utf8mb3_turkish_ci NOT NULL,
  `tutar` decimal(10,2) NOT NULL,
  `aciklama` varchar(255) COLLATE utf8mb3_turkish_ci DEFAULT NULL,
  `tarih` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;

--
-- Tablo döküm verisi `cuzdanhareketleri`
--

INSERT INTO `cuzdanhareketleri` (`hareket_id`, `kullanici_id`, `islem_tipi`, `tutar`, `aciklama`, `tarih`) VALUES
(1, 6, 'yukleme', 1000.00, 'Kredi Kartı ile Yükleme', '2025-11-22 17:48:18'),
(2, 6, 'bonus', 200.00, 'Yükleme Bonusu', '2025-11-22 17:48:18'),
(3, 6, 'bonus', 50.00, 'Sanal Kart Oluşturma Bonusu', '2025-11-22 17:50:03'),
(4, 3, 'bonus', 50.00, 'Sanal Kart Oluşturma Bonusu', '2025-11-22 20:15:13'),
(5, 1, 'bonus', 50.00, 'Sanal Kart Oluşturma Bonusu', '2025-11-22 21:02:49');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `favoriler`
--

CREATE TABLE `favoriler` (
  `favori_id` int NOT NULL,
  `kullanici_id` int NOT NULL,
  `tesis_id` int NOT NULL,
  `ekleme_tarihi` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;

--
-- Tablo döküm verisi `favoriler`
--

INSERT INTO `favoriler` (`favori_id`, `kullanici_id`, `tesis_id`, `ekleme_tarihi`) VALUES
(1, 6, 23, '2025-11-21 22:43:34'),
(2, 1, 19, '2025-11-22 10:50:44'),
(4, 4, 3, '2025-11-22 16:01:44'),
(5, 4, 1, '2025-11-22 16:02:01'),
(6, 4, 2, '2025-11-22 16:02:05'),
(7, 4, 5, '2025-11-22 16:02:08');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `gunlukcheckinler`
--

CREATE TABLE `gunlukcheckinler` (
  `checkin_id` int NOT NULL,
  `kullanici_id` int NOT NULL,
  `checkin_tarihi` date NOT NULL,
  `puan_kazanildi` int DEFAULT '5',
  `olusturma_zamani` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `gunlukcheckinler`
--

INSERT INTO `gunlukcheckinler` (`checkin_id`, `kullanici_id`, `checkin_tarihi`, `puan_kazanildi`, `olusturma_zamani`) VALUES
(1, 6, '2025-11-22', 5, '2025-11-22 12:08:14'),
(2, 4, '2025-11-22', 5, '2025-11-22 13:00:11');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `haftalikliderler`
--

CREATE TABLE `haftalikliderler` (
  `id` int NOT NULL,
  `kullanici_id` int NOT NULL,
  `hafta_baslangic` date NOT NULL,
  `hafta_bitis` date NOT NULL,
  `hafta_numarasi` int NOT NULL,
  `toplam_puan` int NOT NULL,
  `sira` int NOT NULL,
  `olusturma_zamani` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ilceler`
--

CREATE TABLE `ilceler` (
  `ilce_id` int NOT NULL,
  `sehir_id` int NOT NULL,
  `ilce_adi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `ilceler`
--

INSERT INTO `ilceler` (`ilce_id`, `sehir_id`, `ilce_adi`) VALUES
(1, 1, 'Kadıköy'),
(2, 1, 'Beşiktaş'),
(3, 1, 'Üsküdar'),
(4, 2, 'Çankaya'),
(5, 2, 'Yenimahalle'),
(6, 3, 'Karşıyaka'),
(7, 3, 'Bornova');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `islemloglari`
--

CREATE TABLE `islemloglari` (
  `log_id` int NOT NULL,
  `rezervasyon_id` int DEFAULT NULL,
  `eski_durum` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `yeni_durum` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `islem_tarihi` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `islemloglari`
--

INSERT INTO `islemloglari` (`log_id`, `rezervasyon_id`, `eski_durum`, `yeni_durum`, `islem_tarihi`) VALUES
(1, 5, 'onay_bekliyor', 'onaylandi', '2025-11-22 20:15:04'),
(2, 3, 'onaylandi', 'tamamlandi', '2025-11-22 21:01:48'),
(3, 4, 'onaylandi', 'tamamlandi', '2025-11-22 21:01:48'),
(4, 5, 'onaylandi', 'tamamlandi', '2025-11-22 21:01:48');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanicikuponlari`
--

CREATE TABLE `kullanicikuponlari` (
  `id` int NOT NULL,
  `kullanici_id` int NOT NULL,
  `kupon_id` int NOT NULL,
  `kullanildi` tinyint(1) DEFAULT '0',
  `alinma_tarihi` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `kullanilma_tarihi` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanicilar`
--

CREATE TABLE `kullanicilar` (
  `kullanici_id` int NOT NULL,
  `eposta` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `sifre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `ad` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `soyad` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `telefon` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `rol` enum('musteri','tesis_sahibi','admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `kayit_tarihi` datetime DEFAULT CURRENT_TIMESTAMP,
  `toplam_puan` int DEFAULT '0',
  `bakiye` decimal(10,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `kullanicilar`
--

INSERT INTO `kullanicilar` (`kullanici_id`, `eposta`, `sifre`, `ad`, `soyad`, `telefon`, `rol`, `kayit_tarihi`, `toplam_puan`, `bakiye`) VALUES
(1, 'admin@istun.edu.tr', '$2y$10$TKJaNJXp3ewqcB.qw.ZMB.oufPTx2ZrNLcoZvMbWPxCCfq.k1WSNe', 'Kemal', 'Serdaroğlu', '05001112234', 'admin', '2025-11-20 21:02:22', 0, 50.00),
(2, 'sahip1@mail.com', '$2y$10$TKJaNJXp3ewqcB.qw.ZMB.oufPTx2ZrNLcoZvMbWPxCCfq.k1WSNe', 'Ahmet', 'Yılmaz', '05321112233', 'tesis_sahibi', '2025-11-20 21:02:22', 0, 0.00),
(3, 'sahip2@mail.com', '$2y$10$TKJaNJXp3ewqcB.qw.ZMB.oufPTx2ZrNLcoZvMbWPxCCfq.k1WSNe', 'Mehmet', 'Demir', '05334445566', 'tesis_sahibi', '2025-11-20 21:02:22', 0, 50.00),
(4, 'efe@mail.com', '$2y$10$TKJaNJXp3ewqcB.qw.ZMB.oufPTx2ZrNLcoZvMbWPxCCfq.k1WSNe', 'Murat Efe', 'Nalbant', '05410000001', 'musteri', '2025-11-20 21:02:22', 110, 0.00),
(5, 'emin@mail.com', '$2y$10$TKJaNJXp3ewqcB.qw.ZMB.oufPTx2ZrNLcoZvMbWPxCCfq.k1WSNe', 'Muhammet Emin', 'Başar', '05410000002', 'musteri', '2025-11-20 21:02:22', 5, 0.00),
(6, 'fatih@mail.com', '$2y$10$TKJaNJXp3ewqcB.qw.ZMB.oufPTx2ZrNLcoZvMbWPxCCfq.k1WSNe', 'Fatih', 'Korkmaz', '05410000003', 'musteri', '2025-11-20 21:02:22', 35, 1250.00),
(7, 'yusuf@mail.com', '$2y$10$TKJaNJXp3ewqcB.qw.ZMB.oufPTx2ZrNLcoZvMbWPxCCfq.k1WSNe', 'Yusuf', 'Dünya', '05410000004', 'musteri', '2025-11-20 21:02:22', 0, 0.00),
(8, 'eren@mail.com', '$2y$10$TKJaNJXp3ewqcB.qw.ZMB.oufPTx2ZrNLcoZvMbWPxCCfq.k1WSNe', 'Eren', 'Kaçal', '05410000005', 'musteri', '2025-11-20 21:02:22', 0, 0.00),
(9, 'ayse@mail.com', '$2y$10$TKJaNJXp3ewqcB.qw.ZMB.oufPTx2ZrNLcoZvMbWPxCCfq.k1WSNe', 'Ayşe', 'Yıldız', '05410000006', 'musteri', '2025-11-20 21:02:22', 0, 0.00),
(10, 'fatma@mail.com', '$2y$10$TKJaNJXp3ewqcB.qw.ZMB.oufPTx2ZrNLcoZvMbWPxCCfq.k1WSNe', 'Fatma', 'Kara', '05410000007', 'musteri', '2025-11-20 21:02:22', 0, 0.00),
(11, 'ali@mail.com', '$2y$10$TKJaNJXp3ewqcB.qw.ZMB.oufPTx2ZrNLcoZvMbWPxCCfq.k1WSNe', 'Ali', 'Veli', '05410000008', 'musteri', '2025-11-20 21:02:22', 0, 0.00),
(12, 'burak@mail.com', '$2y$10$TKJaNJXp3ewqcB.qw.ZMB.oufPTx2ZrNLcoZvMbWPxCCfq.k1WSNe', 'Burak', 'Can', '05410000009', 'musteri', '2025-11-20 21:02:22', 0, 0.00),
(13, 'cem@mail.com', '$2y$10$TKJaNJXp3ewqcB.qw.ZMB.oufPTx2ZrNLcoZvMbWPxCCfq.k1WSNe', 'Cem', 'Uzun', '05410000010', 'musteri', '2025-11-20 21:02:22', 0, 0.00);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullaniciquestleri`
--

CREATE TABLE `kullaniciquestleri` (
  `id` int NOT NULL,
  `kullanici_id` int NOT NULL,
  `quest_id` int NOT NULL,
  `ilerleme` int DEFAULT '0',
  `tamamlandi` tinyint(1) DEFAULT '0',
  `tamamlanma_tarihi` datetime DEFAULT NULL,
  `hafta_numarasi` int DEFAULT NULL,
  `guncelleme_zamani` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `kullaniciquestleri`
--

INSERT INTO `kullaniciquestleri` (`id`, `kullanici_id`, `quest_id`, `ilerleme`, `tamamlandi`, `tamamlanma_tarihi`, `hafta_numarasi`, `guncelleme_zamani`) VALUES
(1, 6, 1, 1, 1, '2025-11-22 12:08:14', 202547, '2025-11-22 12:08:14'),
(2, 4, 1, 1, 1, '2025-11-22 13:00:11', 202547, '2025-11-22 13:00:11'),
(10, 4, 5, 2, 0, NULL, 202547, '2025-11-22 13:30:43'),
(11, 4, 2, 1, 0, NULL, 202547, '2025-11-22 13:10:24'),
(12, 4, 8, 1, 0, NULL, 202547, '2025-11-22 13:10:24'),
(13, 6, 5, 2, 0, NULL, 202547, '2025-11-22 14:53:39'),
(14, 6, 3, 1, 1, '2025-11-22 14:13:49', 202547, '2025-11-22 14:13:49'),
(15, 6, 7, 1, 0, NULL, 202547, '2025-11-22 14:13:49');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanicirozetleri`
--

CREATE TABLE `kullanicirozetleri` (
  `id` int NOT NULL,
  `kullanici_id` int NOT NULL,
  `rozet_id` int NOT NULL,
  `kazanilma_tarihi` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;

--
-- Tablo döküm verisi `kullanicirozetleri`
--

INSERT INTO `kullanicirozetleri` (`id`, `kullanici_id`, `rozet_id`, `kazanilma_tarihi`) VALUES
(1, 5, 1, '2025-11-22 08:48:50'),
(2, 4, 1, '2025-11-22 10:01:49'),
(3, 6, 1, '2025-11-22 10:01:49');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kuponlar`
--

CREATE TABLE `kuponlar` (
  `kupon_id` int NOT NULL,
  `kupon_kodu` varchar(50) COLLATE utf8mb3_turkish_ci NOT NULL,
  `indirim_orani` int NOT NULL,
  `gerekli_puan` int NOT NULL,
  `aktif` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;

--
-- Tablo döküm verisi `kuponlar`
--

INSERT INTO `kuponlar` (`kupon_id`, `kupon_kodu`, `indirim_orani`, `gerekli_puan`, `aktif`) VALUES
(1, 'INDIRIM10', 10, 500, 1),
(2, 'INDIRIM20', 20, 1000, 1),
(3, 'BEDAVA_MAC', 100, 5000, 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `musteriler`
--

CREATE TABLE `musteriler` (
  `musteri_id` int NOT NULL,
  `cinsiyet` enum('E','K','Belirtilmemis') CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci DEFAULT 'Belirtilmemis',
  `dogum_tarihi` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `musteriler`
--

INSERT INTO `musteriler` (`musteri_id`, `cinsiyet`, `dogum_tarihi`) VALUES
(4, 'E', '2003-01-01'),
(5, 'E', '2003-05-20'),
(6, 'E', '2002-03-15'),
(7, 'E', '2003-08-10'),
(8, 'E', '2001-12-30'),
(9, 'K', '2000-07-07'),
(10, 'K', '1999-11-11'),
(11, 'E', '1998-02-28'),
(12, 'E', '1995-06-06'),
(13, 'E', '2004-09-09');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `odemeler`
--

CREATE TABLE `odemeler` (
  `odeme_id` int NOT NULL,
  `rezervasyon_id` int NOT NULL,
  `tutar` decimal(10,2) NOT NULL,
  `odeme_yontemi` enum('kredi_karti','havale','nakit') CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `odeme_tarihi` datetime DEFAULT CURRENT_TIMESTAMP,
  `durum` enum('basarili','bekliyor','iade') CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci DEFAULT 'bekliyor'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `odemeler`
--

INSERT INTO `odemeler` (`odeme_id`, `rezervasyon_id`, `tutar`, `odeme_yontemi`, `odeme_tarihi`, `durum`) VALUES
(1, 4, 800.00, 'nakit', '2025-11-20 23:30:51', 'bekliyor'),
(2, 5, 1200.00, 'nakit', '2025-11-21 22:25:10', 'bekliyor'),
(3, 6, 900.00, 'nakit', '2025-11-21 22:46:43', 'bekliyor');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ozellikler`
--

CREATE TABLE `ozellikler` (
  `ozellik_id` int NOT NULL,
  `ozellik_adi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `ikon_kodu` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `ozellikler`
--

INSERT INTO `ozellikler` (`ozellik_id`, `ozellik_adi`, `ikon_kodu`) VALUES
(1, 'Otopark', 'fa-parking'),
(2, 'Duş', 'fa-shower'),
(3, 'Kafeterya', 'fa-coffee'),
(4, 'Krampon Kiralama', 'fa-shoe-prints'),
(5, 'Tribün', 'fa-users'),
(6, 'Duş', 'fa-shower'),
(7, 'Soyunma Odası', 'fa-tshirt'),
(8, 'Kafeterya', 'fa-coffee'),
(9, 'WiFi', 'fa-wifi'),
(10, 'Kredi Kartı', 'fa-credit-card'),
(11, 'Tribün', 'fa-users'),
(12, 'Kamera Kaydı', 'fa-video'),
(13, 'Duş', 'fa-shower'),
(14, 'Soyunma Odası', 'fa-tshirt'),
(15, 'Kafeterya', 'fa-coffee'),
(16, 'WiFi', 'fa-wifi'),
(17, 'Kredi Kartı', 'fa-credit-card'),
(18, 'Tribün', 'fa-users'),
(19, 'Kamera Kaydı', 'fa-video'),
(20, 'Duş', 'fa-shower'),
(21, 'Soyunma Odası', 'fa-tshirt'),
(22, 'Kafeterya', 'fa-coffee'),
(23, 'WiFi', 'fa-wifi'),
(24, 'Kredi Kartı', 'fa-credit-card'),
(25, 'Tribün', 'fa-users'),
(26, 'Kamera Kaydı', 'fa-video'),
(27, 'Otopark', 'fa-parking'),
(28, 'Duş', 'fa-shower'),
(29, 'Soyunma Odası', 'fa-tshirt'),
(30, 'Kafeterya', 'fa-coffee'),
(31, 'WiFi', 'fa-wifi'),
(32, 'Kredi Kartı', 'fa-credit-card'),
(33, 'Tribün', 'fa-users'),
(34, 'Kamera Kaydı', 'fa-video'),
(35, 'Otopark', 'fa-parking'),
(36, 'Duş', 'fa-shower'),
(37, 'Soyunma Odası', 'fa-tshirt'),
(38, 'Kafeterya', 'fa-coffee'),
(39, 'WiFi', 'fa-wifi'),
(40, 'Kredi Kartı', 'fa-credit-card'),
(41, 'Tribün', 'fa-users'),
(42, 'Kamera Kaydı', 'fa-video'),
(43, 'Otopark', 'fas fa-parking'),
(44, 'Duş', 'fas fa-shower'),
(45, 'Soyunma Odası', 'fas fa-door-closed'),
(46, 'Kafeterya', 'fas fa-coffee'),
(47, 'WiFi', 'fas fa-wifi'),
(48, 'Tribün', 'fas fa-users'),
(49, 'Kamera Kaydı', 'fas fa-video'),
(50, 'Ayakkabı Kiralama', 'fas fa-shoe-prints');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `puangecmisi`
--

CREATE TABLE `puangecmisi` (
  `islem_id` int NOT NULL,
  `kullanici_id` int NOT NULL,
  `islem_tipi` varchar(50) COLLATE utf8mb3_turkish_ci NOT NULL,
  `puan` int NOT NULL,
  `aciklama` varchar(255) COLLATE utf8mb3_turkish_ci DEFAULT NULL,
  `tarih` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;

--
-- Tablo döküm verisi `puangecmisi`
--

INSERT INTO `puangecmisi` (`islem_id`, `kullanici_id`, `islem_tipi`, `puan`, `aciklama`, `tarih`) VALUES
(1, 5, 'yorum_begenildi', 5, 'Yorumunuz beğenildi!', '2025-11-22 08:48:50'),
(2, 6, 'cark', 10, 'Şans çarkından kazanıldı', '2025-11-22 12:06:44'),
(3, 6, 'checkin', 5, 'Günlük check-in (1 gün streak)', '2025-11-22 12:08:14'),
(4, 6, 'quest', 5, 'Quest tamamlandı: gunluk_checkin', '2025-11-22 12:08:14'),
(5, 4, 'checkin', 5, 'Günlük check-in (1 gün streak)', '2025-11-22 13:00:11'),
(6, 4, 'quest', 5, 'Quest tamamlandı: gunluk_checkin', '2025-11-22 13:00:11'),
(7, 4, 'cark', 100, 'Şans çarkından kazanıldı', '2025-11-22 13:00:29'),
(18, 6, 'quest', 15, 'Quest tamamlandı: yorum_yap_1', '2025-11-22 14:13:49');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `questler`
--

CREATE TABLE `questler` (
  `quest_id` int NOT NULL,
  `quest_tipi` enum('gunluk','haftalik') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'gunluk',
  `quest_kodu` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `baslik` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `aciklama` text COLLATE utf8mb4_unicode_ci,
  `hedef_sayi` int NOT NULL DEFAULT '1',
  `odul_puan` int NOT NULL DEFAULT '10',
  `ikon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'fas fa-tasks',
  `aktif` tinyint(1) DEFAULT '1',
  `sira` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `questler`
--

INSERT INTO `questler` (`quest_id`, `quest_tipi`, `quest_kodu`, `baslik`, `aciklama`, `hedef_sayi`, `odul_puan`, `ikon`, `aktif`, `sira`) VALUES
(1, 'gunluk', 'gunluk_checkin', 'Günlük Giriş', 'Bugün uygulamaya giriş yap', 1, 5, 'fas fa-sign-in-alt', 1, 1),
(2, 'gunluk', 'favori_ekle_3', '3 Saha Favorile', 'Bugün 3 farklı sahayı favorilere ekle', 3, 10, 'fas fa-heart', 1, 2),
(3, 'gunluk', 'yorum_yap_1', 'Yorum Yap', 'Bir tesise yorum yap', 1, 15, 'fas fa-comment', 1, 3),
(4, 'gunluk', 'profil_duzenle', 'Profil Güncelle', 'Profil bilgilerini düzenle', 1, 20, 'fas fa-user-edit', 0, 4),
(5, 'gunluk', 'tesis_ziyaret_3', '3 Tesis İncele', 'Bugün 3 farklı tesis sayfasını ziyaret et', 3, 8, 'fas fa-eye', 1, 5),
(6, 'haftalik', 'rezervasyon_yap_2', '2 Rezervasyon Yap', 'Bu hafta 2 rezervasyon yap', 2, 100, 'fas fa-calendar-check', 1, 1),
(7, 'haftalik', 'yorum_yap_5', '5 Yorum Yaz', 'Bu hafta 5 yorum yaz', 5, 150, 'fas fa-comments', 1, 2),
(8, 'haftalik', 'favori_20', '20 Favori Ekle', 'Bu hafta 20 sahayı favorile', 20, 200, 'fas fa-star', 1, 3),
(9, 'haftalik', 'cark_cevir_5', '5 Kez Çark Çevir', 'Bu hafta 5 gün çark çevir', 5, 80, 'fas fa-dharmachakra', 1, 4),
(10, 'haftalik', 'streak_7', '7 Gün Streak', 'Bu hafta 7 gün üst üste giriş yap', 7, 250, 'fas fa-fire', 1, 5);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `rezervasyonlar`
--

CREATE TABLE `rezervasyonlar` (
  `rezervasyon_id` int NOT NULL,
  `musteri_id` int NOT NULL,
  `saha_id` int NOT NULL,
  `saat_id` int NOT NULL,
  `tarih` date NOT NULL,
  `durum` enum('onay_bekliyor','onaylandi','iptal','tamamlandi') CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci DEFAULT 'onay_bekliyor',
  `olusturma_tarihi` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `rezervasyonlar`
--

INSERT INTO `rezervasyonlar` (`rezervasyon_id`, `musteri_id`, `saha_id`, `saat_id`, `tarih`, `durum`, `olusturma_tarihi`) VALUES
(1, 4, 1, 3, '2025-11-01', 'tamamlandi', '2025-11-20 21:02:22'),
(2, 5, 1, 4, '2025-11-01', 'tamamlandi', '2025-11-20 21:02:22'),
(3, 6, 2, 3, '2025-11-02', 'tamamlandi', '2025-11-20 21:02:22'),
(4, 12, 4, 5, '2025-11-20', 'tamamlandi', '2025-11-20 23:30:51'),
(5, 6, 35, 4, '2025-11-21', 'tamamlandi', '2025-11-21 22:25:10'),
(6, 6, 37, 6, '2025-11-21', 'onay_bekliyor', '2025-11-21 22:46:43');

--
-- Tetikleyiciler `rezervasyonlar`
--
DELIMITER $$
CREATE TRIGGER `trg_GecmisTarihKontrol` BEFORE INSERT ON `rezervasyonlar` FOR EACH ROW BEGIN
    IF NEW.tarih < CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Hata: Geçmiş bir tarihe rezervasyon yapılamaz!';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_RezervasyonDurumDegisimi` AFTER UPDATE ON `rezervasyonlar` FOR EACH ROW BEGIN
    -- Sadece durum değiştiyse log at
    IF OLD.durum <> NEW.durum THEN
        INSERT INTO IslemLoglari (rezervasyon_id, eski_durum, yeni_durum)
        VALUES (NEW.rezervasyon_id, OLD.durum, NEW.durum);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `rozetler`
--

CREATE TABLE `rozetler` (
  `rozet_id` int NOT NULL,
  `rozet_adi` varchar(100) COLLATE utf8mb3_turkish_ci NOT NULL,
  `rozet_kodu` varchar(50) COLLATE utf8mb3_turkish_ci NOT NULL,
  `aciklama` varchar(255) COLLATE utf8mb3_turkish_ci DEFAULT NULL,
  `ikon` varchar(100) COLLATE utf8mb3_turkish_ci DEFAULT NULL,
  `gerekli_islem_sayisi` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;

--
-- Tablo döküm verisi `rozetler`
--

INSERT INTO `rozetler` (`rozet_id`, `rozet_adi`, `rozet_kodu`, `aciklama`, `ikon`, `gerekli_islem_sayisi`) VALUES
(1, 'İlk Yorum', 'ilk_yorum', 'İlk yorumunu yaptın!', 'fas fa-comment-dots', 1),
(2, '5 Rezervasyon', '5_rezervasyon', '5 kez rezervasyon yaptın!', 'fas fa-calendar-check', 5),
(3, 'Sadık Müşteri', 'sadik_musteri', '10 kez rezervasyon yaptın!', 'fas fa-crown', 10),
(4, 'Popüler Yorumcu', 'populer_yorumcu', 'Yorumların 10 beğeni aldı!', 'fas fa-star', 10),
(13, '7 Gün Ateşi', '7_gun_streak', '7 gün üst üste giriş yaptın! ?', 'fas fa-fire', 7),
(14, 'Aylık Şampiyon', '30_gun_streak', 'Tam bir ay boyunca her gün aktif kaldın! ?', 'fas fa-fire-alt', 30),
(15, 'Yılın Efsanesi', '365_gun_streak', 'İnanılmaz! 1 yıl boyunca her gün geldin! ?', 'fas fa-trophy', 365),
(16, 'Görev Uzmanı', 'quest_master_10', '10 görev tamamladın!', 'fas fa-tasks', 10),
(17, 'Görev Ustası', 'quest_master_50', '50 görev tamamladın!', 'fas fa-trophy', 50),
(18, 'Görev Efsanesi', 'quest_master_100', '100 görev tamamladın!', 'fas fa-crown', 100),
(19, 'Haftalık Şampiyon', 'haftalik_lider', 'Bu hafta en aktif kullanıcıydın!', 'fas fa-medal', 1),
(20, 'Haftalık Kral', 'haftalik_lider_5', '5 kez haftalık lider oldun!', 'fas fa-crown', 5),
(21, 'Şanslı Yıldız', 'lucky_star', 'Çarktan özel ödül kazandın! ⭐', 'fas fa-star', 1),
(22, 'Şans Tanrısı', 'cark_100', '100 kez çark çevirdin!', 'fas fa-dharmachakra', 100);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `saatbloklari`
--

CREATE TABLE `saatbloklari` (
  `saat_id` int NOT NULL,
  `baslangic_saati` time NOT NULL,
  `bitis_saati` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `saatbloklari`
--

INSERT INTO `saatbloklari` (`saat_id`, `baslangic_saati`, `bitis_saati`) VALUES
(1, '17:00:00', '18:00:00'),
(2, '18:00:00', '19:00:00'),
(3, '19:00:00', '20:00:00'),
(4, '20:00:00', '21:00:00'),
(5, '21:00:00', '22:00:00'),
(6, '22:00:00', '23:00:00');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sahalar`
--

CREATE TABLE `sahalar` (
  `saha_id` int NOT NULL,
  `tesis_id` int NOT NULL,
  `saha_adi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `zemin_tipi` enum('suni_cim','dogal_cim','hali','parke') CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci DEFAULT 'suni_cim',
  `kapasite` int DEFAULT '14',
  `fiyat_saatlik` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `sahalar`
--

INSERT INTO `sahalar` (`saha_id`, `tesis_id`, `saha_adi`, `zemin_tipi`, `kapasite`, `fiyat_saatlik`) VALUES
(1, 1, 'Saha A (Kapalı)', 'suni_cim', 14, 1200.00),
(2, 1, 'Saha B (Açık)', 'suni_cim', 14, 1000.00),
(3, 2, 'Profesyonel Saha', 'dogal_cim', 14, 1500.00),
(4, 3, 'Merkez Saha', 'hali', 14, 800.00),
(5, 4, 'Körfez Manzaralı', 'suni_cim', 14, 900.00),
(6, 5, 'Vodafone Arena Mini', 'dogal_cim', 14, 2500.00),
(7, 5, 'Kartal Kapalı Saha', 'suni_cim', 12, 2000.00),
(8, 6, 'Boğaz Saha 1', 'suni_cim', 14, 1400.00),
(9, 6, 'Boğaz Saha 2', 'hali', 14, 1200.00),
(10, 6, 'Antrenman Sahası', 'suni_cim', 10, 900.00),
(11, 7, 'Isıtmalı Kapalı Saha', 'suni_cim', 14, 1600.00),
(12, 7, 'Açık Saha Olimpik', 'dogal_cim', 16, 1800.00),
(13, 8, 'Öğrenci Dostu A', 'hali', 14, 800.00),
(14, 8, 'Öğrenci Dostu B', 'hali', 14, 800.00),
(15, 8, 'VIP Saha', 'parke', 10, 1100.00),
(16, 9, 'Deniz Tarafı', 'dogal_cim', 14, 1800.00),
(17, 9, 'Arka Saha', 'suni_cim', 12, 1500.00),
(18, 10, 'Büyük Saha', 'suni_cim', 16, 1400.00),
(19, 10, 'Küçük Saha', 'hali', 12, 1100.00),
(20, 11, 'VIP Saha A', 'dogal_cim', 14, 3000.00),
(21, 11, 'VIP Saha B', 'dogal_cim', 14, 3000.00),
(22, 12, 'Park Sahası', 'hali', 12, 1000.00),
(23, 13, 'Manzara 1', 'suni_cim', 14, 1300.00),
(24, 13, 'Manzara 2', 'suni_cim', 14, 1300.00),
(25, 14, 'Nostalji Saha', 'dogal_cim', 12, 1250.00),
(26, 15, 'Kampüs A', 'suni_cim', 14, 900.00),
(27, 15, 'Kampüs B', 'suni_cim', 14, 900.00),
(28, 16, 'Vadi Saha', 'hali', 14, 850.00),
(29, 17, 'Sanayi 1', 'hali', 14, 700.00),
(30, 17, 'Sanayi 2', 'hali', 14, 700.00),
(31, 18, 'Merkez Saha', 'suni_cim', 12, 750.00),
(32, 19, 'Pro Saha', 'dogal_cim', 14, 1600.00),
(33, 19, 'Antrenman', 'suni_cim', 10, 1000.00),
(34, 20, 'Sahil 1', 'suni_cim', 14, 1200.00),
(35, 20, 'Sahil 2', 'suni_cim', 14, 1200.00),
(36, 21, 'Çarşı Saha', 'hali', 12, 700.00),
(37, 22, 'AVM Saha', 'parke', 10, 900.00),
(38, 22, 'Dış Saha', 'suni_cim', 14, 800.00),
(39, 23, 'Düz - Basit Saha', 'suni_cim', 14, 500.00);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sanalkartlar`
--

CREATE TABLE `sanalkartlar` (
  `kart_id` int NOT NULL,
  `kullanici_id` int NOT NULL,
  `kart_numarasi` varchar(20) COLLATE utf8mb3_turkish_ci NOT NULL,
  `son_kullanma_tarihi` varchar(5) COLLATE utf8mb3_turkish_ci NOT NULL,
  `cvv` varchar(3) COLLATE utf8mb3_turkish_ci NOT NULL,
  `kart_adi` varchar(50) COLLATE utf8mb3_turkish_ci DEFAULT 'Sanal Kart',
  `olusturma_tarihi` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;

--
-- Tablo döküm verisi `sanalkartlar`
--

INSERT INTO `sanalkartlar` (`kart_id`, `kullanici_id`, `kart_numarasi`, `son_kullanma_tarihi`, `cvv`, `kart_adi`, `olusturma_tarihi`) VALUES
(1, 6, '5724 3992 4424 3039', '11/30', '644', 'Sanal Kart', '2025-11-22 17:50:03'),
(2, 3, '5821 3408 4928 5436', '11/30', '319', 'Sanal Kart', '2025-11-22 20:15:13'),
(3, 1, '5254 4906 2846 3733', '11/30', '667', 'Sanal Kart', '2025-11-22 21:02:49');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sehirler`
--

CREATE TABLE `sehirler` (
  `sehir_id` int NOT NULL,
  `sehir_adi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `plaka_kodu` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `sehirler`
--

INSERT INTO `sehirler` (`sehir_id`, `sehir_adi`, `plaka_kodu`) VALUES
(1, 'İstanbul', 34),
(2, 'Ankara', 6),
(3, 'İzmir', 35);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `streaktakibi`
--

CREATE TABLE `streaktakibi` (
  `streak_id` int NOT NULL,
  `kullanici_id` int NOT NULL,
  `mevcut_streak` int DEFAULT '0',
  `en_uzun_streak` int DEFAULT '0',
  `son_checkin_tarihi` date DEFAULT NULL,
  `guncelleme_zamani` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `streaktakibi`
--

INSERT INTO `streaktakibi` (`streak_id`, `kullanici_id`, `mevcut_streak`, `en_uzun_streak`, `son_checkin_tarihi`, `guncelleme_zamani`) VALUES
(1, 4, 1, 1, '2025-11-22', '2025-11-22 13:00:11'),
(2, 5, 0, 0, NULL, '2025-11-22 11:32:46'),
(3, 6, 1, 1, '2025-11-22', '2025-11-22 12:08:14'),
(4, 7, 0, 0, NULL, '2025-11-22 11:32:46'),
(5, 8, 0, 0, NULL, '2025-11-22 11:32:46'),
(6, 9, 0, 0, NULL, '2025-11-22 11:32:46'),
(7, 10, 0, 0, NULL, '2025-11-22 11:32:46'),
(8, 11, 0, 0, NULL, '2025-11-22 11:32:46'),
(9, 12, 0, 0, NULL, '2025-11-22 11:32:46'),
(10, 13, 0, 0, NULL, '2025-11-22 11:32:46');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tesisler`
--

CREATE TABLE `tesisler` (
  `tesis_id` int NOT NULL,
  `tesis_sahibi_id` int NOT NULL,
  `ilce_id` int NOT NULL,
  `tesis_adi` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `adres` text CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `telefon` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci,
  `kapak_resmi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `onay_durumu` tinyint(1) DEFAULT '0',
  `ortalama_puan` float DEFAULT '5'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `tesisler`
--

INSERT INTO `tesisler` (`tesis_id`, `tesis_sahibi_id`, `ilce_id`, `tesis_adi`, `adres`, `telefon`, `aciklama`, `kapak_resmi`, `onay_durumu`, `ortalama_puan`) VALUES
(1, 2, 1, 'Kadıköy Arena', 'Caferağa Mah. Moda Cad. No:1', '02163334455', NULL, 'https://images.unsplash.com/photo-1529900748604-07564a03e7a6?auto=format&fit=crop&w=800&q=80', 1, 2.5),
(2, 2, 1, 'Fenerbahçe Spor Tesisleri', 'Fenerbahçe Mah. Kalamış Cad.', '02163334466', NULL, 'https://images.unsplash.com/photo-1575361204480-aadea25e6e68?auto=format&fit=crop&w=800&q=80', 1, 5),
(3, 3, 4, 'Ankara Çankaya Halı Saha', 'Bahçelievler Mah. 7. Cadde', '03124445566', NULL, 'https://images.unsplash.com/photo-1556056504-5c7696c4c28d?auto=format&fit=crop&w=800&q=80', 1, 5),
(4, 3, 6, 'Karşıyaka Spor Kompleksi', 'Bostanlı Mah. Sahil Yolu', '02325556677', NULL, 'https://images.unsplash.com/photo-1570498839593-e565b39455fc?auto=format&fit=crop&w=1600&h=900&q=80', 1, 5),
(5, 2, 2, 'Beşiktaş Kartal Yuvası', 'Çırağan Cad. No:12 Beşiktaş/İstanbul', '02122223344', 'Boğaz manzaralı, elit bir futbol deneyimi. Profesyonel ışıklandırma mevcuttur.', 'https://images.unsplash.com/photo-1570498839593-e565b39455fc?auto=format&fit=crop&w=800&q=80', 1, 5),
(6, 3, 3, 'Üsküdar Anadolu Spor', 'Beylerbeyi Mah. Yalı Boyu Cad. Üsküdar', '02165554433', 'Anadolu yakasının en ferah sahaları. Aile ortamı ve geniş otopark.', 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=800&q=80', 1, 5),
(7, 3, 5, 'Ankara Batıkent Arena', 'Batıkent Mah. Metro Yanı Yenimahalle/Ankara', '03129998877', 'Metroya yürüme mesafesinde, kapalı ve ısıtmalı halı sahalar.', 'https://images.unsplash.com/photo-1529900748604-07564a03e7a6?auto=format&fit=crop&w=1600&h=900&q=80', 1, 5),
(8, 2, 7, 'İzmir Ege Park Tesisleri', 'Kazımdirik Mah. Üniversite Cad. Bornova/İzmir', '02321112211', 'Öğrencilere özel indirimli saatler. 24 saat açık kafeterya.', 'https://images.unsplash.com/photo-1551958219-acbc608c6377?auto=format&fit=crop&w=1600&h=900&q=80', 1, 5),
(9, 2, 1, 'Moda Deniz Spor', 'Moda Sahil Yolu No:5 Kadıköy', '02163330011', 'Deniz kenarında, esintili ve ferah bir maç keyfi.', 'https://images.unsplash.com/photo-1431324155629-1a6deb1dec8d?auto=format&fit=crop&w=800&q=80', 1, 5),
(10, 2, 1, 'Göztepe Özgürlük Parkı', 'Bağdat Cad. Göztepe Parkı İçi', '02163330022', 'Şehrin göbeğinde yeşillikler içinde futbol.', 'https://images.unsplash.com/photo-1575361204480-aadea25e6e68?auto=format&fit=crop&w=1600&h=900&q=80', 1, 5),
(11, 3, 2, 'Etiler Lüks Arena', 'Nispetiye Cad. No:44 Beşiktaş', '02122220011', 'VIP soyunma odaları ve vale hizmeti.', 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&w=800&q=80', 1, 5),
(12, 3, 2, 'Abbasağa Gençlik', 'Abbasağa Parkı Yanı Beşiktaş', '02122220022', 'Mahalle maçı tadında samimi bir ortam.', 'https://images.unsplash.com/photo-1551958219-acbc608c6377?auto=format&fit=crop&w=800&q=80', 1, 5),
(13, 2, 3, 'Çamlıca Tepesi Spor', 'Kısıklı Mah. Turistik Cad. Üsküdar', '02165550011', 'İstanbul manzaralı, yüksek rakımlı temiz hava.', 'https://images.unsplash.com/photo-1560272564-c83b66b1ad12?auto=format&fit=crop&w=800&q=80', 1, 5),
(14, 2, 3, 'Kuzguncuk Bostan Sahası', 'İcadiye Cad. No:88 Üsküdar', '02165550022', 'Tarihi doku içerisinde, nostaljik zemin.', 'https://images.unsplash.com/photo-1510051640316-cee39563ddab?auto=format&fit=crop&w=800&q=80', 1, 5),
(15, 3, 4, 'Bilkent Arena', 'Üniversiteler Mah. Bilkent Plaza', '03124440011', 'Öğrenci dostu, geniş kafeteryalı modern tesis.', 'https://images.unsplash.com/photo-1556056504-5c7696c4c28d?auto=format&fit=crop&w=1600&h=900&q=80', 1, 5),
(16, 3, 4, 'Dikmen Vadisi Spor', 'Dikmen Cad. Vadi Girişi', '03124440022', 'Doğa ile iç içe, sessiz ve sakin.', 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&w=1600&h=900&q=80', 1, 5),
(17, 2, 5, 'Ostim Sanayi Ligi', '100. Yıl Bulvarı Ostim', '03129990011', 'İş çıkışı maçları için ideal, geniş otopark.', 'https://images.unsplash.com/photo-1517927033932-b3d18e61fb3a?auto=format&fit=crop&w=800&q=80', 1, 5),
(18, 2, 5, 'Demetevler Park Sahası', 'İvedik Cad. Hastane Karşısı', '03129990022', 'Merkezi konum, kolay ulaşım.', 'https://images.unsplash.com/photo-1589487391730-58f20eb2c308?auto=format&fit=crop&w=800&q=80', 1, 5),
(19, 3, 6, 'Mavişehir Sports', 'Cahar Dudayev Bulvarı', '02323330011', 'Profesyonel zemin, gece maçları için mükemmel ışık.', 'https://images.unsplash.com/photo-1459865264687-595d652de67e?auto=format&fit=crop&w=1600&h=900&q=80', 1, 5),
(20, 3, 6, 'Bostanlı Sahil Tesisleri', 'Hasan Ali Yücel Bulvarı', '02323330022', 'Maçtan sonra sahilde yürüyüş imkanı.', 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=1600&h=900&q=80', 1, 5),
(21, 2, 7, 'Küçükpark Arena', 'Süvari Cad. No:55 Bornova', '02321110011', 'Bornova merkeze yürüme mesafesinde.', 'https://images.unsplash.com/photo-1518091043644-c1d4457512c6?auto=format&fit=crop&w=1600&h=900&q=80', 1, 5),
(22, 2, 7, 'Forum Bornova Yanı', 'Kazımdirik Mah. 372. Sokak', '02321110022', 'AVM yanı, alışveriş ve spor bir arada.', 'https://images.unsplash.com/photo-1431324155629-1a6deb1dec8d?auto=format&fit=crop&w=1600&h=900&q=80', 1, 2.5),
(23, 3, 2, 'Kardeşler Halı Saha', 'Beşiktaş\'ta kimse sorsanız gösterirler', '5325554361', 'Sadece kafeterya ve otoparkımız var. Pek nezih bir ortam olduğu söylenemez. Agalarla takılamk için gelebilirsiniz. Uygun bütçelidir', 'https://images.unsplash.com/photo-1489944440615-453fc2b6a9a9?auto=format&fit=crop&w=800&q=80', 1, 5);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tesisozellikiliski`
--

CREATE TABLE `tesisozellikiliski` (
  `id` int NOT NULL,
  `tesis_id` int NOT NULL,
  `ozellik_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tesisozellikleri`
--

CREATE TABLE `tesisozellikleri` (
  `tesis_id` int NOT NULL,
  `ozellik_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `tesisozellikleri`
--

INSERT INTO `tesisozellikleri` (`tesis_id`, `ozellik_id`) VALUES
(1, 1),
(2, 1),
(5, 1),
(6, 1),
(9, 1),
(10, 1),
(11, 1),
(13, 1),
(16, 1),
(17, 1),
(19, 1),
(22, 1),
(23, 1),
(1, 2),
(2, 2),
(5, 2),
(6, 2),
(7, 2),
(9, 2),
(11, 2),
(14, 2),
(17, 2),
(19, 2),
(20, 2),
(23, 2),
(1, 3),
(3, 3),
(5, 3),
(7, 3),
(8, 3),
(9, 3),
(11, 3),
(13, 3),
(15, 3),
(17, 3),
(20, 3),
(22, 3),
(3, 4),
(5, 4),
(8, 4),
(11, 4),
(12, 4),
(15, 4),
(21, 4),
(5, 5),
(11, 5),
(15, 5),
(19, 5);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tesissahipleri`
--

CREATE TABLE `tesissahipleri` (
  `sahip_id` int NOT NULL,
  `vergi_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `iban` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `tesissahipleri`
--

INSERT INTO `tesissahipleri` (`sahip_id`, `vergi_no`, `iban`) VALUES
(2, '1234567890', 'TR123456789012345678901234'),
(3, '0987654321', 'TR987654321098765432109876');

-- --------------------------------------------------------

--
-- Görünüm yapısı durumu `v_haftalikpuanlar`
-- (Asıl görünüm için aşağıya bakın)
--
CREATE TABLE `v_haftalikpuanlar` (
`ad` varchar(100)
,`aktivite_sayisi` bigint
,`hafta_numarasi` int
,`haftalik_puan` decimal(32,0)
,`kullanici_id` int
,`soyad` varchar(100)
,`toplam_puan` int
);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `yorumbegenileri`
--

CREATE TABLE `yorumbegenileri` (
  `begeni_id` int NOT NULL,
  `yorum_id` int NOT NULL,
  `kullanici_id` int NOT NULL,
  `durum` enum('like','dislike') COLLATE utf8mb3_turkish_ci NOT NULL,
  `tarih` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `yorumlar`
--

CREATE TABLE `yorumlar` (
  `yorum_id` int NOT NULL,
  `musteri_id` int NOT NULL,
  `tesis_id` int NOT NULL,
  `puan` tinyint NOT NULL,
  `yorum_metni` text CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci,
  `resim_yolu` varchar(255) COLLATE utf8mb3_turkish_ci DEFAULT NULL,
  `onay_durumu` enum('Beklemede','Onaylandı','Reddedildi') COLLATE utf8mb3_turkish_ci DEFAULT 'Onaylandı',
  `tarih` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;

--
-- Tablo döküm verisi `yorumlar`
--

INSERT INTO `yorumlar` (`yorum_id`, `musteri_id`, `tesis_id`, `puan`, `yorum_metni`, `resim_yolu`, `onay_durumu`, `tarih`) VALUES
(1, 4, 1, 5, 'Zemin harikaydı, duşlar temiz.', NULL, 'Onaylandı', '2025-11-20 21:02:22'),
(2, 5, 1, 4, 'Biraz pahalı ama hizmet güzel.', NULL, 'Onaylandı', '2025-11-20 21:02:22'),
(3, 4, 1, 2, 'Gitmeyin beyler. Tamam Santiago Bernabau beklemiyorduk da bu kadar kötü de olmaz yani. Verdiğimiz paraya yazık.', NULL, 'Onaylandı', '2025-11-21 00:44:24'),
(4, 4, 1, 1, 'RezalEt', NULL, 'Onaylandı', '2025-11-21 00:47:05'),
(5, 6, 1, 1, 'Rambo Okan&#039;ın sahibi olduğu saha :( Gitmeyin ', NULL, 'Onaylandı', '2025-11-21 23:09:47'),
(6, 4, 2, 5, 'Harika bir saha, zemin çok iyi!', NULL, 'Onaylandı', '2025-11-22 11:11:02'),
(7, 5, 2, 4, 'Soyunma odaları biraz daha temiz olabilirdi ama genel olarak iyi.', NULL, 'Onaylandı', '2025-11-22 11:11:02'),
(8, 6, 3, 5, 'Otopark sorunu yok, maç keyifliydi.', NULL, 'Onaylandı', '2025-11-22 11:11:02'),
(9, 6, 22, 3, 'Zemin değil beton! Sakatlık garantili ', NULL, 'Onaylandı', '2025-11-22 11:52:56'),
(10, 6, 1, 2, 'Ali Koç geri dön :)\r\n', 'uploads/yorumlar/yorum_6_1763820565.jpg', 'Onaylandı', '2025-11-22 12:09:11'),
(11, 6, 22, 2, 'GS-FB maçı daha stressiz geçerdi ', NULL, 'Onaylandı', '2025-11-22 12:52:26'),
(12, 6, 20, 5, 'Güzeldi', NULL, 'Onaylandı', '2025-11-22 17:13:49');

--
-- Tetikleyiciler `yorumlar`
--
DELIMITER $$
CREATE TRIGGER `trg_YorumSilinincePuanGuncelle` AFTER DELETE ON `yorumlar` FOR EACH ROW BEGIN
    UPDATE Tesisler 
    SET ortalama_puan = (SELECT IFNULL(AVG(puan), 5) FROM Yorumlar WHERE tesis_id = OLD.tesis_id)
    WHERE tesis_id = OLD.tesis_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `yorumyanitlari`
--

CREATE TABLE `yorumyanitlari` (
  `yanit_id` int NOT NULL,
  `yorum_id` int NOT NULL,
  `tesis_sahibi_id` int NOT NULL,
  `yanit_metni` text COLLATE utf8mb3_turkish_ci NOT NULL,
  `tarih` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `adminler`
--
ALTER TABLE `adminler`
  ADD PRIMARY KEY (`admin_id`);

--
-- Tablo için indeksler `carkcevirmeler`
--
ALTER TABLE `carkcevirmeler`
  ADD PRIMARY KEY (`cevrim_id`),
  ADD UNIQUE KEY `unique_daily_spin` (`kullanici_id`,`cevrim_tarihi`),
  ADD KEY `idx_kullanici_tarih` (`kullanici_id`,`cevrim_tarihi`);

--
-- Tablo için indeksler `cuzdanhareketleri`
--
ALTER TABLE `cuzdanhareketleri`
  ADD PRIMARY KEY (`hareket_id`),
  ADD KEY `kullanici_id` (`kullanici_id`);

--
-- Tablo için indeksler `favoriler`
--
ALTER TABLE `favoriler`
  ADD PRIMARY KEY (`favori_id`),
  ADD UNIQUE KEY `unique_favori` (`kullanici_id`,`tesis_id`),
  ADD KEY `tesis_id` (`tesis_id`);

--
-- Tablo için indeksler `gunlukcheckinler`
--
ALTER TABLE `gunlukcheckinler`
  ADD PRIMARY KEY (`checkin_id`),
  ADD UNIQUE KEY `unique_daily_checkin` (`kullanici_id`,`checkin_tarihi`),
  ADD KEY `idx_kullanici_tarih` (`kullanici_id`,`checkin_tarihi`);

--
-- Tablo için indeksler `haftalikliderler`
--
ALTER TABLE `haftalikliderler`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kullanici_id` (`kullanici_id`),
  ADD KEY `idx_hafta_sira` (`hafta_numarasi`,`sira`);

--
-- Tablo için indeksler `ilceler`
--
ALTER TABLE `ilceler`
  ADD PRIMARY KEY (`ilce_id`),
  ADD KEY `sehir_id` (`sehir_id`);

--
-- Tablo için indeksler `islemloglari`
--
ALTER TABLE `islemloglari`
  ADD PRIMARY KEY (`log_id`);

--
-- Tablo için indeksler `kullanicikuponlari`
--
ALTER TABLE `kullanicikuponlari`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kullanici_id` (`kullanici_id`),
  ADD KEY `kupon_id` (`kupon_id`);

--
-- Tablo için indeksler `kullanicilar`
--
ALTER TABLE `kullanicilar`
  ADD PRIMARY KEY (`kullanici_id`),
  ADD UNIQUE KEY `eposta` (`eposta`);

--
-- Tablo için indeksler `kullaniciquestleri`
--
ALTER TABLE `kullaniciquestleri`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_quest_week` (`kullanici_id`,`quest_id`,`hafta_numarasi`),
  ADD KEY `quest_id` (`quest_id`),
  ADD KEY `idx_kullanici_tamamlandi` (`kullanici_id`,`tamamlandi`);

--
-- Tablo için indeksler `kullanicirozetleri`
--
ALTER TABLE `kullanicirozetleri`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_badge` (`kullanici_id`,`rozet_id`),
  ADD KEY `rozet_id` (`rozet_id`);

--
-- Tablo için indeksler `kuponlar`
--
ALTER TABLE `kuponlar`
  ADD PRIMARY KEY (`kupon_id`),
  ADD UNIQUE KEY `kupon_kodu` (`kupon_kodu`);

--
-- Tablo için indeksler `musteriler`
--
ALTER TABLE `musteriler`
  ADD PRIMARY KEY (`musteri_id`);

--
-- Tablo için indeksler `odemeler`
--
ALTER TABLE `odemeler`
  ADD PRIMARY KEY (`odeme_id`),
  ADD UNIQUE KEY `rezervasyon_id` (`rezervasyon_id`);

--
-- Tablo için indeksler `ozellikler`
--
ALTER TABLE `ozellikler`
  ADD PRIMARY KEY (`ozellik_id`);

--
-- Tablo için indeksler `puangecmisi`
--
ALTER TABLE `puangecmisi`
  ADD PRIMARY KEY (`islem_id`),
  ADD KEY `kullanici_id` (`kullanici_id`);

--
-- Tablo için indeksler `questler`
--
ALTER TABLE `questler`
  ADD PRIMARY KEY (`quest_id`),
  ADD UNIQUE KEY `quest_kodu` (`quest_kodu`),
  ADD KEY `idx_tip_aktif` (`quest_tipi`,`aktif`);

--
-- Tablo için indeksler `rezervasyonlar`
--
ALTER TABLE `rezervasyonlar`
  ADD PRIMARY KEY (`rezervasyon_id`),
  ADD UNIQUE KEY `unique_rezervasyon` (`saha_id`,`tarih`,`saat_id`),
  ADD KEY `musteri_id` (`musteri_id`),
  ADD KEY `saat_id` (`saat_id`);

--
-- Tablo için indeksler `rozetler`
--
ALTER TABLE `rozetler`
  ADD PRIMARY KEY (`rozet_id`),
  ADD UNIQUE KEY `rozet_kodu` (`rozet_kodu`);

--
-- Tablo için indeksler `saatbloklari`
--
ALTER TABLE `saatbloklari`
  ADD PRIMARY KEY (`saat_id`),
  ADD UNIQUE KEY `baslangic_saati` (`baslangic_saati`,`bitis_saati`);

--
-- Tablo için indeksler `sahalar`
--
ALTER TABLE `sahalar`
  ADD PRIMARY KEY (`saha_id`),
  ADD KEY `tesis_id` (`tesis_id`);

--
-- Tablo için indeksler `sanalkartlar`
--
ALTER TABLE `sanalkartlar`
  ADD PRIMARY KEY (`kart_id`),
  ADD UNIQUE KEY `kart_numarasi` (`kart_numarasi`),
  ADD UNIQUE KEY `kart_numarasi_2` (`kart_numarasi`),
  ADD KEY `kullanici_id` (`kullanici_id`);

--
-- Tablo için indeksler `sehirler`
--
ALTER TABLE `sehirler`
  ADD PRIMARY KEY (`sehir_id`),
  ADD UNIQUE KEY `plaka_kodu` (`plaka_kodu`);

--
-- Tablo için indeksler `streaktakibi`
--
ALTER TABLE `streaktakibi`
  ADD PRIMARY KEY (`streak_id`),
  ADD UNIQUE KEY `kullanici_id` (`kullanici_id`);

--
-- Tablo için indeksler `tesisler`
--
ALTER TABLE `tesisler`
  ADD PRIMARY KEY (`tesis_id`),
  ADD KEY `tesis_sahibi_id` (`tesis_sahibi_id`),
  ADD KEY `ilce_id` (`ilce_id`);

--
-- Tablo için indeksler `tesisozellikiliski`
--
ALTER TABLE `tesisozellikiliski`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tesis_id` (`tesis_id`),
  ADD KEY `ozellik_id` (`ozellik_id`);

--
-- Tablo için indeksler `tesisozellikleri`
--
ALTER TABLE `tesisozellikleri`
  ADD PRIMARY KEY (`tesis_id`,`ozellik_id`),
  ADD KEY `ozellik_id` (`ozellik_id`);

--
-- Tablo için indeksler `tesissahipleri`
--
ALTER TABLE `tesissahipleri`
  ADD PRIMARY KEY (`sahip_id`);

--
-- Tablo için indeksler `yorumbegenileri`
--
ALTER TABLE `yorumbegenileri`
  ADD PRIMARY KEY (`begeni_id`),
  ADD UNIQUE KEY `unique_user_comment` (`yorum_id`,`kullanici_id`),
  ADD KEY `kullanici_id` (`kullanici_id`);

--
-- Tablo için indeksler `yorumlar`
--
ALTER TABLE `yorumlar`
  ADD PRIMARY KEY (`yorum_id`),
  ADD KEY `musteri_id` (`musteri_id`),
  ADD KEY `tesis_id` (`tesis_id`);

--
-- Tablo için indeksler `yorumyanitlari`
--
ALTER TABLE `yorumyanitlari`
  ADD PRIMARY KEY (`yanit_id`),
  ADD KEY `yorum_id` (`yorum_id`),
  ADD KEY `tesis_sahibi_id` (`tesis_sahibi_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `carkcevirmeler`
--
ALTER TABLE `carkcevirmeler`
  MODIFY `cevrim_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `cuzdanhareketleri`
--
ALTER TABLE `cuzdanhareketleri`
  MODIFY `hareket_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `favoriler`
--
ALTER TABLE `favoriler`
  MODIFY `favori_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `gunlukcheckinler`
--
ALTER TABLE `gunlukcheckinler`
  MODIFY `checkin_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `haftalikliderler`
--
ALTER TABLE `haftalikliderler`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `ilceler`
--
ALTER TABLE `ilceler`
  MODIFY `ilce_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `islemloglari`
--
ALTER TABLE `islemloglari`
  MODIFY `log_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `kullanicikuponlari`
--
ALTER TABLE `kullanicikuponlari`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `kullanicilar`
--
ALTER TABLE `kullanicilar`
  MODIFY `kullanici_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Tablo için AUTO_INCREMENT değeri `kullaniciquestleri`
--
ALTER TABLE `kullaniciquestleri`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Tablo için AUTO_INCREMENT değeri `kullanicirozetleri`
--
ALTER TABLE `kullanicirozetleri`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `kuponlar`
--
ALTER TABLE `kuponlar`
  MODIFY `kupon_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `odemeler`
--
ALTER TABLE `odemeler`
  MODIFY `odeme_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `ozellikler`
--
ALTER TABLE `ozellikler`
  MODIFY `ozellik_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- Tablo için AUTO_INCREMENT değeri `puangecmisi`
--
ALTER TABLE `puangecmisi`
  MODIFY `islem_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Tablo için AUTO_INCREMENT değeri `questler`
--
ALTER TABLE `questler`
  MODIFY `quest_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Tablo için AUTO_INCREMENT değeri `rezervasyonlar`
--
ALTER TABLE `rezervasyonlar`
  MODIFY `rezervasyon_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `rozetler`
--
ALTER TABLE `rozetler`
  MODIFY `rozet_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- Tablo için AUTO_INCREMENT değeri `saatbloklari`
--
ALTER TABLE `saatbloklari`
  MODIFY `saat_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `sahalar`
--
ALTER TABLE `sahalar`
  MODIFY `saha_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- Tablo için AUTO_INCREMENT değeri `sanalkartlar`
--
ALTER TABLE `sanalkartlar`
  MODIFY `kart_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `sehirler`
--
ALTER TABLE `sehirler`
  MODIFY `sehir_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `streaktakibi`
--
ALTER TABLE `streaktakibi`
  MODIFY `streak_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Tablo için AUTO_INCREMENT değeri `tesisler`
--
ALTER TABLE `tesisler`
  MODIFY `tesis_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Tablo için AUTO_INCREMENT değeri `tesisozellikiliski`
--
ALTER TABLE `tesisozellikiliski`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `yorumbegenileri`
--
ALTER TABLE `yorumbegenileri`
  MODIFY `begeni_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `yorumlar`
--
ALTER TABLE `yorumlar`
  MODIFY `yorum_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Tablo için AUTO_INCREMENT değeri `yorumyanitlari`
--
ALTER TABLE `yorumyanitlari`
  MODIFY `yanit_id` int NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Görünüm yapısı `v_haftalikpuanlar`
--
DROP TABLE IF EXISTS `v_haftalikpuanlar`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_haftalikpuanlar`  AS SELECT `k`.`kullanici_id` AS `kullanici_id`, `k`.`ad` AS `ad`, `k`.`soyad` AS `soyad`, `k`.`toplam_puan` AS `toplam_puan`, yearweek(curdate(),1) AS `hafta_numarasi`, coalesce(sum(`pg`.`puan`),0) AS `haftalik_puan`, count(`pg`.`puan`) AS `aktivite_sayisi` FROM (`kullanicilar` `k` left join `puangecmisi` `pg` on(((`k`.`kullanici_id` = `pg`.`kullanici_id`) and (`pg`.`tarih` >= (curdate() - interval 7 day))))) WHERE (`k`.`rol` = 'musteri') GROUP BY `k`.`kullanici_id` ORDER BY `haftalik_puan` DESC ;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `adminler`
--
ALTER TABLE `adminler`
  ADD CONSTRAINT `adminler_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `kullanicilar` (`kullanici_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `carkcevirmeler`
--
ALTER TABLE `carkcevirmeler`
  ADD CONSTRAINT `carkcevirmeler_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`kullanici_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `cuzdanhareketleri`
--
ALTER TABLE `cuzdanhareketleri`
  ADD CONSTRAINT `cuzdanhareketleri_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`kullanici_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `favoriler`
--
ALTER TABLE `favoriler`
  ADD CONSTRAINT `favoriler_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`kullanici_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favoriler_ibfk_2` FOREIGN KEY (`tesis_id`) REFERENCES `tesisler` (`tesis_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `gunlukcheckinler`
--
ALTER TABLE `gunlukcheckinler`
  ADD CONSTRAINT `gunlukcheckinler_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`kullanici_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `haftalikliderler`
--
ALTER TABLE `haftalikliderler`
  ADD CONSTRAINT `haftalikliderler_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`kullanici_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `ilceler`
--
ALTER TABLE `ilceler`
  ADD CONSTRAINT `ilceler_ibfk_1` FOREIGN KEY (`sehir_id`) REFERENCES `sehirler` (`sehir_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `kullanicikuponlari`
--
ALTER TABLE `kullanicikuponlari`
  ADD CONSTRAINT `kullanicikuponlari_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`kullanici_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kullanicikuponlari_ibfk_2` FOREIGN KEY (`kupon_id`) REFERENCES `kuponlar` (`kupon_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `kullaniciquestleri`
--
ALTER TABLE `kullaniciquestleri`
  ADD CONSTRAINT `kullaniciquestleri_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`kullanici_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kullaniciquestleri_ibfk_2` FOREIGN KEY (`quest_id`) REFERENCES `questler` (`quest_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `kullanicirozetleri`
--
ALTER TABLE `kullanicirozetleri`
  ADD CONSTRAINT `kullanicirozetleri_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`kullanici_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kullanicirozetleri_ibfk_2` FOREIGN KEY (`rozet_id`) REFERENCES `rozetler` (`rozet_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `musteriler`
--
ALTER TABLE `musteriler`
  ADD CONSTRAINT `musteriler_ibfk_1` FOREIGN KEY (`musteri_id`) REFERENCES `kullanicilar` (`kullanici_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `odemeler`
--
ALTER TABLE `odemeler`
  ADD CONSTRAINT `odemeler_ibfk_1` FOREIGN KEY (`rezervasyon_id`) REFERENCES `rezervasyonlar` (`rezervasyon_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `puangecmisi`
--
ALTER TABLE `puangecmisi`
  ADD CONSTRAINT `puangecmisi_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`kullanici_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `rezervasyonlar`
--
ALTER TABLE `rezervasyonlar`
  ADD CONSTRAINT `fk_rezervasyon_musteri` FOREIGN KEY (`musteri_id`) REFERENCES `musteriler` (`musteri_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rezervasyonlar_ibfk_2` FOREIGN KEY (`saha_id`) REFERENCES `sahalar` (`saha_id`),
  ADD CONSTRAINT `rezervasyonlar_ibfk_3` FOREIGN KEY (`saat_id`) REFERENCES `saatbloklari` (`saat_id`);

--
-- Tablo kısıtlamaları `sahalar`
--
ALTER TABLE `sahalar`
  ADD CONSTRAINT `sahalar_ibfk_1` FOREIGN KEY (`tesis_id`) REFERENCES `tesisler` (`tesis_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `sanalkartlar`
--
ALTER TABLE `sanalkartlar`
  ADD CONSTRAINT `sanalkartlar_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`kullanici_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `streaktakibi`
--
ALTER TABLE `streaktakibi`
  ADD CONSTRAINT `streaktakibi_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`kullanici_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `tesisler`
--
ALTER TABLE `tesisler`
  ADD CONSTRAINT `tesisler_ibfk_1` FOREIGN KEY (`tesis_sahibi_id`) REFERENCES `tesissahipleri` (`sahip_id`),
  ADD CONSTRAINT `tesisler_ibfk_2` FOREIGN KEY (`ilce_id`) REFERENCES `ilceler` (`ilce_id`);

--
-- Tablo kısıtlamaları `tesisozellikiliski`
--
ALTER TABLE `tesisozellikiliski`
  ADD CONSTRAINT `tesisozellikiliski_ibfk_1` FOREIGN KEY (`tesis_id`) REFERENCES `tesisler` (`tesis_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tesisozellikiliski_ibfk_2` FOREIGN KEY (`ozellik_id`) REFERENCES `ozellikler` (`ozellik_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `tesisozellikleri`
--
ALTER TABLE `tesisozellikleri`
  ADD CONSTRAINT `tesisozellikleri_ibfk_1` FOREIGN KEY (`tesis_id`) REFERENCES `tesisler` (`tesis_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tesisozellikleri_ibfk_2` FOREIGN KEY (`ozellik_id`) REFERENCES `ozellikler` (`ozellik_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `tesissahipleri`
--
ALTER TABLE `tesissahipleri`
  ADD CONSTRAINT `tesissahipleri_ibfk_1` FOREIGN KEY (`sahip_id`) REFERENCES `kullanicilar` (`kullanici_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `yorumbegenileri`
--
ALTER TABLE `yorumbegenileri`
  ADD CONSTRAINT `yorumbegenileri_ibfk_1` FOREIGN KEY (`yorum_id`) REFERENCES `yorumlar` (`yorum_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `yorumbegenileri_ibfk_2` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`kullanici_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `yorumlar`
--
ALTER TABLE `yorumlar`
  ADD CONSTRAINT `yorumlar_ibfk_1` FOREIGN KEY (`musteri_id`) REFERENCES `musteriler` (`musteri_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `yorumlar_ibfk_2` FOREIGN KEY (`tesis_id`) REFERENCES `tesisler` (`tesis_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `yorumyanitlari`
--
ALTER TABLE `yorumyanitlari`
  ADD CONSTRAINT `yorumyanitlari_ibfk_1` FOREIGN KEY (`yorum_id`) REFERENCES `yorumlar` (`yorum_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `yorumyanitlari_ibfk_2` FOREIGN KEY (`tesis_sahibi_id`) REFERENCES `kullanicilar` (`kullanici_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
