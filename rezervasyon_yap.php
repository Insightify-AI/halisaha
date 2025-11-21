<?php
require_once 'includes/db.php';
include 'includes/header.php';

// 1. GÜVENLİK KONTROLLERİ
// Giriş yapmamışsa Login'e at
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: login.php?msg=once_giris");
    exit;
}

// Saha ID gelmemişse anasayfaya at
if (!isset($_GET['saha_id'])) {
    header("Location: index.php");
    exit;
}

$saha_id = (int)$_GET['saha_id'];
// Tarih seçilmediyse BUGÜN, seçildiyse O GÜN olsun
$secilen_tarih = isset($_GET['tarih']) ? $_GET['tarih'] : date('Y-m-d');

// 2. SAHA BİLGİLERİNİ ÇEK (Stored Procedure)
$stmt = $pdo->prepare("CALL sp_SahaDetayGetir(?)");
$stmt->execute([$saha_id]);
$saha = $stmt->fetch();
$stmt->closeCursor(); // İmleci kapatmayı unutma

if (!$saha) die("Saha bulunamadı.");

// 3. SAATLERİ ve DOLULUK DURUMUNU ÇEK
// Tüm saat bloklarını getir
$tumSaatler = $pdo->query("SELECT * FROM SaatBloklari ORDER BY baslangic_saati ASC")->fetchAll();

// Prosedür ile o günkü dolu saatleri getir (SQL tarafında hazırlamıştık)
$sqlMusaitlik = "CALL sp_MusaitlikKontrol(:saha_id, :tarih)";
$stmtM = $pdo->prepare($sqlMusaitlik);
$stmtM->execute([':saha_id' => $saha_id, ':tarih' => $secilen_tarih]);
$doluSaatlerHam = $stmtM->fetchAll();
$stmtM->closeCursor(); // Prosedür sonrası temizlik

