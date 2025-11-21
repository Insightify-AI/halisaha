<?php
require_once 'includes/db.php';
include 'includes/header.php';

if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] != 'admin') {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

try {
    // 1. Mevcut Rapor Verisi (Tablo için)
    $stmt = $pdo->prepare("CALL sp_AdminRapor()");
    $stmt->execute();
    $raporlar = $stmt->fetchAll();
    $stmt->closeCursor();

    // 2. Grafik Verileri
    // Şehir Dağılımı
    $stmt = $pdo->prepare("CALL sp_AdminGrafikSehir()");
    $stmt->execute();
    $sehirVerileri = $stmt->fetchAll();
    $stmt->closeCursor();

    // Aylık Trend
    $stmt = $pdo->prepare("CALL sp_AdminGrafikAylik()");
    $stmt->execute();
    $aylikVeriler = $stmt->fetchAll();
    $stmt->closeCursor();
} catch (PDOException $e) {
    // Eğer prosedür yok hatası ise (1305)
    if (strpos($e->getMessage(), '1305') !== false) {
        echo "<div class='container mt-5'>
                <div class='alert alert-warning shadow'>
                    <h4 class='alert-heading'><i class='fas fa-exclamation-triangle'></i> Veritabanı Güncellemesi Gerekli</h4>
                    <p>Raporları görüntülemek için gerekli olan yeni özellikler veritabanınızda eksik.</p>
                    <hr>
                    <p class='mb-0'>Lütfen aşağıdaki butona tıklayarak veritabanını güncelleyin:</p>
                    <a href='update_db.php' class='btn btn-warning mt-3'>Veritabanını Güncelle</a>
                </div>
              </div>";
        include 'includes/footer.php';
        exit;
    } else {
        // Başka bir hataysa normal göster
        die("Veritabanı Hatası: " . $e->getMessage());
    }
}

// PHP Dizilerini JSON'a çevir (JS için)
$sehirLabels = [];
$sehirData = [];
foreach ($sehirVerileri as $s) {
    $sehirLabels[] = $s['sehir_adi'];
    $sehirData[] = $s['toplam'];
}

$ayLabels = ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];
$ayData = array_fill(0, 12, 0); // 12 aylık boş dizi
foreach ($aylikVeriler as $a) {
    // Ay indeksi 1-12 arası gelir, dizide 0-11 arası kullanacağız
    $ayData[$a['ay'] - 1] = $a['toplam'];
}
?>

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fas fa-chart-line me-2"></i>Sistem Raporları</h2>
        <a href="admin_panel.php" class="btn btn-outline-secondary">Geri Dön</a>
    </div>

    <!-- GRAFİKLER -->
    <div class="row mb-4">
        <!-- Pasta Grafik: Şehir Dağılımı -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Şehir Bazlı Rezervasyon Dağılımı</div>
                <div class="card-body">
                    <canvas id="sehirChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Sütun Grafik: Aylık Trend -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Bu Yılın Aylık Rezervasyonları</div>
                <div class="card-body">
                    <canvas id="aylikChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Aşağıdaki tablo, şehir bazında tesislerin puanlarını ve doluluk performansını detaylandırır.
    </div>

    <!-- TABLO -->
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table class="table table-striped table-hover align-middle">
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
                                <?php 
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

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Şehir Grafiği
const ctxSehir = document.getElementById('sehirChart').getContext('2d');
new Chart(ctxSehir, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($sehirLabels); ?>,
        datasets: [{
            data: <?php echo json_encode($sehirData); ?>,
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Aylık Grafik
const ctxAylik = document.getElementById('aylikChart').getContext('2d');
new Chart(ctxAylik, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($ayLabels); ?>,
        datasets: [{
            label: 'Rezervasyon Sayısı',
            data: <?php echo json_encode($ayData); ?>,
            backgroundColor: '#36A2EB',
            borderColor: '#2485C6',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>