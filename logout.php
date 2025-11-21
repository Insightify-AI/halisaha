<?php
// 1. Oturumu başlat (Mevcut oturuma erişmek için şart)
session_start();

// 2. Tüm session değişkenlerini hafızadan sil
$_SESSION = [];

// 3. Oturumu sunucudan tamamen yok et
session_destroy();

// 4. Kullanıcıyı giriş sayfasına yönlendir
// (URL sonuna ?msg=cikis eklendi ki istersek "Güle güle" mesajı gösterebilelim)
header("Location: login.php?msg=cikis");
exit;
?>