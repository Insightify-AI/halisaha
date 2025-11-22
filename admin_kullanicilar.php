<?php
require_once 'includes/db.php';
include 'includes/header.php';

// GÜVENLİK
if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] != 'admin') {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

// Rol Güncelleme İşlemi
$mesaj = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['islem']) && $_POST['islem'] == 'rol_guncelle') {
    $hedef_id = $_POST['kullanici_id'];
    $yeni_rol = $_POST['yeni_rol'];
    
    try {
        $stmt = $pdo->prepare("CALL sp_AdminRolGuncelle(?, ?)");
        $stmt->execute([$hedef_id, $yeni_rol]);
        $mesaj = "<div class='alert alert-success'>Kullanıcı yetkisi başarıyla güncellendi: <strong>" . ucfirst($yeni_rol) . "</strong></div>";
    } catch (PDOException $e) {
        $mesaj = "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
    }
}

// Kullanıcı Silme İşlemi
if (isset($_GET['sil_id'])) {
    $sil_id = $_GET['sil_id'];
    // Kendini silmeyi engelle
    if ($sil_id == $_SESSION['kullanici_id']) {
        $mesaj = "<div class='alert alert-danger'>Kendinizi silemezsiniz!</div>";
    } else {
        try {
            $stmt = $pdo->prepare("CALL sp_AdminKullaniciSil(?)");
            $stmt->execute([$sil_id]);
            $mesaj = "<div class='alert alert-success'>Kullanıcı silindi.</div>";
        } catch (PDOException $e) {
            $mesaj = "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
        }
    }
}

// Kullanıcıları Getir
$stmt = $pdo->prepare("CALL sp_AdminKullanicilariGetir()");
$stmt->execute();
$kullanicilar = $stmt->fetchAll();
$stmt->closeCursor();
?>

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fas fa-users-cog me-2"></i>Kullanıcı Yönetimi</h2>
        <a href="admin_panel.php" class="btn btn-outline-secondary">Geri Dön</a>
    </div>

    <?php echo $mesaj; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ad Soyad</th>
                            <th>E-posta / Telefon</th>
                            <th>Rol</th>
                            <th>Kayıt Tarihi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kullanicilar as $k): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded-circle p-2 me-2 text-center" style="width:40px;height:40px;">
                                            <i class="fas fa-user text-secondary"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold"><?php echo $k['ad'] . ' ' . $k['soyad']; ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php echo $k['eposta']; ?><br>
                                    <small class="text-muted"><?php echo $k['telefon']; ?></small>
                                </td>
                                <td>
                                    <?php if($k['rol'] == 'admin'): ?>
                                        <span class="badge bg-danger">Yönetici</span>
                                    <?php elseif($k['rol'] == 'tesis_sahibi'): ?>
                                        <span class="badge bg-success">Tesis Sahibi</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark">Müşteri</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date("d.m.Y", strtotime($k['kayit_tarihi'])); ?></td>
                                <td>
                                    <?php if($k['kullanici_id'] == 1): ?>
                                        <span class="badge bg-secondary"><i class="fas fa-lock me-1"></i>Süper Admin</span>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-warning btn-sm me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#rolModal" 
                                                onclick="rolDuzenle(<?php echo $k['kullanici_id']; ?>, '<?php echo $k['rol']; ?>', '<?php echo $k['ad'] . ' ' . $k['soyad']; ?>')">
                                            <i class="fas fa-user-tag"></i>
                                        </button>
                                        
                                        <?php if($k['kullanici_id'] != $_SESSION['kullanici_id']): ?>
                                            <a href="?sil_id=<?php echo $k['kullanici_id']; ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Rol Düzenleme Modalı -->
<div class="modal fade" id="rolModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yetki Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="islem" value="rol_guncelle">
                    <input type="hidden" name="kullanici_id" id="modal_kullanici_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Kullanıcı</label>
                        <input type="text" class="form-control" id="modal_kullanici_ad" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Yeni Yetki Seviyesi</label>
                        <select name="yeni_rol" class="form-select" id="modal_yeni_rol">
                            <option value="musteri">Müşteri (Standart)</option>
                            <option value="tesis_sahibi">Tesis Sahibi (İşletme)</option>
                            <option value="admin">Yönetici (Tam Yetki)</option>
                        </select>
                        <div class="form-text text-warning mt-2">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Dikkat: Yönetici yetkisi verdiğiniz kullanıcı tüm sisteme erişebilir.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function rolDuzenle(id, mevcutRol, adSoyad) {
    document.getElementById('modal_kullanici_id').value = id;
    document.getElementById('modal_kullanici_ad').value = adSoyad;
    document.getElementById('modal_yeni_rol').value = mevcutRol;
}
</script>

<?php include 'includes/footer.php'; ?>
