-- Kullanıcılar tablosuna bakiye sütunu ekle
ALTER TABLE Kullanicilar ADD COLUMN bakiye DECIMAL(10,2) DEFAULT 0.00;

-- Sanal Kartlar Tablosu
CREATE TABLE SanalKartlar (
    kart_id INT PRIMARY KEY AUTO_INCREMENT,
    kullanici_id INT NOT NULL,
    kart_numarasi VARCHAR(20) NOT NULL UNIQUE,
    son_kullanma_tarihi VARCHAR(5) NOT NULL, -- MM/YY formatında
    cvv VARCHAR(3) NOT NULL,
    kart_adi VARCHAR(50) DEFAULT 'Sanal Kart',
    olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES Kullanicilar(kullanici_id) ON DELETE CASCADE
);

-- Cüzdan Hareketleri Tablosu
CREATE TABLE CuzdanHareketleri (
    hareket_id INT PRIMARY KEY AUTO_INCREMENT,
    kullanici_id INT NOT NULL,
    islem_tipi ENUM('yukleme', 'harcama', 'bonus', 'iade', 'cashback') NOT NULL,
    tutar DECIMAL(10,2) NOT NULL,
    aciklama VARCHAR(255),
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES Kullanicilar(kullanici_id) ON DELETE CASCADE
);

-- Mevcut kullanıcılara başlangıç bonusu (Opsiyonel, kampanya kapsamında)
-- UPDATE Kullanicilar SET bakiye = 25.00 WHERE bakiye = 0;
