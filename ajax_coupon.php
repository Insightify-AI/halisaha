<?php
require_once 'includes/db.php';
require_once 'includes/GamificationService.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

$kullanici_id = 1; // Şimdilik varsayılan (Login entegrasyonu sonrası session'dan gelecek)
$kupon_id = $_POST['kupon_id'] ?? 0;

if (!$kupon_id) {
    echo json_encode(['success' => false, 'message' => 'Kupon seçilmedi.']);
    exit;
}

$gamification = new GamificationService($pdo);
$result = $gamification->redeemCoupon($kullanici_id, $kupon_id);

echo json_encode($result);
?>
