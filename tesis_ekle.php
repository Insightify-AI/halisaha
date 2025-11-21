<?php
require_once 'includes/db.php';
include 'includes/header.php';

// GÜVENLİK: Sadece Tesis Sahibi
if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] != 'tesis_sahibi') {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

// Şehirleri ve Özellikleri Çek (Form için)
$sehirler = $pdo->query("SELECT * FROM Sehirler ORDER BY plaka_kodu ASC")->fetchAll();
$tumOzellikler = $pdo->query("SELECT * FROM Ozellikler")->fetchAll();

$mesaj = "";

// FORM GÖNDERİLDİ Mİ?
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sahip_id = $_SESSION['rol_id'];
    $ilce_id = $_POST['ilce_id'];
    $ad = trim($_POST['tesis_adi']);
    $adres = trim($_POST['adres']);
    $telefon = trim($_POST['telefon']);
    $aciklama = trim($_POST['aciklama']);
    $resim = !empty($_POST['kapak_resmi']) ? $_POST['kapak_resmi'] : 'https://images.unsplash.com/photo-1529900748604-07564a03e7a6?q=80&w=800&auto=format&fit=crop';
    
    $secilen_ozellikler = isset($_POST['ozellikler']) ? $_POST['ozellikler'] : [];

    if (empty($ilce_id) || empty($ad) || empty($adres)) {
        $mesaj = "<div class='alert alert-danger'>Lütfen zorunlu alanları doldurun.</div>";
    } else {
        try {
            // 1. TESİSİ EKLE (SP ile)
            $stmt = $pdo->prepare("CALL sp_TesisEkle(?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$sahip_id, $ilce_id, $ad, $adres, $telefon, $aciklama, $resim]);
            $sonuc = $stmt->fetch();
            $stmt->closeCursor();
            
            $yeni_tesis_id = $sonuc['yeni_id'];

            // 2. ÖZELLİKLERİ EKLE (Döngü ile SP çağırarak)
            if (!empty($secilen_ozellikler)) {
                foreach ($secilen_ozellikler as $ozellik_id) {
                    $stmtOz = $pdo->prepare("CALL sp_TesisOzellikBagla(?, ?)");
                    $stmtOz->execute([$yeni_tesis_id, $ozellik_id]);
                    //$stmtOz->closeCursor(); // Bazı sürümlerde gerekebilir
                }
            }

            // Başarılı Mesajı ve Yönlendirme
            echo "<script>
                alert('Tesis başvurunuz alındı! Yönetici onayından sonra yayınlanacaktır.');
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
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Yeni Tesis Ekle</h4>
                </div>
                <div class="card-body p-4">
                    
                    <?php echo $mesaj; ?>

                    <form method="POST" action="">
                        <!-- Temel Bilgiler -->
                        <h5 class="border-bottom pb-2 mb-3 text-muted">Tesis Bilgileri</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Şehir</label>
                                <select name="sehir_id" class="form-select" required>
                                    <option value="">Seçiniz...</option>
                                    <?php foreach ($sehirler as $s): ?>
                                        <option value="<?php echo $s['sehir_id']; ?>"><?php echo $s['sehir_adi']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">İlçe</label>
                                <select name="ilce_id" class="form-select" required disabled>
                                    <option value="">Önce Şehir Seçin</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tesis Adı</label>
                            <input type="text" name="tesis_adi" class="form-control" required placeholder="Örn: Yıldız Halı Saha">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Adres</label>
                            <textarea name="adres" class="form-control" rows="2" required></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Telefon</label>
                                <input type="text" name="telefon" class="form-control" placeholder="0212...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kapak Resmi (URL)</label>
                                <input type="text" name="kapak_resmi" class="form-control" placeholder="https://...">
                                <div class="form-text">Boş bırakırsanız varsayılan resim kullanılır.</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Açıklama</label>
                            <textarea name="aciklama" class="form-control" rows="3" placeholder="Tesisiniz hakkında kısa bilgi..."></textarea>
                        </div>

                        <!-- Özellikler -->
                        <h5 class="border-bottom pb-2 mb-3 text-muted">Tesis İmkanları</h5>
                        <div class="row row-cols-2 row-cols-md-3 g-3 mb-4">
                            <?php foreach ($tumOzellikler as $oz): ?>
                                <div class="col">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="ozellikler[]" value="<?php echo $oz['ozellik_id']; ?>" id="oz_<?php echo $oz['ozellik_id']; ?>">
                                        <label class="form-check-label" for="oz_<?php echo $oz['ozellik_id']; ?>">
                                            <i class="fas <?php echo $oz['ikon_kodu']; ?> me-1 text-muted"></i>
                                            <?php echo $oz['ozellik_adi']; ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="tesislerim.php" class="btn btn-secondary me-md-2">İptal</a>
                            <button type="submit" class="btn btn-primary px-5">Kaydet ve Başvur</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Şehir-İlçe AJAX Scripti -->
<script>
document.querySelector('select[name="sehir_id"]').addEventListener('change', function() {
    var sehirId = this.value;
    var ilceSelect = document.querySelector('select[name="ilce_id"]');
    
    ilceSelect.innerHTML = '<option>Yükleniyor...</option>';
    ilceSelect.disabled = true;

    fetch('ajax_ilceler.php?sehir_id=' + sehirId)
        .then(response => response.json())
        .then(data => {
            ilceSelect.innerHTML = '<option value="">İlçe Seçiniz</option>';
            data.forEach(ilce => {
                var option = document.createElement('option');
                option.value = ilce.ilce_id;
                option.text = ilce.ilce_adi;
                ilceSelect.appendChild(option);
            });
            ilceSelect.disabled = false;
        });
});
</script>

<?php include 'includes/footer.php'; ?>