<?php
require_once 'includes/db.php';
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

// 3. Tesisin Özelliklerini Çek (Stored Procedure ile)
$stmt = $pdo->prepare("CALL sp_TesisOzellikleriGetir(?)");
$stmt->execute([$tesis_id]);
$ozellikler = $stmt->fetchAll();
$stmt->closeCursor(); // <--- Yine kapatıyoruz

// Sahalar
$stmt = $pdo->prepare("CALL sp_SahalariGetir(?)");
$stmt->execute([$tesis_id]);
$sahalar = $stmt->fetchAll();
$stmt->closeCursor();

// Yorumlar
$stmt = $pdo->prepare("CALL sp_TesisYorumlariGetir(?)");
$stmt->execute([$tesis_id]);
$yorumlar = $stmt->fetchAll();
$stmt->closeCursor();

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
                        <?php if (empty($ozellikler)): ?>
                            <p class="text-muted small">Belirtilmiş özellik yok.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- YORUMLAR BÖLÜMÜ (GÜNCELLENDİ) -->
            <div class="card border-0 shadow-sm p-4 mt-4">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h5 class="fw-bold mb-0">Oyuncu Yorumları (<?php echo count($yorumlar); ?>)</h5>
                    <span class="text-warning fw-bold fs-5">
                        <?php echo isset($tesis['ortalama_puan']) ? number_format($tesis['ortalama_puan'], 1) : '5.0'; ?> <i class="fas fa-star"></i>
                    </span>
                </div>

                <?php if (count($yorumlar) > 0): ?>
                    <?php foreach ($yorumlar as $yorum): ?>
                        <div class="mb-3 border-bottom pb-2">
                            <div class="d-flex justify-content-between">
                                <strong class="text-dark">
                                    <?php echo substr($yorum['ad'],0,1) . '*** ' . substr($yorum['soyad'],0,1) . '***'; ?>
                                </strong>
                                <small class="text-muted"><?php echo date("d.m.Y", strtotime($yorum['tarih'])); ?></small>
                            </div>
                            <div class="mb-1">
                                <?php for($i=0; $i<$yorum['puan']; $i++) echo '<i class="fas fa-star text-warning small"></i>'; ?>
                                <?php for($i=$yorum['puan']; $i<5; $i++) echo '<i class="far fa-star text-warning small"></i>'; ?>
                            </div>
                            <p class="text-muted mb-0 small">
                                <?php echo htmlspecialchars($yorum['yorum_metni']); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="far fa-comment-dots fa-3x text-muted mb-2"></i>
                        <p class="text-muted">Henüz yorum yapılmamış. Bu tesisi deneyimleyen ilk kişi sen ol!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- SAĞ KOLON: SAHALAR VE REZERVASYON -->
        <div class="col-lg-4">
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
    
    fetch('ajax_favori.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'tesis_id=' + tesisId
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            if (data.islem === 'eklendi') {
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
</script>

<?php include 'includes/footer.php'; ?>