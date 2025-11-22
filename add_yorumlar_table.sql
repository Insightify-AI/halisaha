-- Yorumlar tablosunu ekle
CREATE TABLE IF NOT EXISTS `yorumlar` (
  `yorum_id` int NOT NULL AUTO_INCREMENT,
  `tesis_id` int NOT NULL,
  `uye_id` int NOT NULL,
  `puan` tinyint NOT NULL DEFAULT '5',
  `yorum` text COLLATE utf8mb4_turkish_ci,
  `resim_yolu` varchar(255) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `onay_durumu` enum('Beklemede','Onaylandı','Reddedildi') COLLATE utf8mb4_turkish_ci DEFAULT 'Beklemede',
  `tarih` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`yorum_id`),
  KEY `tesis_id` (`tesis_id`),
  KEY `uye_id` (`uye_id`),
  CONSTRAINT `yorumlar_ibfk_1` FOREIGN KEY (`tesis_id`) REFERENCES `tesisler` (`tesis_id`) ON DELETE CASCADE,
  CONSTRAINT `yorumlar_ibfk_2` FOREIGN KEY (`uye_id`) REFERENCES `kullanicilar` (`kullanici_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Örnek yorumlar ekle (uye_id = 1 varsayılan kullanıcı olarak)
INSERT INTO `yorumlar` (`tesis_id`, `uye_id`, `puan`, `yorum`, `onay_durumu`) VALUES 
(1, 1, 5, 'Harika bir saha, zemin çok iyi!', 'Onaylandı'),
(1, 1, 4, 'Soyunma odaları biraz daha temiz olabilirdi ama genel olarak iyi.', 'Onaylandı'),
(2, 1, 5, 'Otopark sorunu yok, maç keyifliydi.', 'Onaylandı');
