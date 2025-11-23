# 🏟️ Halı Saha Rezervasyon Sistemi

![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)
![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-8.0-orange)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple)

Modern, kullanıcı dostu ve kapsamlı bir halı saha yönetim ve rezervasyon platformu. Bu proje, halı saha tutkunlarını tesis sahipleriyle buluştururken, oyunlaştırma ve sosyal özelliklerle deneyimi zenginleştirmeyi hedefler.

## 🌟 Öne Çıkan Özellikler

### 👥 Müşteri Paneli
*   **🔍 Gelişmiş Arama:** Şehir ve ilçe bazlı filtreleme ile size en yakın halı sahaları anında bulun.
*   **📅 Kolay Rezervasyon:** Tesislerin müsaitlik durumunu takvim üzerinde görüntüleyin ve saniyeler içinde rezervasyon yapın.
*   **💳 Cüzdan & Sanal Kart:**
    *   Kullanıcıya özel cüzdan sistemi.
    *   Bakiye yükleme ve harcama takibi.
    *   **Bonus Sistemi:** Yüklemelerde ekstra bonus kazanma fırsatı.
*   **🏆 Oyunlaştırma (Gamification):**
    *   **Görevler:** "İlk Rezervasyon", "Yorum Yap" gibi görevleri tamamlayın.
    *   **Rozetler:** Başarılarınızı profilinizde sergileyin (Örn: Gol Kralı, Sadık Müşteri).
    *   **Liderlik Tablosu:** En aktif kullanıcılar arasında yerinizi alın.
*   **💬 Sosyal Etkileşim:**
    *   Tesisleri puanlayın ve yorum yapın.
    *   Diğer kullanıcıların yorumlarını beğenin/beğenmeyin.
    *   Favori tesislerinizi listenize ekleyin.
*   **🌤️ Hava Durumu Entegrasyonu:** Maç yapacağınız günün hava durumunu tesis detay sayfasında görün.

### 🏢 Tesis Sahibi Paneli
*   **🏟️ Tesis Yönetimi:** Tesis bilgileri, fotoğraflar, özellikler (Duş, Otopark, WiFi vb.) ve konum ekleme.
*   **⚽ Saha & Fiyatlandırma:** Farklı zemin türleri ve saatlik ücretlendirme seçenekleri.
*   **✅ Rezervasyon Takibi:** Gelen rezervasyon taleplerini onaylayın veya reddedin.
*   **🗣️ Yorum Yönetimi:** Müşteri yorumlarına kurumsal yanıtlar verin.

### 🛡️ Admin (Yönetici) Paneli
*   **📊 Dashboard:** Toplam üye, tesis, rezervasyon ve ciro istatistiklerini tek ekranda görün.
*   **👥 Kullanıcı Yönetimi:** Tüm kullanıcıların detaylı profillerini, harcamalarını ve aktivitelerini inceleyin.
*   **📈 Raporlama:** Şehir bazlı rezervasyon dağılımı ve finansal raporlar.
*   **⚙️ İçerik Yönetimi:** Tesis onaylama, yorum denetimi ve sistem ayarları.

## 🚀 Teknoloji Yığını

Bu proje, performans ve sürdürülebilirlik odaklı modern web teknolojileri kullanılarak geliştirilmiştir.

| Alan | Teknoloji |
|---|---|
| **Backend** | PHP 8.x (PDO, OOP Mimarisi) |
| **Veritabanı** | MySQL (İlişkisel Veritabanı Tasarımı, Stored Procedures) |
| **Frontend** | HTML5, CSS3, JavaScript (ES6+) |
| **UI Framework** | Bootstrap 5 |
| **İkon Seti** | FontAwesome 6 |
| **Veri İletişimi** | AJAX (Asenkron Veri İşleme) |

## 🛠️ Kurulum Adımları

Projeyi yerel makinenizde veya sunucunuzda çalıştırmak için aşağıdaki adımları izleyin:

1.  **Dosyaları Kopyalayın:** Proje dosyalarını web sunucunuzun (Apache/Nginx) kök dizinine (`www` veya `htdocs`) taşıyın.
2.  **Veritabanını Oluşturun:**
    *   MySQL'de yeni bir veritabanı oluşturun (Örn: `halisaha_db`).
    *   `halisaha_db (7).sql` dosyasını içe aktarın (Import).
3.  **Bağlantı Ayarları:**
    *   `includes/db.php` dosyasını açın.
    *   Veritabanı bilgilerinizi (host, dbname, user, password) güncelleyin.
    ```php
    $host = 'localhost';
    $dbname = 'halisaha_db';
    $username = 'root';
    $password = '';
    ```
4.  **Çalıştırın:** Tarayıcınızda `http://localhost/halisaha` adresine gidin.

## 📂 Proje Yapısı

```
halisaha/
├── 📂 assets/          # CSS, JS ve Resim dosyaları
├── 📂 includes/        # Veritabanı bağlantısı, Header, Footer vb. parçalar
├── 📂 uploads/         # Kullanıcı ve tesis resimleri
├── 📄 index.php        # Anasayfa
├── 📄 login.php        # Giriş sayfası
├── 📄 register.php     # Kayıt sayfası
├── 📄 profil.php       # Kullanıcı profili
├── 📄 tesis_detay.php  # Tesis detay ve rezervasyon sayfası
├── 📄 cuzdan.php       # Cüzdan ve bakiye işlemleri
├── 📄 admin_panel.php  # Yönetici paneli
└── 📄 README.md        # Proje dokümantasyonu
```

## 🤝 Katkıda Bulunma (Contributing)

Katkılarınızı bekliyoruz! Projeyi geliştirmek için şu adımları izleyebilirsiniz:

1.  Bu depoyu (repository) forklayın.
2.  Yeni bir özellik dalı (feature branch) oluşturun (`git checkout -b yeni-ozellik`).
3.  Değişikliklerinizi yapın ve commitleyin (`git commit -m 'Yeni özellik eklendi'`).
4.  Dalınızı (branch) gönderin (`git push origin yeni-ozellik`).
5.  Bir Pull Request (PR) oluşturun.

## 📄 Lisans (License)

Bu proje **MIT Lisansı** altında lisanslanmıştır. Detaylar için [LICENSE](LICENSE) dosyasına bakabilirsiniz.

```text
MIT License

Copyright (c) 2025 Halı Saha Rezervasyon Sistemi

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.
```

---

✨ **Halı Saha Rezervasyon Sistemi** ile maç keyfini bir üst seviyeye taşıyın!
