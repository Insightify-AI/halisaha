<?php
session_start();
header('Content-Type: application/json');

// Show all session data
$debug = [
    'session_started' => session_status() === PHP_SESSION_ACTIVE,
    'session_id' => session_id(),
    'session_data' => $_SESSION ?? [],
    'has_kullanici_id' => isset($_SESSION['kullanici_id']),
    'kullanici_id' => $_SESSION['kullanici_id'] ?? 'NOT SET',
    'ad' => $_SESSION['ad'] ?? 'NOT SET',
    'rol' => $_SESSION['rol'] ?? 'NOT SET'
];

echo json_encode($debug, JSON_PRETTY_PRINT);
?>
