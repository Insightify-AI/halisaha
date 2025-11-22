<?php
require_once 'includes/db.php';
require_once 'includes/GamificationService.php';
include 'includes/header.php';

$gamification = new GamificationService($pdo);
$leaderboard = $gamification->getLeaderboard(20);
?>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="text-center mb-5 mt-4">
                <h2 class="fw-bold text-primary"><i class="fas fa-trophy text-warning me-2"></i>Liderlik Tablosu</h2>
                <p class="text-muted">En aktif halı saha oyuncuları ve puanları</p>
            </div>

            <div class="card border-0 shadow-lg overflow-hidden">
                <div class="card-header bg-primary text-white p-3">
                    <div class="row fw-bold text-center">
                        <div class="col-2">Sıra</div>
                        <div class="col-6 text-start">Oyuncu</div>
                        <div class="col-4">Puan</div>
                    </div>
                </div>
                <div class="list-group list-group-flush">
                    <?php $rank = 1; ?>
                    <?php foreach ($leaderboard as $user): ?>
                        <div class="list-group-item p-3 <?php echo $rank <= 3 ? 'bg-light' : ''; ?>">
                            <div class="row align-items-center text-center">
                                <div class="col-2">
                                    <?php if ($rank == 1): ?>
                                        <i class="fas fa-crown text-warning fa-2x"></i>
                                    <?php elseif ($rank == 2): ?>
                                        <i class="fas fa-medal text-secondary fa-2x"></i>
                                    <?php elseif ($rank == 3): ?>
                                        <i class="fas fa-medal text-danger fa-2x"></i>
                                    <?php else: ?>
                                        <span class="fw-bold fs-5 text-muted">#<?php echo $rank; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-6 text-start">
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['ad'] . ' ' . $user['soyad']); ?>&background=random" class="rounded-circle me-3" width="40">
                                        <div>
                                            <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($user['ad'] . ' ' . substr($user['soyad'], 0, 1) . '.'); ?></h6>
                                            <small class="text-muted"><?php echo $user['rezervasyon_sayisi']; ?> Maç</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <span class="badge bg-success rounded-pill px-3 py-2 fs-6">
                                        <?php echo number_format($user['toplam_puan']); ?> P
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php $rank++; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="profil.php" class="btn btn-outline-primary">Kendi Puanını Gör</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
