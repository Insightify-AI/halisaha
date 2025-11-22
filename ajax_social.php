<?php
require_once 'includes/db.php';
require_once 'includes/GamificationService.php';
require_once 'includes/QuestService.php';
session_start();

header('Content-Type: application/json');

// DEBUGGING: Log everything
error_log("=== AJAX SOCIAL DEBUG ===");
error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));
error_log("POST data: " . print_r($_POST, true));

// Authentication check
if (!isset($_SESSION['kullanici_id'])) {
    error_log("ERROR: No kullanici_id in session");
    echo json_encode([
        'success' => false, 
        'message' => 'Lütfen giriş yapınız.',
        'debug' => [
            'session_id' => session_id(),
            'session_data' => $_SESSION ?? []
        ]
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

$action = $_POST['action'] ?? '';
$gamification = new GamificationService($pdo);
$questService = new QuestService($pdo);

// 1. YORUM BEĞENME / BEĞENMEME
if ($action === 'like' || $action === 'dislike') {
    $yorum_id = $_POST['yorum_id'] ?? 0;
    $kullanici_id = $_SESSION['kullanici_id']; // Use actual logged-in user

    error_log("Like/Dislike action for user: $kullanici_id, yorum: $yorum_id, action: $action");

    if (!$yorum_id) {
        echo json_encode(['success' => false, 'message' => 'Yorum ID eksik.']);
        exit;
    }

    try {
        // Önce var mı kontrol et
        $stmt = $pdo->prepare("SELECT begeni_id, durum FROM YorumBegenileri WHERE yorum_id = ? AND kullanici_id = ?");
        $stmt->execute([$yorum_id, $kullanici_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing['durum'] === $action) {
                // Aynı aksiyon ise kaldır (Toggle)
                $stmt = $pdo->prepare("DELETE FROM YorumBegenileri WHERE begeni_id = ?");
                $stmt->execute([$existing['begeni_id']]);
                $message = 'İşlem geri alındı.';
            } else {
                // Farklı ise güncelle
                $stmt = $pdo->prepare("UPDATE YorumBegenileri SET durum = ? WHERE begeni_id = ?");
                $stmt->execute([$action, $existing['begeni_id']]);
                $message = ($action == 'like' ? 'Beğendiniz.' : 'Beğenmediniz.');
            }
        } else {
            // Yoksa ekle
            $stmt = $pdo->prepare("INSERT INTO YorumBegenileri (yorum_id, kullanici_id, durum) VALUES (?, ?, ?)");
            $stmt->execute([$yorum_id, $kullanici_id, $action]);
            $message = ($action == 'like' ? 'Beğendiniz.' : 'Beğenmediniz.');
            
            // Gamification: Yorum sahibine puan ver (Kendi yorumu değilse)
            // Yorum sahibini bul
            $stmtOwner = $pdo->prepare("SELECT musteri_id FROM Yorumlar WHERE yorum_id = ?");
            $stmtOwner->execute([$yorum_id]);
            $ownerId = $stmtOwner->fetchColumn();
            
            if ($ownerId && $ownerId != $kullanici_id && $action == 'like') {
                $gamification->addPoints($ownerId, 'yorum_begenildi', 5, 'Yorumunuz beğenildi!');
                $gamification->checkBadges($ownerId); // Popüler Yorumcu rozeti kontrolü için
            }
        }

        // Güncel sayıları döndür
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN durum = 'like' THEN 1 ELSE 0 END) as like_count,
                SUM(CASE WHEN durum = 'dislike' THEN 1 ELSE 0 END) as dislike_count
            FROM YorumBegenileri 
            WHERE yorum_id = ?
        ");
        $stmt->execute([$yorum_id]);
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true, 
            'message' => $message,
            'likes' => $counts['like_count'] ?? 0,
            'dislikes' => $counts['dislike_count'] ?? 0
        ]);

    } catch (PDOException $e) {
        error_log("Database error in ajax_social: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }
}

// 2. YORUM YANITLAMA (Tesis Sahibi)
elseif ($action === 'reply') {
    $yorum_id = $_POST['yorum_id'] ?? 0;
    $yanit_metni = trim($_POST['yanit_metni'] ?? '');
    $tesis_sahibi_id = $_SESSION['kullanici_id']; // Use actual logged-in user

    if (!$yorum_id || empty($yanit_metni)) {
        echo json_encode(['success' => false, 'message' => 'Eksik bilgi.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO YorumYanitlari (yorum_id, tesis_sahibi_id, yanit_metni) VALUES (?, ?, ?)");
        $stmt->execute([$yorum_id, $tesis_sahibi_id, $yanit_metni]);
        
        // Quest: Bir yoruma yanıt ver (tesis sahipleri için özel quest olabilir)
        // $questService->updateQuestProgress($tesis_sahibi_id, 'yorum_yanitla', 1);

        echo json_encode(['success' => true, 'message' => 'Yanıtınız kaydedildi.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
}
?>
