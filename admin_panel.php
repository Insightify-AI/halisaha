<?php
require_once 'includes/db.php';
include 'includes/header.php';

// GÜVENLİK: Sadece Admin Girebilir
if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] != 'admin') {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

// İstatistikleri Çek
$stmt = $pdo->prepare("CALL sp_AdminIstatistik()");
$stmt->execute();
$istatistik = $stmt->fetch();
$stmt->closeCursor();

// Son Hareketleri Çek
$stmt = $pdo->prepare("CALL sp_AdminSonHareketler()");
$stmt->execute();
$sonHareketler = $stmt->fetchAll();
$stmt->closeCursor();
?>

<?php
// KASA DURUMUNU ÇEK
$stmt = $pdo->prepare("CALL sp_AdminKasaDurumu()");
$stmt->execute();
$kasa = $stmt->fetch();
$stmt->closeCursor();
?>

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark"><i class="fas fa-tachometer-alt me-2"></i>Yönetim Paneli</h2>
        <div class="text-muted">
            <i class="far fa-clock me-1"></i> <?php echo date("d.m.Y H:i"); ?>
        </div>
    </div>

    <!-- SİSTEM KASASI (FİNANSAL DURUM) -->
    <div class="card shadow-sm border-0 mb-4 bg-dark text-white overflow-hidden">
        <div class="card-header bg-transparent border-bottom border-secondary d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-vault me-2 text-warning"></i>Sistem Kasası (Süper Admin)</h5>
            <span class="badge bg-warning text-dark">Komisyon Oranı: %5</span>
        </div>
        <div class="card-body position-relative">
            <!-- Arkaplan Dekoru -->
            <i class="fas fa-coins position-absolute end-0 bottom-0 opacity-25" style="font-size: 10rem; margin-right: -20px; margin-bottom: -20px; color: #ffd700;"></i>
            
            <div class="row g-4 position-relative" style="z-index: 1;">
                <!-- 1. ANLIK KASA -->
                <div class="col-md-4">
                    <div class="p-3 rounded border border-secondary bg-gradient" style="background-color: rgba(255,255,255,0.1);">
                        <div class="text-secondary small mb-1">GÜNCEL BAKİYE</div>
                        <h2 class="fw-bold text-warning mb-0">₺<?php echo number_format($kasa['guncel_kasa'], 2); ?></h2>
                        <small class="text-light opacity-75"><i class="fas fa-wallet me-1"></i>Hesap: Kemal Serdaroğlu</small>
                    </div>
                </div>
                
                <!-- 2. BU AYKİ KAZANÇ -->
                <div class="col-md-4">
                    <div class="p-3 rounded border border-secondary bg-gradient" style="background-color: rgba(255,255,255,0.05);">
                        <div class="text-secondary small mb-1">BU AYKİ KOMİSYON</div>
                        <h3 class="fw-bold text-success mb-0">+₺<?php echo number_format($kasa['bu_ay_komisyon'], 2); ?></h3>
                        <small class="text-light opacity-75">Son 30 Gün</small>
                    </div>
                </div>

                <!-- 3. TOPLAM KAZANÇ -->
                <div class="col-md-4">
                    <div class="p-3 rounded border border-secondary bg-gradient" style="background-color: rgba(255,255,255,0.05);">
                        <div class="text-secondary small mb-1">TOPLAM KOMİSYON GELİRİ</div>
                        <h3 class="fw-bold text-info mb-0">₺<?php echo number_format($kasa['toplam_komisyon'], 2); ?></h3>
                        <small class="text-light opacity-75">Tüm Zamanlar</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- İstatistik Kartları -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card bg-primary text-white shadow-sm h-100 border-0">
                <div class="card-body text-center">
                    <h1><i class="fas fa-users"></i></h1>
                    <h5 class="card-title">Toplam Kullanıcı</h5>
                    <p class="display-6 fw-bold"><?php echo $istatistik['toplam_kullanici']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white shadow-sm h-100 border-0">
                <div class="card-body text-center">
                    <h1><i class="fas fa-futbol"></i></h1>
                    <h5 class="card-title">Toplam Tesis</h5>
                    <p class="display-6 fw-bold"><?php echo $istatistik['toplam_tesis']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark shadow-sm h-100 border-0">
                <div class="card-body text-center">
                    <h1><i class="fas fa-clock"></i></h1>
                    <h5 class="card-title">Onay Bekleyen</h5>
                    <p class="display-6 fw-bold"><?php echo $istatistik['bekleyen_tesis']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white shadow-sm h-100 border-0">
                <div class="card-body text-center">
                    <h1><i class="fas fa-lira-sign"></i></h1>
                    <h5 class="card-title">Tahmini Ciro</h5>
                    <p class="display-6 fw-bold"><?php echo number_format($istatistik['toplam_ciro'], 0); ?> ₺</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Hızlı Menü -->
    <div class="row mb-5">
        <div class="col-md-3">
            <a href="admin_tesisler.php" class="text-decoration-none">
                <div class="card shadow-sm border-0 hover-effect p-4 h-100">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning p-3 rounded-circle text-dark me-3">
                            <i class="fas fa-check-double fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Tesis Onayı</h5>
                            <p class="text-muted small mb-0">Bekleyen başvurular.</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="admin_rezervasyonlar.php" class="text-decoration-none">
                <div class="card shadow-sm border-0 hover-effect p-4 h-100">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary p-3 rounded-circle text-white me-3">
                            <i class="fas fa-calendar-alt fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Rezervasyonlar</h5>
                            <p class="text-muted small mb-0">Tüm maçları yönet.</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="admin_kullanicilar.php" class="text-decoration-none">
                <div class="card shadow-sm border-0 hover-effect p-4 h-100">
                    <div class="d-flex align-items-center">
                        <div class="bg-info p-3 rounded-circle text-white me-3">
                            <i class="fas fa-users-cog fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Kullanıcılar</h5>
                            <p class="text-muted small mb-0">Üyeleri listele/sil.</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="admin_rapor.php" class="text-decoration-none">
                <div class="card shadow-sm border-0 hover-effect p-4 h-100">
                    <div class="d-flex align-items-center">
                        <div class="bg-danger p-3 rounded-circle text-white me-3">
                            <i class="fas fa-chart-bar fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Raporlar</h5>
                            <p class="text-muted small mb-0">Sistem analizleri.</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    </div>

    <!-- GRAFİKLER VE DETAYLI İSTATİSTİKLER -->
    <div class="row g-4 mb-4">
        <!-- Aylık Gelir Grafiği -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="fas fa-chart-line me-2 text-primary"></i>Aylık Gelir Trendi (Son 6 Ay)
                </div>
                <div class="card-body">
                    <canvas id="gelirGrafik" height="80"></canvas>
                </div>
            </div>
        </div>

        <!-- Rezervasyon Durumu Pasta Grafik -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="fas fa-chart-pie me-2 text-success"></i>Rezervasyon Durumu
                </div>
                <div class="card-body">
                    <canvas id="durumGrafik"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Şehir Bazlı İstatistikler ve Popüler Tesisler -->
    <div class="row g-4 mb-4">
        <!-- Şehir Bazlı Rezervasyonlar -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-map-marker-alt me-2 text-danger"></i>Şehir Bazlı Rezervasyonlar</span>
                    <span class="badge bg-primary">Top 5</span>
                </div>
                <div class="card-body">
                    <?php
                    $stmtSehir = $pdo->query("
                        SELECT se.sehir_adi as sehir, COUNT(r.rezervasyon_id) as toplam
                        FROM Rezervasyonlar r
                        JOIN Sahalar s ON r.saha_id = s.saha_id
                        JOIN Tesisler t ON s.tesis_id = t.tesis_id
                        JOIN Ilceler i ON t.ilce_id = i.ilce_id
                        JOIN Sehirler se ON i.sehir_id = se.sehir_id
                        GROUP BY se.sehir_adi
                        ORDER BY toplam DESC
                        LIMIT 5
                    ");
                    $sehirler = $stmtSehir->fetchAll();
                    $maxSehir = $sehirler[0]['toplam'] ?? 1;
                    ?>
                    <?php foreach ($sehirler as $index => $sehir): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-bold"><?php echo $index + 1; ?>. <?php echo $sehir['sehir']; ?></span>
                                <span class="badge bg-primary"><?php echo $sehir['toplam']; ?> rezervasyon</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-gradient" 
                                     style="width: <?php echo ($sehir['toplam'] / $maxSehir) * 100; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Popüler Tesisler -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-fire me-2 text-warning"></i>En Popüler Tesisler</span>
                    <span class="badge bg-success">Top 5</span>
                </div>
                <div class="card-body">
                    <?php
                    $stmtPopuler = $pdo->query("
                        SELECT t.tesis_adi, se.sehir_adi as sehir, COUNT(r.rezervasyon_id) as rez_sayisi
                        FROM Tesisler t
                        JOIN Ilceler i ON t.ilce_id = i.ilce_id
                        JOIN Sehirler se ON i.sehir_id = se.sehir_id
                        LEFT JOIN Sahalar s ON t.tesis_id = s.tesis_id
                        LEFT JOIN Rezervasyonlar r ON s.saha_id = r.saha_id
                        WHERE r.durum != 'iptal'
                        GROUP BY t.tesis_id
                        ORDER BY rez_sayisi DESC
                        LIMIT 5
                    ");
                    $populerTesisler = $stmtPopuler->fetchAll();
                    ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($populerTesisler as $index => $tesis): ?>
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold">
                                        <span class="badge bg-warning text-dark me-2">#<?php echo $index + 1; ?></span>
                                        <?php echo $tesis['tesis_adi']; ?>
                                    </div>
                                    <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i><?php echo $tesis['sehir']; ?></small>
                                </div>
                                <span class="badge bg-success"><?php echo $tesis['rez_sayisi']; ?> maç</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Son Hareketler ve Kul lanıcı Aktiviteleri -->
    <div class="row g-4 mb-4">
        <!-- Son Hareketler (Genişletilmiş) -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-history me-2 text-secondary"></i>Son Rezervasyon Hareketleri</span>
                    <a href="admin_rezervasyonlar.php" class="btn btn-sm btn-outline-primary">Tümünü Gör</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Tarih & Saat</th>
                                    <th>Müşteri</th>
                                    <th>Tesis</th>
                                    <th>Tutar</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($sonHareketler) > 0): ?>
                                    <?php foreach (array_slice($sonHareketler, 0, 10) as $h): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo date("d.m.Y", strtotime($h['tarih'])); ?></div>
                                                <small class="text-muted"><?php echo date("H:i", strtotime($h['tarih'])); ?></small>
                                            </td>
                                            <td><?php echo $h['musteri_ad'] . ' ' . $h['musteri_soyad']; ?></td>
                                            <td><?php echo $h['tesis_adi']; ?></td>
                                            <td>
                                                <?php
                                                $stmtTutar = $pdo->prepare("SELECT tutar FROM Odemeler WHERE rezervasyon_id = ?");
                                                $stmtTutar->execute([$h['rezervasyon_id']]);
                                                $tutar = $stmtTutar->fetchColumn();
                                                echo $tutar ? number_format($tutar, 0) . '₺' : '-';
                                                ?>
                                            </td>
                                            <td>
                                                <?php if($h['durum'] == 'onay_bekliyor'): ?>
                                                    <span class="badge bg-warning text-dark">Bekliyor</span>
                                                <?php elseif($h['durum'] == 'onaylandi'): ?>
                                                    <span class="badge bg-orange">Onaylandı</span>
                                                <?php elseif($h['durum'] == 'tamamlandi'): ?>
                                                    <span class="badge bg-success">Tamamlandı</span>
                                                <?php elseif($h['durum'] == 'iptal'): ?>
                                                    <span class="badge bg-danger">İptal</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?php echo $h['durum']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center text-muted py-3">Henüz hareket yok.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kullanıcı Aktiviteleri -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="fas fa-users me-2 text-info"></i>Kullanıcı Aktiviteleri
                </div>
                <div class="card-body">
                    <?php
                    $stmtAktivite = $pdo->query("
                        SELECT 
                            (SELECT COUNT(*) FROM Kullanicilar WHERE rol = 'musteri') as toplam_musteri,
                            (SELECT COUNT(*) FROM TesisSahipleri) as toplam_sahip,
                            (SELECT COUNT(DISTINCT musteri_id) FROM Rezervasyonlar WHERE MONTH(tarih) = MONTH(NOW())) as aktif_musteri,
                            (SELECT COUNT(*) FROM Kullanicilar WHERE DATE(kayit_tarihi) > DATE_SUB(NOW(), INTERVAL 7 DAY)) as yeni_kayit
                    ");
                    $aktivite = $stmtAktivite->fetch();
                    ?>
                    <div class="mb-3 p-3 bg-light rounded">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Toplam Müşteri</span>
                            <span class="fs-4 fw-bold text-primary"><?php echo $aktivite['toplam_musteri']; ?></span>
                        </div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar bg-primary" style="width: 100%"></div>
                        </div>
                    </div>

                    <div class="mb-3 p-3 bg-light rounded">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Tesis Sahipleri</span>
                            <span class="fs-4 fw-bold text-success"><?php echo $aktivite['toplam_sahip']; ?></span>
                        </div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar bg-success" style="width: 100%"></div>
                        </div>
                    </div>

                    <div class="mb-3 p-3 bg-light rounded">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Bu Ay Aktif</span>
                            <span class="fs-4 fw-bold text-warning"><?php echo $aktivite['aktif_musteri']; ?></span>
                        </div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar bg-warning" style="width: <?php echo min(($aktivite['aktif_musteri'] / $aktivite['toplam_musteri']) * 100, 100); ?>%"></div>
                        </div>
                    </div>

                    <div class="p-3 bg-success bg-opacity-10 rounded border border-success">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-success fw-bold"><i class="fas fa-user-plus me-2"></i>Son 7 Gün</span>
                            <span class="fs-4 fw-bold text-success">+<?php echo $aktivite['yeni_kayit']; ?></span>
                        </div>
                        <small class="text-muted">Yeni kayıt</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Aylık Gelir Grafiği
<?php
$stmtGelir = $pdo->query("
    SELECT 
        DATE_FORMAT(tarih, '%Y-%m') as ay,
        SUM(tutar) as toplam
    FROM Odemeler o
    JOIN Rezervasyonlar r ON o.rezervasyon_id = r.rezervasyon_id
    WHERE o.durum = 'basarili'
    AND tarih >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY ay
    ORDER BY ay
");
$gelirData = $stmtGelir->fetchAll();
$aylar = array_map(function($g) { 
    return date('M Y', strtotime($g['ay'] . '-01')); 
}, $gelirData);
$gelirler = array_map(function($g) { return $g['toplam']; }, $gelirData);
?>

const gelirCtx = document.getElementById('gelirGrafik').getContext('2d');
new Chart(gelirCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($aylar); ?>,
        datasets: [{
            label: 'Gelir (₺)',
            data: <?php echo json_encode($gelirler); ?>,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: true }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Rezervasyon Durumu Pasta Grafiği
<?php
$stmtDurum = $pdo->query("
    SELECT durum, COUNT(*) as sayi
    FROM Rezervasyonlar
    GROUP BY durum
");
$durumData = $stmtDurum->fetchAll();
$durumlar = array_map(function($d) { return ucfirst($d['durum']); }, $durumData);
$durumSayilari = array_map(function($d) { return $d['sayi']; }, $durumData);
?>

const durumCtx = document.getElementById('durumGrafik').getContext('2d');
new Chart(durumCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($durumlar); ?>,
        datasets: [{
            data: <?php echo json_encode($durumSayilari); ?>,
            backgroundColor: [
                'rgba(255, 206, 86, 0.8)',
                'rgba(255, 159, 64, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(255, 99, 132, 0.8)',
                'rgba(153, 102, 255, 0.8)'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>

<style>
.hover-effect:hover { transform: translateY(-5px); transition: 0.3s; }
</style>

<?php include 'includes/footer.php'; ?>