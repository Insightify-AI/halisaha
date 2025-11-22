<?php
require_once 'includes/db.php';
require_once 'includes/WeatherService.php'; // Hava Durumu Servisi
include 'includes/header.php';

// 1. URL'den ID'yi al ve Güvenlik Kontrolü yap
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php"); // ID yoksa anasayfaya at
    exit;
}
$tesis_id = (int)$_GET['id'];

// 2. Tesis Temel Bilgilerini Çek (Stored Procedure ile)
// Eski SQL yerine CALL kullanıyoruz
$stmt = $pdo->prepare("CALL sp_TesisDetayGetir(?)");
$stmt->execute([$tesis_id]);
$tesis = $stmt->fetch();
$stmt->closeCursor(); // <--- ÇOK ÖNEMLİ: İmleci kapat ki sonraki sorgu çalışabilsin!

// Eğer böyle bir tesis yoksa anasayfaya at
if (!$tesis) {
    header("Location: index.php");
    exit;
}

// Hava Durumu Verisi (Tesisin bulunduğu şehre göre)
$bugun = date('Y-m-d');
$yarin = date('Y-m-d', strtotime('+1 day'));
$havaBugun = WeatherService::getWeather($tesis['sehir_adi'], $bugun);
$havaYarin = WeatherService::getWeather($tesis['sehir_adi'], $yarin);


