<?php
require_once 'includes/db.php';
include 'includes/header.php';

// 1. GET PARAMETRELERİNİ AL
$sehir_id = isset($_GET['sehir_id']) ? (int)$_GET['sehir_id'] : 0;
$ilce_id = isset($_GET['ilce_id']) ? (int)$_GET['ilce_id'] : 0;
// Seçilen özellikleri dizi olarak al (name="ozellikler[]" olduğu için)
$secilen_ozellikler = isset($_GET['ozellikler']) ? $_GET['ozellikler'] : [];

// 2. VERİTABANINDAN TÜM ÖZELLİKLERİ ÇEK (Checkboxları oluşturmak için)
// ONLY_FULL_GROUP_BY moduna uygun olması için MIN/MAX kullanıyoruz
$tumOzellikler = $pdo->query("SELECT MIN(ozellik_id) as ozellik_id, ozellik_adi, MIN(ikon_kodu) as ikon_kodu FROM Ozellikler GROUP BY ozellik_adi")->fetchAll();

// 3. BAŞLIK OLUŞTURMA
$baslik = "Tüm Tesisler";
if ($sehir_id > 0) {
    $stmt = $pdo->prepare("SELECT sehir_adi FROM Sehirler WHERE sehir_id = ?");
    $stmt->execute([$sehir_id]);
    $sehir_adi = $stmt->fetchColumn();
    $baslik = $sehir_adi . " Halı Sahaları";
    
    if ($ilce_id > 0) {
        $stmt = $pdo->prepare("SELECT ilce_adi FROM Ilceler WHERE ilce_id = ?");
        $stmt->execute([$ilce_id]);
        $ilce_adi = $stmt->fetchColumn();
        $baslik .= " - " . $ilce_adi;
    }
}

// 4. SORGULAMA MANTIĞI (Stored Procedure Çağrısı)

// Varsayılan Değerler
$p_sehir = ($sehir_id > 0) ? $sehir_id : 0;
$p_ilce = ($ilce_id > 0) ? $ilce_id : 0;
$p_ozellik_ids = "";
$p_ozellik_count = 0;

// Özellikler seçildiyse formatla (Dizi -> String)
if (!empty($secilen_ozellikler)) {
    $p_ozellik_count = count($secilen_ozellikler);
    // Güvenlik için intval yapıp virgülle birleştiriyoruz: "1,3,5"
    $ozellik_ids_arr = array_map('intval', $secilen_ozellikler);
    $p_ozellik_ids = implode(',', $ozellik_ids_arr);
}

// Prosedürü Çağır
// CALL sp_TesisArama(sehir, ilce, '1,3,5', 3)
$stmt = $pdo->prepare("CALL sp_TesisArama(?, ?, ?, ?)");
$stmt->execute([$p_sehir, $p_ilce, $p_ozellik_ids, $p_ozellik_count]);
$tesisler = $stmt->fetchAll();
$stmt->closeCursor();
?>

