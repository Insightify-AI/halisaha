<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/StreakService.php';

header('Content-Type: application/json');

// Giriş kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Lütfen giriş yapın'
    ]);
    exit;
}

$kullanici_id = $_SESSION['kullanici_id'];
$streakService = new StreakService($pdo);

// Check-in işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $streakService->checkIn($kullanici_id);
    echo json_encode($result);
    exit;
}

// GET: Streak bilgilerini getir
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $streak = $streakService->getCurrentStreak($kullanici_id);
    $hasCheckedIn = $streakService->hasCheckedInToday($kullanici_id);
    $monthlyCheckins = $streakService->getMonthlyCheckIns($kullanici_id);
    $history = $streakService->getCheckInHistory($kullanici_id, 7); // Son 7 gün
    
    echo json_encode([
        'success' => true,
        'streak' => $streak,
        'has_checked_in_today' => $hasCheckedIn,
        'monthly_checkins' => $monthlyCheckins,
        'recent_history' => $history
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
?>