// Dolu saat ID'lerini basit bir diziye çevir (kontrol kolaylığı için)
$doluSaatlerDizisi = [];
foreach ($doluSaatlerHam as $d) {
    $doluSaatlerDizisi[] = $d['saat_id'];
}
?>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            
            <!-- Üst Bilgi Kartı -->
            <div class="card shadow-sm border-0 mb-4 bg-primary text-white">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="fw-bold mb-0"><i class="far fa-calendar-alt me-2"></i>Rezervasyon Oluştur</h3>
                        <p class="mb-0 opacity-75">
                            <?php echo $saha['tesis_adi']; ?> - <?php echo $saha['saha_adi']; ?>
                        </p>
                    </div>
                    <div class="text-end">
                        <span class="display-6 fw-bold"><?php echo number_format($saha['fiyat_saatlik'], 0); ?> ₺</span>
                        <span class="fs-6">/ Saat</span>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- SOL: TARİH VE SAAT SEÇİMİ -->
                <div class="col-lg-8">
                    <div class="card shadow border-0 mb-3">
                        <div class="card-header bg-white py-3">
                            <h5 class="fw-bold mb-0 text-primary">1. Tarih Seçimi</h5>
                        </div>
                        <div class="card-body">
                            <form action="" method="GET" id="tarihForm">
                                <input type="hidden" name="saha_id" value="<?php echo $saha_id; ?>">
                                <label class="form-label">Maç Tarihi:</label>
                                <input type="date" name="tarih" class="form-control form-control-lg" 
                                       value="<?php echo $secilen_tarih; ?>" 
                                       min="<?php echo date('Y-m-d'); ?>" 
                                       onchange="document.getElementById('tarihForm').submit()">
                                <small class="text-muted">Tarihi değiştirdiğinizde saatler güncellenecektir.</small>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow border-0">
                        <div class="card-header bg-white py-3">
                            <h5 class="fw-bold mb-0 text-primary">2. Saat Seçimi</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-center mb-4">
                                <span class="badge bg-success me-2">BOŞ</span> Seçilebilir
                                <span class="badge bg-danger ms-2">DOLU</span> Başkası kapmış
                            </p>

                            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
                                <?php foreach ($tumSaatler as $saat): ?>
                                    <?php 
                                        $saat_metni = substr($saat['baslangic_saati'], 0, 5) . " - " . substr($saat['bitis_saati'], 0, 5);
                                        $dolu_mu = in_array($saat['saat_id'], $doluSaatlerDizisi);
                                        
                                        // Geçmiş saat kontrolü (Bugünse ve saat geçtiyse engelle)
                                        $gecmis_mi = false;
                                        if ($secilen_tarih == date('Y-m-d')) {
                                            if (date('H:i') > $saat['baslangic_saati']) {
                                                $gecmis_mi = true;
                                            }
                                        }
                                    ?>
                                    
                                    <div class="col">
                                        <?php if ($dolu_mu || $gecmis_mi): ?>
                                            <!-- DOLU VEYA GEÇMİŞ SAAT -->
                                            <button class="btn btn-danger w-100 py-3 disabled" style="opacity: 0.6;">
                                                <?php echo $saat_metni; ?><br>
                                                <small><?php echo $gecmis_mi ? 'Geçti' : 'Dolu'; ?></small>
                                            </button>
                                        <?php else: ?>
                                            <!-- BOŞ SAAT -->
                                            <button type="button" 
                                                    onclick="saatSec(<?php echo $saat['saat_id']; ?>, '<?php echo $saat_metni; ?>')" 
                                                    class="btn btn-outline-success w-100 py-3 saat-btn"
                                                    id="btn-<?php echo $saat['saat_id']; ?>">
                                                <i class="far fa-clock mb-1"></i><br>
                                                <?php echo $saat_metni; ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SAĞ: ÖZET VE ONAY -->
                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="card shadow border-0 sticky-top" style="top: 20px;">
                        <div class="card-header bg-dark text-white text-center py-3">
                            <h5 class="mb-0">Rezervasyon Özeti</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush mb-3">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Tarih:</span>
                                    <strong><?php echo date("d.m.Y", strtotime($secilen_tarih)); ?></strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between bg-light">
                                    <span>Seçilen Saat:</span>
                                    <strong id="ozetSaat" class="text-primary">-- : --</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Hizmet Bedeli:</span>
                                    <span>0 TL</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between mt-2">
                                    <span class="fw-bold">TOPLAM:</span>
                                    <span class="fw-bold fs-5 text-success"><?php echo number_format($saha['fiyat_saatlik'], 0); ?> TL</span>
                                </li>
                            </ul>

                            <!-- İŞLEM FORM (Hidden Inputlar) -->
                            <form action="rezervasyon_tamamla.php" method="POST">
                                <input type="hidden" name="saha_id" value="<?php echo $saha_id; ?>">
                                <input type="hidden" name="tarih" value="<?php echo $secilen_tarih; ?>">
                                <input type="hidden" name="saat_id" id="secilenSaatInput" required>
                                <input type="hidden" name="fiyat" value="<?php echo $saha['fiyat_saatlik']; ?>">

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success btn-lg" id="onayBtn" disabled>
                                        <i class="fas fa-check-circle me-2"></i>Onayla ve Bitir
                                    </button>
                                </div>
                            </form>
                            <div class="text-center mt-3">
                                <small class="text-muted">Rezervasyonunuz tesis onayına düşecektir.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- SAAT SEÇİM JAVASCRIPT (Basit Görsel Efektler) -->
<script>
function saatSec(id, metin) {
    // 1. Tüm butonların rengini sıfırla
    document.querySelectorAll('.saat-btn').forEach(btn => {
        btn.classList.remove('btn-success', 'text-white');
        btn.classList.add('btn-outline-success');
    });

    // 2. Tıklanan butonu yeşil yap
    const tiklananBtn = document.getElementById('btn-' + id);
    tiklananBtn.classList.remove('btn-outline-success');
    tiklananBtn.classList.add('btn-success', 'text-white');

    // 3. Özeti Güncelle
    document.getElementById('ozetSaat').innerText = metin;
    
    // 4. Gizli input'a veriyi yaz (Form gönderimi için)
    document.getElementById('secilenSaatInput').value = id;

    // 5. Butonu Aktif Et
    document.getElementById('onayBtn').disabled = false;
}
</script>

<?php include 'includes/footer.php'; ?>