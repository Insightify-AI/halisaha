<?php
require_once 'includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['kullanici_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Giriş yapmalısınız']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tesis_id'])) {
    $kullanici_id = $_SESSION['kullanici_id'];
    $tesis_id = (int)$_POST['tesis_id'];

    try {
        $stmt = $pdo->prepare("CALL sp_FavoriToggle(?, ?)");
        $stmt->execute([$kullanici_id, $tesis_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'islem' => $result['islem']]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Veritabanı hatası']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek']);
}
?>
