<?php
require_once 'includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['kullanici_id'])) {
    $musteri_id = $_SESSION['rol_id'];
    $tesis_id = $_POST['tesis_id'];
    $puan = $_POST['puan'];
    $yorum = htmlspecialchars($_POST['yorum']);

    try {
        // Stored Procedure Çağır
        $sql = "CALL sp_YorumEkle(?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$musteri_id, $tesis_id, $puan, $yorum]);

        // Başarılıysa Profil'e geri dön (Mesajlı)
        header("Location: profil.php?msg=yorum_basarili");
    } catch (PDOException $e) {
        echo "Hata: " . $e->getMessage();
    }
} else {
    header("Location: index.php");
}
?>