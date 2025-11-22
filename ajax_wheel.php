<?php
// ajax_wheel.php - Çark çevirme işlemi
session_start();
header('Content-Type: application/json');

require_once 'includes/db.php';
require_once 'includes/WheelService.php';

// Giriş kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    echo json_encode(['success' => false, 'message' => 'Lütfen önce giriş yapın.']);
    exit;
}

$kullanici_id = $_SESSION['kullanici_id'];

try {
    $wheelService = new WheelService($pdo);
    $result = $wheelService->spinWheel($kullanici_id);
    
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
}
?>
