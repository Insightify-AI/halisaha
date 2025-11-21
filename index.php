<?php
require_once 'includes/db.php';
include 'includes/header.php';

// 1. VERİ ÇEKME İŞLEMLERİ
// Arama kutusu için Şehirleri çekelim
$sehirler = $pdo->query("SELECT * FROM Sehirler ORDER BY plaka_kodu ASC")->fetchAll();

// sp_VitrinTesisleriGetir prosedürü ile çekiyoruz
$stmt = $pdo->prepare("CALL sp_VitrinTesisleriGetir()");
$stmt->execute();
$vitrinTesisler = $stmt->fetchAll();
$stmt->closeCursor(); // Diğer sorgular için imleci temizle
?>

<!-- ÖZEL CSS (Sadece bu sayfa için) -->
<style>
    /* Hero Section: Arka plan resmi ve karartma efekti */
    .hero-header {
        background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1529900748604-07564a03e7a6?q=80&w=1920&auto=format&fit=crop');
        background-size: cover;
        background-position: center;
        color: white;
        padding: 100px 0;
        border-radius: 15px;
        margin-bottom: 40px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    
    /* Kart Tasarımı */
    .card-hover {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
        border-radius: 12px;
        overflow: hidden;
    }
    .card-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    }
    .card-img-top {
        height: 200px;
        object-fit: cover;
    }
    .badge-city {
        background-color: #28a745; /* Yeşil ton */
        font-size: 0.8rem;
    }
</style>

<!-- 2. HERO SEARCH SECTION -->
<div class="hero-header text-center text-white">
    <div class="container">
        <h1 class="display-4 fw-bold mb-3">Maç Yapmaya Hazır Mısın?</h1>
        <p class="lead mb-4">Şehrindeki en iyi halı sahaları bul, takımını kur ve sahaya çık!</p>
        
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Arama Formu -->
                <form action="arama.php" method="GET" class="card p-3 shadow-lg border-0" style="background: rgba(255,255,255,0.95);">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <select name="sehir_id" class="form-select form-select-lg" required>
                                <option value="" selected disabled>Şehir Seçiniz...</option>
                                <?php foreach ($sehirler as $sehir): ?>
                                    <option value="<?php echo $sehir['sehir_id']; ?>">
                                        <?php echo $sehir['sehir_adi']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <!-- İleride AJAX ile ilçeler dolacak, şimdilik görsel -->
                            <select name="ilce_id" class="form-select form-select-lg" disabled>
                                <option value="">Önce Şehir Seçin</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
                                <i class="fas fa-search me-2"></i>ARA
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 3. POPÜLER TESİSLER LİSTESİ -->
<div class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold border-start border-5 border-primary ps-3">Popüler Tesisler</h2>
        <a href="arama.php" class="btn btn-outline-primary">Tümünü Gör <i class="fas fa-arrow-right ms-1"></i></a>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php if (count($vitrinTesisler) > 0): ?>
            <?php foreach ($vitrinTesisler as $tesis): ?>
                <div class="col">
                    <div class="card h-100 card-hover shadow-sm">
                        <!-- Resim Kontrolü: Veritabanında resim yoksa varsayılan resim koy -->
                        <?php 
                            $resim = !empty($tesis['kapak_resmi']) ? $tesis['kapak_resmi'] : 'https://images.unsplash.com/photo-1551958219-acbc608c6377?q=80&w=800&auto=format&fit=crop';
                        ?>
                        <div class="position-relative">
                            <img src="<?php echo $resim; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($tesis['tesis_adi']); ?>">
                            <span class="position-absolute top-0 end-0 m-2 badge rounded-pill bg-light text-dark shadow-sm">
                                <i class="fas fa-star text-warning"></i> 4.8
                            </span>
                        </div>
                        
                        <div class="card-body">
                            <div class="mb-2">
                                <span class="badge badge-city">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo $tesis['sehir_adi'] . " / " . $tesis['ilce_adi']; ?>
                                </span>
                            </div>
                            <h5 class="card-title fw-bold text-dark">
                                <?php echo htmlspecialchars($tesis['tesis_adi']); ?>
                            </h5>
                            <p class="card-text text-muted small">
                                Modern soyunma odaları, otopark ve kafeterya hizmeti ile...
                            </p>
                        </div>
                        <div class="card-footer bg-white border-top-0 pb-3">
                            <div class="d-grid">
                                <a href="tesis_detay.php?id=<?php echo $tesis['tesis_id']; ?>" class="btn btn-outline-dark">
                                    İncele & Rezervasyon Yap
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                <p class="text-muted">Henüz sistemde onaylı bir tesis bulunmuyor.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- İletişim / Bilgi Bölümü -->
<div class="row mt-5 mb-5 bg-light p-5 rounded-3 shadow-sm">
    <div class="col-md-4 text-center mb-3 mb-md-0">
        <div class="display-4 text-primary mb-2"><i class="fas fa-headset"></i></div>
        <h4>7/24 Destek</h4>
        <p class="text-muted">Rezervasyonlarınla ilgili her an yanındayız.</p>
    </div>
    <div class="col-md-4 text-center mb-3 mb-md-0">
        <div class="display-4 text-success mb-2"><i class="fas fa-shield-alt"></i></div>
        <h4>Güvenli Ödeme</h4>
        <p class="text-muted">Bilgilerin 256-bit SSL ile korunmaktadır.</p>
    </div>
    <div class="col-md-4 text-center">
        <div class="display-4 text-warning mb-2"><i class="fas fa-mobile-alt"></i></div>
        <h4>Mobil Uyumlu</h4>
        <p class="text-muted">Telefondan veya tabletten kolayca yer ayırt.</p>
    </div>
</div>

<!-- Index.php en altına (footer'dan önce) eklenecek -->
<script>
document.querySelector('select[name="sehir_id"]').addEventListener('change', function() {
    var sehirId = this.value;
    var ilceSelect = document.querySelector('select[name="ilce_id"]');
    
    // İlçe kutusunu temizle ve yükleniyor yaz
    ilceSelect.innerHTML = '<option>Yükleniyor...</option>';
    ilceSelect.disabled = true;

    // AJAX İsteği
    fetch('ajax_ilceler.php?sehir_id=' + sehirId)
        .then(response => response.json())
        .then(data => {
            ilceSelect.innerHTML = '<option value="">Tüm İlçeler</option>';
            
            data.forEach(ilce => {
                var option = document.createElement('option');
                option.value = ilce.ilce_id;
                option.text = ilce.ilce_adi;
                ilceSelect.appendChild(option);
            });
            
            ilceSelect.disabled = false; // Kilidi aç
        })
        .catch(error => {
            console.error('Hata:', error);
            ilceSelect.innerHTML = '<option>Hata oluştu</option>';
        });
});
</script>

<?php include 'includes/footer.php'; ?>