<?php
// ajax_ilceler.php
require_once 'includes/db.php';

if (isset($_GET['sehir_id'])) {
    $sehir_id = (int)$_GET['sehir_id'];
    
    $stmt = $pdo->prepare("SELECT ilce_id, ilce_adi FROM Ilceler WHERE sehir_id = ? ORDER BY ilce_adi ASC");
    $stmt->execute([$sehir_id]);
    $ilceler = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // JSON olarak döndür
    header('Content-Type: application/json');
    echo json_encode($ilceler);
}
?>