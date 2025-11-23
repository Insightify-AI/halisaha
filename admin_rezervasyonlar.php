<?php
require_once 'includes/db.php';
include 'includes/header.php';

// GÜVENLİK: Sadece Admin Girebilir
if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] != 'admin') {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

// İŞLEM: Durum Güncelleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['islem']) && $_POST['islem'] == 'durum_guncelle') {
    $rez_id = $_POST['rezervasyon_id'];
    $yeni_durum = $_POST['yeni_durum'];
    
    try {
        $stmt = $pdo->prepare("CALL sp_AdminRezervasyonDurumGuncelle(?, ?)");
        $stmt->execute([$rez_id, $yeni_durum]);
        $mesaj = "Rezervasyon durumu başarıyla güncellendi.";
        $mesaj_tur = "success";
    } catch (PDOException $e) {
        $mesaj = "Hata: " . $e->getMessage();
        $mesaj_tur = "danger";
    }
}

// REZERVASYONLARI ÇEK
$stmt = $pdo->prepare("CALL sp_AdminTumRezervasyonlar()");
$stmt->execute();
$rezervasyonlar = $stmt->fetchAll();
$stmt->closeCursor();
?>

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-4">
        <h2 class="fw-bold text-primary"><i class="fas fa-calendar-alt me-2"></i>Rezervasyon Yönetimi</h2>
        <a href="admin_panel.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Panele Dön</a>
    </div>

    <?php if (isset($mesaj)): ?>
        <div class="alert alert-<?php echo $mesaj_tur; ?> alert-dismissible fade show" role="alert">
            <?php echo $mesaj; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="rezTable">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Tarih & Saat</th>
                            <th>Müşteri</th>
                            <th>Tesis & Saha</th>
                            <th>Durum</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rezervasyonlar as $rez): ?>
                            <tr>
                                <td>#<?php echo $rez['rezervasyon_id']; ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo date("d.m.Y", strtotime($rez['tarih'])); ?></div>
                                    <small class="text-muted">
                                        <?php echo substr($rez['baslangic_saati'], 0, 5) . '-' . substr($rez['bitis_saati'], 0, 5); ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="fw-bold"><?php echo $rez['musteri_ad'] . ' ' . $rez['musteri_soyad']; ?></div>
                                    <small class="text-muted"><?php echo $rez['musteri_telefon']; ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold"><?php echo $rez['tesis_adi']; ?></div>
                                    <small class="text-muted"><?php echo $rez['saha_adi']; ?></small>
                                </td>
                                <td>
                                    <?php 
                                        $durumRenk = 'secondary';
                                        $durumMetin = $rez['durum'];
                                        switch($rez['durum']) {
                                            case 'onay_bekliyor': $durumRenk='warning text-dark'; $durumMetin='Onay Bekliyor'; break;
                                            case 'onaylandi': $durumRenk='info'; $durumMetin='Onaylandı'; break;
                                            case 'iptal': $durumRenk='danger'; $durumMetin='İptal'; break;
                                            case 'tamamlandi': $durumRenk='success'; $durumMetin='Tamamlandı'; break;
                                        }
                                    ?>
                                    <span class="badge bg-<?php echo $durumRenk; ?>"><?php echo $durumMetin; ?></span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#islemModal"
                                            onclick="modalHazirla(<?php echo $rez['rezervasyon_id']; ?>, '<?php echo $rez['durum']; ?>')">
                                        <i class="fas fa-edit"></i> Düzenle
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- İŞLEM MODALI -->
<div class="modal fade" id="islemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title fw-bold">Rezervasyon Durumu Güncelle</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="islem" value="durum_guncelle">
            <input type="hidden" name="rezervasyon_id" id="modalRezId">
            
            <div class="mb-3">
                <label class="form-label">Yeni Durum Seçin:</label>
                <select name="yeni_durum" id="modalDurumSelect" class="form-select">
                    <option value="onay_bekliyor">Onay Bekliyor</option>
                    <option value="onaylandi">Onaylandı</option>
                    <option value="tamamlandi">Tamamlandı</option>
                    <option value="iptal">İptal Edildi</option>
                </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
            <button type="submit" class="btn btn-success">Güncelle</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
function modalHazirla(id, mevcutDurum) {
    document.getElementById('modalRezId').value = id;
    document.getElementById('modalDurumSelect').value = mevcutDurum;
}
</script>

<?php include 'includes/footer.php'; ?>
