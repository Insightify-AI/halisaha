-- Backfill Script: Award badges to users who already meet the criteria
-- This should be run once to grant badges to existing users who qualify

-- 1. İlk Yorum Rozeti - Kullanıcılara en az 1 yorumu varsa
INSERT IGNORE INTO KullaniciRozetleri (kullanici_id, rozet_id)
SELECT DISTINCT y.musteri_id, r.rozet_id
FROM Yorumlar y
JOIN Rozetler r ON r.rozet_kodu = 'ilk_yorum'
WHERE y.musteri_id NOT IN (
    SELECT kr.kullanici_id 
    FROM KullaniciRozetleri kr 
    WHERE kr.rozet_id = r.rozet_id
);

-- 2. 5 Rezervasyon Rozeti - Kullanıcılara en az 5 tamamlanmış rezervasyonu varsa
INSERT IGNORE INTO KullaniciRozetleri (kullanici_id, rozet_id)
SELECT DISTINCT rez.musteri_id, r.rozet_id
FROM Rezervasyonlar rez
JOIN Rozetler r ON r.rozet_kodu = '5_rezervasyon'
WHERE rez.durum = 'tamamlandi'
AND rez.musteri_id NOT IN (
    SELECT kr.kullanici_id 
    FROM KullaniciRozetleri kr 
    WHERE kr.rozet_id = r.rozet_id
)
GROUP BY rez.musteri_id
HAVING COUNT(rez.rezervasyon_id) >= 5;

-- 3. Sadık Müşteri Rozeti (10 Rezervasyon) - Kullanıcılara en az 10 tamamlanmış rezervasyonu varsa
INSERT IGNORE INTO KullaniciRozetleri (kullanici_id, rozet_id)
SELECT DISTINCT rez.musteri_id, r.rozet_id
FROM Rezervasyonlar rez
JOIN Rozetler r ON r.rozet_kodu = 'sadik_musteri'
WHERE rez.durum = 'tamamlandi'
AND rez.musteri_id NOT IN (
    SELECT kr.kullanici_id 
    FROM KullaniciRozetleri kr 
    WHERE kr.rozet_id = r.rozet_id
)
GROUP BY rez.musteri_id
HAVING COUNT(rez.rezervasyon_id) >= 10;

-- 4. Popüler Yorumcu Rozeti - Yorumları toplamda 10+ beğeni almışsa
INSERT IGNORE INTO KullaniciRozetleri (kullanici_id, rozet_id)
SELECT DISTINCT y.musteri_id, r.rozet_id
FROM Yorumlar y
JOIN Rozetler r ON r.rozet_kodu = 'populer_yorumcu'
JOIN YorumBegenileri yb ON y.yorum_id = yb.yorum_id AND yb.durum = 'like'
WHERE y.musteri_id NOT IN (
    SELECT kr.kullanici_id 
    FROM KullaniciRozetleri kr 
    WHERE kr.rozet_id = r.rozet_id
)
GROUP BY y.musteri_id
HAVING COUNT(yb.begeni_id) >= 10;

-- 5. Puanları Hesapla ve Güncelle (Kullanıcıların puanlarını sıfırdan hesapla)
UPDATE Kullanicilar k
SET toplam_puan = (
    SELECT COALESCE(SUM(pg.puan), 0)
    FROM PuanGecmisi pg
    WHERE pg.kullanici_id = k.kullanici_id
)
WHERE k.rol = 'musteri';

SELECT 'Backfill tamamlandı! Kullanıcılar şartları sağladıkları rozetleri aldılar.' AS Mesaj;
