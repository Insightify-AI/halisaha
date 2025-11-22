<?php
require_once 'includes/db.php';

$sqlFile = 'social_gamification.sql';
$sql = file_get_contents($sqlFile);

if (!$sql) {
    die("SQL dosyası okunamadı.");
}

try {
    $pdo->exec($sql);
    echo "SQL başarıyla çalıştırıldı!";
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?>
