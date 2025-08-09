<?php
// Pengaturan Database
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Default username Laragon
define('DB_PASSWORD', '1Wildan26R2008!!!');     // Default password Laragon kosong
define('DB_NAME', 'menu-awal');

// Membuat koneksi ke database
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Cek koneksi
if ($mysqli === false) {
    die("ERROR: Tidak bisa terhubung. " . $mysqli->connect_error);
}
?>