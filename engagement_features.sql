-- ================================================================
-- GÜNLÜK KATILIM & OYUNLAŞTIRMA ÖZELLİKLERİ
-- Check-in, Streak, Quest, Çark Çevirme, Haftalık Liderlik
-- ================================================================

-- 1. GÜNLÜK CHECK-IN TABLOSU
CREATE TABLE IF NOT EXISTS `GunlukCheckinler` (
    `checkin_id` INT AUTO_INCREMENT PRIMARY KEY,
    `kullanici_id` INT NOT NULL,
    `checkin_tarihi` DATE NOT NULL,
    `puan_kazanildi` INT DEFAULT 5,
    `olusturma_zamani` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_daily_checkin` (`kullanici_id`, `checkin_tarihi`),
    FOREIGN KEY (`kullanici_id`) REFERENCES `Kullanicilar`(`kullanici_id`) ON DELETE CASCADE,
    INDEX `idx_kullanici_tarih` (`kullanici_id`, `checkin_tarihi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. STREAK TAKİBİ TABLOSU
CREATE TABLE IF NOT EXISTS `StreakTakibi` (
    `streak_id` INT AUTO_INCREMENT PRIMARY KEY,
    `kullanici_id` INT NOT NULL UNIQUE,
    `mevcut_streak` INT DEFAULT 0,
    `en_uzun_streak` INT DEFAULT 0,
    `son_checkin_tarihi` DATE DEFAULT NULL,
    `guncelleme_zamani` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`kullanici_id`) REFERENCES `Kullanicilar`(`kullanici_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. GÖREV TANIMLARI (QUEST) TABLOSU
CREATE TABLE IF NOT EXISTS `Questler` (
    `quest_id` INT AUTO_INCREMENT PRIMARY KEY,
    `quest_tipi` ENUM('gunluk', 'haftalik') NOT NULL DEFAULT 'gunluk',
    `quest_kodu` VARCHAR(50) NOT NULL UNIQUE,
    `baslik` VARCHAR(255) NOT NULL,
    `aciklama` TEXT,
    `hedef_sayi` INT NOT NULL DEFAULT 1,
    `odul_puan` INT NOT NULL DEFAULT 10,
    `ikon` VARCHAR(100) DEFAULT 'fas fa-tasks',
    `aktif` BOOLEAN DEFAULT TRUE,
    `sira` INT DEFAULT 0,
    INDEX `idx_tip_aktif` (`quest_tipi`, `aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. KULLANICI QUEST İLERLEMELERİ
CREATE TABLE IF NOT EXISTS `KullaniciQuestleri` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kullanici_id` INT NOT NULL,
    `quest_id` INT NOT NULL,
    `ilerleme` INT DEFAULT 0,
    `tamamlandi` BOOLEAN DEFAULT FALSE,
    `tamamlanma_tarihi` DATETIME DEFAULT NULL,
    `hafta_numarasi` INT DEFAULT NULL, -- YEARWEEK() formatı
    `guncelleme_zamani` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_quest_week` (`kullanici_id`, `quest_id`, `hafta_numarasi`),
    FOREIGN KEY (`kullanici_id`) REFERENCES `Kullanicilar`(`kullanici_id`) ON DELETE CASCADE,
    FOREIGN KEY (`quest_id`) REFERENCES `Questler`(`quest_id`) ON DELETE CASCADE,
    INDEX `idx_kullanici_tamamlandi` (`kullanici_id`, `tamamlandi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. ÇARK ÇEVİRME KAYITLARI
CREATE TABLE IF NOT EXISTS `CarkCevirmeler` (
    `cevrim_id` INT AUTO_INCREMENT PRIMARY KEY,
    `kullanici_id` INT NOT NULL,
    `cevrim_tarihi` DATE NOT NULL,
    `odul_tipi` ENUM('puan', 'kupon', 'rozet') NOT NULL,
    `odul_degeri` VARCHAR(100) NOT NULL, -- Puan için sayı, kupon/rozet için kod
    `olusturma_zamani` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_daily_spin` (`kullanici_id`, `cevrim_tarihi`),
    FOREIGN KEY (`kullanici_id`) REFERENCES `Kullanicilar`(`kullanici_id`) ON DELETE CASCADE,
    INDEX `idx_kullanici_tarih` (`kullanici_id`, `cevrim_tarihi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. HAFTALIK LİDERLER ARŞİVİ
CREATE TABLE IF NOT EXISTS `HaftalikLiderler` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kullanici_id` INT NOT NULL,
    `hafta_baslangic` DATE NOT NULL,
    `hafta_bitis` DATE NOT NULL,
    `hafta_numarasi` INT NOT NULL, -- YEARWEEK() formatı
    `toplam_puan` INT NOT NULL,
    `sira` INT NOT NULL,
    `olusturma_zamani` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`kullanici_id`) REFERENCES `Kullanicilar`(`kullanici_id`) ON DELETE CASCADE,
    INDEX `idx_hafta_sira` (`hafta_numarasi`, `sira`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- VARSAYILAN GÖREVLER (QUEST) EKLE
-- ================================================================

INSERT IGNORE INTO `Questler` (`quest_tipi`, `quest_kodu`, `baslik`, `aciklama`, `hedef_sayi`, `odul_puan`, `ikon`, `sira`) VALUES
-- GÜNLÜK GÖREVLER
('gunluk', 'gunluk_checkin', 'Günlük Giriş', 'Bugün uygulamaya giriş yap', 1, 5, 'fas fa-sign-in-alt', 1),
('gunluk', 'favori_ekle_3', '3 Saha Favorile', 'Bugün 3 farklı sahayı favorilere ekle', 3, 10, 'fas fa-heart', 2),
('gunluk', 'yorum_yap_1', 'Yorum Yap', 'Bir tesise yorum yap', 1, 15, 'fas fa-comment', 3),
('gunluk', 'profil_duzenle', 'Profil Güncelle', 'Profil bilgilerini düzenle', 1, 20, 'fas fa-user-edit', 4),
('gunluk', 'tesis_ziyaret_3', '3 Tesis İncele', 'Bugün 3 farklı tesis sayfasını ziyaret et', 3, 8, 'fas fa-eye', 5),

-- HAFTALIK GÖREVLER
('haftalik', 'rezervasyon_yap_2', '2 Rezervasyon Yap', 'Bu hafta 2 rezervasyon yap', 2, 100, 'fas fa-calendar-check', 1),
('haftalik', 'yorum_yap_5', '5 Yorum Yaz', 'Bu hafta 5 yorum yaz', 5, 150, 'fas fa-comments', 2),
('haftalik', 'favori_20', '20 Favori Ekle', 'Bu hafta 20 sahayı favorile', 20, 200, 'fas fa-star', 3),
('haftalik', 'cark_cevir_5', '5 Kez Çark Çevir', 'Bu hafta 5 gün çark çevir', 5, 80, 'fas fa-dharmachakra', 4),
('haftalik', 'streak_7', '7 Gün Streak', 'Bu hafta 7 gün üst üste giriş yap', 7, 250, 'fas fa-fire', 5);

-- ================================================================
-- YENİ ROZETLER EKLE
-- ================================================================

INSERT IGNORE INTO `Rozetler` (`rozet_adi`, `rozet_kodu`, `aciklama`, `ikon`, `gerekli_islem_sayisi`) VALUES
-- STREAK ROZETLERİ
('7 Gün Ateşi', '7_gun_streak', '7 gün üst üste giriş yaptın! 🔥', 'fas fa-fire', 7),
('Aylık Şampiyon', '30_gun_streak', 'Tam bir ay boyunca her gün aktif kaldın! 👑', 'fas fa-fire-alt', 30),
('Yılın Efsanesi', '365_gun_streak', 'İnanılmaz! 1 yıl boyunca her gün geldin! 💎', 'fas fa-trophy', 365),

-- QUEST ROZETLERİ
('Görev Uzmanı', 'quest_master_10', '10 görev tamamladın!', 'fas fa-tasks', 10),
('Görev Ustası', 'quest_master_50', '50 görev tamamladın!', 'fas fa-trophy', 50),
('Görev Efsanesi', 'quest_master_100', '100 görev tamamladın!', 'fas fa-crown', 100),

-- LİDERLİK ROZETLERİ
('Haftalık Şampiyon', 'haftalik_lider', 'Bu hafta en aktif kullanıcıydın!', 'fas fa-medal', 1),
('Haftalık Kral', 'haftalik_lider_5', '5 kez haftalık lider oldun!', 'fas fa-crown', 5),

-- ÇARK ROZETLERİ
('Şanslı Yıldız', 'lucky_star', 'Çarktan özel ödül kazandın! ⭐', 'fas fa-star', 1),
('Şans Tanrısı', 'cark_100', '100 kez çark çevirdin!', 'fas fa-dharmachakra', 100);

-- ================================================================
-- HELPER VIEW: HAFTALIK PUAN DAĞILIMI
-- ================================================================

CREATE OR REPLACE VIEW `v_HaftalikPuanlar` AS
SELECT 
    k.kullanici_id,
    k.ad,
    k.soyad,
    k.toplam_puan,
    YEARWEEK(pg.tarih, 1) as hafta_numarasi,
    SUM(pg.puan) as haftalik_puan,
    COUNT(*) as aktivite_sayisi
FROM Kullanicilar k
LEFT JOIN PuanGecmisi pg ON k.kullanici_id = pg.kullanici_id
WHERE k.rol = 'musteri'
  AND pg.tarih >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY k.kullanici_id, hafta_numarasi
ORDER BY haftalik_puan DESC;

-- ================================================================
-- STORED PROCEDURE: STREAK KONTROLÜ VE GÜNCELLEME
-- ================================================================

DELIMITER //

DROP PROCEDURE IF EXISTS sp_CheckInYap//
CREATE PROCEDURE sp_CheckInYap(IN p_kullanici_id INT)
BEGIN
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
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Bugün zaten check-in yaptınız!';
    END IF;
    
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
        -- Bugün zaten yapılmış (güvenlik için)
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
END//

DELIMITER ;

INSERT IGNORE INTO StreakTakibi (kullanici_id, mevcut_streak, en_uzun_streak)
SELECT kullanici_id, 0, 0 
FROM Kullanicilar 
WHERE rol = 'musteri';

-- ================================================================
-- TAMAMLANDI!
-- ================================================================
