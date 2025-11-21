<?php
require_once 'includes/db.php';

// Herkesin şifresi bu olacak
$test_sifre = "123456";
$yeni_hash = password_hash($test_sifre, PASSWORD_DEFAULT);

echo "<h3>TOPLU ŞİFRE ONARIM ARACI</h3>";
echo "Ayarlanacak Şifre: <b>$test_sifre</b><br>";
echo "Yeni Hash: <b>" . substr($yeni_hash, 0, 20) . "...</b><br><hr>";

try {
    // WHERE şartı YOK! Tablodaki HERKESİN şifresini değiştiriyoruz.
    $sql_update = "UPDATE Kullanicilar SET sifre = :pass";
    $stmt = $pdo->prepare($sql_update);
    $stmt->execute([':pass' => $yeni_hash]);
    
    $etkilenen = $stmt->rowCount();

    if ($etkilenen > 0) {
        echo "<h2 style='color:green'>BAŞARILI! ✅</h2>";
        echo "Toplam <b>$etkilenen</b> adet kullanıcının şifresi '123456' olarak güncellendi.<br>";
        echo "Artık Efe, Burak, Fatih... hepsiyle giriş yapabilirsin.";
    } else {
        echo "<span style='color:orange'>⚠️ Değişiklik olmadı (Zaten herkesin şifresi güncel olabilir).</span>";
    }

} catch (PDOException $e) {
    echo "SQL Hatası: " . $e->getMessage();
}
?>