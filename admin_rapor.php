<?php
require_once 'includes/db.php';
include 'includes/header.php';

if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] != 'admin') {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

// Rapor Verisini Çek
$stmt = $pdo->prepare("CALL sp_AdminRapor()");
$stmt->execute();
$raporlar = $stmt->fetchAll();
$stmt->closeCursor();
?>

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fas fa-chart-line me-2"></i>Sistem Raporları</h2>
        <a href="admin_panel.php" class="btn btn-outline-secondary">Geri Dön</a>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Bu rapor, şehir bazında tesislerin puanlarını ve toplam rezervasyon sayılarını (doluluk performansı) listeler.
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Şehir</th>
                        <th>Tesis Adı</th>
                        <th>Puan</th>
                        <th>Toplam Rezervasyon</th>
                        <th>Performans</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($raporlar as $r): ?>
                        <tr>
                            <td class="fw-bold"><?php echo $r['sehir_adi']; ?></td>
                            <td><?php echo $r['tesis_adi']; ?></td>
                            <td>
                                <span class="badge bg-warning text-dark">
                                    <?php echo number_format($r['ortalama_puan'], 1); ?> <i class="fas fa-star"></i>
                                </span>
                            </td>
                            <td><?php echo $r['toplam_rezervasyon']; ?> Adet</td>
                            <td>
                                <!-- Basit bir Progress Bar -->
                                <?php 
                                    // 50 rezervasyon üstü %100 kabul edelim (simülasyon)
                                    $yuzde = min(100, ($r['toplam_rezervasyon'] * 2)); 
                                    $renk = ($yuzde > 50) ? 'success' : 'primary';
                                ?>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-<?php echo $renk; ?>" style="width: <?php echo $yuzde; ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>