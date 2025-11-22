<?php
// Oturumu başlat (Her sayfanın en başında bu olmalı)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Halısaha Rezervasyon Sistemi</title>
    
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome İkonları -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Badge Notifications CSS -->
    <link rel="stylesheet" href="assets/css/badge-notifications.css">
    
    <style>
        body { background-color: #f8f9fa; }
        .navbar { box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<!-- NAVBAR (MENÜ) -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php">
        <i class="fas fa-futbol me-2"></i>Rezervasyon Sistemi
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link active" href="index.php">Anasayfa</a>
        </li>

        <?php if (isset($_SESSION['kullanici_id'])): ?>
            <!-- KULLANICI GİRİŞ YAPMIŞSA GÖRÜNECEK KISIM -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle me-1"></i>
                    <?php echo htmlspecialchars($_SESSION['ad'] . ' ' . $_SESSION['soyad']); ?>
                    <span class="badge bg-warning text-dark ms-1"><?php echo ucfirst($_SESSION['rol']); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="profil.php">Profilim</a></li>
                    <li><a class="dropdown-item" href="cuzdan.php"><i class="fas fa-wallet me-2 text-success"></i>Cüzdanım</a></li>
                    <?php if($_SESSION['rol'] == 'admin'): ?>
                        <li><a class="dropdown-item" href="admin_panel.php">Yönetim Paneli</a></li>
                    <?php elseif($_SESSION['rol'] == 'tesis_sahibi'): ?>
                        <li><a class="dropdown-item" href="tesislerim.php">Tesislerim</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php">Çıkış Yap</a></li>
                </ul>
            </li>
        <?php else: ?>
            <!-- ZİYARETÇİ (GİRİŞ YAPMAMIŞ) İSE GÖRÜNECEK KISIM -->
            <li class="nav-item">
                <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i>Giriş Yap</a>
            </li>
            <li class="nav-item">
                <a class="nav-link btn btn-outline-light ms-2" href="register.php">Kayıt Ol</a>
            </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
    <!-- İçerik Container Başlangıcı -->