<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/QuestService.php';

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
$questService = new QuestService($pdo);

// GET: Quest listesini getir
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $type = $_GET['type'] ?? 'all'; // 'all', 'gunluk', 'haftalik'
    
    $response = [
        'success' => true
    ];
    
    if ($type === 'all' || $type === 'gunluk') {
        $response['daily_quests'] = $questService->getDailyQuests($kullanici_id);
    }
    
    if ($type === 'all' || $type === 'haftalik') {
        $response['weekly_quests'] = $questService->getWeeklyQuests($kullanici_id);
    }
    
    // İstatistikler
    $response['stats'] = $questService->getUserQuestStats($kullanici_id);
    
    echo json_encode($response);
    exit;
}

// POST: Quest ilerlemesini güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_progress') {
        $questCode = $_POST['quest_code'] ?? '';
        $increment = intval($_POST['increment'] ?? 1);
        
        if (empty($questCode)) {
            echo json_encode([
                'success' => false,
                'message' => 'Quest kodu gerekli'
            ]);
            exit;
        }
        
        $result = $questService->updateQuestProgress($kullanici_id, $questCode, $increment);
        echo json_encode($result);
        exit;
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz aksiyon'
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
?>
