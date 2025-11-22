<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

if (!isset($_SESSION['kullanici_id'])) {
    header("Location: login.php");
    exit;
}

$kullanici_id = $_SESSION['kullanici_id'];

// Kullanıcı Bilgileri ve Bakiye
$stmt = $pdo->prepare("SELECT * FROM Kullanicilar WHERE kullanici_id = ?");
$stmt->execute([$kullanici_id]);
$kullanici = $stmt->fetch();

// Sanal Kart Bilgileri
$stmt = $pdo->prepare("SELECT * FROM SanalKartlar WHERE kullanici_id = ?");
$stmt->execute([$kullanici_id]);
$kart = $stmt->fetch();

// Son İşlemler
$stmt = $pdo->prepare("SELECT * FROM CuzdanHareketleri WHERE kullanici_id = ? ORDER BY tarih DESC LIMIT 10");
$stmt->execute([$kullanici_id]);
$hareketler = $stmt->fetchAll();
?>

<div class="container mt-5">
    <div class="row">
        <!-- Sol Kolon: Sanal Kart ve Bakiye -->
        <div class="col-md-5 mb-4">
            <!-- Sanal Kart -->
            <div class="card border-0 shadow-lg text-white mb-4" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); border-radius: 15px; overflow: hidden;">
                <div class="card-body p-4 position-relative">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <i class="fas fa-wifi fa-2x opacity-50"></i>
                        <span class="badge bg-warning text-dark">PREMIUM</span>
                    </div>
                    
                    <div class="mb-4">
                        <h3 class="font-monospace mb-0" style="letter-spacing: 2px; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                            <?php echo $kart ? $kart['kart_numarasi'] : '**** **** **** ****'; ?>
                        </h3>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-end">
                        <div>
                            <small class="d-block opacity-75" style="font-size: 0.8rem;">KART SAHİBİ</small>
                            <span class="fw-bold text-uppercase"><?php echo htmlspecialchars($kullanici['ad'] . ' ' . $kullanici['soyad']); ?></span>
                        </div>
                        <div class="text-end">
                            <small class="d-block opacity-75" style="font-size: 0.8rem;">SON KULLANMA</small>
                            <span class="fw-bold"><?php echo $kart ? $kart['son_kullanma_tarihi'] : '**/**'; ?></span>
                        </div>
                    </div>
                    
                    <!-- Dekoratif Daireler -->
                    <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                    <div style="position: absolute; bottom: -30px; left: -30px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                </div>
            </div>

            <?php if (!$kart): ?>
                <div class="alert alert-info shadow-sm border-0">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-gift fa-2x me-3 text-primary"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Hemen Sanal Kart Oluştur!</h6>
                            <p class="mb-0 small">İlk karta özel <strong class="text-success">50₺ Bonus</strong> hediye!</p>
                        </div>
                        <button onclick="sanalKartOlustur()" class="btn btn-primary btn-sm ms-auto">Oluştur</button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Bakiye Kartı -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center p-4">
                    <h6 class="text-muted text-uppercase mb-2">Mevcut Bakiye</h6>
                    <h2 class="display-4 fw-bold text-success mb-3">
                        <?php echo number_format($kullanici['bakiye'] ?? 0, 2); ?> <small class="fs-4 text-muted">₺</small>
                    </h2>
                    <?php if ($kart): ?>
                        <button class="btn btn-success w-100 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#paraYukleModal">
                            <i class="fas fa-plus-circle me-2"></i> Bakiye Yükle
                        </button>
                    <?php else: ?>
                        <button class="btn btn-secondary w-100 py-2 fw-bold" disabled title="Önce sanal kart oluşturmalısınız">
                            <i class="fas fa-lock me-2"></i> Bakiye Yükle
                        </button>
                        <small class="text-muted d-block mt-2">Önce yukarıdan sanal kart oluşturun</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sağ Kolon: İşlem Geçmişi -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-history me-2 text-primary"></i>Son İşlemler</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">İşlem</th>
                                    <th>Tarih</th>
                                    <th class="text-end pe-4">Tutar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($hareketler) > 0): ?>
                                    <?php foreach ($hareketler as $hareket): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <?php
                                                    $icon = 'fa-exchange-alt';
                                                    $color = 'text-secondary';
                                                    $bg = 'bg-light';
                                                    
                                                    switch($hareket['islem_tipi']) {
                                                        case 'yukleme': 
                                                            $icon = 'fa-arrow-down'; 
                                                            $color = 'text-success'; 
                                                            $bg = 'bg-success-subtle';
                                                            break;
                                                        case 'harcama': 
                                                            $icon = 'fa-shopping-cart'; 
                                                            $color = 'text-danger'; 
                                                            $bg = 'bg-danger-subtle';
                                                            break;
                                                        case 'bonus': 
                                                            $icon = 'fa-gift'; 
                                                            $color = 'text-warning'; 
                                                            $bg = 'bg-warning-subtle';
                                                            break;
                                                        case 'cashback': 
                                                            $icon = 'fa-undo'; 
                                                            $color = 'text-info'; 
                                                            $bg = 'bg-info-subtle';
                                                            break;
                                                    }
                                                    ?>
                                                    <div class="rounded-circle p-2 me-3 <?php echo $bg; ?>">
                                                        <i class="fas <?php echo $icon; ?> <?php echo $color; ?>"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-capitalize"><?php echo $hareket['islem_tipi']; ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($hareket['aciklama']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-muted small">
                                                <?php echo date('d.m.Y H:i', strtotime($hareket['tarih'])); ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <span class="fw-bold <?php echo in_array($hareket['islem_tipi'], ['harcama']) ? 'text-danger' : 'text-success'; ?>">
                                                    <?php echo in_array($hareket['islem_tipi'], ['harcama']) ? '-' : '+'; ?>
                                                    <?php echo number_format($hareket['tutar'], 2); ?> ₺
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">Henüz işlem geçmişi yok.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Para Yükleme Modalı -->
<div class="modal fade" id="paraYukleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-wallet me-2"></i>Bakiye Yükle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <button onclick="tutarSec(100)" class="btn btn-outline-success w-100 py-3 position-relative">
                            <span class="d-block fs-4 fw-bold">100 ₺</span>
                            <span class="badge bg-warning text-dark position-absolute top-0 start-100 translate-middle">+10₺ Bonus</span>
                        </button>
                    </div>
                    <div class="col-6">
                        <button onclick="tutarSec(250)" class="btn btn-outline-success w-100 py-3 position-relative">
                            <span class="d-block fs-4 fw-bold">250 ₺</span>
                            <span class="badge bg-warning text-dark position-absolute top-0 start-100 translate-middle">+30₺ Bonus</span>
                        </button>
                    </div>
                    <div class="col-6">
                        <button onclick="tutarSec(500)" class="btn btn-outline-success w-100 py-3 position-relative">
                            <span class="d-block fs-4 fw-bold">500 ₺</span>
                            <span class="badge bg-danger text-white position-absolute top-0 start-100 translate-middle">POPÜLER</span>
                            <small class="d-block text-muted mt-1">+75₺ Bonus</small>
                        </button>
                    </div>
                    <div class="col-6">
                        <button onclick="tutarSec(1000)" class="btn btn-outline-success w-100 py-3 position-relative">
                            <span class="d-block fs-4 fw-bold">1000 ₺</span>
                            <span class="badge bg-warning text-dark position-absolute top-0 start-100 translate-middle">%20 Bonus</span>
                        </button>
                    </div>
                </div>

                <form id="yuklemeForm" onsubmit="bakiyeYukle(event)">
                    <div class="input-group input-group-lg mb-3">
                        <span class="input-group-text">₺</span>
                        <input type="number" id="yuklenecekTutar" class="form-control" placeholder="Tutar giriniz" min="50" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100 btn-lg fw-bold">Ödeme Yap ve Yükle</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function tutarSec(tutar) {
    document.getElementById('yuklenecekTutar').value = tutar;
}

function sanalKartOlustur() {
    if(confirm('Sanal kart oluşturup 50₺ bonus kazanmak istiyor musunuz?')) {
        const formData = new FormData();
        formData.append('action', 'sanal_kart_olustur');

        fetch('cuzdan_islem.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        });
    }
}

function bakiyeYukle(e) {
    e.preventDefault();
    const tutar = document.getElementById('yuklenecekTutar').value;
    
    if(confirm(tutar + '₺ yüklemek istediğinize emin misiniz?')) {
        const formData = new FormData();
        formData.append('action', 'bakiye_yukle');
        formData.append('tutar', tutar);

        fetch('cuzdan_islem.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert(data.message + '\nYeni Bakiye: ' + data.yeni_bakiye + '₺\nKazanılan Bonus: ' + data.bonus + '₺');
                location.reload();
            } else {
                alert(data.message);
            }
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
