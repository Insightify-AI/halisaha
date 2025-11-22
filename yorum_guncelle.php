<?php
require_once 'includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['kullanici_id'])) {
    $yorum_id = $_POST['yorum_id'];
    $puan = $_POST['puan'];
    $yorum = htmlspecialchars($_POST['yorum']);
    $kullanici_id = $_SESSION['kullanici_id'];
    $musteri_id = $_SESSION['rol_id'];
    
    try {
        // Güvenlik: Yorumun sahibi mi kontrol et
        $stmt = $pdo->prepare("SELECT musteri_id, resim_yolu FROM Yorumlar WHERE yorum_id = ?");
        $stmt->execute([$yorum_id]);
        $mevcut_yorum = $stmt->fetch();
        
        if (!$mevcut_yorum || $mevcut_yorum['musteri_id'] != $musteri_id) {
            throw new Exception("Bu yorumu düzenleme yetkiniz yok!");
        }
        
        $resim_yolu = $mevcut_yorum['resim_yolu']; // Mevcut resim yolu
        
        // Yeni Resim Yükleme İşlemi
        if (isset($_FILES['resim']) && $_FILES['resim']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/yorumlar/';
            
            // Klasör yoksa oluştur
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_tmp = $_FILES['resim']['tmp_name'];
            $file_size = $_FILES['resim']['size'];
            $file_name = $_FILES['resim']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Dosya boyutu kontrolü (Max 5MB)
            $max_size = 5 * 1024 * 1024; // 5MB
            if ($file_size > $max_size) {
                throw new Exception("Dosya boyutu 5MB'dan büyük olamaz!");
            }
            
            // Dosya formatı kontrolü
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($file_ext, $allowed_extensions)) {
                throw new Exception("Sadece JPG, JPEG, PNG ve WEBP formatları kabul edilir!");
            }
            
            // Eski resmi sil (varsa)
            if ($resim_yolu && file_exists($resim_yolu)) {
                unlink($resim_yolu);
            }
            
            // Benzersiz dosya adı oluştur
            $new_file_name = 'yorum_' . $musteri_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_file_name;
            
            // Dosyayı taşı
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $resim_yolu = $upload_path;
            } else {
                throw new Exception("Dosya yüklenirken bir hata oluştu!");
            }
        }
        
        // Yorumu güncelle
        $sql = "UPDATE Yorumlar SET puan = ?, yorum_metni = ?, resim_yolu = ? WHERE yorum_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$puan, $yorum, $resim_yolu, $yorum_id]);
        
        // Başarılıysa Profil'e geri dön
        header("Location: profil.php?msg=yorum_guncellendi");
    } catch (Exception $e) {
        header("Location: profil.php?msg=hata&error=" . urlencode($e->getMessage()));
    } catch (PDOException $e) {
        header("Location: profil.php?msg=hata&error=" . urlencode($e->getMessage()));
    }
} else {
    header("Location: index.php");
}
?>
