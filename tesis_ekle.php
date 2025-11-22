<?php
require_once 'includes/db.php';
include 'includes/header.php';

// GÜVENLİK: Sadece Tesis Sahibi
if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] != 'tesis_sahibi') {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

// Şehirleri ve Özellikleri Çek (Form için)
$tumOzellikler = $pdo->query("SELECT MIN(ozellik_id) as ozellik_id, ozellik_adi, MIN(ikon_kodu) as ikon_kodu FROM Ozellikler GROUP BY ozellik_adi")->fetchAll();

$mesaj = "";

// FORM GÖNDERİLDİ Mİ?
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sahip_id = $_SESSION['rol_id'];
    
    // Form verilerini al ve formatla
    $il_adi = trim($_POST['il_adi']);
    $ilce_adi = mb_convert_case(trim($_POST['ilce_adi']), MB_CASE_TITLE, "UTF-8");
    $ad = trim($_POST['tesis_adi']);
    $adres = trim($_POST['adres']);
    $telefon = trim($_POST['telefon']);
    $aciklama = trim($_POST['aciklama']);
    
    // Resim Upload İşlemi
    $resim = 'https://images.unsplash.com/photo-1529900748604-07564a03e7a6?q=80&w=800&auto=format&fit=crop'; // Varsayılan
    
    if (isset($_FILES['kapak_resmi']) && $_FILES['kapak_resmi']['error'] == 0) {
        $dosya = $_FILES['kapak_resmi'];
        $dosya_boyut = $dosya['size'];
        $dosya_tip = $dosya['type'];
        $dosya_tmp = $dosya['tmp_name'];
        
        // Dosya boyutu kontrolü (5MB = 5 * 1024 * 1024)
        if ($dosya_boyut > 5 * 1024 * 1024) {
            $mesaj = "<div class='alert alert-danger'>Resim boyutu 5MB'dan büyük olamaz!</div>";
        } else {
            // Dosya tipi kontrolü
            $izin_verilen = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            if (!in_array($dosya_tip, $izin_verilen)) {
                $mesaj = "<div class='alert alert-danger'>Sadece JPG, PNG ve WEBP formatları kabul edilir!</div>";
            } else {
                // Dosyayı kaydet
                $uploads_dir = 'uploads/tesisler/';
                if (!is_dir($uploads_dir)) {
                    mkdir($uploads_dir, 0777, true);
                }
                
                $dosya_adi = uniqid('tesis_') . '_' . time() . '.' . pathinfo($dosya['name'], PATHINFO_EXTENSION);
                $hedef_yol = $uploads_dir . $dosya_adi;
                
                if (move_uploaded_file($dosya_tmp, $hedef_yol)) {
                    $resim = $hedef_yol;
                } else {
                    $mesaj = "<div class='alert alert-warning'>Resim yüklenemedi, varsayılan resim kullanılacak.</div>";
                }
            }
        }
    }
    
    $secilen_ozellikler = isset($_POST['ozellikler']) ? $_POST['ozellikler'] : [];

    if (empty($il_adi) || empty($ilce_adi) || empty($ad) || empty($adres)) {
        $mesaj = "<div class='alert alert-danger'>Lütfen zorunlu alanları doldurun.</div>";
    } else if (!isset($mesaj)) { // Dosya upload hatası yoksa devam et
        try {
            // 1. Şehir kontrolü ve ekleme
            $stmt = $pdo->prepare("SELECT sehir_id FROM Sehirler WHERE sehir_adi = ?");
            $stmt->execute([$il_adi]);
            $sehir = $stmt->fetch();
            
            if (!$sehir) {
                // Şehir yoksa ekle
                $stmt = $pdo->prepare("INSERT INTO Sehirler (sehir_adi, plaka_kodu) VALUES (?, 0)");
                $stmt->execute([$il_adi]);
                $sehir_id = $pdo->lastInsertId();
            } else {
                $sehir_id = $sehir['sehir_id'];
            }
            
            // 2. İlçe kontrolü ve ekleme
            $stmt = $pdo->prepare("SELECT ilce_id FROM Ilceler WHERE ilce_adi = ? AND sehir_id = ?");
            $stmt->execute([$ilce_adi, $sehir_id]);
            $ilce = $stmt->fetch();
            
            if (!$ilce) {
                // İlçe yoksa ekle
                $stmt = $pdo->prepare("INSERT INTO Ilceler (ilce_adi, sehir_id) VALUES (?, ?)");
                $stmt->execute([$ilce_adi, $sehir_id]);
                $ilce_id = $pdo->lastInsertId();
            } else {
                $ilce_id = $ilce['ilce_id'];
            }
            
            // 3. TESİSİ EKLE
            $stmt = $pdo->prepare("CALL sp_TesisEkle(?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$sahip_id, $ilce_id, $ad, $adres, $telefon, $aciklama, $resim]);
            $sonuc = $stmt->fetch();
            $stmt->closeCursor();
            
            $yeni_tesis_id = $sonuc['yeni_id'];

            // 4. ÖZELLİKLERİ EKLE
            if (!empty($secilen_ozellikler)) {
                foreach ($secilen_ozellikler as $ozellik_id) {
                    $stmtOz = $pdo->prepare("CALL sp_TesisOzellikBagla(?, ?)");
                    $stmtOz->execute([$yeni_tesis_id, $ozellik_id]);
                }
            }

            // Başarılı Mesajı ve Yönlendirme
            echo "<script>
                alert('Tesis başvurunuz alındı! Yönetici onayından sonra yayınlanacaktır.');
                window.location.href = 'tesislerim.php';
            </script>";
            exit;

        } catch (PDOException $e) {
            $mesaj = "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Yeni Tesis Ekle</h4>
                </div>
                <div class="card-body p-4">
                    
                    <?php echo $mesaj; ?>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <!-- Temel Bilgiler -->
                        <h5 class="border-bottom pb-2 mb-3 text-muted">Tesis Bilgileri</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">İl</label>
                                <select name="il_adi" class="form-select" required>
                                    <option value="">Seçiniz...</option>
                                    <option value="Adana">Adana</option>
                                    <option value="Adıyaman">Adıyaman</option>
                                    <option value="Afyonkarahisar">Afyonkarahisar</option>
                                    <option value="Ağrı">Ağrı</option>
                                    <option value="Aksaray">Aksaray</option>
                                    <option value="Amasya">Amasya</option>
                                    <option value="Ankara">Ankara</option>
                                    <option value="Antalya">Antalya</option>
                                    <option value="Ardahan">Ardahan</option>
                                    <option value="Artvin">Artvin</option>
                                    <option value="Aydın">Aydın</option>
                                    <option value="Balıkesir">Balıkesir</option>
                                    <option value="Bartın">Bartın</option>
                                    <option value="Batman">Batman</option>
                                    <option value="Bayburt">Bayburt</option>
                                    <option value="Bilecik">Bilecik</option>
                                    <option value="Bingöl">Bingöl</option>
                                    <option value="Bitlis">Bitlis</option>
                                    <option value="Bolu">Bolu</option>
                                    <option value="Burdur">Burdur</option>
                                    <option value="Bursa">Bursa</option>
                                    <option value="Çanakkale">Çanakkale</option>
                                    <option value="Çankırı">Çankırı</option>
                                    <option value="Çorum">Çorum</option>
                                    <option value="Denizli">Denizli</option>
                                    <option value="Diyarbakır">Diyarbakır</option>
                                    <option value="Düzce">Düzce</option>
                                    <option value="Edirne">Edirne</option>
                                    <option value="Elazığ">Elazığ</option>
                                    <option value="Erzincan">Erzincan</option>
                                    <option value="Erzurum">Erzurum</option>
                                    <option value="Eskişehir">Eskişehir</option>
                                    <option value="Gaziantep">Gaziantep</option>
                                    <option value="Giresun">Giresun</option>
                                    <option value="Gümüşhane">Gümüşhane</option>
                                    <option value="Hakkari">Hakkari</option>
                                    <option value="Hatay">Hatay</option>
                                    <option value="Iğdır">Iğdır</option>
                                    <option value="Isparta">Isparta</option>
                                    <option value="İstanbul">İstanbul</option>
                                    <option value="İzmir">İzmir</option>
                                    <option value="Kahramanmaraş">Kahramanmaraş</option>
                                    <option value="Karabük">Karabük</option>
                                    <option value="Karaman">Karaman</option>
                                    <option value="Kars">Kars</option>
                                    <option value="Kastamonu">Kastamonu</option>
                                    <option value="Kayseri">Kayseri</option>
                                    <option value="Kilis">Kilis</option>
                                    <option value="Kırıkkale">Kırıkkale</option>
                                    <option value="Kırklareli">Kırklareli</option>
                                    <option value="Kırşehir">Kırşehir</option>
                                    <option value="Kocaeli">Kocaeli</option>
                                    <option value="Konya">Konya</option>
                                    <option value="Kütahya">Kütahya</option>
                                    <option value="Malatya">Malatya</option>
                                    <option value="Manisa">Manisa</option>
                                    <option value="Mardin">Mardin</option>
                                    <option value="Mersin">Mersin</option>
                                    <option value="Muğla">Muğla</option>
                                    <option value="Muş">Muş</option>
                                    <option value="Nevşehir">Nevşehir</option>
                                    <option value="Niğde">Niğde</option>
                                    <option value="Ordu">Ordu</option>
                                    <option value="Osmaniye">Osmaniye</option>
                                    <option value="Rize">Rize</option>
                                    <option value="Sakarya">Sakarya</option>
                                    <option value="Samsun">Samsun</option>
                                    <option value="Şanlıurfa">Şanlıurfa</option>
                                    <option value="Siirt">Siirt</option>
                                    <option value="Sinop">Sinop</option>
                                    <option value="Şırnak">Şırnak</option>
                                    <option value="Sivas">Sivas</option>
                                    <option value="Tekirdağ">Tekirdağ</option>
                                    <option value="Tokat">Tokat</option>
                                    <option value="Trabzon">Trabzon</option>
                                    <option value="Tunceli">Tunceli</option>
                                    <option value="Uşak">Uşak</option>
                                    <option value="Van">Van</option>
                                    <option value="Yalova">Yalova</option>
                                    <option value="Yozgat">Yozgat</option>
                                    <option value="Zonguldak">Zonguldak</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">İlçe</label>
                                <input type="text" name="ilce_adi" class="form-control" required placeholder="Örn: Karşıyaka">
                                <small class="form-text text-muted">İlçe adını yazın (otomatik formatlanacak)</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tesis Adı</label>
                            <input type="text" name="tesis_adi" class="form-control" required placeholder="Örn: Yıldız Halı Saha">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Adres</label>
                            <textarea name="adres" class="form-control" rows="2" required></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Telefon</label>
                                <input type="text" name="telefon" class="form-control" placeholder="0212...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kapak Resmi</label>
                                <input type="file" name="kapak_resmi" class="form-control" accept="image/jpeg,image/jpg,image/png,image/webp">
                                <small class="form-text text-muted">Max 5MB - JPG, PNG, WEBP (İsteğe bağlı)</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Açıklama</label>
                            <textarea name="aciklama" class="form-control" rows="3" placeholder="Tesisiniz hakkında kısa bilgi..."></textarea>
                        </div>

                        <!-- Özellikler -->
                        <h5 class="border-bottom pb-2 mb-3 text-primary">
                            <i class="fas fa-check-circle me-2"></i>Tesis İmkanları
                        </h5>
                        <p class="text-muted small mb-3">Tesisinizde bulunan imkanları seçin:</p>
                        <div class="row g-3 mb-4">
                            <?php foreach ($tumOzellikler as $oz): ?>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <input class="amenity-checkbox" type="checkbox" name="ozellikler[]" 
                                           value="<?php echo $oz['ozellik_id']; ?>" 
                                           id="oz_<?php echo $oz['ozellik_id']; ?>">
                                    <label class="amenity-card" for="oz_<?php echo $oz['ozellik_id']; ?>">
                                        <div class="amenity-icon">
                                            <i class="fas <?php echo $oz['ikon_kodu']; ?>"></i>
                                        </div>
                                        <div class="amenity-text">
                                            <?php echo $oz['ozellik_adi']; ?>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <style>
                            .amenity-checkbox {
                                display: none;
                            }
                            
                            .amenity-card {
                                display: flex;
                                flex-direction: column;
                                align-items: center;
                                padding: 15px 10px;
                                border: 2px solid #e9ecef;
                                border-radius: 12px;
                                cursor: pointer;
                                transition: all 0.3s ease;
                                height: 100%;
                                background: #fff;
                            }
                            
                            .amenity-card:hover {
                                border-color: #0d6efd;
                                transform: translateY(-2px);
                                box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
                            }
                            
                            .amenity-checkbox:checked + .amenity-card {
                                border-color: #0d6efd;
                                background: linear-gradient(135deg, #e7f1ff 0%, #f0f7ff 100%);
                            }
                            
                            .amenity-checkbox:checked + .amenity-card .amenity-icon {
                                background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
                                color: #fff;
                            }
                            
                            .amenity-icon {
                                width: 50px;
                                height: 50px;
                                background: #f8f9fa;
                                border-radius: 50 %;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                margin-bottom: 10px;
                                font-size: 1.4rem;
                                color: #6c757d;
                                transition: all 0.3s ease;
                            }
                            
                            .amenity-text {
                                text-align: center;
                                font-size: 0.85rem;
                                font-weight: 600;
                                color: #495057;
                            }
                        </style>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="tesislerim.php" class="btn btn-secondary me-md-2">İptal</a>
                            <button type="submit" class="btn btn-primary px-5">Kaydet ve Başvur</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>