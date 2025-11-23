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
                                        <div class="flex-grow-1">
                                            <span class="fw-bold"><?php echo $k['ad'] . ' ' . $k['soyad']; ?></span>
                                        </div>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary rounded-circle ms-2" 
                                                onclick="kullaniciDetay(<?php echo $k['kullanici_id']; ?>)"
                                                title="Detaylı Bilgi">
                                            <i class="fas fa-eye"></i>
                                        </button>
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

<!-- Kullanıcı Detay Modalı -->
<div class="modal fade" id="kullaniciDetayModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-circle me-2"></i>Kullanıcı Detaylı Bilgileri</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="kullaniciDetayIcerik">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Yükleniyor...</span>
                    </div>
                    <p class="mt-2 text-muted">Kullanıcı bilgileri yükleniyor...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function rolDuzenle(id, mevcutRol, adSoyad) {
    document.getElementById('modal_kullanici_id').value = id;
    document.getElementById('modal_kullanici_ad').value = adSoyad;
    document.getElementById('modal_yeni_rol').value = mevcutRol;
}

function kullaniciDetay(kullaniciId) {
    // Modalı aç
    var modal = new bootstrap.Modal(document.getElementById('kullaniciDetayModal'));
    modal.show();
    
    // İçeriği yükle
    fetch('ajax_admin_user_details.php?kullanici_id=' + kullaniciId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const k = data.kullanici;
                const stats = data.istatistikler;
                
                let rolBadge = '';
                if (k.rol == 'admin') {
                    rolBadge = '<span class="badge bg-danger">Yönetici</span>';
                } else if (k.rol == 'tesis_sahibi') {
                    rolBadge = '<span class="badge bg-success">Tesis Sahibi</span>';
                } else {
                    rolBadge = '<span class="badge bg-info text-dark">Müşteri</span>';
                }
                
                let html = `
                    <div class="row">
                        <div class="col-md-4 text-center border-end">
                            <div class="p-3">
                                <div class="bg-primary bg-gradient rounded-circle mx-auto d-flex align-items-center justify-content-center" 
                                     style="width:120px;height:120px;">
                                    <i class="fas fa-user fa-4x text-white"></i>
                                </div>
                                <h4 class="mt-3 mb-2">${k.ad} ${k.soyad}</h4>
                                ${rolBadge}
                                <div class="mt-3 text-start">
                                    <p class="mb-2"><i class="fas fa-envelope text-primary me-2"></i><small>${k.eposta}</small></p>
                                    <p class="mb-2"><i class="fas fa-phone text-primary me-2"></i><small>${k.telefon || 'Belirtilmemiş'}</small></p>
                                    <p class="mb-2"><i class="fas fa-calendar text-primary me-2"></i><small>Kayıt: ${k.kayit_tarihi_fmt}</small></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="p-3">
                                <h5 class="mb-3"><i class="fas fa-chart-bar me-2"></i>İstatistikler</h5>
                                
                                <div class="row g-3 mb-4">
                                    <div class="col-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body text-center">
                                                <h3 class="text-primary mb-0">${stats.rezervasyon.toplam_rezervasyon || 0}</h3>
                                                <small class="text-muted">Toplam Rezervasyon</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body text-center">
                                                <h3 class="text-success mb-0">${parseFloat(k.bakiye || 0).toFixed(2)}₺</h3>
                                                <small class="text-muted">Bakiye</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body text-center">
                                                <h3 class="text-warning mb-0">${parseFloat(stats.harcama || 0).toFixed(2)}₺</h3>
                                                <small class="text-muted">Toplam Harcama</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body text-center">
                                                <h3 class="text-info mb-0">${stats.yorum || 0}</h3>
                                                <small class="text-muted">Yorum Sayısı</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <h6 class="mb-2"><i class="fas fa-tasks me-2"></i>Rezervasyon Durumları</h6>
                                <div class="row g-2 mb-4">
                                    <div class="col-3">
                                        <div class="text-center p-2 bg-success bg-opacity-10 rounded">
                                            <strong>${stats.rezervasyon.tamamlanan || 0}</strong>
                                            <p class="mb-0 small">Tamamlandı</p>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="text-center p-2 bg-warning bg-opacity-10 rounded">
                                            <strong>${stats.rezervasyon.onaylanan || 0}</strong>
                                            <p class="mb-0 small">Onaylandı</p>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="text-center p-2 bg-info bg-opacity-10 rounded">
                                            <strong>${stats.rezervasyon.bekleyen || 0}</strong>
                                            <p class="mb-0 small">Bekliyor</p>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="text-center p-2 bg-danger bg-opacity-10 rounded">
                                            <strong>${stats.rezervasyon.iptal || 0}</strong>
                                            <p class="mb-0 small">İptal</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="text-center p-2 border rounded">
                                            <i class="fas fa-heart text-danger"></i>
                                            <strong class="ms-2">${stats.favori || 0}</strong>
                                            <small class="text-muted ms-1">Favori</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center p-2 border rounded">
                                            <i class="fas fa-trophy text-warning"></i>
                                            <strong class="ms-2">${stats.rozet || 0}</strong>
                                            <small class="text-muted ms-1">Rozet</small>
                                        </div>
                                    </div>
                                </div>
                `;
                
                // Tesis sahibiyse tesislerini göster
                if (k.rol == 'tesis_sahibi' && data.tesisler.length > 0) {
                    html += `
                        <div class="mt-4">
                            <h6 class="mb-2"><i class="fas fa-futbol me-2"></i>Tesisleri</h6>
                            <div class="list-group">
                    `;
                    data.tesisler.forEach(tesis => {
                        const durumBadge = tesis.durum == 'aktif' 
                            ? '<span class="badge bg-success">Aktif</span>' 
                            : '<span class="badge bg-secondary">Pasif</span>';
                        html += `
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${tesis.ad}</strong>
                                    <p class="mb-0 small text-muted">${tesis.sehir}</p>
                                </div>
                                ${durumBadge}
                            </div>
                        `;
                    });
                    html += `</div></div>`;
                }
                
                // Son aktiviteler
                if (data.aktiviteler && data.aktiviteler.length > 0) {
                    html += `
                        <div class="mt-4">
                            <h6 class="mb-2"><i class="fas fa-history me-2"></i>Son Aktiviteler</h6>
                            <div class="list-group list-group-flush">
                    `;
                    data.aktiviteler.forEach(akt => {
                        let durumBadge = '';
                        if (akt.durum == 'Tamamlandı') durumBadge = '<span class="badge bg-success">Tamamlandı</span>';
                        else if (akt.durum == 'Onaylandı') durumBadge = '<span class="badge bg-warning">Onaylandı</span>';
                        else if (akt.durum == 'Onay Bekliyor') durumBadge = '<span class="badge bg-info">Bekliyor</span>';
                        else if (akt.durum == 'İptal') durumBadge = '<span class="badge bg-danger">İptal</span>';
                        
                        html += `
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <i class="fas fa-calendar-check text-primary me-2"></i>
                                        <small>${akt.detay}</small>
                                    </div>
                                    ${durumBadge}
                                </div>
                            </div>
                        `;
                    });
                    html += `</div></div>`;
                }
                
                html += `
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('kullaniciDetayIcerik').innerHTML = html;
            } else {
                document.getElementById('kullaniciDetayIcerik').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('kullaniciDetayIcerik').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>Bir hata oluştu: ${error}
                </div>
            `;
        });
}
</script>

<?php include 'includes/footer.php'; ?>
