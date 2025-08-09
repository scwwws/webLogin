<?php
session_start();

// Jika sesi atau cookie sudah ada, langsung redirect ke halaman codex
if (isset($_SESSION['user_id']) || isset($_COOKIE['user_login'])) {
    header("Location: codex/index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <h2>Login Akun</h2>
        
        <?php 
        // Tampilkan pesan error jika login gagal
        if (isset($_GET['error']) && $_GET['error'] == 1) {
            echo '<p class="error-message">Username atau Password salah!</p>';
        }
        ?>

        <form action="login.php" method="POST">
            <div class="input-group">
                <input type="text" name="username" id="username" required>
                <label for="username">Username</label>
            </div>
            <div class="input-group">
                <input type="password" name="password" id="password" required>
                <label for="password">Password</label>
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>
    </div>
</body>
</html>