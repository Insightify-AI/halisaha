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
        // İptal prosedürünü çağır
        $stmt = $pdo->prepare("CALL sp_RezervasyonIptal(?, ?)");
        $stmt->execute([$rezervasyon_id, $kullanici_id]);
        
        // Etkilenen satır var mı kontrolü (Prosedür update yaptığı için rowCount çalışmayabilir, 
        // ama mantıken hata vermediyse işlem tamamdır. Daha sağlam kontrol için prosedürden dönüş değeri alınabilir.)
        
        header("Location: profil.php?msg=iptal_basarili");
    } catch (PDOException $e) {
        // Hata loglanabilir
        header("Location: profil.php?msg=hata");
    }
} else {
    header("Location: profil.php");
}
?>
