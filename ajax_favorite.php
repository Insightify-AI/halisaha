<?php
require_once 'includes/db.php';
require_once 'includes/QuestService.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['kullanici_id'])) {
    echo json_encode(['success' => false, 'message' => 'Lütfen giriş yapınız.']);
    exit;
}

$kullanici_id = $_SESSION['kullanici_id'];
$tesis_id = isset($_POST['tesis_id']) ? (int)$_POST['tesis_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($tesis_id <= 0 || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

try {
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO Favoriler (kullanici_id, tesis_id) VALUES (?, ?)");
        $stmt->execute([$kullanici_id, $tesis_id]);
        
        // Quest: Favori ekleme 
        $questService = new QuestService($pdo);
        $questService->updateQuestProgress($kullanici_id, 'favori_ekle_3', 1);
        $questService->updateQuestProgress($kullanici_id, 'favori_20', 1);
        
        echo json_encode(['success' => true, 'status' => 'added']);
    } elseif ($action === 'remove') {
        $stmt = $pdo->prepare("DELETE FROM Favoriler WHERE kullanici_id = ? AND tesis_id = ?");
        $stmt->execute([$kullanici_id, $tesis_id]);
        echo json_encode(['success' => true, 'status' => 'removed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>
