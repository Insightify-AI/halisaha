<?php
require_once 'includes/db.php';
include 'includes/header.php';

// GÜVENLİK
if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] != 'admin') {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

// İşlem (Onay/Red)
$mesaj = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tesis_id = $_POST['tesis_id'];
    $durum = ($_POST['islem'] == 'onayla') ? 1 : 2; // 1: Onaylı, 2: Reddedildi (Mantıken silinebilir de)
    
    // Eğer red ise direkt silebiliriz veya durumunu pasif yapabiliriz.
    // Biz durum güncelleyelim.
    $stmt = $pdo->prepare("CALL sp_AdminTesisDurumGuncelle(?, ?)");
    $stmt->execute([$tesis_id, $durum]);
    $mesaj = "<div class='alert alert-success'>Tesis durumu güncellendi.</div>";
}

// Bekleyenleri Getir
$stmt = $pdo->prepare("CALL sp_AdminBekleyenTesisler()");
$stmt->execute();
$tesisler = $stmt->fetchAll();
$stmt->closeCursor();
?>

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fas fa-clipboard-check me-2"></i>Tesis Başvuruları</h2>
        <a href="admin_panel.php" class="btn btn-outline-secondary">Geri Dön</a>
    </div>

    <?php echo $mesaj; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tesis Adı</th>
                        <th>Konum</th>
                        <th>Tesis Sahibi</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($tesisler) > 0): ?>
                        <?php foreach ($tesisler as $t): ?>
                            <tr>
                                <td>
                                    <span class="fw-bold"><?php echo $t['tesis_adi']; ?></span><br>
                                    <small class="text-muted"><?php echo $t['telefon']; ?></small>
                                </td>
                                <td>
                                    <?php echo $t['ilce_adi'] . ' / ' . $t['sehir_adi']; ?>
                                </td>
                                <td>
                                    <?php echo $t['ad'] . ' ' . $t['soyad']; ?><br>
                                    <small><?php echo $t['sahip_telefon']; ?></small>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <form method="POST">
                                            <input type="hidden" name="tesis_id" value="<?php echo $t['tesis_id']; ?>">
                                            <input type="hidden" name="islem" value="onayla">
                                            <button class="btn btn-success btn-sm">
                                                <i class="fas fa-check me-1"></i> Onayla
                                            </button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('Reddetmek istediğine emin misin?');">
                                            <input type="hidden" name="tesis_id" value="<?php echo $t['tesis_id']; ?>">
                                            <input type="hidden" name="islem" value="reddet">
                                            <button class="btn btn-danger btn-sm">
                                                <i class="fas fa-times me-1"></i> Reddet
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-4">Bekleyen başvuru yok.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>