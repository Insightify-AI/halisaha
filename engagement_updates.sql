-- 1. Profil Düzenleme Görevini Pasife Al
UPDATE Questler SET aktif = 0 WHERE quest_kodu = 'profil_duzenle';

-- 2. sp_CheckInYap Prosedürünü Güncelle (Idempotent Yapı)
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
END//

DELIMITER ;
