<?php
require_once 'includes/db.php';
include 'includes/header.php';

// GÜVENLİK
if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] != 'admin') {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

// ID KONTROL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>window.location.href='admin_tesisler.php';</script>";
    exit;
}
$tesis_id = (int)$_GET['id'];

// İŞLEM (Onay/Red)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $durum = ($_POST['islem'] == 'onayla') ? 1 : 2; 
    $stmt = $pdo->prepare("CALL sp_AdminTesisDurumGuncelle(?, ?)");
    $stmt->execute([$tesis_id, $durum]);
    
    $mesaj = ($_POST['islem'] == 'onayla') ? "Tesis onaylandı." : "Tesis reddedildi.";
    echo "<script>alert('$mesaj'); window.location.href='admin_tesisler.php';</script>";
    exit;
}

// VERİLERİ ÇEK
// 1. Temel Bilgiler
$stmt = $pdo->prepare("CALL sp_TesisDetayGetir(?)");
$stmt->execute([$tesis_id]);
$tesis = $stmt->fetch();
$stmt->closeCursor();

if (!$tesis) {
    echo "Tesis bulunamadı.";
    exit;
}

// 2. Özellikler
$stmt = $pdo->prepare("CALL sp_TesisOzellikleriGetir(?)");
$stmt->execute([$tesis_id]);
$ozellikler = $stmt->fetchAll();
$stmt->closeCursor();
?>

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fas fa-info-circle me-2"></i>Tesis Detay İnceleme</h2>
        <a href="admin_tesisler.php" class="btn btn-outline-secondary">Listeye Dön</a>
    </div>

    <div class="row">
        <!-- SOL: Resim ve Bilgiler -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0 mb-4">
                <?php $resim = !empty($tesis['kapak_resmi']) ? $tesis['kapak_resmi'] : 'https://via.placeholder.com/800x400'; ?>
                <img src="<?php echo $resim; ?>" class="card-img-top" style="height: 400px; object-fit: cover;">
                <div class="card-body">
                    <h3 class="fw-bold text-primary"><?php echo $tesis['tesis_adi']; ?></h3>
                    <p class="text-muted">
                        <i class="fas fa-map-marker-alt me-1"></i> <?php echo $tesis['ilce_adi'] . ' / ' . $tesis['sehir_adi']; ?>
                    </p>
                    <hr>
                    <h5 class="fw-bold">Hakkında</h5>
                    <p><?php echo nl2br($tesis['aciklama']); ?></p>
                    
                    <h5 class="fw-bold mt-4">İletişim & Adres</h5>
                    <p><strong>Telefon:</strong> <?php echo $tesis['telefon']; ?></p>
                    <p><strong>Adres:</strong> <?php echo $tesis['adres']; ?></p>
                </div>
            </div>

            <!-- Özellikler -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-bold">Tesis İmkanları</div>
                <div class="card-body">
                    <div class="row row-cols-2 row-cols-md-4 g-3">
                        <?php foreach ($ozellikler as $oz): ?>
                            <div class="col">
                                <div class="d-flex align-items-center">
                                    <i class="fas <?php echo $oz['ikon_kodu']; ?> text-primary me-2 fa-lg"></i>
                                    <span><?php echo $oz['ozellik_adi']; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- SAĞ: İşlem Paneli -->
        <div class="col-md-4">
            <div class="card shadow border-0 sticky-top" style="top: 20px;">
                <div class="card-header bg-warning text-dark fw-bold">
                    <i class="fas fa-cog me-2"></i>Yönetici İşlemleri
                </div>
                <div class="card-body">
                    <p><strong>Tesis Sahibi:</strong> <?php echo $tesis['ad'] . ' ' . $tesis['soyad']; ?></p>
                    <p><strong>Sahip Tel:</strong> <?php echo $tesis['sahip_telefon']; ?></p>
                    <p><strong>Şu Anki Durum:</strong> 
                        <?php if($tesis['onay_durumu'] == 0): ?>
                            <span class="badge bg-warning text-dark">Onay Bekliyor</span>
                        <?php elseif($tesis['onay_durumu'] == 1): ?>
                            <span class="badge bg-success">Yayında</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Reddedildi</span>
                        <?php endif; ?>
                    </p>

                    <hr>

                    <div class="d-grid gap-2">
                        <form method="POST">
                            <input type="hidden" name="islem" value="onayla">
                            <button class="btn btn-success w-100 mb-2">
                                <i class="fas fa-check-circle me-2"></i>ONAYLA VE YAYINLA
                            </button>
                        </form>
                        
                        <form method="POST" onsubmit="return confirm('Reddetmek istediğine emin misin?');">
                            <input type="hidden" name="islem" value="reddet">
                            <button class="btn btn-outline-danger w-100">
                                <i class="fas fa-times-circle me-2"></i>REDDET
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
