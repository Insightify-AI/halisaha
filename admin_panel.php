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

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-4">
        <h2 class="fw-bold text-danger"><i class="fas fa-user-shield me-2"></i>Yönetim Paneli</h2>
        <span class="text-muted">Hoşgeldin, Yönetici</span>
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

    <!-- Son Hareketler -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold py-3">
            <i class="fas fa-history me-2 text-secondary"></i>Son Rezervasyon Hareketleri
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tarih</th>
                        <th>Müşteri</th>
                        <th>Tesis</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($sonHareketler) > 0): ?>
                        <?php foreach ($sonHareketler as $h): ?>
                            <tr>
                                <td><?php echo date("d.m.Y H:i", strtotime($h['tarih'])); ?></td>
                                <td><?php echo $h['musteri_ad'] . ' ' . $h['musteri_soyad']; ?></td>
                                <td><?php echo $h['tesis_adi']; ?></td>
                                <td>
                                    <?php if($h['durum'] == 'onay_bekliyor'): ?>
                                        <span class="badge bg-warning text-dark">Bekliyor</span>
                                    <?php elseif($h['durum'] == 'onaylandi'): ?>
                                        <span class="badge bg-success">Onaylandı</span>
                                    <?php elseif($h['durum'] == 'iptal'): ?>
                                        <span class="badge bg-danger">İptal</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo $h['durum']; ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Henüz hareket yok.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.hover-effect:hover { transform: translateY(-5px); transition: 0.3s; }
</style>

<?php include 'includes/footer.php'; ?>