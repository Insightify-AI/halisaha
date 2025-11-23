<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] != 'musteri') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rezervasyon_id'])) {
    $rezervasyon_id = (int)$_POST['rezervasyon_id'];
    $kullanici_id = $_SESSION['kullanici_id'];

    try {
        // Müşteri iptali - Para iadesi ile birlikte
        // NOT: sp_RezervasyonReddetVeIade tesis sahibi reddi için,
        // ama mantık aynı - sadece müşterinin kendi rezervasyonunu iptal etmesi
        $stmt = $pdo->prepare("CALL sp_RezervasyonReddetVeIade(?)");
        $stmt->execute([$rezervasyon_id]);
        $result = $stmt->fetch();
        $stmt->closeCursor();
        
        if ($result && $result['status'] == 'SUCCESS') {
            // Başarılı - Para iadesi yapıldı
            header("Location: profil.php?msg=iptal_basarili&iade=" . $result['iade_tutari']);
        } else {
            header("Location: profil.php?msg=hata&error=iptal_yapilamadi");
        }
    } catch (PDOException $e) {
        // Hata - Muhtemelen zaten iptal edilmiş veya onaylanmış rezervasyon
        header("Location: profil.php?msg=hata&error=" . urlencode($e->getMessage()));
    }
} else {
    header("Location: profil.php");
}
?>
