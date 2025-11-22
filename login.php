<?php
// 1. Veritabanı ve Header'ı dahil et
require_once 'includes/db.php';
$pageTitle = "Giriş Yap";
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

<style>
    body {
        position: relative;
        overflow-x: hidden;
    }
    
    /* Animated Background Particles */
    .particles {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 0;
        pointer-events: none;
    }
    
    .particle {
        position: absolute;
        background: rgba(13, 110, 253, 0.1);
        border-radius: 50%;
        animation: float 15s infinite ease-in-out;
    }
    
    .particle:nth-child(1) {
        width: 80px;
        height: 80px;
        top: 10%;
        left: 10%;
        animation-delay: 0s;
        background: rgba(13, 110, 253, 0.08);
    }
    
    .particle:nth-child(2) {
        width: 120px;
        height: 120px;
        top: 70%;
        right: 15%;
        animation-delay: 2s;
        background: rgba(13, 110, 253, 0.06);
    }
    
    .particle:nth-child(3) {
        width: 60px;
        height: 60px;
        bottom: 15%;
        left: 50%;
        animation-delay: 4s;
        background: rgba(13, 110, 253, 0.1);
    }
    
    .particle:nth-child(4) {
        width: 100px;
        height: 100px;
        top: 30%;
        right: 30%;
        animation-delay: 6s;
        background: rgba(102, 126, 234, 0.07);
    }
    
    .particle:nth-child(5) {
        width: 70px;
        height: 70px;
        top: 50%;
        left: 20%;
        animation-delay: 3s;
        background: rgba(13, 110, 253, 0.09);
    }
    
    .particle:nth-child(6) {
        width: 90px;
        height: 90px;
        bottom: 30%;
        right: 10%;
        animation-delay: 5s;
        background: rgba(13, 110, 253, 0.05);
    }
    
    @keyframes float {
        0%, 100% {
            transform: translate(0, 0) scale(1);
            opacity: 0.5;
        }
        33% {
            transform: translate(30px, -30px) scale(1.1);
            opacity: 0.8;
        }
        66% {
            transform: translate(-20px, 20px) scale(0.9);
            opacity: 0.6;
        }
    }
    
    .auth-page {
        min-height: calc(100vh - 400px);
        display: flex;
        align-items: center;
        padding: 60px 0;
        position: relative;
        z-index: 1;
    }
    
    .auth-card {
        max-width: 450px;
        margin: 0 auto;
    }
    
    .login-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 30px;
        box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
    }
    
    .login-icon i {
        font-size: 2.5rem;
        color: white;
    }
    
    .form-control:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
    }
    
    .btn-login {
        padding: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    @media (max-width: 576px) {
        .auth-page {
            padding: 30px 0;
        }
        
        .login-icon {
            width: 60px;
            height: 60px;
        }
        
        .login-icon i {
            font-size: 2rem;
        }
    }
</style>

<!-- Animated Particles Background -->
<div class="particles">
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
</div>

<div class="auth-page">
    <div class="container">
        <div class="auth-card">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="login-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h2 class="fw-bold mb-2">Hoş Geldiniz!</h2>
                        <p class="text-muted">Hesabınıza giriş yapın</p>
                    </div>
                    
                    <!-- Hata Mesajı Gösterimi -->
                    <?php if (!empty($hata_mesaji)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $hata_mesaji; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label for="eposta" class="form-label fw-semibold">
                                <i class="fas fa-envelope me-2 text-primary"></i>E-Posta Adresi
                            </label>
                            <input type="email" class="form-control form-control-lg" id="eposta" name="eposta" 
                                   required placeholder="ornek@mail.com" autocomplete="email">
                        </div>
                        
                        <div class="mb-4">
                            <label for="sifre" class="form-label fw-semibold">
                                <i class="fas fa-key me-2 text-primary"></i>Şifre
                            </label>
                            <input type="password" class="form-control form-control-lg" id="sifre" name="sifre" 
                                   required placeholder="••••••••" autocomplete="current-password">
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100 btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                        </button>
                    </form>
                </div>
                
                <div class="card-footer text-center bg-light py-3">
                    <p class="mb-0 text-muted">
                        Hesabınız yok mu? 
                        <a href="register.php" class="text-decoration-none fw-bold">
                            <i class="fas fa-user-plus me-1"></i>Kayıt Ol
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>