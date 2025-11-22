-- Ödeme Sistemi için Stored Procedure
-- Rezervasyon onaylandığında %95 tesis sahibine, %5 sistem komisyonu

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_RezervasyonOnayla$$

CREATE PROCEDURE sp_RezervasyonOnayla(
    IN p_rezervasyon_id INT,
    IN p_onaylayan_sahip_id INT
)
BEGIN
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
    SET v_sahip_kazanc = v_tutar * 0.95;  -- %95
    SET v_sistem_komisyon = v_tutar * 0.05;  -- %5
    
    -- Rezervasyon durumunu güncelle
    UPDATE Rezervasyonlar 
    SET durum = 'onaylandi' 
    WHERE rezervasyon_id = p_rezervasyon_id;
    
    -- Tesis sahibinin bakiyesini güncelle
    UPDATE Kullanicilar 
    SET bakiye = bakiye + v_sahip_kazanc 
    WHERE kullanici_id = (
        SELECT kullanici_id 
        FROM TesisSahipleri 
        WHERE sahip_id = v_tesis_sahibi_id
    );
    
    -- Cüzdan Hareketleri Tablosuna kayıt ekle (Tesis Sahibi)
    INSERT INTO CuzdanHareketleri (kullanici_id, islem_tipi, tutar, aciklama, tarih)
    VALUES (
        (SELECT kullanici_id FROM TesisSahipleri WHERE sahip_id = v_tesis_sahibi_id),
        'kazanc',
        v_sahip_kazanc,
        CONCAT('Rezerv #', p_rezervasyon_id, ' onayı - Kazanç (%95)'),
        NOW()
    );
    
    -- Sistem komisyonu için log kaydı
    INSERT INTO CuzdanHareketleri (kullanici_id, islem_tipi, tutar, aciklama, tarih)
    VALUES (
        1, -- Admin/System kullanıcısı (id=1 varsayıyoruz)
        'komisyon',
        v_sistem_komisyon,
        CONCAT('Rezerv #', p_rezervasyon_id, ' komisyonu (%5)'),
        NOW()
    );
    
    COMMIT;
    
    SELECT 'SUCCESS' AS status, v_sahip_kazanc AS kazanc, v_sistem_komisyon AS komisyon;
    
END$$

DELIMITER ;
