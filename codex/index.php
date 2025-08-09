<?php
session_start();

// ==========================================================
// KEAMANAN: PEMERIKSAAN LOGIN UTAMA
// ==========================================================
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php"); // Sesuaikan jika lokasi login berbeda
    exit;
}

// ==========================================================
// KONFIGURASI DAN FUNGSI BANTUAN
// ==========================================================
$base_dir = __DIR__ . '/files/';
$real_base_path = realpath($base_dir);
$message = '';

function formatBytes($bytes, $precision = 2) {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function is_safe_path($path_to_check, $base_path) {
    $real_path = realpath($path_to_check);
    // Memastikan jalur yang diminta berada dalam base_path
    return $real_path !== false && strpos($real_path, $base_path) === 0;
}

function delete_dir($dirPath) {
    if (!is_dir($dirPath)) throw new InvalidArgumentException("$dirPath must be a directory");
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') $dirPath .= '/';
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) delete_dir($file);
        else unlink($file);
    }
    rmdir($dirPath);
}

// ==========================================================
// LOGIKA PEMROSESAN AKSI
// ==========================================================
$current_path_relative = isset($_GET['path']) ? trim($_GET['path'], '/') : '';

// --- LOGIKA SAVE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $file_to_save = $_POST['file_path'];
    $content_to_save = $_POST['file_content'];
    $full_path_to_save = $base_dir . $file_to_save;
    if (is_safe_path($full_path_to_save, $real_base_path)) {
        if (file_put_contents($full_path_to_save, $content_to_save) !== false) {
            $message = '<div class="alert success">File berhasil disimpan!</div>';
        } else {
            $message = '<div class="alert error">Gagal menyimpan file! Periksa izin file.</div>';
        }
    } else {
        $message = '<div class="alert error">Akses tidak diizinkan! Percobaan menyimpan file di luar direktori.</div>';
    }
}

// --- LOGIKA RENAME ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rename') {
    $old_file_path = $_POST['old_path'];
    $new_file_name = $_POST['new_name'];
    $full_old_path = $base_dir . $old_file_path;
    $full_new_path = dirname($full_old_path) . '/' . $new_file_name;
    if (is_safe_path($full_old_path, $real_base_path) && !file_exists($full_new_path)) {
        if (rename($full_old_path, $full_new_path)) {
            $message = '<div class="alert success">Nama berhasil diubah!</div>';
        } else {
            $message = '<div class="alert error">Gagal mengubah nama!</div>';
        }
    } else {
        $message = '<div class="alert error">Nama file baru sudah ada atau akses ditolak.</div>';
    }
}

// --- LOGIKA DELETE ---
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $path_to_delete = $_GET['file'];
    $full_path_to_delete = $base_dir . $path_to_delete;
    if (is_safe_path($full_path_to_delete, $real_base_path)) {
        if (is_file($full_path_to_delete)) {
            unlink($full_path_to_delete);
            $message = '<div class="alert success">File berhasil dihapus!</div>';
        } elseif (is_dir($full_path_to_delete)) {
            try { delete_dir($full_path_to_delete); $message = '<div class="alert success">Folder berhasil dihapus!</div>'; }
            catch (Exception $e) { $message = '<div class="alert error">Gagal menghapus folder.</div>'; }
        }
    } else {
        $message = '<div class="alert error">Akses ditolak. Tidak bisa menghapus.</div>';
    }
}

