-- Mevcut Yorumlar tablosunu güncelle
-- 1. Eksik kolonları ekle
ALTER TABLE `yorumlar` 
ADD COLUMN `resim_yolu` VARCHAR(255) DEFAULT NULL AFTER `yorum_metni`,
ADD COLUMN `onay_durumu` ENUM('Beklemede', 'Onaylandı', 'Reddedildi') DEFAULT 'Onaylandı' AFTER `resim_yolu`;

-- 2. Mevcut verilerin onay_durumu'nu 'Onaylandı' olarak ayarla
UPDATE `yorumlar` SET `onay_durumu` = 'Onaylandı' WHERE `onay_durumu` IS NULL;

-- 3. Yeni örnek yorumlar ekle (musteri_id kullanarak)
INSERT INTO `yorumlar` (`musteri_id`, `tesis_id`, `puan`, `yorum_metni`, `onay_durumu`) VALUES 
(4, 2, 5, 'Harika bir saha, zemin çok iyi!', 'Onaylandı'),
(5, 2, 4, 'Soyunma odaları biraz daha temiz olabilirdi ama genel olarak iyi.', 'Onaylandı'),
(6, 3, 5, 'Otopark sorunu yok, maç keyifliydi.', 'Onaylandı');
