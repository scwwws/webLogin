<?php
session_start();

// Hapus semua variabel sesi
$_SESSION = array();

// Hancurkan sesi
session_destroy();

// Hapus cookie dengan mengatur waktu kedaluwarsa di masa lalu
if (isset($_COOKIE['user_login'])) {
    unset($_COOKIE['user_login']);
    setcookie('user_login', '', time() - 3600, '/'); 
}

// Redirect ke halaman login
header("location: index.php");
exit;
?>