// --- LOGIKA UPLOAD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    if (isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['upload_file']['tmp_name'];
        $file_name = basename($_FILES['upload_file']['name']); // Gunakan basename untuk keamanan
        $upload_path = $base_dir . $current_path_relative . '/' . $file_name;

        if (is_safe_path($upload_path, $real_base_path)) {
            if (move_uploaded_file($file_tmp_name, $upload_path)) {
                $message = '<div class="alert success">File <b>' . htmlspecialchars($file_name) . '</b> berhasil diunggah!</div>';
            } else {
                $message = '<div class="alert error">Gagal mengunggah file. Periksa izin direktori.</div>';
            }
        } else {
            $message = '<div class="alert error">Akses ditolak. Tidak dapat mengunggah file di luar direktori yang diizinkan.</div>';
        }
    } else if (isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $error_codes = [
            UPLOAD_ERR_INI_SIZE   => 'Ukuran file melebihi batas upload_max_filesize di php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'Ukuran file melebihi batas MAX_FILE_SIZE di formulir HTML.',
            UPLOAD_ERR_PARTIAL    => 'File hanya terunggah sebagian.',
            UPLOAD_ERR_NO_TMP_DIR => 'Direktori sementara tidak ada.',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
            UPLOAD_ERR_EXTENSION  => 'Ekstensi PHP menghentikan unggahan file.',
        ];
        $error_msg = isset($error_codes[$_FILES['upload_file']['error']]) ? $error_codes[$_FILES['upload_file']['error']] : 'Kesalahan unggah tidak diketahui.';
        $message = '<div class="alert error">Gagal mengunggah file: ' . $error_msg . '</div>';
    } else if (isset($_POST['action']) && $_POST['action'] === 'upload') {
        $message = '<div class="alert error">Tidak ada file yang dipilih untuk diunggah.</div>';
    }
}


// --- LOGIKA BUAT FOLDER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_folder') {
    $folder_name = trim($_POST['folder_name']);
    // Filter nama folder agar aman (hanya alfanumerik, spasi, underscore, dash)
    if (!preg_match('/^[a-zA-Z0-9\s_\-]+$/', $folder_name)) {
        $message = '<div class="alert error">Nama folder hanya boleh mengandung huruf, angka, spasi, underscore, dan dash.</div>';
    } else if (!empty($folder_name)) {
        $new_folder_path = $base_dir . $current_path_relative . '/' . $folder_name;
        if (is_safe_path($new_folder_path, $real_base_path)) {
            if (!file_exists($new_folder_path)) {
                if (mkdir($new_folder_path, 0755, true)) { // 0755 permissions, true for recursive
                    $message = '<div class="alert success">Folder <b>' . htmlspecialchars($folder_name) . '</b> berhasil dibuat!</div>';
                } else {
                    $message = '<div class="alert error">Gagal membuat folder. Periksa izin direktori.</div>';
                }
            } else {
                $message = '<div class="alert error">Folder dengan nama tersebut sudah ada.</div>';
            }
        } else {
            $message = '<div class="alert error">Akses ditolak. Tidak dapat membuat folder di lokasi ini.</div>';
        }
    } else {
        $message = '<div class="alert error">Nama folder tidak boleh kosong.</div>';
    }
}


// --- LOGIKA VIEW/EDIT/PREVIEW ---
// Variabel untuk mengontrol tampilan modal dan kontennya
$file_to_action = '';
$file_content = '';
$is_editing = false;
$is_viewing = false;
$is_previewing = false;
$is_image = false; // Flag baru untuk gambar

if (isset($_GET['action']) && in_array($_GET['action'], ['view', 'edit', 'preview', 'view_image'])) {
    $file_to_action = $_GET['file'];
    $full_path_to_action = $base_dir . $file_to_action;

    // Pastikan jalur file aman dan file ada
    if (is_safe_path($full_path_to_action, $real_base_path) && is_file($full_path_to_action)) {
        $file_ext = strtolower(pathinfo($full_path_to_action, PATHINFO_EXTENSION));
        $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
        $previewable_extensions = ['html', 'htm'];

        if ($_GET['action'] === 'edit') {
            $file_content = file_get_contents($full_path_to_action);
            $is_editing = true;
        } elseif ($_GET['action'] === 'view') {
            $file_content = file_get_contents($full_path_to_action);
            $is_viewing = true;
        } elseif ($_GET['action'] === 'preview' && in_array($file_ext, $previewable_extensions)) {
            $is_previewing = true;
        } elseif ($_GET['action'] === 'view_image' && in_array($file_ext, $image_extensions)) {
            $is_image = true;
        } else {
            // Jika action tidak sesuai dengan jenis file atau file tidak ada/tidak aman
            $message = '<div class="alert error">Tindakan tidak valid atau file tidak ditemukan/tidak aman.</div>';
        }
    } else {
        $message = '<div class="alert error">File tidak ditemukan atau akses ditolak.</div>';
    }
}

