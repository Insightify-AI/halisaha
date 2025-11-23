<?php
require_once 'includes/db.php';
include 'includes/header.php';

// 1. GÜVENLİK
if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] != 'tesis_sahibi') {
    echo "<script>window.location.href = 'index.php';</script>";
    exit;
}

$sahip_id = $_SESSION['rol_id'];

// 2. İŞLEM: Rezervasyon Onaylama/Reddetme
$mesaj = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rez_id'])) {
    $rez_id = $_POST['rez_id'];
    $islem = $_POST['islem'];
    
    if ($islem == 'onayla') {
        try {
            // Stored Procedure ile otomatik ödeme dağıtımı
            $stmt = $pdo->prepare("CALL sp_RezervasyonOnayla(?, ?)");
            $stmt->execute([$rez_id, $sahip_id]);
            $result = $stmt->fetch();
            $stmt->closeCursor();
            
            if ($result && $result['status'] == 'SUCCESS') {
                $kazanc = number_format($result['kazanc'], 2);
                $mesaj = "<div class='alert alert-success'>
                    <i class='fas fa-check-circle me-2'></i>
                    Rezervasyon onaylandı! Hesabınıza <strong>{$kazanc}₺</strong> eklendi. 
                    <small class='d-block mt-1'>(%5 sistem komisyonu düşüldü)</small>
                </div>";
            }
        } catch (PDOException $e) {
            $mesaj = "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
        }
    } else {
        // REDDETME İŞLEMİ - Para iadesi ile birlikte
        try {
            $stmt = $pdo->prepare("CALL sp_RezervasyonReddetVeIade(?)");
            $stmt->execute([$rez_id]);
            $result = $stmt->fetch();
            $stmt->closeCursor();
            
            if ($result && $result['status'] == 'SUCCESS') {
                $iade_tutari = number_format($result['iade_tutari'], 2);
                $mesaj = "<div class='alert alert-warning'>
                    <i class='fas fa-times-circle me-2'></i>
                    Rezervasyon reddedildi ve müşteriye <strong>{$iade_tutari}₺</strong> iade yapıldı.
                </div>";
            }
        } catch (PDOException $e) {
            $mesaj = "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
        }
    }
}

// 3. VERİLERİ ÇEK
// A) Sahibin Tesisleri
$stmt = $pdo->prepare("CALL sp_SahipTesisleriGetir(?)");
$stmt->execute([$sahip_id]);
$tesislerim = $stmt->fetchAll();
$stmt->closeCursor();

// B) Gelen Rezervasyonlar
$stmt = $pdo->prepare("CALL sp_SahipGelenRezervasyonlar(?)");
$stmt->execute([$sahip_id]);
$gelen_rezervasyonlar = $stmt->fetchAll();
$stmt->closeCursor();
?>

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-4">
        <h2 class="fw-bold text-primary mb-0"><i class="fas fa-briefcase me-2"></i>Tesis Yönetim Paneli</h2>
        <a href="tesis_ekle.php" class="btn btn-success"><i class="fas fa-plus me-1"></i> Yeni Tesis Ekle</a>
    </div>
    
    <?php echo $mesaj; ?>

    <!-- BÖLÜM 1: TESİSLERİM LİSTESİ -->
    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-building me-2"></i>Tesislerim</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Görsel</th>
                        <th>Tesis Adı</th>
                        <th>Konum</th>
                        <th>Durum</th>
                        <th class="text-end">Yönetim</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($tesislerim) > 0): ?>
                        <?php foreach ($tesislerim as $t): ?>
                            <tr>
                                <td width="80">
                                    <img src="<?php echo $t['kapak_resmi']; ?>" class="rounded" width="60" height="40" style="object-fit: cover;">
                                </td>
                                <td class="fw-bold"><?php echo $t['tesis_adi']; ?></td>
                                <td><?php echo $t['adres']; ?></td>
                                <td>
                                    <?php if($t['onay_durumu'] == 1): ?>
                                        <span class="badge bg-success">Yayında</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Onay Bekliyor</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <!-- SAHA EKLE BUTONU -->
                                    <a href="saha_ekle.php?tesis_id=<?php echo $t['tesis_id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-plus-circle me-1"></i> Saha Ekle
                                    </a>
                                    <a href="tesis_detay.php?id=<?php echo $t['tesis_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-3">Henüz tesis eklemediniz.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- BÖLÜM 2: GELEN REZERVASYONLAR -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-inbox me-2"></i>Gelen Rezervasyon Talepleri</h5>
            <span class="badge bg-light text-dark"><?php echo count($gelen_rezervasyonlar); ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tarih</th>
                            <th>Tesis / Saha</th>
                            <th>Müşteri</th>
                            <th>Tutar</th>
                            <th>Durum</th>
                            <th class="text-end">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($gelen_rezervasyonlar) > 0): ?>
                            <?php foreach ($gelen_rezervasyonlar as $rez): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date("d.m.Y", strtotime($rez['tarih'])); ?></strong><br>
                                        <small><?php echo substr($rez['baslangic_saati'],0,5); ?></small>
                                    </td>
                                    <td>
                                        <?php echo $rez['tesis_adi']; ?><br>
                                        <small class="text-muted"><?php echo $rez['saha_adi']; ?></small>
                                    </td>
                                    <td><?php echo $rez['musteri_ad'] . ' ' . $rez['musteri_soyad']; ?></td>
                                    <td class="text-success fw-bold"><?php echo number_format($rez['tutar'],0); ?> ₺</td>
                                    <td>
                                        <?php if($rez['durum'] == 'onay_bekliyor'): ?>
                                            <span class="badge bg-warning text-dark">Bekliyor</span>
                                        <?php elseif($rez['durum'] == 'onaylandi'): ?>
                                            <span class="badge bg-orange">Onaylı</span>
                                        <?php elseif($rez['durum'] == 'tamamlandi'): ?>
                                            <span class="badge bg-success">Tamamlandı</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><?php echo $rez['durum']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if($rez['durum'] == 'onay_bekliyor'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="rez_id" value="<?php echo $rez['rezervasyon_id']; ?>">
                                                <input type="hidden" name="islem" value="onayla">
                                                <button class="btn btn-success btn-sm"><i class="fas fa-check"></i></button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="rez_id" value="<?php echo $rez['rezervasyon_id']; ?>">
                                                <input type="hidden" name="islem" value="reddet">
                                                <button class="btn btn-danger btn-sm"><i class="fas fa-times"></i></button>
                                            </form>
                                        <?php else: ?>
                                            <small class="text-muted">Tamamlandı</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-3">Talep yok.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>