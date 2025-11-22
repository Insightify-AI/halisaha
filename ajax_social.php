<?php
require_once 'includes/db.php';
require_once 'includes/GamificationService.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

$action = $_POST['action'] ?? '';
$gamification = new GamificationService($pdo);

// 1. YORUM BEĞENME / BEĞENMEME
if ($action === 'like' || $action === 'dislike') {
    $yorum_id = $_POST['yorum_id'] ?? 0;
    $kullanici_id = 1; // Şimdilik varsayılan (Login entegrasyonu sonrası session'dan gelecek)

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
        echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }
}

// 2. YORUM YANITLAMA (Tesis Sahibi)
elseif ($action === 'reply') {
    $yorum_id = $_POST['yorum_id'] ?? 0;
    $yanit_metni = trim($_POST['yanit_metni'] ?? '');
    $tesis_sahibi_id = 1; // Şimdilik varsayılan (Login entegrasyonu sonrası session'dan gelecek)

    if (!$yorum_id || empty($yanit_metni)) {
        echo json_encode(['success' => false, 'message' => 'Eksik bilgi.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO YorumYanitlari (yorum_id, tesis_sahibi_id, yanit_metni) VALUES (?, ?, ?)");
        $stmt->execute([$yorum_id, $tesis_sahibi_id, $yanit_metni]);

        echo json_encode(['success' => true, 'message' => 'Yanıtınız kaydedildi.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
}
?>
