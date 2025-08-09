<?php
session_start();
require_once "config.php";

// Cek apakah form sudah disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Siapkan statement SQL untuk mencegah SQL Injection
    $sql = "SELECT id, username, password FROM users WHERE username = ?";

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("s", $param_username);
        $param_username = $username;

        if ($stmt->execute()) {
            $stmt->store_result();

            // Cek jika username ada
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $username, $hashed_password);
                if ($stmt->fetch()) {
                    // Verifikasi password
                    if (password_verify($password, $hashed_password)) {
                        // Password benar, mulai sesi baru
                        session_regenerate_id();
                        
                        $_SESSION["loggedin"] = true;
                        $_SESSION["user_id"] = $id;
                        $_SESSION["username"] = $username;
                        
                        // Buat cookie selama 5 jam
                        // 5 jam = 5 * 60 menit * 60 detik
                        $cookie_name = "user_login";
                        $cookie_value = $id; // Simpan ID user di cookie
                        $expiration_time = time() + (5 * 3600); // 5 jam dari sekarang
                        setcookie($cookie_name, $cookie_value, $expiration_time, "/");

                        // Redirect ke halaman codex
                        header("location: codex/");
                        exit;
                    } else {
                        // Password salah
                        header("location: index.php?error=1");
                        exit;
                    }
                }
            } else {
                // Username tidak ditemukan
                header("location: index.php?error=1");
                exit;
            }
        }
        $stmt->close();
    }
    $mysqli->close();
}
?>