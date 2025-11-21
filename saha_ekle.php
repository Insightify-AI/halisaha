<?php
require_once 'includes/db.php';
include 'includes/header.php';

// GÜVENLİK
if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] != 'tesis_sahibi') {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

// URL'den Tesis ID gelmeli
if (!isset($_GET['tesis_id'])) {
    echo "<script>alert('Hata: Tesis seçilmedi!'); window.location.href='tesislerim.php';</script>";
    exit;
}

$tesis_id = (int)$_GET['tesis_id'];
$mesaj = "";

// FORM GÖNDERİLDİ Mİ?
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $saha_adi = trim($_POST['saha_adi']);
    $zemin = $_POST['zemin_tipi'];
    $kapasite = (int)$_POST['kapasite'];
    $fiyat = (float)$_POST['fiyat'];

    if (empty($saha_adi) || empty($fiyat)) {
        $mesaj = "<div class='alert alert-danger'>Lütfen isim ve fiyat giriniz.</div>";
    } else {
        try {
            $stmt = $pdo->prepare("CALL sp_SahaEkle(?, ?, ?, ?, ?)");
            $stmt->execute([$tesis_id, $saha_adi, $zemin, $kapasite, $fiyat]);
            
            echo "<script>
                alert('Saha başarıyla eklendi!');
                window.location.href = 'tesislerim.php';
            </script>";
            exit;
        } catch (PDOException $e) {
            $mesaj = "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow border-0">
                <div class="card-header bg-success text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-plus-square me-2"></i>Yeni Saha Tanımla</h5>
                </div>
                <div class="card-body p-4">
                    <?php echo $mesaj; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Saha Adı</label>
                            <input type="text" name="saha_adi" class="form-control" placeholder="Örn: A Sahası, Kapalı Saha" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Zemin Tipi</label>
                            <select name="zemin_tipi" class="form-select">
                                <option value="suni_cim">Suni Çim</option>
                                <option value="dogal_cim">Doğal Çim</option>
                                <option value="hali">Halı</option>
                                <option value="parke">Parke</option>
                            </select>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Kişi Kapasitesi</label>
                                <input type="number" name="kapasite" class="form-control" value="14">
                                <div class="form-text">Örn: 7'ye 7 maç için 14 yazın.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Saatlik Ücret (TL)</label>
                                <input type="number" name="fiyat" class="form-control" placeholder="1000" required>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">Kaydet ve Ekle</button>
                            <a href="tesislerim.php" class="btn btn-secondary">İptal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>