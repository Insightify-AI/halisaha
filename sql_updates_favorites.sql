-- Favoriler Tablosu
CREATE TABLE IF NOT EXISTS Favoriler (
    favori_id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id INT NOT NULL,
    tesis_id INT NOT NULL,
    ekleme_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favori (kullanici_id, tesis_id),
    FOREIGN KEY (kullanici_id) REFERENCES Kullanicilar(kullanici_id) ON DELETE CASCADE,
    FOREIGN KEY (tesis_id) REFERENCES Tesisler(tesis_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Özellikler Tablosu (Örn: Otopark, Duş, WiFi)
CREATE TABLE IF NOT EXISTS Ozellikler (
    ozellik_id INT AUTO_INCREMENT PRIMARY KEY,
    ozellik_adi VARCHAR(50) NOT NULL,
    ikon_kodu VARCHAR(50) DEFAULT 'fas fa-check', -- FontAwesome class
    aktif BOOLEAN DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tesis Özellikleri İlişki Tablosu
CREATE TABLE IF NOT EXISTS TesisOzellikleri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tesis_id INT NOT NULL,
    ozellik_id INT NOT NULL,
    FOREIGN KEY (tesis_id) REFERENCES Tesisler(tesis_id) ON DELETE CASCADE,
    FOREIGN KEY (ozellik_id) REFERENCES Ozellikler(ozellik_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Varsayılan Özellikleri Ekle
INSERT INTO Ozellikler (ozellik_adi, ikon_kodu) VALUES 
('Otopark', 'fas fa-parking'),
('Duş', 'fas fa-shower'),
('Soyunma Odası', 'fas fa-door-closed'),
('Kafeterya', 'fas fa-coffee'),
('WiFi', 'fas fa-wifi'),
('Tribün', 'fas fa-users'),
('Kamera Kaydı', 'fas fa-video'),
('Ayakkabı Kiralama', 'fas fa-shoe-prints');
