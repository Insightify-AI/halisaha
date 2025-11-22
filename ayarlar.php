<?php
require_once 'includes/db.php';
include 'includes/header.php';

// GÜVENLİK: Giriş yapmamışsa Login'e at
if (!isset($_SESSION['kullanici_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$kullanici_id = $_SESSION['kullanici_id'];

// Kullanıcı bilgilerini çek
$stmt = $pdo->prepare("SELECT * FROM Kullanicilar WHERE kullanici_id = ?");
$stmt->execute([$kullanici_id]);
$user = $stmt->fetch();
?>

<div class="container mb-5">
    <div class="row justify-content-center mt-5">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Ayarlar</h5>
                </div>
                <div class="card-body p-4">
                    
                    <!-- Hata/Başarı Mesajları -->
                    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i> Bilgileriniz başarıyla güncellendi.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> 
                            <?php echo isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : 'Bir hata oluştu.'; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="ayarlar_kaydet.php" method="POST">
                        <h6 class="fw-bold text-secondary mb-3">Kişisel Bilgiler</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Ad</label>
                                <input type="text" name="ad" class="form-control" value="<?php echo htmlspecialchars($user['ad']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Soyad</label>
                                <input type="text" name="soyad" class="form-control" value="<?php echo htmlspecialchars($user['soyad']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">E-posta</label>
                                <input type="email" class="form-control bg-light" value="<?php echo htmlspecialchars($user['eposta']); ?>" readonly>
                                <small class="text-muted">E-posta adresi değiştirilemez.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Telefon</label>
                                <input type="tel" name="telefon" class="form-control" value="<?php echo htmlspecialchars($user['telefon']); ?>" required>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-secondary mb-0">Güvenlik</h6>
                            <button type="button" class="btn btn-outline-warning btn-sm" id="btnSifreDegistir" onclick="togglePasswordFields()">
                                <i class="fas fa-key me-1"></i> Şifre Değiştir
                            </button>
                        </div>

                        <div id="passwordFields" style="display: none;">
                            <div class="alert alert-warning small">
                                <i class="fas fa-lock me-1"></i> Şifrenizi değiştirmek için mevcut şifrenizi ve yeni şifrenizi girin.
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Mevcut Şifre</label>
                                <input type="password" name="mevcut_sifre" class="form-control">
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Yeni Şifre</label>
                                    <input type="password" name="yeni_sifre" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Yeni Şifre (Tekrar)</label>
                                    <input type="password" name="yeni_sifre_tekrar" class="form-control">
                                </div>
                            </div>
                        </div>

                        <script>
                        function togglePasswordFields() {
                            var fields = document.getElementById('passwordFields');
                            var btn = document.getElementById('btnSifreDegistir');
                            
                            if (fields.style.display === 'none') {
                                fields.style.display = 'block';
                                btn.innerHTML = '<i class="fas fa-times me-1"></i> İptal';
                                btn.classList.remove('btn-outline-warning');
                                btn.classList.add('btn-outline-secondary');
                            } else {
                                fields.style.display = 'none';
                                btn.innerHTML = '<i class="fas fa-key me-1"></i> Şifre Değiştir';
                                btn.classList.remove('btn-outline-secondary');
                                btn.classList.add('btn-outline-warning');
                                // Temizle
                                document.getElementsByName('mevcut_sifre')[0].value = '';
                                document.getElementsByName('yeni_sifre')[0].value = '';
                                document.getElementsByName('yeni_sifre_tekrar')[0].value = '';
                            }
                        }
                        </script>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Değişiklikleri Kaydet
                            </button>
                            <a href="profil.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Profile Dön
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
