<?php
require_once 'includes/db.php';
include 'includes/header.php';

// Eğer zaten giriş yapmışsa anasayfaya gönder
if (isset($_SESSION['kullanici_id'])) {
    header("Location: index.php");
    exit;
}

$mesaj = "";

// Form Gönderildi mi?
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al ve temizle
    $ad = trim($_POST['ad']);
    $soyad = trim($_POST['soyad']);
    $eposta = trim($_POST['eposta']);
    $telefon = trim($_POST['telefon']);
    $cinsiyet = $_POST['cinsiyet'];
    $dogum_tarihi = $_POST['dogum_tarihi'];
    $sifre = $_POST['sifre'];
    $sifre_tekrar = $_POST['sifre_tekrar'];

    // Basit Doğrulamalar
    if ($sifre !== $sifre_tekrar) {
        $mesaj = "<div class='alert alert-danger'>Şifreler birbiriyle uyuşmuyor!</div>";
    } else {
        // Şifreyi Hash'le
        $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);

        try {
            // STORED PROCEDURE ÇAĞRISI (sp_MusteriKayit)
            // Bu prosedür hem Kullanicilar hem Musteriler tablosuna kayıt atar (Transaction ile)
            $sql = "CALL sp_MusteriKayit(?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                $ad, 
                $soyad, 
                $eposta, 
                $sifre_hash, 
                $telefon, 
                $cinsiyet, 
                $dogum_tarihi
            ]);

            // Başarılı ise Login'e yönlendir
            echo "<script>
                alert('Kayıt başarıyla oluşturuldu! Şimdi giriş yapabilirsiniz.');
                window.location.href = 'login.php';
            </script>";
            exit;

        } catch (PDOException $e) {
            // Hata yakalama (Örn: E-posta zaten varsa)
            if ($e->getCode() == 23000) { // Duplicate entry hatası
                $mesaj = "<div class='alert alert-warning'>Bu e-posta adresi zaten kayıtlı!</div>";
            } else {
                $mesaj = "<div class='alert alert-danger'>Veritabanı hatası: " . $e->getMessage() . "</div>";
            }
        }
    }
}
?>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0 mt-4">
                <div class="card-header bg-success text-white text-center py-3">
                    <h3 class="mb-0"><i class="fas fa-user-plus me-2"></i>Aramıza Katıl</h3>
                    <small>Halı Saha Rezervasyon Sistemi</small>
                </div>
                <div class="card-body p-4">
                    
                    <?php echo $mesaj; ?>

                    <form action="register.php" method="POST" class="needs-validation">
                        <!-- Ad Soyad Satırı -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Adınız</label>
                                <input type="text" name="ad" class="form-control" required placeholder="Örn: Ahmet">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Soyadınız</label>
                                <input type="text" name="soyad" class="form-control" required placeholder="Örn: Yılmaz">
                            </div>
                        </div>

                        <!-- E-posta ve Telefon -->
                        <div class="mb-3">
                            <label class="form-label">E-Posta Adresi</label>
                            <input type="email" name="eposta" class="form-control" required placeholder="ornek@mail.com">
                            <div class="form-text">Giriş yaparken bu adresi kullanacaksınız.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Cep Telefonu</label>
                            <input type="tel" name="telefon" class="form-control" required placeholder="0555 123 45 67">
                        </div>

                        <!-- Cinsiyet ve Doğum Tarihi -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Cinsiyet</label>
                                <select name="cinsiyet" class="form-select">
                                    <option value="Belirtilmemis">Belirtmek İstemiyorum</option>
                                    <option value="E">Erkek</option>
                                    <option value="K">Kadın</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Doğum Tarihi</label>
                                <input type="date" name="dogum_tarihi" class="form-control" required>
                            </div>
                        </div>

                        <hr>

                        <!-- Şifre Alanları -->
                        <div class="mb-3">
                            <label class="form-label">Şifre Belirle</label>
                            <input type="password" name="sifre" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Şifre Tekrar</label>
                            <input type="password" name="sifre_tekrar" class="form-control" required minlength="6">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">
                                Kaydı Tamamla <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center py-3">
                    Hesabınız var mı? <a href="login.php" class="text-decoration-none fw-bold">Giriş Yap</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>