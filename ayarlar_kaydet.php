<?php
require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// GÜVENLİK: Giriş yapmamışsa Login'e at
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kullanici_id = $_SESSION['kullanici_id'];
    $ad = trim($_POST['ad']);
    $soyad = trim($_POST['soyad']);
    $telefon = trim($_POST['telefon']);
    
    $mevcut_sifre = $_POST['mevcut_sifre'];
    $yeni_sifre = $_POST['yeni_sifre'];
    $yeni_sifre_tekrar = $_POST['yeni_sifre_tekrar'];

    try {
        // 1. Kişisel Bilgileri Güncelle
        $stmt = $pdo->prepare("UPDATE Kullanicilar SET ad = ?, soyad = ?, telefon = ? WHERE kullanici_id = ?");
        $stmt->execute([$ad, $soyad, $telefon, $kullanici_id]);

        // Session bilgilerini de güncelle
        $_SESSION['ad'] = $ad;
        $_SESSION['soyad'] = $soyad;

        // 2. Şifre Değişikliği İstenmişse
        if (!empty($mevcut_sifre) || !empty($yeni_sifre)) {
            
            // Mevcut şifreyi kontrol et
            $stmt = $pdo->prepare("SELECT sifre FROM Kullanicilar WHERE kullanici_id = ?");
            $stmt->execute([$kullanici_id]);
            $user = $stmt->fetch();

            if (!password_verify($mevcut_sifre, $user['sifre'])) {
                header("Location: ayarlar.php?status=error&msg=Mevcut şifreniz hatalı.");
                exit;
            }

            if ($yeni_sifre !== $yeni_sifre_tekrar) {
                header("Location: ayarlar.php?status=error&msg=Yeni şifreler uyuşmuyor.");
                exit;
            }

            if (strlen($yeni_sifre) < 6) {
                header("Location: ayarlar.php?status=error&msg=Yeni şifre en az 6 karakter olmalı.");
                exit;
            }

            // Yeni şifreyi güncelle
            $yeni_sifre_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE Kullanicilar SET sifre = ? WHERE kullanici_id = ?");
            $stmt->execute([$yeni_sifre_hash, $kullanici_id]);
        }

        header("Location: ayarlar.php?status=success");
        exit;

    } catch (PDOException $e) {
        header("Location: ayarlar.php?status=error&msg=Veritabanı hatası: " . $e->getMessage());
        exit;
    }
} else {
    header("Location: ayarlar.php");
    exit;
}
