-- ================================================================
-- FIX LEADERBOARD VIEW VISIBILITY
-- ================================================================

-- Redefine the view to include users with 0 points in the current week.
-- The previous version filtered out users with no activity in the WHERE clause.
-- Moving the date condition to the ON clause of the LEFT JOIN fixes this.

CREATE OR REPLACE VIEW `v_HaftalikPuanlar` AS
SELECT 
    k.kullanici_id,
    k.ad,
    k.soyad,
    k.toplam_puan,
    YEARWEEK(CURDATE(), 1) as hafta_numarasi, -- Always show for current week
    COALESCE(SUM(pg.puan), 0) as haftalik_puan, -- Handle NULLs as 0
    COUNT(pg.puan) as aktivite_sayisi
FROM Kullanicilar k
LEFT JOIN PuanGecmisi pg ON k.kullanici_id = pg.kullanici_id 
    AND pg.tarih >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) -- Condition moved here
WHERE k.rol = 'musteri'
GROUP BY k.kullanici_id
ORDER BY haftalik_puan DESC;
