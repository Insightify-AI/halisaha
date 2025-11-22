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

    // 4. GAMIFICATION VERİLERİNİ ÇEK
    require_once 'includes/GamificationService.php';
    $gamification = new GamificationService($pdo);
    
    // Rozetler
    $stmt = $pdo->prepare("
        SELECT r.*, kr.kazanilma_tarihi 
        FROM Rozetler r
        LEFT JOIN KullaniciRozetleri kr ON r.rozet_id = kr.rozet_id AND kr.kullanici_id = ?
        ORDER BY kr.kazanilma_tarihi DESC, r.gerekli_islem_sayisi ASC
    ");
    $stmt->execute([$kullanici_id]);
    $rozetler = $stmt->fetchAll();

    // Puan Geçmişi
    $stmt = $pdo->prepare("SELECT * FROM PuanGecmisi WHERE kullanici_id = ? ORDER BY tarih DESC LIMIT 20");
    $stmt->execute([$kullanici_id]);
    $puanGecmisi = $stmt->fetchAll();

    // Kuponlar
    $stmt = $pdo->prepare("SELECT * FROM Kuponlar WHERE aktif = 1");
    $stmt->execute();
    $kuponlar = $stmt->fetchAll();
    
    // Kullanıcının Kuponları
    $stmt = $pdo->prepare("
        SELECT kk.*, k.kupon_kodu, k.indirim_orani 
        FROM KullaniciKuponlari kk
        JOIN Kuponlar k ON kk.kupon_id = k.kupon_id
        WHERE kk.kullanici_id = ?
    ");
    $stmt->execute([$kullanici_id]);
    $kullaniciKuponlari = $stmt->fetchAll();
    
    // STREAK VE QUEST VERİLERİ
    require_once 'includes/StreakService.php';
    require_once 'includes/QuestService.php';
    require_once 'includes/WheelService.php';
    
    $streakService = new StreakService($pdo);
    $questService = new QuestService($pdo);
    $wheelService = new WheelService($pdo);
    
    $streakData = $streakService->getCurrentStreak($kullanici_id);
    $hasCheckedIn = $streakService->hasCheckedInToday($kullanici_id);
    $canSpin = $wheelService->canSpinToday($kullanici_id);
    $dailyQuests = $questService->getDailyQuests($kullanici_id);
    $weeklyQuests = $questService->getWeeklyQuests($kullanici_id);
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
                
                <?php if ($rol == 'musteri'): ?>
                    <!-- STREAK WIDGET -->
                    <div class="mb-3 p-3 rounded" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="text-white text-center">
                            <i class="fas fa-fire text-warning fa-2x mb-2"></i>
                            <h3 class="mb-0 fw-bold" id="streakCount"><?php echo $streakData['mevcut_streak'] ?? 0; ?></h3>
                            <small>Gün Streak! 🔥</small>
                            <div class="mt-2 small">
                                <i class="fas fa-trophy text-warning"></i>
                                En Uzun: <?php echo $streakData['en_uzun_streak'] ?? 0; ?> gün
                            </div>
                        </div>
                    </div>
                    
                    <!-- CHECK-IN VE ÇARK BUTONLARI -->
                    <div class="d-grid gap-2 mb-3">
                        <button class="btn <?php echo $hasCheckedIn ? 'btn-secondary' : 'btn-success'; ?> btn-sm" 
                                onclick="dailyCheckIn()" 
                                <?php echo $hasCheckedIn ? 'disabled' : ''; ?>>
                            <i class="fas fa-calendar-check me-1"></i>
                            <?php echo $hasCheckedIn ? '✅ Bugün Giriş Yaptın!' : 'Günlük Check-In'; ?>
                        </button>
                        <button class="btn <?php echo $canSpin ? 'btn-warning' : 'btn-secondary'; ?> text-dark btn-sm" 
                                data-bs-toggle="modal" 
                                data-bs-target="#wheelModal"
                                <?php echo !$canSpin ? 'disabled' : ''; ?>>
                            <i class="fas fa-dharmachakra me-1"></i>
                            <?php echo $canSpin ? 'Çarkı Çevir! 🎰' : '✅ Bugün Çevirdin'; ?>
                        </button>
                    </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <span class="badge bg-info text-dark"><?php echo ucfirst($user['rol']); ?></span>
                    <?php if ($rol == 'musteri'): ?>
                        <span class="badge bg-warning text-dark"><i class="fas fa-star me-1"></i><?php echo $user['toplam_puan']; ?> Puan</span>
                    <?php endif; ?>
                </div>
                
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
                    <?php
                        // Rezervasyonları Kategorize Et
                        $aktif_rez = [];
                        $gecmis_rez = [];
                        $iptal_rez = [];

                        foreach ($rezervasyonlar as $r) {
                            if ($r['durum'] == 'iptal') {
                                $iptal_rez[] = $r;
                            } elseif ($r['durum'] == 'tamamlandi' || strtotime($r['tarih']) < strtotime(date('Y-m-d'))) {
                                // Tamamlandı veya tarihi geçmişse geçmişe at
                                $gecmis_rez[] = $r;
                            } else {
                                // Gelecek ve onaylı/bekleyen
                                $aktif_rez[] = $r;
                            }
                        }
                    ?>

                    <!-- TAB MENÜSÜ -->
                    <ul class="nav nav-tabs nav-fill mb-3" id="rezTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold" id="aktif-tab" data-bs-toggle="tab" data-bs-target="#aktif" type="button" role="tab">
                                <i class="fas fa-calendar-check me-2"></i>Aktif
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" id="gecmis-tab" data-bs-toggle="tab" data-bs-target="#gecmis" type="button" role="tab">
                                <i class="fas fa-history me-2"></i>Geçmiş
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-danger" id="iptal-tab" data-bs-toggle="tab" data-bs-target="#iptal" type="button" role="tab">
                                <i class="fas fa-ban me-2"></i>İptal
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-warning" id="oduller-tab" data-bs-toggle="tab" data-bs-target="#oduller" type="button" role="tab">
                                <i class="fas fa-trophy me-2"></i>Ödüllerim
                            </button>
                        </li>
                        <?php if ($rol == 'musteri'): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-info" id="questler-tab" data-bs-toggle="tab" data-bs-target="#questler" type="button" role="tab">
                                <i class="fas fa-tasks me-2"></i>Görevler
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-danger" id="favoriler-tab" data-bs-toggle="tab" data-bs-target="#favoriler" type="button" role="tab">
                                <i class="fas fa-heart me-2"></i>Favorilerim
                            </button>
                        </li>
                        <?php endif; ?>
                    </ul>

                    <!-- TAB İÇERİKLERİ -->
                    <div class="tab-content" id="rezTabsContent">
                        
                        <!-- 1. AKTİF REZERVASYONLAR -->
                        <div class="tab-pane fade show active" id="aktif" role="tabpanel">
                            <?php if (count($aktif_rez) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Tarih</th>
                                                <th>Tesis</th>
                                                <th>Durum</th>
                                                <th>İşlem</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($aktif_rez as $rez): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-bold"><?php echo date("d.m.Y", strtotime($rez['tarih'])); ?></div>
                                                        <small class="text-muted"><?php echo substr($rez['baslangic_saati'], 0, 5); ?></small>
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold"><?php echo $rez['tesis_adi']; ?></div>
                                                        <small class="text-muted"><?php echo $rez['saha_adi']; ?></small>
                                                    </td>
                                                    <td>
                                                        <?php if($rez['durum'] == 'onay_bekliyor'): ?>
                                                            <span class="badge bg-warning text-dark">Onay Bekliyor</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success">Onaylandı</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if($rez['durum'] == 'onay_bekliyor'): ?>
                                                            <button class="btn btn-sm btn-outline-danger" 
                                                                    data-bs-toggle="modal" data-bs-target="#iptalModal"
                                                                    onclick="iptalModalHazirla(<?php echo $rez['rezervasyon_id']; ?>)">
                                                                <i class="fas fa-times"></i> İptal
                                                            </button>
                                                        <?php else: ?>
                                                            <small class="text-muted">İşlem Yok</small>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">Aktif rezervasyonunuz bulunmuyor.</div>
                            <?php endif; ?>
                        </div>

                        <!-- 2. GEÇMİŞ REZERVASYONLAR -->
                        <div class="tab-pane fade" id="gecmis" role="tabpanel">
                            <?php if (count($gecmis_rez) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($gecmis_rez as $rez): ?>
                                        <div class="list-group-item p-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="fw-bold mb-1">
                                                        <i class="fas fa-check-circle text-success me-2"></i>
                                                        <?php echo $rez['tesis_adi']; ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <i class="far fa-calendar-alt me-1"></i> <?php echo date("d.m.Y", strtotime($rez['tarih'])); ?> 
                                                        <i class="far fa-clock ms-2 me-1"></i> <?php echo substr($rez['baslangic_saati'], 0, 5); ?>
                                                    </small>
                                                </div>
                                                <div class="text-end">
                                                    <button class="btn btn-sm btn-warning text-dark fw-bold mb-1" 
                                                            data-bs-toggle="modal" data-bs-target="#yorumModal"
                                                            onclick="yorumModalHazirla(<?php echo $rez['tesis_id']; ?>, '<?php echo htmlspecialchars($rez['tesis_adi'], ENT_QUOTES); ?>')">
                                                        <i class="fas fa-star"></i> Puanla
                                                    </button>
                                                    <br>
                                                    <a href="rezervasyon_yap.php?saha_id=<?php echo $rez['saha_id']; ?>" class="btn btn-sm btn-outline-primary" title="Tekrar Kirala">
                                                        <i class="fas fa-redo"></i> Tekrar
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">Geçmiş maç kaydı bulunamadı.</div>
                            <?php endif; ?>
                        </div>

                        <!-- 3. İPTAL EDİLENLER -->
                        <div class="tab-pane fade" id="iptal" role="tabpanel">
                            <?php if (count($iptal_rez) > 0): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($iptal_rez as $rez): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center opacity-75">
                                            <div>
                                                <span class="text-decoration-line-through fw-bold"><?php echo $rez['tesis_adi']; ?></span>
                                                <br>
                                                <small><?php echo date("d.m.Y", strtotime($rez['tarih'])); ?></small>
                                            </div>
                                            <span class="badge bg-danger">İptal Edildi</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">İptal edilmiş rezervasyon yok.</div>
                            <?php endif; ?>
                        </div>

                        <!-- 4. ÖDÜLLER VE ROZETLER -->
                        <div class="tab-pane fade" id="oduller" role="tabpanel">
                            <div class="p-3">
                                <!-- ROZETLER -->
                                <h6 class="fw-bold mb-3"><i class="fas fa-medal me-2 text-warning"></i>Rozetlerim</h6>
                                <div class="row row-cols-2 row-cols-md-4 g-3 mb-4">
                                    <?php foreach ($rozetler as $rozet): ?>
                                        <div class="col">
                                            <div class="card h-100 text-center p-2 <?php echo $rozet['kazanilma_tarihi'] ? 'border-warning shadow-sm' : 'opacity-50 grayscale'; ?>">
                                                <div class="card-body p-1">
                                                    <i class="<?php echo $rozet['ikon']; ?> fa-2x mb-2 <?php echo $rozet['kazanilma_tarihi'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                    <h6 class="card-title small fw-bold mb-1"><?php echo $rozet['rozet_adi']; ?></h6>
                                                    <p class="card-text x-small text-muted" style="font-size: 0.7rem;"><?php echo $rozet['aciklama']; ?></p>
                                                    <?php if ($rozet['kazanilma_tarihi']): ?>
                                                        <span class="badge bg-success" style="font-size: 0.6rem;">Kazanıldı</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <hr>

                                <!-- KUPONLAR -->
                                <h6 class="fw-bold mb-3"><i class="fas fa-ticket-alt me-2 text-success"></i>Kuponlarım</h6>
                                
                                <!-- Mevcut Kuponlar -->
                                <?php if (count($kullaniciKuponlari) > 0): ?>
                                    <div class="list-group mb-4">
                                        <?php foreach ($kullaniciKuponlari as $kk): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center bg-light">
                                                <div>
                                                    <h6 class="fw-bold mb-0 text-success"><?php echo $kk['kupon_kodu']; ?></h6>
                                                    <small>%<?php echo $kk['indirim_orani']; ?> İndirim</small>
                                                </div>
                                                <span class="badge bg-secondary">Kullanılmadı</span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted small mb-4">Henüz aktif kuponunuz yok.</p>
                                <?php endif; ?>

                                <!-- Kupon Market -->
                                <h6 class="fw-bold mb-2">Puan Harca</h6>
                                <div class="row g-2">
                                    <?php foreach ($kuponlar as $kupon): ?>
                                        <div class="col-md-4">
                                            <div class="border rounded p-2 text-center">
                                                <div class="fw-bold text-primary"><?php echo $kupon['kupon_kodu']; ?></div>
                                                <div class="small text-muted mb-2">%<?php echo $kupon['indirim_orani']; ?> İndirim</div>
                                                <button class="btn btn-sm btn-outline-primary w-100" onclick="redeemCoupon(<?php echo $kupon['kupon_id']; ?>)">
                                                    <?php echo $kupon['gerekli_puan']; ?> Puan
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <hr>

                                <!-- PUAN GEÇMİŞİ -->
                                <h6 class="fw-bold mb-3"><i class="fas fa-history me-2"></i>Puan Geçmişi</h6>
                                <ul class="list-group list-group-flush small">
                                    <?php foreach ($puanGecmisi as $pg): ?>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span><?php echo $pg['aciklama']; ?></span>
                                            <span class="badge bg-light text-dark">+<?php echo $pg['puan']; ?> Puan</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- 5. GÖREVLER (QUESTS) -->
                        <div class="tab-pane fade" id="questler" role="tabpanel">
                            <div class="p-3">
                                <!-- GÜNLÜK GÖREVLER -->
                                <h6 class="fw-bold mb-3 text-primary"><i class="fas fa-sun me-2 text-warning"></i>Günlük Görevler</h6>
                                <div class="list-group mb-4">
                                    <?php foreach ($dailyQuests as $quest): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center <?php echo $quest['tamamlandi'] ? 'bg-light' : ''; ?>">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3 text-center" style="width: 40px;">
                                                    <i class="<?php echo $quest['ikon']; ?> fa-lg <?php echo $quest['tamamlandi'] ? 'text-success' : 'text-muted'; ?>"></i>
                                                </div>
                                                <div>
                                                    <h6 class="fw-bold mb-0 <?php echo $quest['tamamlandi'] ? 'text-decoration-line-through text-muted' : ''; ?>">
                                                        <?php echo $quest['baslik']; ?>
                                                    </h6>
                                                    <small class="text-muted"><?php echo $quest['aciklama']; ?></small>
                                                    
                                                    <?php if (!$quest['tamamlandi']): ?>
                                                        <div class="progress mt-1" style="height: 5px; width: 150px;">
                                                            <div class="progress-bar bg-warning" role="progressbar" 
                                                                 style="width: <?php echo ($quest['ilerleme'] / $quest['hedef_sayi']) * 100; ?>%"></div>
                                                        </div>
                                                        <small class="text-muted" style="font-size: 0.7rem;">
                                                            <?php echo $quest['ilerleme']; ?>/<?php echo $quest['hedef_sayi']; ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div>
                                                <?php if ($quest['tamamlandi']): ?>
                                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Tamamlandı</span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark border">+<?php echo $quest['odul_puan']; ?> Puan</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <hr>
                                
                                <h6 class="fw-bold mb-3 text-primary"><i class="fas fa-calendar-week me-2 text-info"></i>Haftalık Görevler</h6>
                                <div class="list-group">
                                    <?php foreach ($weeklyQuests as $quest): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center <?php echo $quest['tamamlandi'] ? 'bg-light' : ''; ?>">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3 text-center" style="width: 40px;">
                                                    <i class="<?php echo $quest['ikon']; ?> fa-lg <?php echo $quest['tamamlandi'] ? 'text-success' : 'text-muted'; ?>"></i>
                                                </div>
                                                <div>
                                                    <h6 class="fw-bold mb-0 <?php echo $quest['tamamlandi'] ? 'text-decoration-line-through text-muted' : ''; ?>">
                                                        <?php echo $quest['baslik']; ?>
                                                    </h6>
                                                    <small class="text-muted"><?php echo $quest['aciklama']; ?></small>
                                                    
                                                    <?php if (!$quest['tamamlandi']): ?>
                                                        <div class="progress mt-1" style="height: 5px; width: 150px;">
                                                            <div class="progress-bar bg-info" role="progressbar" 
                                                                 style="width: <?php echo ($quest['ilerleme'] / $quest['hedef_sayi']) * 100; ?>%"></div>
                                                        </div>
                                                        <small class="text-muted" style="font-size: 0.7rem;">
                                                            <?php echo $quest['ilerleme']; ?>/<?php echo $quest['hedef_sayi']; ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div>
                                                <?php if ($quest['tamamlandi']): ?>
                                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Tamamlandı</span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark border">+<?php echo $quest['odul_puan']; ?> Puan</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- 6. FAVORİLER -->
                        <div class="tab-pane fade" id="favoriler" role="tabpanel">
                            <div class="p-3">
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
                                    <div class="text-center py-5">
                                        <i class="far fa-heart fa-3x text-muted mb-3"></i>
                                        <p class="text-muted mb-0">Henüz favori tesisiniz yok.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

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

function redeemCoupon(kuponId) {
    if (!confirm('Bu kuponu almak için puan harcamak istiyor musunuz?')) return;

    fetch('ajax_coupon.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `kupon_id=${kuponId}`
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.success) location.reload();
    })
    .catch(error => console.error('Hata:', error));
}
</script>

<script>
// GÜNLÜK CHECK-IN
function dailyCheckIn() {
    fetch('ajax_checkin.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Başarılı modalı göster
            let message = `
                <div class="text-center">
                    <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                    <h4>Harika!</h4>
                    <p class="lead">${data.message}</p>
                    <div class="badge bg-warning text-dark fs-6 p-2">
                        🔥 ${data.streak} Gün Streak
                    </div>
                </div>
            `;
            
            if (data.bonus_earned) {
                message += `
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-gift me-2"></i> BONUS KAZANDINIZ!
                    </div>
                `;
            }
            
            // Basit bir alert yerine özel modal kullanılabilir ama şimdilik reload
            alert("Tebrikler! Check-in başarılı.\n+" + data.puan + " Puan kazandınız!");
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Hata:', error));
}

// ÇARK ÇEVİRME
let wheelSpinning = false;



// Çarkı Çizme Fonksiyonu
function drawWheel() {
    const canvas = document.getElementById('wheelCanvas');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;
    const centerX = width / 2;
    const centerY = height / 2;
    const radius = Math.min(width, height) / 2 - 10;
    
    // Ödüller (WheelService.php'den alındı)
    const segments = [
        { label: '10 Puan', color: '#FFC107', text: '#000' },
        { label: '25 Puan', color: '#FF9800', text: '#FFF' },
        { label: '50 Puan', color: '#FF5722', text: '#FFF' },
        { label: '75 Puan', color: '#F44336', text: '#FFF' },
        { label: '100 Puan', color: '#E91E63', text: '#FFF' },
        { label: '200 Puan', color: '#9C27B0', text: '#FFF' },
        { label: '%10 İndirim', color: '#3F51B5', text: '#FFF' },
        { label: 'Şanslı Rozet', color: '#00BCD4', text: '#FFF' }
    ];
    
    const segmentAngle = (2 * Math.PI) / segments.length;
    
    // Temizle
    ctx.clearRect(0, 0, width, height);
    
    // Dilimleri çiz
    segments.forEach((segment, i) => {
        const startAngle = i * segmentAngle;
        const endAngle = (i + 1) * segmentAngle;
        
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, startAngle, endAngle);
        ctx.closePath();
        
        ctx.fillStyle = segment.color;
        ctx.fill();
        ctx.stroke();
        
        // Metin
        ctx.save();
        ctx.translate(centerX, centerY);
        ctx.rotate(startAngle + segmentAngle / 2);
        ctx.textAlign = "right";
        ctx.fillStyle = segment.text;
        ctx.font = "bold 14px Arial";
        ctx.fillText(segment.label, radius - 20, 5);
        ctx.restore();
    });
    
    // Dış çember
    ctx.beginPath();
    ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
    ctx.lineWidth = 5;
    ctx.strokeStyle = '#333';
    ctx.stroke();
    
    // İç çember (Merkez)
    ctx.beginPath();
    ctx.arc(centerX, centerY, 20, 0, 2 * Math.PI);
    ctx.fillStyle = '#FFF';
    ctx.fill();
    ctx.stroke();
    
    // Ok işareti (Sağ tarafta)
    ctx.beginPath();
    ctx.moveTo(width - 10, centerY);
    ctx.lineTo(width + 10, centerY - 10);
    ctx.lineTo(width + 10, centerY + 10);
    ctx.closePath();
    ctx.fillStyle = '#333';
    ctx.fill();
}

// Çark Çevirme Fonksiyonu (Vanilla JS Modal Kapatma)
function spinWheel() {
    if (wheelSpinning) return;
    wheelSpinning = true;
    
    const spinButton = document.querySelector('#wheelModal button.btn-primary');
    spinButton.disabled = true;
    spinButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Çevriliyor...';
    
    // API isteği
    fetch('ajax_wheel.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Animasyon simülasyonu
            let duration = 3000; // 3 saniye
            let start = null;
            const canvas = document.getElementById('wheelCanvas');
            const ctx = canvas.getContext('2d');
            
            // Basit dönme animasyonu
            function animate(timestamp) {
                if (!start) start = timestamp;
                let progress = timestamp - start;
                
                if (progress < duration) {
                    // Dönme efekti için canvas'ı döndür
                    // Gerçekçi bir dönme için tüm çizimi döndürmek gerekir
                    // Şimdilik sadece bekleme süresi olarak kalsın, görsel dönme sonra eklenebilir
                    requestAnimationFrame(animate);
                } else {
                    // Bitti
                    wheelSpinning = false;
                    
                    // Modalı kapat (Vanilla JS)
                    const modalInstance = bootstrap.Modal.getInstance(wheelModalEl);
                    modalInstance.hide();
                    
                    // Sonuç göster
                    alert(data.message);
                    location.reload();
                }
            }
            requestAnimationFrame(animate);
            
        } else {
            wheelSpinning = false;
            spinButton.disabled = false;
            spinButton.innerText = 'Çevir!';
            alert(data.message);
        }
    })
    .catch(error => {
        wheelSpinning = false;
        spinButton.disabled = false;
        spinButton.innerText = 'Çevir!';
        console.error('Hata:', error);
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
    });
}
</script>

<!-- ÇARK MODALI -->
<div class="modal fade" id="wheelModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title fw-bold text-dark"><i class="fas fa-dharmachakra me-2"></i>Şans Çarkı</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center bg-light">
        <div class="mb-3">
            <canvas id="wheelCanvas" width="400" height="400" style="max-width: 100%;"></canvas>
        </div>
        <p class="text-muted">Her gün 1 kez çevirme hakkın var!</p>
        <button class="btn btn-primary btn-lg px-5 rounded-pill shadow" onclick="spinWheel()">
            <i class="fas fa-sync-alt me-2"></i>Çevir!
        </button>
      </div>
    </div>
  </div>
</div>

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