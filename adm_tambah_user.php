<?php
require_once "config.php";
$message = '';

// Logika penambahan user jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['username']) && !empty($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Cek dulu apakah username sudah ada
    $sql_check = "SELECT id FROM users WHERE username = ?";
    if ($stmt_check = $mysqli->prepare($sql_check)) {
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $message = '<p style="color: red;">Gagal! Username \'' . htmlspecialchars($username) . '\' sudah digunakan.</p>';
        } else {
            // Jika username tersedia, lakukan INSERT
            $sql_insert = "INSERT INTO users (username, password) VALUES (?, ?)";
            if ($stmt_insert = $mysqli->prepare($sql_insert)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_insert->bind_param("ss", $username, $hashed_password);

                if ($stmt_insert->execute()) {
                    $message = '<p style="color: green;">Sukses! Pengguna \'' . htmlspecialchars($username) . '\' berhasil ditambahkan.</p>';
                } else {
                    $message = '<p style="color: red;">Gagal menambahkan pengguna.</p>';
                }
                $stmt_insert->close();
            }
        }
        $stmt_check->close();
    }
    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin: Tambah Pengguna Manual</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <h2>Tambah Pengguna Baru (Manual)</h2>
        <p style="color: #ddd; font-size: 14px; margin-top: -20px; margin-bottom: 20px;">Hanya untuk Administrator.</p>

        <?php echo $message; // Tampilkan pesan sukses atau error di sini ?>

        <form action="adm_tambah_user.php" method="POST">
            <div class="input-group">
                <input type="text" name="username" id="username" required>
                <label for="username">Username Baru</label>
            </div>
            <div class="input-group">
                <input type="password" name="password" id="password" required>
                <label for="password">Password Baru</label>
            </div>
            <button type="submit" class="btn-login">Tambah Pengguna</button>
        </form>
        <p class="register-link" style="margin-top: 30px;"><a href="index.php">Kembali ke Halaman Login</a></p>
    </div>
</body>
</html>