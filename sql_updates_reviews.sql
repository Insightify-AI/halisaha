CREATE TABLE IF NOT EXISTS Yorumlar (
    yorum_id INT AUTO_INCREMENT PRIMARY KEY,
    tesis_id INT NOT NULL,
    uye_id INT NOT NULL, -- Eğer üyelik sistemi tam aktif değilse NULL olabilir veya misafir için 0
    puan TINYINT NOT NULL DEFAULT 5,
    yorum TEXT,
    resim_yolu VARCHAR(255) DEFAULT NULL,
    onay_durumu ENUM('Beklemede', 'Onaylandı', 'Reddedildi') DEFAULT 'Beklemede',
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tesis_id) REFERENCES Tesisler(tesis_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Örnek Yorumlar (uye_id = 1 varsayılan kullanıcı olarak)
INSERT INTO Yorumlar (tesis_id, uye_id, puan, yorum, onay_durumu) VALUES 
(1, 1, 5, 'Harika bir saha, zemin çok iyi!', 'Onaylandı'),
(1, 1, 4, 'Soyunma odaları biraz daha temiz olabilirdi ama genel olarak iyi.', 'Onaylandı'),
(2, 1, 5, 'Otopark sorunu yok, maç keyifliydi.', 'Onaylandı');