// 3. Tesisin Özelliklerini Çek
$stmt = $pdo->prepare("
    SELECT o.ozellik_adi, o.ikon_kodu 
    FROM TesisOzellikleri toz
    JOIN Ozellikler o ON toz.ozellik_id = o.ozellik_id
    WHERE toz.tesis_id = ?
");
$stmt->execute([$tesis_id]);
$ozellikler = $stmt->fetchAll();
$stmt->closeCursor();

// Sahalar
$stmt = $pdo->prepare("CALL sp_SahalariGetir(?)");
$stmt->execute([$tesis_id]);
$sahalar = $stmt->fetchAll();
$stmt->closeCursor();

// Yorumları Çek (Kullanıcı bilgileriyle birlikte)
$stmt = $pdo->prepare("
    SELECT y.*, k.ad, k.soyad 
    FROM Yorumlar y
    JOIN Kullanicilar k ON y.musteri_id = k.kullanici_id
    WHERE y.tesis_id = ? AND y.onay_durumu = 'Onaylandı' 
    ORDER BY y.tarih DESC
");
$stmt->execute([$tesis_id]);
$yorumlar = $stmt->fetchAll();
$stmt->closeCursor();

// Kullanıcının bu tesiste tamamlanmış rezervasyonu var mı kontrol et
$kullaniciYorumYapabilir = false;
$currentUserId = 1; // Şimdilik varsayılan (Login sistemi entegre olunca session'dan alınacak)

$stmt = $pdo->prepare("
    SELECT COUNT(*) as rezervasyon_sayisi 
    FROM Rezervasyonlar r
    JOIN Sahalar s ON r.saha_id = s.saha_id
    WHERE s.tesis_id = ? 
    AND r.musteri_id = ? 
    AND r.durum = 'tamamlandi'
");
$stmt->execute([$tesis_id, $currentUserId]);
$result = $stmt->fetch();
$kullaniciYorumYapabilir = ($result['rezervasyon_sayisi'] > 0);


?>

<!-- SAYFA BAŞLIĞI (Breadcrumb) -->
<nav aria-label="breadcrumb" class="bg-light py-3 mb-4 border-bottom">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index.php">Anasayfa</a></li>
            <li class="breadcrumb-item"><?php echo $tesis['sehir_adi']; ?></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo $tesis['tesis_adi']; ?></li>
        </ol>
    </div>
</nav>

<div class="container mb-5">
    <div class="row">
        <!-- SOL KOLON: RESİM VE BİLGİLER -->
        <div class="col-lg-8">
            <!-- Tesis Resmi -->
            <div class="card border-0 shadow-sm mb-4">
                <?php 
                     $resim = !empty($tesis['kapak_resmi']) ? $tesis['kapak_resmi'] : 'https://images.unsplash.com/photo-1551958219-acbc608c6377?q=80&w=1200&auto=format&fit=crop';
                ?>
                <img src="<?php echo $resim; ?>" class="card-img-top rounded-3" style="height: 400px; object-fit: cover;" alt="Tesis Resmi">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h1 class="fw-bold text-primary mb-1"><?php echo htmlspecialchars($tesis['tesis_adi']); ?></h1>
                            <p class="text-muted mb-2">
                                <i class="fas fa-map-marker-alt text-danger me-1"></i> 
                                <?php echo $tesis['adres'] . ' - ' . $tesis['ilce_adi'] . '/' . $tesis['sehir_adi']; ?>
                            </p>
                        </div>
                        <!-- DİNAMİK PUAN ROZETİ VE FAVORİ BUTONU -->
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-outline-danger btn-sm rounded-circle shadow-sm" id="favoriBtn" onclick="favoriToggle(<?php echo $tesis_id; ?>)" title="Favorilere Ekle">
                                <i class="far fa-heart"></i>
                            </button>
                            <span class="badge bg-success fs-6 p-2">
                                <i class="fas fa-star text-warning"></i> 
                                <?php echo isset($tesis['ortalama_puan']) ? number_format($tesis['ortalama_puan'], 1) : '5.0'; ?> / 5
                            </span>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h5 class="fw-bold">Tesis Hakkında</h5>
                    <p><?php echo nl2br(htmlspecialchars($tesis['aciklama'] ?? 'Bu tesis için açıklama girilmemiş.')); ?></p>
                    
                    <!-- Özellikler Listesi -->
                    <h5 class="fw-bold mt-4 mb-3">İmkanlar & Özellikler</h5>
                    <div class="row row-cols-2 row-cols-md-4 g-3">
                        <?php foreach ($ozellikler as $ozellik): ?>
                            <div class="col">
                                <div class="d-flex align-items-center p-2 border rounded bg-light">
                                    <i class="fas <?php echo $ozellik['ikon_kodu']; ?> text-primary me-2 fa-lg"></i>
                                    <span class="small fw-bold"><?php echo $ozellik['ozellik_adi']; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <hr class="my-5">

                    <!-- Yorumlar Bölümü -->
                    <h5 class="fw-bold mb-4">Kullanıcı Yorumları (<?php echo count($yorumlar); ?>)</h5>
                    
                    <!-- Yorum Listesi -->
                    <div class="mb-5">
                        <?php if (count($yorumlar) > 0): ?>
                            <?php foreach ($yorumlar as $yorum): ?>
                                <div class="card mb-3 border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="fw-bold mb-0">
                                                <?php 
                                                // Kullanıcı adını gizli göster (örn: "Fatih K***")
                                                echo htmlspecialchars($yorum['ad']) . ' ' . substr($yorum['soyad'], 0, 1) . '***'; 
                                                ?>
                                            </h6>
                                            <small class="text-muted"><?php echo date('d.m.Y', strtotime($yorum['tarih'])); ?></small>
                                        </div>
                                        <div class="mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $yorum['puan'] ? 'text-warning' : 'text-muted'; ?> small"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <p class="card-text"><?php echo htmlspecialchars($yorum['yorum_metni']); ?></p>
                                        <?php if ($yorum['resim_yolu']): ?>
                                            <img src="<?php echo $yorum['resim_yolu']; ?>" class="img-thumbnail mt-2" style="max-height: 100px;">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-light text-center">Henüz yorum yapılmamış. İlk yorumu sen yap!</div>
                        <?php endif; ?>
                    </div>

                    <!-- Yorum Yap Formu - Sadece rezervasyonu tamamlanmış kullanıcılara göster -->
                    <?php if ($kullaniciYorumYapabilir): ?>
                        <div class="card bg-light border-0 p-4">
                            <h6 class="fw-bold mb-3">Yorum Yap</h6>
                            <form id="commentForm" enctype="multipart/form-data">
                                <input type="hidden" name="tesis_id" value="<?php echo $tesis_id; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Puanınız</label>
                                    <div class="rating">
                                        <select name="puan" class="form-select w-auto">
                                            <option value="5">⭐⭐⭐⭐⭐ (5)</option>
                                            <option value="4">⭐⭐⭐⭐ (4)</option>
                                            <option value="3">⭐⭐⭐ (3)</option>
                                            <option value="2">⭐⭐ (2)</option>
                                            <option value="1">⭐ (1)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Yorumunuz</label>
                                    <textarea name="yorum" class="form-control" rows="3" required placeholder="Deneyimlerinizi paylaşın..."></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Fotoğraf Ekle (İsteğe bağlı, Max: 5MB)</label>
                                    <input type="file" name="resim" class="form-control" accept="image/*">
                                    <small class="text-muted">Kabul edilen formatlar: JPG, JPEG, PNG, WEBP</small>
                                </div>
                                <button type="submit" class="btn btn-primary">Gönder</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Yorum yapabilmek için bu tesiste tamamlanmış bir rezervasyonunuz olmalıdır.
                        </div>
                    <?php endif; ?>
                </div>
            </div>


        </div>

        <!-- SAĞ KOLON: REZERVASYON & HAVA DURUMU -->
        <div class="col-lg-4">
            <!-- Hava Durumu Widget -->
            <div class="card border-0 shadow-sm mb-4 bg-gradient-primary text-white" style="background: linear-gradient(45deg, #4e54c8, #8f94fb);">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><i class="fas fa-cloud-sun-rain me-2"></i>Saha Hava Durumu</h5>
                    <div class="d-flex justify-content-between align-items-center mb-2 border-bottom border-white pb-2" style="border-opacity: 0.2;">
                        <span>Bugün</span>
                        <div class="text-end">
                            <i class="<?php echo $havaBugun['icon']; ?> fa-lg me-1"></i> 
                            <span class="fw-bold"><?php echo $havaBugun['temp']; ?>°C</span>
                            <br>
                            <small style="font-size: 0.8rem; opacity: 0.9;"><?php echo $havaBugun['text']; ?></small>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Yarın</span>
                        <div class="text-end">
                            <i class="<?php echo $havaYarin['icon']; ?> fa-lg me-1"></i> 
                            <span class="fw-bold"><?php echo $havaYarin['temp']; ?>°C</span>
                            <br>
                            <small style="font-size: 0.8rem; opacity: 0.9;"><?php echo $havaYarin['text']; ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-lg border-0 sticky-top" style="top: 20px; z-index: 1;">
                <div class="card-header bg-dark text-white text-center py-3">
                    <h4 class="mb-0"><i class="far fa-calendar-check me-2"></i>Rezervasyon Yap</h4>
                </div>
                <div class="card-body bg-light">
                    <h6 class="fw-bold text-muted mb-3">Müsait Sahalar</h6>
                    
                    <?php if (count($sahalar) > 0): ?>
                        <div class="list-group mb-4">
                            <?php foreach ($sahalar as $saha): ?>
                                <label class="list-group-item d-flex justify-content-between align-items-center" style="cursor: pointer;">
                                    <div>
                                        <input class="form-check-input me-2" type="radio" name="saha_secimi" value="<?php echo $saha['saha_id']; ?>" id="saha_<?php echo $saha['saha_id']; ?>">
                                        <strong><?php echo $saha['saha_adi']; ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $saha['zemin_tipi'])); ?></small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">
                                        <?php echo number_format($saha['fiyat_saatlik'], 0); ?> TL
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-grid">
                            <button id="rezervasyonBtn" class="btn btn-success btn-lg fw-bold" onclick="rezervasyonAdiminaGec()">
                                Tarih ve Saat Seç <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                        <p class="text-muted small text-center mt-3 mb-0">
                            <i class="fas fa-info-circle"></i> Önce yukarıdan bir saha seçiniz.
                        </p>
                    <?php else: ?>
                        <div class="alert alert-warning text-center">
                            Bu tesise ait tanımlı saha bulunmamaktadır.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center bg-white">
                    <small class="text-muted">Tesis Yetkilisi: <?php echo $tesis['sahip_ad'] . ' ' . substr($tesis['sahip_soyad'], 0, 1) . '.'; ?></small>
                </div>
            </div>
            
            <!-- İletişim Kartı -->
            <div class="card mt-3 border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold">İletişim</h6>
                    <p class="mb-1"><i class="fas fa-phone me-2 text-muted"></i> <?php echo $tesis['telefon']; ?></p>
                    <p class="mb-0"><i class="fas fa-map-pin me-2 text-muted"></i> <a href="#" class="text-decoration-none">Haritada Göster</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function rezervasyonAdiminaGec() {
    // Seçili radio button'u bul
    const secilenSaha = document.querySelector('input[name="saha_secimi"]:checked');
    
    if (!secilenSaha) {
        alert("Lütfen rezervasyon yapmak için listeden bir saha seçiniz!");
        return;
    }
    
    const sahaId = secilenSaha.value;
    // Direkt sayfaya yönlendiriyoruz:
    window.location.href = 'rezervasyon_yap.php?saha_id=' + sahaId;
}

function favoriToggle(tesisId) {
    const btn = document.getElementById('favoriBtn');
    const icon = btn.querySelector('i');
    
    // Determine action based on current state (visual check)
    const isActive = icon.classList.contains('fas');
    const action = isActive ? 'remove' : 'add';

    fetch('ajax_favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `tesis_id=${tesisId}&action=${action}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.status === 'added') {
                btn.classList.remove('btn-outline-danger');
                btn.classList.add('btn-danger');
                icon.classList.remove('far');
                icon.classList.add('fas');
            } else {
                btn.classList.remove('btn-danger');
                btn.classList.add('btn-outline-danger');
                icon.classList.remove('fas');
                icon.classList.add('far');
            }
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Hata:', error));
}

// Yorum Gönderme İşlemi
document.getElementById('commentForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('ajax_comment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload(); // Yorumu görmek için sayfayı yenile
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Hata:', error));
});
</script>

<?php include 'includes/footer.php'; ?>