<div class="container mb-5">
    <!-- Başlık Alanı -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h2 class="fw-bold text-primary border-start border-5 border-primary ps-3">
                <?php echo htmlspecialchars($baslik); ?>
            </h2>
            <p class="text-muted ms-3 mb-0">
                Toplam <strong><?php echo count($tesisler); ?></strong> tesis bulundu.
            </p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-search me-1"></i> Yeni Arama Yap
            </a>
        </div>
    </div>

    <!-- İçerik -->
    <div class="row">
        <!-- SOL KOLON: FİLTRELEME -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
                <div class="card-header bg-white fw-bold">
                    <i class="fas fa-filter me-2 text-primary"></i>Filtrele
                </div>
                <div class="card-body">
                    <form action="arama.php" method="GET">
                        <!-- Mevcut Şehir/İlçe bilgisini kaybetmemek için gizli inputlar -->
                        <input type="hidden" name="sehir_id" value="<?php echo $sehir_id; ?>">
                        <input type="hidden" name="ilce_id" value="<?php echo $ilce_id; ?>">

                        <div class="mb-3">
                            <label class="form-label small text-muted fw-bold mb-2">Özellikler</label>
                            
                            <?php foreach ($tumOzellikler as $ozellik): ?>
                                <?php 
                                    // Eğer bu özellik daha önce seçildiyse "checked" yap
                                    $checked = in_array($ozellik['ozellik_id'], $secilen_ozellikler) ? 'checked' : '';
                                ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="ozellikler[]" 
                                           value="<?php echo $ozellik['ozellik_id']; ?>" 
                                           id="oz_<?php echo $ozellik['ozellik_id']; ?>"
                                           <?php echo $checked; ?>>
                                    <label class="form-check-label" for="oz_<?php echo $ozellik['ozellik_id']; ?>">
                                        <i class="fas <?php echo $ozellik['ikon_kodu']; ?> text-muted me-1" style="width:20px;"></i>
                                        <?php echo $ozellik['ozellik_adi']; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-check me-1"></i>Uygula
                            </button>
                            <?php if(!empty($secilen_ozellikler)): ?>
                                <a href="arama.php?sehir_id=<?php echo $sehir_id; ?>&ilce_id=<?php echo $ilce_id; ?>" class="btn btn-outline-danger btn-sm">
                                    Temizle
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- SAĞ KOLON: TESİS KARTLARI -->
        <div class="col-lg-9">
            <?php if (count($tesisler) > 0): ?>
                <?php foreach ($tesisler as $tesis): ?>
                    <div class="card shadow-sm border-0 mb-4 overflow-hidden card-hover">
                        <div class="row g-0">
                            <div class="col-md-4">
                                <?php 
                                    $resim = !empty($tesis['kapak_resmi']) ? $tesis['kapak_resmi'] : 'https://images.unsplash.com/photo-1551958219-acbc608c6377?q=80&w=600&auto=format&fit=crop';
                                ?>
                                <img src="<?php echo $resim; ?>" class="img-fluid h-100 w-100" style="object-fit: cover; min-height: 200px;" alt="Tesis">
                            </div>
                            <div class="col-md-8">
                                <div class="card-body d-flex flex-column h-100">
                                    <div class="d-flex justify-content-between mb-2">
                                        <h5 class="card-title fw-bold mb-0">
                                            <a href="tesis_detay.php?id=<?php echo $tesis['tesis_id']; ?>" class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars($tesis['tesis_adi']); ?>
                                            </a>
                                        </h5>
                                        <span class="badge bg-success align-self-start fs-6">
                                            <?php echo $tesis['baslangic_fiyat'] ? number_format($tesis['baslangic_fiyat'],0) . " ₺" : "Fiyat Sorunuz"; ?>
                                        </span>
                                    </div>
                                    
                                    <p class="card-text text-muted small mb-2">
                                        <i class="fas fa-map-marker-alt me-1 text-danger"></i> 
                                        <?php echo $tesis['ilce_adi'] . ', ' . $tesis['sehir_adi']; ?>
                                    </p>
                                    
                                    <p class="card-text flex-grow-1 text-muted">
                                        <?php echo mb_substr($tesis['aciklama'], 0, 100) . '...'; ?>
                                    </p>
                                    
                                    <div class="text-end">
                                        <a href="tesis_detay.php?id=<?php echo $tesis['tesis_id']; ?>" class="btn btn-primary">
                                            Randevu Al <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-warning text-center py-5 shadow-sm">
                    <div class="mb-3">
                        <i class="fas fa-filter fa-3x text-warning opacity-50"></i>
                    </div>
                    <h4>Sonuç Bulunamadı</h4>
                    <p class="mb-3">Seçtiğiniz filtrelere uygun bir tesis maalesef yok.</p>
                    <a href="arama.php?sehir_id=<?php echo $sehir_id; ?>&ilce_id=<?php echo $ilce_id; ?>" class="btn btn-outline-dark btn-sm">
                        Filtreleri Temizle
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>