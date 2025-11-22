-- 1. KULLANICILAR TABLOSUNA PUAN KOLONU EKLE
ALTER TABLE `Kullanicilar` 
ADD COLUMN `toplam_puan` INT DEFAULT 0;

-- 2. YORUM BEĞENİLERİ TABLOSU
CREATE TABLE IF NOT EXISTS `YorumBegenileri` (
    `begeni_id` INT AUTO_INCREMENT PRIMARY KEY,
    `yorum_id` INT NOT NULL,
    `kullanici_id` INT NOT NULL,
    `durum` ENUM('like', 'dislike') NOT NULL,
    `tarih` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_comment` (`yorum_id`, `kullanici_id`),
    FOREIGN KEY (`yorum_id`) REFERENCES `Yorumlar`(`yorum_id`) ON DELETE CASCADE,
    FOREIGN KEY (`kullanici_id`) REFERENCES `Kullanicilar`(`kullanici_id`) ON DELETE CASCADE
);

-- 3. YORUM YANITLARI TABLOSU (Tesis Sahipleri İçin)
CREATE TABLE IF NOT EXISTS `YorumYanitlari` (
    `yanit_id` INT AUTO_INCREMENT PRIMARY KEY,
    `yorum_id` INT NOT NULL,
    `tesis_sahibi_id` INT NOT NULL,
    `yanit_metni` TEXT NOT NULL,
    `tarih` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`yorum_id`) REFERENCES `Yorumlar`(`yorum_id`) ON DELETE CASCADE,
    FOREIGN KEY (`tesis_sahibi_id`) REFERENCES `Kullanicilar`(`kullanici_id`) ON DELETE CASCADE
);

-- 4. PUAN GEÇMİŞİ TABLOSU
CREATE TABLE IF NOT EXISTS `PuanGecmisi` (
    `islem_id` INT AUTO_INCREMENT PRIMARY KEY,
    `kullanici_id` INT NOT NULL,
    `islem_tipi` VARCHAR(50) NOT NULL, -- 'yorum', 'rezervasyon', 'ilk_yorum' vb.
    `puan` INT NOT NULL,
    `aciklama` VARCHAR(255),
    `tarih` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`kullanici_id`) REFERENCES `Kullanicilar`(`kullanici_id`) ON DELETE CASCADE
);

-- 5. ROZETLER TABLOSU
CREATE TABLE IF NOT EXISTS `Rozetler` (
    `rozet_id` INT AUTO_INCREMENT PRIMARY KEY,
    `rozet_adi` VARCHAR(100) NOT NULL,
    `rozet_kodu` VARCHAR(50) NOT NULL UNIQUE, -- 'ilk_yorum', 'sadik_musteri' vb.
    `aciklama` VARCHAR(255),
    `ikon` VARCHAR(100), -- FontAwesome class veya resim yolu
    `gerekli_islem_sayisi` INT DEFAULT 0
);

-- 6. KULLANICI ROZETLERİ TABLOSU
CREATE TABLE IF NOT EXISTS `KullaniciRozetleri` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kullanici_id` INT NOT NULL,
    `rozet_id` INT NOT NULL,
    `kazanilma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_badge` (`kullanici_id`, `rozet_id`),
    FOREIGN KEY (`kullanici_id`) REFERENCES `Kullanicilar`(`kullanici_id`) ON DELETE CASCADE,
    FOREIGN KEY (`rozet_id`) REFERENCES `Rozetler`(`rozet_id`) ON DELETE CASCADE
);

-- 7. KUPONLAR TABLOSU
CREATE TABLE IF NOT EXISTS `Kuponlar` (
    `kupon_id` INT AUTO_INCREMENT PRIMARY KEY,
    `kupon_kodu` VARCHAR(50) NOT NULL UNIQUE,
    `indirim_orani` INT NOT NULL, -- Yüzde olarak
    `gerekli_puan` INT NOT NULL,
    `aktif` BOOLEAN DEFAULT TRUE
);

-- 8. KULLANICI KUPONLARI TABLOSU
CREATE TABLE IF NOT EXISTS `KullaniciKuponlari` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kullanici_id` INT NOT NULL,
    `kupon_id` INT NOT NULL,
    `kullanildi` BOOLEAN DEFAULT FALSE,
    `alinma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `kullanilma_tarihi` DATETIME DEFAULT NULL,
    FOREIGN KEY (`kullanici_id`) REFERENCES `Kullanicilar`(`kullanici_id`) ON DELETE CASCADE,
    FOREIGN KEY (`kupon_id`) REFERENCES `Kuponlar`(`kupon_id`) ON DELETE CASCADE
);

-- VARSAYILAN ROZETLERİ EKLE
INSERT INTO `Rozetler` (`rozet_adi`, `rozet_kodu`, `aciklama`, `ikon`, `gerekli_islem_sayisi`) VALUES
('İlk Yorum', 'ilk_yorum', 'İlk yorumunu yaptın!', 'fas fa-comment-dots', 1),
('5 Rezervasyon', '5_rezervasyon', '5 kez rezervasyon yaptın!', 'fas fa-calendar-check', 5),
('Sadık Müşteri', 'sadik_musteri', '10 kez rezervasyon yaptın!', 'fas fa-crown', 10),
('Popüler Yorumcu', 'populer_yorumcu', 'Yorumların 10 beğeni aldı!', 'fas fa-star', 10);

-- VARSAYILAN KUPONLARI EKLE
INSERT INTO `Kuponlar` (`kupon_kodu`, `indirim_orani`, `gerekli_puan`) VALUES
('INDIRIM10', 10, 500),
('INDIRIM20', 20, 1000),
('BEDAVA_MAC', 100, 5000);