// --- LOGIKA MENAMPILKAN DAFTAR FILE ---
$full_path_current = realpath($base_dir . $current_path_relative);
if (!is_safe_path($full_path_current, $real_base_path)) { die("Akses terlarang."); }
$items = scandir($full_path_current);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Codex - Pro Editor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/dracula.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/php/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/clike/clike.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/sql/sql.min.js"></script>
    <link rel="stylesheet" href="../style.css"> <style>
        body { align-items: flex-start; padding-top: 50px; }
        .file-explorer { width: 80%; max-width: 1000px; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 20px; padding: 30px; box-shadow: 0 15px 25px rgba(0, 0, 0, 0.2); color: #fff; }
        .explorer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            padding-bottom: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .explorer-header h2 { font-size: 1.5em; margin: 0; }
        .explorer-header .action-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .explorer-header .logout-link {
            color: #fff;
            background-color: #9b59b6;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .explorer-header .logout-link:hover { background-color: #71b7e6; }
        .current-path { font-family: monospace; background-color: rgba(0,0,0,0.3); padding: 8px; border-radius: 5px; margin-bottom: 20px; word-break: break-all; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; color: #fff; } .alert.success { background-color: #2ecc71; } .alert.error { background-color: #e74c3c; }
        .explorer-table { width: 100%; border-collapse: collapse; }
        .explorer-table th, .explorer-table td { text-align: left; padding: 12px; border-bottom: 1px solid rgba(255, 255, 255, 0.2); }
        .explorer-table a { color: #fff; text-decoration: none; }
        .explorer-table i.fas { margin-right: 15px; width: 20px; text-align: center; }
        .fa-folder { color: #71b7e6; }
        /* Ikon untuk jenis file tertentu */
        .fa-file-alt { color: #eee; }
        .fa-arrow-up { color: #f9d71c; }
        .fa-image { color: #8e44ad; } /* Warna ungu untuk ikon gambar */
        .fa-file-code { color: #f39c12; } /* Warna oranye untuk ikon HTML/Code */

        .col-actions { width: 80px; text-align: center !important; }
        .context-menu-container { position: relative; }
        .options-btn { background: none; border: none; color: #fff; cursor: pointer; font-size: 16px; padding: 5px 10px; }
        .context-menu { position: absolute; top: 30px; right: 0; background-color: #34495e; border: 1px solid #4a6278; border-radius: 5px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); z-index: 10; width: 180px; padding: 5px 0; display: none; }
        .context-menu.active { display: block; }
        .context-menu a { display: block; padding: 10px 15px; color: #ecf0f1; text-decoration: none; font-size: 14px; white-space: nowrap; }
        .context-menu a:hover { background-color: #4a6278; }
        .context-menu .delete-action { color: #e74c3c; } .context-menu .delete-action:hover { background-color: #c0392b; color: #fff; }

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); backdrop-filter: blur(5px); display: none; justify-content: center; align-items: center; z-index: 1000; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: #2c3e50; color: #ecf0f1; border-radius: 10px; width: 80%; height: 85%; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.5); transition: all 0.3s ease; }
        .modal-content.fullscreen { position: fixed; top: 0; left: 0; width: 100%; height: 100%; border-radius: 0; }
        .modal-header { padding: 15px; background: #34495e; border-bottom: 1px solid #4a6278; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { margin: 0; font-family: monospace; font-size: 16px; }
        .modal-header .controls button { background: none; border: none; color: #fff; font-size: 20px; cursor: pointer; margin-left: 15px; }
        .modal-body { flex-grow: 1; padding: 0; display: flex; }
        .modal-body textarea, .modal-body pre { width: 100%; height: 100%; margin: 0; padding: 15px; background: #283747; border: none; color: #f2f2f2; font-family: 'Courier New', Courier, monospace; font-size: 15px; line-height: 1.6; resize: none; outline: none; white-space: pre-wrap; word-wrap: break-word; }
        .modal-footer { padding: 10px; background: #34495e; border-top: 1px solid #4a6278; text-align: right; }
        .btn-save { background-color: #27ae60; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .modal-body iframe { width: 100%; height: 100%; background-color: #fff; border: none; }
        .modal-body img { max-width: 100%; max-height: 100%; display: block; margin: auto; object-fit: contain; } /* Gaya untuk gambar di modal */
        .CodeMirror { height: 100%; font-size: 15px; }

        /* Gaya untuk tombol aksi umum di header */
        .action-button {
            background-color: #71b7e6;
            color: #fff;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .action-button:hover {
            background-color: #9b59b6;
        }

        /* Gaya untuk modal input folder */
        .input-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(5px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1100;
        }
        .input-modal-overlay.active {
            display: flex;
        }
        .input-modal-content {
            background: #2c3e50;
            color: #ecf0f1;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            width: 90%;
            max-width: 400px;
            text-align: center;
        }
        .input-modal-content h3 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.5em;
        }
        .input-modal-content input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #4a6278;
            border-radius: 5px;
            background-color: #34495e;
            color: #ecf0f1;
            box-sizing: border-box;
        }
        .input-modal-content .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .input-modal-content .modal-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        .input-modal-content .modal-buttons .btn-confirm {
            background-color: #2ecc71;
            color: #fff;
        }
        .input-modal-content .modal-buttons .btn-confirm:hover {
            background-color: #27ae60;
        }
        .input-modal-content .modal-buttons .btn-cancel {
            background-color: #e74c3c;
            color: #fff;
        }
        .input-modal-content .modal-buttons .btn-cancel:hover {
            background-color: #c0392b;
        }

        /* Gaya untuk form upload file di header */
        .header-upload-form {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            position: relative;
        }
        .header-upload-form input[type="file"] {
            display: none;
        }
        .header-upload-form .custom-file-upload-header {
            background-color: #71b7e6;
            color: #fff;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .header-upload-form .custom-file-upload-header:hover {
            background-color: #9b59b6;
        }
        .header-upload-form .file-name-display-header {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            max-width: 120px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: none;
        }
        .header-upload-form button[type="submit"] {
            background-color: #2ecc71;
            color: #fff;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }
        .header-upload-form button[type="submit"]:hover {
            background-color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="file-explorer">
        <div class="explorer-header">
            <h2><i class="fas fa-folder-open"></i> File Manager</h2>
            <div class="action-buttons">
                <button class="action-button" id="createFolderBtn"><i class="fas fa-folder-plus"></i> Buat Folder</button>

                <form action="index.php?path=<?php echo urlencode($current_path_relative); ?>" method="POST" enctype="multipart/form-data" class="header-upload-form">
                    <input type="hidden" name="action" value="upload">
                    <label for="uploadFileHeader" class="custom-file-upload-header">
                        <i class="fas fa-cloud-upload-alt"></i> Unggah File
                    </label>
                    <input type="file" name="upload_file" id="uploadFileHeader">
                    <span class="file-name-display-header" id="fileNameDisplayHeader"></span>
                    <button type="submit" id="uploadSubmitBtn" style="display:none;">Unggah</button>
                </form>

                <a href="../logout.php" class="logout-link">Logout</a>
            </div>
        </div>
        <?php echo $message; ?>
        <div class="current-path">Lokasi: /files/<?php echo htmlspecialchars($current_path_relative); ?></div>
        <table class="explorer-table">
            <thead><tr><th>Nama</th><th>Ukuran</th><th>Modifikasi</th><th class="col-actions">Opsi</th></tr></thead>
            <tbody>
                <?php
                if ($full_path_current !== $real_base_path) {
                    $parent_path = dirname($current_path_relative);
                    $parent_path = ($parent_path === '.' || $parent_path === '\\') ? '' : $parent_path;
                    echo '<tr><td><a href="?path=' . urlencode($parent_path) . '"><i class="fas fa-arrow-up"></i>... (Naik)</a></td><td></td><td></td><td></td></tr>';
                }
                
                // Daftar ekstensi file
                $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
                $editable_extensions = ['php', 'html', 'htm', 'css', 'js', 'txt', 'json', 'md', 'xml', 'sql']; 
                $previewable_extensions = ['html', 'htm']; // HTML/HTM bisa dipreview di iframe

                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') continue;
                    $item_full_path = $full_path_current . DIRECTORY_SEPARATOR . $item;
                    $item_relative_path = $current_path_relative ? $current_path_relative . '/' . $item : $item;
                    $is_dir = is_dir($item_full_path);
                    $file_ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));

                    echo '<tr>';
                    if ($is_dir) {
                        echo '<td><a href="?path=' . urlencode($item_relative_path) . '"><i class="fas fa-folder"></i>' . htmlspecialchars($item) . '</a></td><td>Folder</td><td>' . date("d M Y H:i", filemtime($item_full_path)) . '</td>';
                    } else {
                        // Tentukan tindakan klik untuk nama file
                        $file_click_attributes = '';
                        $icon_class = 'fa-file-alt'; // Ikon default

                        if (in_array($file_ext, $image_extensions)) {
                            $file_click_attributes = 'data-action="view_image" data-file="' . htmlspecialchars($item_relative_path) . '" class="file-link view-file"';
                            $icon_class = 'fa-image';
                        } elseif (in_array($file_ext, $previewable_extensions)) {
                            $file_click_attributes = 'data-action="preview" data-file="' . htmlspecialchars($item_relative_path) . '" class="file-link view-file"';
                            $icon_class = 'fa-file-code'; // Ikon untuk HTML/Previewable
                        } elseif (in_array($file_ext, $editable_extensions)) {
                            $file_click_attributes = 'data-action="view" data-file="' . htmlspecialchars($item_relative_path) . '" class="file-link view-file"';
                            $icon_class = 'fa-file-code'; // Ikon untuk kode
                        } else {
                            // Default: unduh file
                            $file_click_attributes = 'href="files/' . rawurlencode($item_relative_path) . '" download';
                            $icon_class = 'fa-file-alt';
                        }
                        
                        echo '<td><a ' . $file_click_attributes . '><i class="fas ' . $icon_class . '"></i>' . htmlspecialchars($item) . '</a></td><td>' . formatBytes(filesize($item_full_path)) . '</td><td>' . date("d M Y H:i", filemtime($item_full_path)) . '</td>';
                    }
                    echo '<td class="col-actions"><div class="context-menu-container"><button class="options-btn"><i class="fas fa-ellipsis-v"></i></button><div class="context-menu">';
                    if (!$is_dir) {
                        $download_link = 'files/' . rawurlencode($item_relative_path);
                        echo '<a href="' . htmlspecialchars($download_link) . '" download><i class="fas fa-download"></i>Download</a>';
                        if (in_array($file_ext, $previewable_extensions)) { echo '<a href="?path=' . urlencode($current_path_relative) . '&action=preview&file=' . urlencode($item_relative_path) . '"><i class="fas fa-globe"></i>Preview</a>'; }
                        if (in_array($file_ext, $image_extensions)) { echo '<a href="?path=' . urlencode($current_path_relative) . '&action=view_image&file=' . urlencode($item_relative_path) . '"><i class="fas fa-image"></i>Lihat Gambar</a>'; }
                        if (in_array($file_ext, $editable_extensions)) {
                            echo '<a href="?path=' . urlencode($current_path_relative) . '&action=view&file=' . urlencode($item_relative_path) . '"><i class="fas fa-eye"></i>Lihat Kode</a>';
                            echo '<a href="?path=' . urlencode($current_path_relative) . '&action=edit&file=' . urlencode($item_relative_path) . '"><i class="fas fa-edit"></i>Edit Kode</a>';
                        }
                    }
                    echo '<a href="#" class="rename-action" data-path="' . htmlspecialchars($item_relative_path) . '" data-name="' . htmlspecialchars($item) . '"><i class="fas fa-i-cursor"></i>Ubah Nama</a>';
                    echo '<a href="?path=' . urlencode($current_path_relative) . '&action=delete&file=' . urlencode($item_relative_path) . '" class="delete-action"><i class="fas fa-trash-alt"></i>Hapus</a>';
                    echo '</div></div></td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="modal-overlay <?php if ($is_viewing || $is_editing || $is_previewing || $is_image) echo 'active'; ?>" id="fileModal">
        <div class="modal-content" id="modalContent">
            <div class="modal-header"><h3 id="modalFilename"><?php echo htmlspecialchars($file_to_action); ?></h3><div class="controls"><button id="fullscreenBtn" title="Fullscreen"><i class="fas fa-expand"></i></button><button id="closeBtn" title="Tutup"><i class="fas fa-times"></i></button></div></div>
            <div class="modal-body">
                <?php if ($is_viewing): ?>
                    <pre id="viewContent" style="padding:15px; white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars($file_content); ?></pre>
                <?php endif; ?>
                <?php if ($is_editing): ?>
                    <form id="editorForm" action="index.php?path=<?php echo urlencode($current_path_relative); ?>" method="POST" style="width:100%; height:100%; display:flex; flex-direction:column;">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="file_path" value="<?php echo htmlspecialchars($file_to_action); ?>">
                        <textarea id="code-editor" name="file_content"><?php echo htmlspecialchars($file_content); ?></textarea>
                        <div class="modal-footer"><button type="submit" class="btn-save">Simpan Perubahan</button></div>
                    </form>
                <?php endif; ?>
                <?php if ($is_previewing): ?>
                    <iframe id="previewContent" src="files/<?php echo htmlspecialchars($file_to_action); ?>" style="width: 100%; height: 100%; border: none;"></iframe>
                <?php endif; ?>
                <?php if ($is_image): ?>
                    <img id="imageContent" src="files/<?php echo htmlspecialchars($file_to_action); ?>" alt="<?php echo htmlspecialchars($file_to_action); ?>">
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <form id="renameForm" action="index.php?path=<?php echo urlencode($current_path_relative); ?>" method="POST" style="display:none;">
        <input type="hidden" name="action" value="rename">
        <input type="hidden" name="old_path" id="renameOldPath">
        <input type="hidden" name="new_name" id="renameNewName">
    </form>

    <div class="input-modal-overlay" id="createFolderModal">
        <div class="input-modal-content">
            <h3>Buat Folder Baru</h3>
            <form id="createFolderForm" action="index.php?path=<?php echo urlencode($current_path_relative); ?>" method="POST">
                <input type="hidden" name="action" value="create_folder">
                <input type="text" name="folder_name" id="newFolderName" placeholder="Nama Folder" required>
                <div class="modal-buttons">
                    <button type="submit" class="btn-confirm">Buat</button>
                    <button type="button" class="btn-cancel" id="cancelCreateFolder">Batal</button>
                </div>
            </form>
        </div>
    </div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('fileModal');
    const modalContent = document.getElementById('modalContent');
    const closeBtn = document.getElementById('closeBtn');
    const fullscreenBtn = document.getElementById('fullscreenBtn');
    const modalFilename = document.getElementById('modalFilename');
    const modalBody = document.querySelector('#fileModal .modal-body');

    // Fungsi untuk menutup modal utama
    function closeModal() {
        modal.classList.remove('active');
        modalContent.classList.remove('fullscreen'); // Reset fullscreen state
        fullscreenBtn.querySelector('i').classList.remove('fa-compress');
        fullscreenBtn.querySelector('i').classList.add('fa-expand');

        // Bersihkan konten modal saat ditutup
        modalBody.innerHTML = '';
        modalFilename.textContent = '';

        // Hapus parameter action dan file dari URL saat modal ditutup
        const url = new URL(window.location);
        url.searchParams.delete('action');
        url.searchParams.delete('file');
        window.history.pushState({}, '', url);

        // Hancurkan instance CodeMirror jika ada
        if (typeof codeMirrorEditor !== 'undefined' && codeMirrorEditor !== null) {
             codeMirrorEditor.toTextArea();
             codeMirrorEditor = null;
        }
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) { // Hanya jika klik di overlay, bukan di konten modal
                closeModal();
            }
        });
    }
    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', function() {
            modalContent.classList.toggle('fullscreen');
            const icon = fullscreenBtn.querySelector('i');
            if (modalContent.classList.contains('fullscreen')) {
                icon.classList.remove('fa-expand');
                icon.classList.add('fa-compress');
            } else {
                icon.classList.remove('fa-compress');
                icon.classList.add('fa-expand');
            }
            // Refresh CodeMirror jika aktif agar tata letak tidak rusak
            if (codeMirrorEditor) {
                codeMirrorEditor.refresh();
            }
        });
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === "Escape" && modal.classList.contains('active')) {
            closeModal();
        }
    });

    const optionsButtons = document.querySelectorAll('.options-btn');

    function closeAllMenus() {
        document.querySelectorAll('.context-menu.active').forEach(menu => {
            menu.classList.remove('active');
        });
    }

    optionsButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            let menu = this.nextElementSibling;
            if (menu.classList.contains('active')) {
                menu.classList.remove('active');
            } else {
                closeAllMenus();
                menu.classList.add('active');
            }
        });
    });

    document.addEventListener('click', function() {
        closeAllMenus();
    });

    document.querySelectorAll('.delete-action').forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Anda yakin ingin menghapus item ini? Aksi ini tidak bisa dibatalkan.')) {
                e.preventDefault();
            }
        });
    });

    const renameForm = document.getElementById('renameForm');
    document.querySelectorAll('.rename-action').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const oldPath = this.dataset.path;
            const oldName = this.dataset.name;
            const newName = prompt('Masukkan nama baru untuk "' + oldName + '":', oldName);
            if (newName && newName !== oldName) {
                document.getElementById('renameOldPath').value = oldPath;
                document.getElementById('renameNewName').value = newName;
                renameForm.submit();
            }
        });
    });

    let codeMirrorEditor = null; // Variabel global untuk instance CodeMirror

    // Fungsi untuk menginisialisasi atau menginisialisasi ulang CodeMirror
    function initCodeMirror(textareaElement, fileName) {
        // Hancurkan instance CodeMirror yang ada jika ada
        if (codeMirrorEditor) {
            codeMirrorEditor.toTextArea();
        }

        const fileExtension = fileName.split('.').pop().toLowerCase();
        let mode = 'text/plain';
        const languageModes = {
            'js': 'javascript',
            'json': 'application/json',
            'html': 'xml',
            'htm': 'xml',
            'css': 'css',
            'php': 'application/x-httpd-php',
            'sql': 'text/x-sql',
            'xml': 'xml'
        };
        if (languageModes[fileExtension]) {
            mode = languageModes[fileExtension];
        }

        codeMirrorEditor = CodeMirror.fromTextArea(textareaElement, {
            lineNumbers: true,
            mode: mode,
            theme: 'dracula',
            indentUnit: 4,
            smartIndent: true,
            lineWrapping: true
        });

        setTimeout(function() { codeMirrorEditor.refresh(); }, 1); // Refresh setelah DOM siap

        const editorForm = textareaElement.closest('form');
        if (editorForm) {
            editorForm.onsubmit = function() { // Gunakan onsubmit agar tidak menumpuk listener
                codeMirrorEditor.save();
                return true; // Izinkan submit form
            };
        }
    }

    // JS untuk form upload file di header
    const uploadFileHeader = document.getElementById('uploadFileHeader');
    const fileNameDisplayHeader = document.getElementById('fileNameDisplayHeader');
    const uploadSubmitBtn = document.getElementById('uploadSubmitBtn');

    if (uploadFileHeader && fileNameDisplayHeader && uploadSubmitBtn) {
        uploadFileHeader.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileNameDisplayHeader.textContent = this.files[0].name;
                fileNameDisplayHeader.style.display = 'inline-block';
                uploadSubmitBtn.style.display = 'inline-block';
            } else {
                fileNameDisplayHeader.textContent = '';
                fileNameDisplayHeader.style.display = 'none';
                uploadSubmitBtn.style.display = 'none';
            }
        });
    }

    // JS untuk modal buat folder
    const createFolderBtn = document.getElementById('createFolderBtn');
    const createFolderModal = document.getElementById('createFolderModal');
    const cancelCreateFolder = document.getElementById('cancelCreateFolder');
    const createFolderForm = document.getElementById('createFolderForm');
    const newFolderNameInput = document.getElementById('newFolderName');

    if (createFolderBtn && createFolderModal && cancelCreateFolder && createFolderForm) {
        createFolderBtn.addEventListener('click', function() {
            createFolderModal.classList.add('active');
            newFolderNameInput.focus();
        });

        cancelCreateFolder.addEventListener('click', function() {
            createFolderModal.classList.remove('active');
            newFolderNameInput.value = '';
        });

        createFolderModal.addEventListener('click', function(e) {
            if (e.target === createFolderModal) {
                createFolderModal.classList.remove('active');
                newFolderNameInput.value = '';
            }
        });

        createFolderForm.addEventListener('submit', function() {
            // Modal akan tertutup dan input dikosongkan secara otomatis setelah PHP redirect
            // createFolderModal.classList.remove('active');
        });
    }

    // --- LOGIKA KLIK NAMA FILE UNTUK MEMBUKA MODAL ---
    document.querySelectorAll('.file-link.view-file').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault(); // Mencegah navigasi default
            const action = this.dataset.action;
            const file = this.dataset.file;
            // Ambil nama file dari teks setelah ikon (nodeValue dari textNode)
            const iconElement = this.querySelector('i');
            let fileName = '';
            if (iconElement && iconElement.nextSibling) {
                fileName = iconElement.nextSibling.nodeValue.trim();
            } else {
                fileName = file.split('/').pop(); // Fallback jika struktur HTML berubah
            }

            modalFilename.textContent = fileName; // Set nama file di header modal
            modal.classList.add('active'); // Tampilkan modal

            modalBody.innerHTML = ''; // Kosongkan modal body sebelum mengisi konten baru

            const filePath = 'files/' + encodeURIComponent(file);

            if (action === 'view_image') {
                const img = document.createElement('img');
                img.id = 'imageContent';
                img.src = filePath;
                img.alt = fileName;
                img.onerror = function() {
                    console.error('Gagal memuat gambar:', this.src);
                    modalBody.innerHTML = '<div style="color: red; text-align: center; padding: 20px;">Gagal memuat gambar atau gambar rusak.<br>Pastikan file ada dan izinnya benar.</div>';
                };
                modalBody.appendChild(img);
            } else if (action === 'preview') {
                const iframe = document.createElement('iframe');
                iframe.id = 'previewContent';
                iframe.src = filePath;
                iframe.style.width = '100%';
                iframe.style.height = '100%';
                iframe.style.border = 'none';
                modalBody.appendChild(iframe);
            } else if (action === 'view' || action === 'edit') {
                // Memuat konten file melalui AJAX untuk view/edit
                fetch(`index.php?path=<?php echo urlencode($current_path_relative); ?>&action=${action}&file=${encodeURIComponent(file)}`)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        // Cari elemen form editor atau pre yang ada di respons HTML yang baru dimuat
                        const fetchedEditorForm = doc.getElementById('editorForm');
                        const fetchedPreContent = doc.getElementById('viewContent');

                        if (fetchedEditorForm) {
                            // Tambahkan form editor ke modal body yang sudah ada
                            modalBody.appendChild(fetchedEditorForm);
                            const textarea = modalBody.querySelector('#code-editor');
                            if (textarea) {
                                initCodeMirror(textarea, fileName); // Inisialisasi CodeMirror
                            }
                        } else if (fetchedPreContent) {
                            // Tambahkan pre element ke modal body yang sudah ada
                            modalBody.appendChild(fetchedPreContent);
                        } else {
                             modalBody.innerHTML = '<div style="color: red; text-align: center; padding: 20px;">Gagal memuat konten file.</div>';
                             console.error('Failed to find editor form or pre content in fetched HTML.');
                        }
                    })
                    .catch(error => {
                        console.error('Error loading file content via AJAX:', error);
                        modalBody.innerHTML = '<div style="color: red; text-align: center; padding: 20px;">Terjadi kesalahan saat memuat konten file.</div>';
                    });
            }
        });
    });
});
</script>
</body>
</html>