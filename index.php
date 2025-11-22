<?php
require_once 'includes/db.php';
include 'includes/header.php';

// 1. VERİ ÇEKME İŞLEMLERİ

// İstatistikler
$stats = [
    'tesis' => $pdo->query("SELECT COUNT(*) FROM Tesisler WHERE onay_durumu = 1")->fetchColumn(),
    'uye' => $pdo->query("SELECT COUNT(*) FROM Kullanicilar WHERE rol = 'musteri'")->fetchColumn(),
    'mac' => $pdo->query("SELECT COUNT(*) FROM Rezervasyonlar WHERE durum = 'tamamlandi'")->fetchColumn(),
    'puan' => 4.8 // Ortalama puan (şimdilik sabit)
];

// Popüler Tesisler (Puan ve Yorum Sayısına Göre)
$populerTesisler = $pdo->query("
    SELECT t.*, s.sehir_adi, i.ilce_adi, 
           COALESCE(AVG(y.puan), 0) as ortalama_puan,
           COUNT(y.yorum_id) as yorum_sayisi
    FROM Tesisler t
    JOIN Ilceler i ON t.ilce_id = i.ilce_id
    JOIN Sehirler s ON i.sehir_id = s.sehir_id
    LEFT JOIN Yorumlar y ON t.tesis_id = y.tesis_id AND y.onay_durumu = 'Onaylandı'
    WHERE t.onay_durumu = 1
    GROUP BY t.tesis_id
    ORDER BY ortalama_puan DESC, yorum_sayisi DESC
    LIMIT 3
")->fetchAll();

// En Uygun Fiyatlı Tesisler
$uygunFiyatliTesisler = $pdo->query("
    SELECT t.tesis_adi, MIN(sa.fiyat_saatlik) as en_dusuk_fiyat
    FROM Tesisler t
    JOIN Sahalar sa ON t.tesis_id = sa.tesis_id
    WHERE t.onay_durumu = 1
    GROUP BY t.tesis_id
    ORDER BY en_dusuk_fiyat ASC
    LIMIT 5
")->fetchAll();

// Son Yorumlar
$sonYorumlar = $pdo->query("
    SELECT y.*, k.ad, k.soyad, t.tesis_adi
    FROM Yorumlar y
    JOIN Kullanicilar k ON y.musteri_id = k.kullanici_id
    JOIN Tesisler t ON y.tesis_id = t.tesis_id
    WHERE y.onay_durumu = 'Onaylandı'
    ORDER BY y.tarih DESC
    LIMIT 5
")->fetchAll();

// Şehirler ve Saha Sayıları
$sehirIstatistikleri = $pdo->query("
    SELECT s.sehir_adi, s.sehir_id, COUNT(t.tesis_id) as tesis_sayisi
    FROM Sehirler s
    LEFT JOIN Ilceler i ON s.sehir_id = i.sehir_id
    LEFT JOIN Tesisler t ON i.ilce_id = t.ilce_id AND t.onay_durumu = 1
    GROUP BY s.sehir_id
    ORDER BY tesis_sayisi DESC
    LIMIT 4
")->fetchAll();

// Fiyat Trendi (Şehirlere Göre Ortalama)
$fiyatTrendi = $pdo->query("
    SELECT s.sehir_adi, AVG(sa.fiyat_saatlik) as ortalama_fiyat
    FROM Sahalar sa
    JOIN Tesisler t ON sa.tesis_id = t.tesis_id
    JOIN Ilceler i ON t.ilce_id = i.ilce_id
    JOIN Sehirler s ON i.sehir_id = s.sehir_id
    GROUP BY s.sehir_id
")->fetchAll();

// Şu Anda Müsait Tesisler (random 3 available)
$musaitTesisler = $pdo->query("SELECT t.tesis_adi, sa.saha_adi, sa.fiyat_saatlik
    FROM Tesisler t
    JOIN Sahalar sa ON t.tesis_id = sa.tesis_id
    WHERE t.onay_durumu = 1
    ORDER BY RAND()
    LIMIT 3")->fetchAll();

// Öne Çıkan Özellikler
$ozellikStats = $pdo->query("
    SELECT o.ozellik_adi, o.ikon_kodu, COUNT(toz.tesis_id) as tesis_sayisi
    FROM Ozellikler o
    JOIN tesisozellikleri toz ON o.ozellik_id = toz.ozellik_id
    GROUP BY o.ozellik_id
    ORDER BY tesis_sayisi DESC
    LIMIT 4
")->fetchAll();

// Haftalık Liderler
$haftalikLiderler = $pdo->query("
    SELECT * FROM v_HaftalikPuanlar 
    LIMIT 5
")->fetchAll();
?>

<!-- HERO SECTION & STATS -->
<div class="bg-dark text-white py-5 mb-5" style="background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1529900748604-07564a03e7a6?q=80&w=1920&auto=format&fit=crop'); background-size: cover; background-position: center;">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-4">Halı Saha Bulmanın En Kolay Yolu</h1>
        <p class="lead mb-5">İstediğin şehirde, istediğin saatte, en uygun fiyatlarla halı saha kirala.</p>
        
        <!-- İSTATİSTİKLER -->
        <div class="row justify-content-center mb-5">
            <div class="col-md-8">
                <div class="row bg-white text-dark rounded shadow p-3 mx-1">
                    <div class="col-3 border-end border-secondary">
                        <h4 class="fw-bold mb-0 counter" data-target="<?php echo $stats['tesis']; ?>">0</h4>
                        <small class="text-muted">Tesis</small>
                    </div>
                    <div class="col-3 border-end border-secondary">
                        <h4 class="fw-bold mb-0 counter" data-target="<?php echo $stats['uye']; ?>">0</h4>
                        <small class="text-muted">Üye</small>
                    </div>
                    <div class="col-3 border-end border-secondary">
                        <h4 class="fw-bold mb-0 counter" data-target="<?php echo $stats['mac']; ?>">0</h4>
                        <small class="text-muted">Maç</small>
                    </div>
                    <div class="col-3">
                        <h4 class="fw-bold mb-0 counter" data-target="480">0</h4>
                        <small class="text-muted">Puan</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. ŞEHİRLERE GÖRE ARA -->
        <div class="row justify-content-center g-3 mb-4">
            <?php foreach ($sehirIstatistikleri as $sehir): ?>
                <div class="col-6 col-md-3">
                    <a href="arama.php?sehir_id=<?php echo $sehir['sehir_id']; ?>" class="btn btn-outline-light btn-lg w-100 py-3 position-relative overflow-hidden group-hover">
                        <span class="d-block fw-bold"><?php echo $sehir['sehir_adi']; ?></span>
                        <small class="d-block text-white-50"><?php echo $sehir['tesis_sayisi']; ?> Saha</small>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="row">
        <!-- SOL KOLON (ANA İÇERİK) -->
        <div class="col-lg-8">
            
            <!-- 2. BU HAFTANIN EN POPÜLERLERİ -->
            <div class="mb-5">
                <h3 class="fw-bold mb-4 border-start border-5 border-primary ps-3">🏆 Bu Haftanın En Popülerleri</h3>
                <div class="row g-4">
                    <?php foreach ($populerTesisler as $index => $tesis): ?>
                        <div class="col-md-12">
                            <div class="card shadow-sm border-0 hover-effect">
                                <div class="row g-0 align-items-center">
                                    <div class="col-md-4">
                                        <?php $resim = !empty($tesis['kapak_resmi']) ? $tesis['kapak_resmi'] : 'https://images.unsplash.com/photo-1551958219-acbc608c6377?q=80&w=400&auto=format&fit=crop'; ?>
                                        <img src="<?php echo $resim; ?>" class="img-fluid rounded-start h-100" style="object-fit: cover; min-height: 150px;" alt="...">
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <h5 class="card-title fw-bold">
                                                    <?php 
                                                    $madalya = ['🥇', '🥈', '🥉'];
                                                    echo ($madalya[$index] ?? '') . ' ' . $tesis['tesis_adi']; 
                                                    ?>
                                                </h5>
                                                <span class="badge bg-warning text-dark"><i class="fas fa-star"></i> <?php echo number_format($tesis['ortalama_puan'], 1); ?></span>
                                            </div>
                                            <p class="card-text text-muted mb-2"><i class="fas fa-map-marker-alt me-1"></i> <?php echo $tesis['sehir_adi'] . ', ' . $tesis['ilce_adi']; ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted"><?php echo $tesis['yorum_sayisi']; ?> Yorum</small>
                                                
                                                <!-- 9. SOSYAL PAYLAŞIM -->
                                                <div class="btn-group btn-group-sm">
                                                    <a href="#" class="btn btn-outline-primary" title="Facebook'ta Paylaş"><i class="fab fa-facebook"></i></a>
                                                    <a href="#" class="btn btn-outline-info" title="Twitter'da Paylaş"><i class="fab fa-twitter"></i></a>
                                                    <a href="https://wa.me/?text=Bu harika sahaya bak: <?php echo $tesis['tesis_adi']; ?>" target="_blank" class="btn btn-outline-success" title="WhatsApp'ta Paylaş"><i class="fab fa-whatsapp"></i></a>
                                                </div>
                                                
                                                <a href="tesis_detay.php?id=<?php echo $tesis['tesis_id']; ?>" class="btn btn-primary btn-sm">İncele</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 4. SON YORUMLAR (CAROUSEL) -->
            <div class="mb-5">
                <h3 class="fw-bold mb-4 border-start border-5 border-info ps-3">💬 Son Yorumlar</h3>
                <div id="commentsCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($sonYorumlar as $index => $yorum): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <div class="card border-0 bg-light p-4 text-center">
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <?php for($i=0; $i<$yorum['puan']; $i++) echo '<i class="fas fa-star text-warning"></i>'; ?>
                                        </div>
                                        <h5 class="card-title fst-italic">"<?php echo htmlspecialchars($yorum['yorum_metni']); ?>"</h5>
                                        <p class="card-text text-muted mt-3">
                                            <small class="fw-bold">- <?php echo $yorum['ad'] . ' ' . substr($yorum['soyad'], 0, 1) . '.'; ?></small>
                                            <br>
                                            <small class="text-muted"><?php echo $yorum['tesis_adi']; ?> (<?php echo date('d.m.Y', strtotime($yorum['tarih'])); ?>)</small>
                                        </p>
                                        <?php if($yorum['resim_yolu']): ?>
                                            <img src="<?php echo $yorum['resim_yolu']; ?>" class="rounded shadow-sm mt-2" style="height: 80px;">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#commentsCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon bg-dark rounded-circle" aria-hidden="true"></span>
                        <span class="visually-hidden">Önceki</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#commentsCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon bg-dark rounded-circle" aria-hidden="true"></span>
                        <span class="visually-hidden">Sonraki</span>
                    </button>
                </div>
            </div>
            
            <!-- 6. FİYAT TREND GRAFİĞİ -->
            <div class="mb-5">
                <h3 class="fw-bold mb-4 border-start border-5 border-success ps-3">📈 Fiyat Trendleri</h3>
                <div class="card border-0 shadow-sm p-3">
                    <canvas id="priceChart"></canvas>
                </div>
            </div>

        </div>

        <!-- SAĞ KOLON (YAN BİLEŞENLER) -->
        <div class="col-lg-4">
            
            <!-- HAFTALIK LİDERLER -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-warning text-dark fw-bold">
                    <i class="fas fa-trophy me-2"></i>Haftanın Liderleri
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($haftalikLiderler as $index => $lider): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-<?php echo $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : ($index == 2 ? 'danger' : 'light text-dark border')); ?> me-2 rounded-pill" style="width: 25px;">
                                    <?php echo $index + 1; ?>
                                </span>
                                <div class="fw-bold">
                                    <?php echo htmlspecialchars($lider['ad'] . ' ' . substr($lider['soyad'], 0, 1) . '.'); ?>
                                </div>
                            </div>
                            <span class="badge bg-primary rounded-pill"><?php echo $lider['haftalik_puan']; ?> P</span>
                        </li>
                    <?php endforeach; ?>
                    <?php if (count($haftalikLiderler) == 0): ?>
                        <li class="list-group-item text-center text-muted small">Henüz veri yok.</li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- 7. ŞU ANDA MÜSAİT -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-success text-white fw-bold">
                    <i class="fas fa-clock me-2"></i>Şu Anda Müsait
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($musaitTesisler as $mt): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0"><?php echo $mt['tesis_adi']; ?></h6>
                                <small class="text-muted"><?php echo $mt['saha_adi']; ?></small>
                            </div>
                            <span class="badge bg-light text-dark border"><?php echo date('H:00'); ?> - <?php echo date('H:00', strtotime('+1 hour')); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="card-footer text-center bg-white">
                    <a href="arama.php" class="btn btn-sm btn-outline-success w-100">Hemen Kirala</a>
                </div>
            </div>

            <!-- 3. EN UYGUN FİYATLAR -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="fas fa-tags me-2"></i>En Uygun Fiyatlar
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($uygunFiyatliTesisler as $utf): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?php echo $utf['tesis_adi']; ?></span>
                            <span class="fw-bold text-success"><?php echo number_format($utf['en_dusuk_fiyat'], 0); ?>₺</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- 8. ÖNE ÇIKAN ÖZELLİKLER -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-dark text-white fw-bold">
                    <i class="fas fa-star me-2"></i>Öne Çıkan Özellikler
                </div>
                <div class="card-body">
                    <div class="row text-center g-2">
                        <?php foreach ($ozellikStats as $os): ?>
                            <div class="col-6">
                                <div class="border rounded p-2 bg-light">
                                    <i class="fas <?php echo $os['ikon_kodu']; ?> fa-2x text-primary mb-2"></i>
                                    <h6 class="mb-0 small fw-bold"><?php echo $os['ozellik_adi']; ?></h6>
                                    <small class="text-muted"><?php echo $os['tesis_sayisi']; ?> Tesis</small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Chart.js Kütüphanesi -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Sayaç Animasyonu
document.querySelectorAll('.counter').forEach(counter => {
    const target = +counter.getAttribute('data-target');
    const increment = target / 100;
    
    const updateCounter = () => {
        const c = +counter.innerText;
        if (c < target) {
            counter.innerText = Math.ceil(c + increment);
            setTimeout(updateCounter, 20);
        } else {
            counter.innerText = target;
        }
    };
    updateCounter();
});

// Fiyat Grafiği
const ctx = document.getElementById('priceChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($fiyatTrendi, 'sehir_adi')); ?>,
        datasets: [{
            label: 'Ortalama Saatlik Fiyat (₺)',
            data: <?php echo json_encode(array_column($fiyatTrendi, 'ortalama_fiyat')); ?>,
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>