<?php
// 1. Veritabanı ve Header'ı dahil et
require_once 'includes/db.php';
include 'includes/header.php';

// Eğer kullanıcı zaten giriş yapmışsa anasayfaya at
if (isset($_SESSION['kullanici_id'])) {
    header("Location: index.php");
    exit;
}

$hata_mesaji = "";

// 2. Form Gönderildi mi Kontrol Et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $eposta = trim($_POST['eposta']);
    $sifre = $_POST['sifre'];

    if (empty($eposta) || empty($sifre)) {
        $hata_mesaji = "Lütfen tüm alanları doldurun.";
    } else {
        try {
            // 3. STORED PROCEDURE ÇAĞRISI
            $sql = "CALL sp_KullaniciGiris(?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$eposta]);
            
            // Sonucu al
            $kullanici = $stmt->fetch();
            
            // PDO kuralı: Sonraki sorgular için imleci kapat
            $stmt->closeCursor(); 

            // 4. Kullanıcı var mı ve Şifre Doğru mu?
            if ($kullanici && password_verify($sifre, $kullanici['sifre'])) {
                // GİRİŞ BAŞARILI
                
                // Session Değişkenlerini Ata
                $_SESSION['kullanici_id'] = $kullanici['kullanici_id'];
                $_SESSION['ad'] = $kullanici['ad'];
                $_SESSION['soyad'] = $kullanici['soyad'];
                $_SESSION['rol'] = $kullanici['rol']; // admin, musteri, tesis_sahibi
                
                // Role göre özel ID'leri de session'a at (İşlemlerde kolaylık sağlar)
                if ($kullanici['rol'] == 'musteri') {
                    $_SESSION['rol_id'] = $kullanici['musteri_id'];
                } elseif ($kullanici['rol'] == 'tesis_sahibi') {
                    $_SESSION['rol_id'] = $kullanici['sahip_id'];
                } elseif ($kullanici['rol'] == 'admin') {
                    $_SESSION['rol_id'] = $kullanici['admin_id'];
                }

                // Anasayfaya Yönlendir
                header("Location: index.php");
                exit;

            } else {
                // GİRİŞ BAŞARISIZ
                $hata_mesaji = "E-posta adresi veya şifre hatalı!";
            }

        } catch (PDOException $e) {
            $hata_mesaji = "Veritabanı hatası: " . $e->getMessage();
        }
    }
}
?>

<!-- GİRİŞ SAYFASI ARAYÜZÜ -->
<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white text-center">
                <h3><i class="fas fa-lock me-2"></i>Giriş Yap</h3>
            </div>
            <div class="card-body p-4">
                
                <!-- Hata Mesajı Gösterimi -->
                <?php if (!empty($hata_mesaji)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $hata_mesaji; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="eposta" class="form-label">E-Posta Adresi</label>
                        <input type="email" class="form-control" id="eposta" name="eposta" required placeholder="ornek@mail.com">
                    </div>
                    <div class="mb-3">
                        <label for="sifre" class="form-label">Şifre</label>
                        <input type="password" class="form-control" id="sifre" name="sifre" required placeholder="******">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Giriş Yap</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <small>Hesabınız yok mu? <a href="register.php">Hemen Kayıt Olun</a></small>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>