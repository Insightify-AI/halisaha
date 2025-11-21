<?php
require_once 'includes/db.php';
include 'includes/header.php';

// GÜVENLİK
if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] != 'admin') {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

// SİLME İŞLEMİ
$mesaj = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['sil_id'])) {
    $sil_id = $_POST['sil_id'];
    try {
        $stmt = $pdo->prepare("CALL sp_AdminKullaniciSil(?)");
        $stmt->execute([$sil_id]);
        $mesaj = "<div class='alert alert-success'>Kullanıcı başarıyla silindi.</div>";
    } catch (PDOException $e) {
        $mesaj = "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
    }
}

// KULLANICILARI ÇEK
$stmt = $pdo->prepare("CALL sp_AdminKullanicilariGetir()");
$stmt->execute();
$kullanicilar = $stmt->fetchAll();
$stmt->closeCursor();
?>

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark"><i class="fas fa-users-cog me-2"></i>Kullanıcı Yönetimi</h2>
        <a href="admin_panel.php" class="btn btn-outline-secondary">Panele Dön</a>
    </div>

    <?php echo $mesaj; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Ad Soyad</th>
                            <th>E-posta</th>
                            <th>Rol</th>
                            <th>Kayıt Tarihi</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kullanicilar as $k): ?>
                            <tr>
                                <td>#<?php echo $k['kullanici_id']; ?></td>
                                <td class="fw-bold"><?php echo $k['ad'] . ' ' . $k['soyad']; ?></td>
                                <td><?php echo $k['eposta']; ?></td>
                                <td>
                                    <?php if($k['rol'] == 'admin'): ?>
                                        <span class="badge bg-danger">Yönetici</span>
                                    <?php elseif($k['rol'] == 'tesis_sahibi'): ?>
                                        <span class="badge bg-warning text-dark">Tesis Sahibi</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark">Müşteri</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date("d.m.Y", strtotime($k['kayit_tarihi'])); ?></td>
                                <td>
                                    <?php if($k['rol'] != 'admin'): ?>
                                        <form method="POST" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz? Bu işlem geri alınamaz!');">
                                            <input type="hidden" name="sil_id" value="<?php echo $k['kullanici_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash-alt"></i> Sil
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <small class="text-muted">Silinemez</small>
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

<?php include 'includes/footer.php'; ?>
