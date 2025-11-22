<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

// Basit bir oturum kontrolü (Geliştirilebilir)
// if (!isset($_SESSION['user_id'])) { ... }

$tesis_id = $_POST['tesis_id'] ?? 0;
$puan = $_POST['puan'] ?? 5;
$yorum = trim($_POST['yorum'] ?? '');
$uye_id = 1; // Şimdilik varsayılan üye ID (Login sistemi tam entegre olana kadar)

// Rezervasyon kontrolü - Sadece tamamlanmış rezervasyonu olanlar yorum yapabilir
$stmt = $pdo->prepare("
    SELECT COUNT(*) as rezervasyon_sayisi 
    FROM Rezervasyonlar r
    JOIN Sahalar s ON r.saha_id = s.saha_id
    WHERE s.tesis_id = ? 
    AND r.musteri_id = ? 
    AND r.durum = 'tamamlandi'
");
$stmt->execute([$tesis_id, $uye_id]);
$result = $stmt->fetch();

if ($result['rezervasyon_sayisi'] == 0) {
    echo json_encode(['success' => false, 'message' => 'Yorum yapabilmek için bu tesiste tamamlanmış bir rezervasyonunuz olmalıdır.']);
    exit;
}

if (empty($yorum)) {
    echo json_encode(['success' => false, 'message' => 'Lütfen bir yorum yazın.']);
    exit;
}

// Resim Yükleme İşlemi
$resim_yolu = null;
if (isset($_FILES['resim']) && $_FILES['resim']['error'] === UPLOAD_ERR_OK) {
    // Dosya boyutu kontrolü (5MB = 5 * 1024 * 1024 bytes)
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    if ($_FILES['resim']['size'] > $maxFileSize) {
        echo json_encode(['success' => false, 'message' => 'Dosya boyutu 5MB\'dan büyük olamaz.']);
        exit;
    }
    
    $uploadDir = 'uploads/comments/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileExt = strtolower(pathinfo($_FILES['resim']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    
    if (in_array($fileExt, $allowed)) {
        $fileName = uniqid() . '.' . $fileExt;
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['resim']['tmp_name'], $targetPath)) {
            $resim_yolu = $targetPath;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Sadece JPG, JPEG, PNG ve WEBP formatları kabul edilir.']);
        exit;
    }
}

try {
    $stmt = $pdo->prepare("INSERT INTO Yorumlar (tesis_id, musteri_id, puan, yorum_metni, resim_yolu) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$tesis_id, $uye_id, $puan, $yorum, $resim_yolu]);
    
    // Gamification: Puan Ekle ve Rozetleri Kontrol Et
    require_once 'includes/GamificationService.php';
    $gamification = new GamificationService($pdo);
    $result = $gamification->addPoints($uye_id, 'yorum_yapma', 10, 'Yorum yaptığınız için 10 puan kazandınız!');
    
    $message = 'Yorumunuz başarıyla gönderildi. +10 Puan kazandınız!';
    $badges = [];
    
    // Kazanılan rozetleri kontrol et
    if ($result['success'] && !empty($result['badges'])) {
        $badges = $result['badges'];
        $badgeNames = array_column($badges, 'rozet_adi');
        $message .= ' 🎉 Yeni Rozet: ' . implode(', ', $badgeNames);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'badges' => $badges
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>
