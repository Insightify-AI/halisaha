<?php
require_once 'includes/db.php';
include 'includes/header.php';

// 1. GÜVENLİK: Giriş yapmamışsa Login'e at
if (!isset($_SESSION['kullanici_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$kullanici_id = $_SESSION['kullanici_id'];
$rol = $_SESSION['rol'];

// 2. KULLANICI BİLGİLERİNİ ÇEK
$stmt = $pdo->prepare("SELECT * FROM Kullanicilar WHERE kullanici_id = ?");
$stmt->execute([$kullanici_id]);
$user = $stmt->fetch();

// 3. REZERVASYONLARI ÇEK (Sadece Müşteriyse)
$rezervasyonlar = [];
if ($rol == 'musteri') {
    $musteri_id = $_SESSION['rol_id'];
    $stmtR = $pdo->prepare("CALL sp_MusteriRezervasyonlari(?)");
    $stmtR->execute([$musteri_id]);
    $rezervasyonlar = $stmtR->fetchAll();
    $stmtR->closeCursor();
}
?>

<div class="container mb-5">
    
    <!-- Mesaj Gösterimi (Yorum yapıldıktan sonra gelen) -->
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'yorum_basarili'): ?>
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            <i class="fas fa-check-circle me-2"></i> Harika! Yorumun kaydedildi.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mt-4">
        <!-- SOL MENÜ: Profil Kartı -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 text-center p-4">
                <div class="mb-3">
                    <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" width="120" class="rounded-circle img-thumbnail">
                </div>
                <h4 class="fw-bold"><?php echo htmlspecialchars($user['ad'] . ' ' . $user['soyad']); ?></h4>
                <p class="text-muted mb-1"><?php echo $user['eposta']; ?></p>
                <span class="badge bg-info text-dark mb-3"><?php echo ucfirst($user['rol']); ?> Hesabı</span>
                
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary btn-sm"><i class="fas fa-cog me-1"></i> Ayarları Düzenle</button>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-sign-out-alt me-1"></i> Çıkış Yap</a>
                </div>
            </div>
        </div>

        <!-- SAĞ İÇERİK: Rezervasyon Listesi -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-history me-2"></i>Rezervasyonlarım</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (count($rezervasyonlar) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tarih & Saat</th>
                                        <th>Tesis & Saha</th>
                                        <th>Tutar</th>
                                        <th>Durum</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rezervasyonlar as $rez): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo date("d.m.Y", strtotime($rez['tarih'])); ?></div>
                                                <small class="text-muted">
                                                    <?php echo substr($rez['baslangic_saati'], 0, 5) . '-' . substr($rez['bitis_saati'], 0, 5); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?php echo $rez['tesis_adi']; ?></div>
                                                <small class="text-muted"><?php echo $rez['saha_adi']; ?></small>
                                            </td>
                                            <td>
                                                <span class="text-success fw-bold"><?php echo number_format($rez['tutar'], 0); ?> ₺</span>
                                            </td>
                                            <td>
                                                <?php 
                                                    $durumRenk = 'secondary';
                                                    $durumMetin = 'Bilinmiyor';
                                                    switch($rez['durum']) {
                                                        case 'onay_bekliyor': $durumRenk='warning text-dark'; $durumMetin='Onay Bekliyor'; break;
                                                        case 'onaylandi': $durumRenk='success'; $durumMetin='Onaylandı'; break;
                                                        case 'iptal': $durumRenk='danger'; $durumMetin='İptal Edildi'; break;
                                                        case 'tamamlandi': $durumRenk='primary'; $durumMetin='Tamamlandı'; break;
                                                    }
                                                ?>
                                                <span class="badge bg-<?php echo $durumRenk; ?>"><?php echo $durumMetin; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($rez['durum'] == 'tamamlandi'): ?>
                                                    <!-- YORUM BUTONU -->
                                                    <button class="btn btn-sm btn-warning text-dark fw-bold" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#yorumModal"
                                                            onclick="yorumModalHazirla(<?php echo $rez['tesis_id']; ?>, '<?php echo htmlspecialchars($rez['tesis_adi'], ENT_QUOTES); ?>')">
                                                        <i class="fas fa-star me-1"></i> Puan Ver
                                                    </button>
                                                    
                                                    <!-- TEKRAR KİRALA BUTONU -->
                                                    <a href="rezervasyon_yap.php?saha_id=<?php echo $rez['saha_id']; ?>" class="btn btn-sm btn-outline-primary ms-1" title="Tekrar Kirala">
                                                        <i class="fas fa-redo"></i>
                                                    </a>

                                                <?php elseif ($rez['durum'] == 'onay_bekliyor'): ?>
                                                    <!-- İPTAL BUTONU -->
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#iptalModal"
                                                            onclick="iptalModalHazirla(<?php echo $rez['rezervasyon_id']; ?>)">
                                                        <i class="fas fa-times me-1"></i> İptal
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted small">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
            </div>

            <!-- FAVORİ TESİSLERİM -->
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-danger"><i class="fas fa-heart me-2"></i>Favori Tesislerim</h5>
                </div>
                <div class="card-body">
                    <?php
                        // Favorileri Çek
                        $stmtF = $pdo->prepare("CALL sp_KullaniciFavorileri(?)");
                        $stmtF->execute([$kullanici_id]);
                        $favoriler = $stmtF->fetchAll();
                        $stmtF->closeCursor();
                    ?>
                    
                    <?php if (count($favoriler) > 0): ?>
                        <div class="row row-cols-1 row-cols-md-2 g-3">
                            <?php foreach ($favoriler as $fav): ?>
                                <div class="col">
                                    <div class="card h-100 border-0 shadow-sm bg-light">
                                        <div class="card-body d-flex align-items-center">
                                            <img src="<?php echo !empty($fav['kapak_resmi']) ? $fav['kapak_resmi'] : 'https://via.placeholder.com/80'; ?>" 
                                                 class="rounded me-3" width="60" height="60" style="object-fit: cover;">
                                            <div>
                                                <h6 class="fw-bold mb-1">
                                                    <a href="tesis_detay.php?id=<?php echo $fav['tesis_id']; ?>" class="text-dark text-decoration-none stretched-link">
                                                        <?php echo $fav['tesis_adi']; ?>
                                                    </a>
                                                </h6>
                                                <small class="text-muted"><?php echo $fav['ilce_adi'] . '/' . $fav['sehir_adi']; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">Henüz favori tesisiniz yok.</p>
                    <?php endif; ?>
                </div>
            </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <img src="https://cdn-icons-png.flaticon.com/512/7486/7486747.png" width="80" class="opacity-50 mb-3">
                            <p class="text-muted">Henüz hiç rezervasyon yapmadınız.</p>
                            <a href="index.php" class="btn btn-primary">Hemen Saha Ara</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- YORUM MODALI (Sayfanın en altında, container dışında) -->
<div class="modal fade" id="yorumModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="yorum_kaydet.php" method="POST">
          <div class="modal-header bg-warning">
            <h5 class="modal-title fw-bold text-dark"><i class="fas fa-star me-2"></i>Değerlendir & Yorum Yap</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="tesis_id" id="modalTesisId">
            
            <p class="text-center fs-5">
                <strong id="modalTesisAdi" class="text-primary">Tesis Adı</strong> deneyimin nasıldı?
            </p>

            <div class="mb-3 text-center">
                <label class="form-label d-block text-muted">Puanın</label>
                <select name="puan" class="form-select form-select-lg text-center mx-auto" style="width: 150px;" required>
                    <option value="5">⭐⭐⭐⭐⭐ (5)</option>
                    <option value="4">⭐⭐⭐⭐ (4)</option>
                    <option value="3">⭐⭐⭐ (3)</option>
                    <option value="2">⭐⭐ (2)</option>
                    <option value="1">⭐ (1)</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Yorumun</label>
                <textarea name="yorum" class="form-control" rows="3" placeholder="Zemin nasıldı? Soyunma odaları temiz miydi?" required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
            <button type="submit" class="btn btn-primary">Yorumu Gönder</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- JAVASCRIPT (Sayfanın en altında) -->
<script>
function yorumModalHazirla(id, ad) {
    document.getElementById('modalTesisId').value = id;
    document.getElementById('modalTesisAdi').innerText = ad;
}

function iptalModalHazirla(id) {
    document.getElementById('iptalRezervasyonId').value = id;
}
</script>

<!-- İPTAL MODALI -->
<div class="modal fade" id="iptalModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <form action="rezervasyon_iptal.php" method="POST">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title fw-bold">İptal Onayı</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body text-center">
            <input type="hidden" name="rezervasyon_id" id="iptalRezervasyonId">
            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
            <p>Rezervasyonunuzu iptal etmek istediğinize emin misiniz?</p>
          </div>
          <div class="modal-footer justify-content-center">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Vazgeç</button>
            <button type="submit" class="btn btn-danger btn-sm">Evet, İptal Et</button>
          </div>
      </form>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>