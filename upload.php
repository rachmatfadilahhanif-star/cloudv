<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['file'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $file = $_FILES['file'];
    $user_id = $_SESSION['user_id'];
    
    // Debug info
    error_log("File uploaded: " . $file['name'] . ", Type: " . $file['type'] . ", Size: " . $file['size']);
    
    // Validasi error upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'File terlalu besar',
            UPLOAD_ERR_FORM_SIZE => 'File terlalu besar',
            UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang dipilih',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ada',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
            UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh extension PHP'
        ];
        $error_msg = $upload_errors[$file['error']] ?? 'Unknown upload error';
        $_SESSION['upload_error'] = "Error upload: " . $error_msg;
        header("Location: dashboard.php");
        exit();
    }
    
    // ⚡ PERBAIKAN: IZINKAN SEMUA JENIS FILE ⚡
    // Hapus validasi MIME type restriction
    // $allowed_types = [ ... ]; // DIHAPUS
    
    // Hanya batasi berdasarkan UKURAN FILE saja
    $max_size = 50 * 1024 * 1024; // 50MB (diperbesar dari 10MB)
    
    if ($file['size'] > $max_size) {
        $_SESSION['upload_error'] = "Ukuran file terlalu besar (maks 50MB)! File Anda: " . formatFileSize($file['size']);
        header("Location: dashboard.php");
        exit();
    }
    
    // Juga batasi file kosong
    if ($file['size'] == 0) {
        $_SESSION['upload_error'] = "File kosong atau tidak valid!";
        header("Location: dashboard.php");
        exit();
    }
    
    // Buat folder uploads jika belum ada
    $upload_dir = 'uploads/' . $user_id . '/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate nama file unik
    $original_name = basename($file['name']);
    $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . $original_name;
    $file_path = $upload_dir . $filename;
    
    // Cek jika file dengan nama yang sama sudah ada
    if (file_exists($file_path)) {
        $filename = uniqid() . '_' . time() . '_' . $original_name;
        $file_path = $upload_dir . $filename;
    }
    
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        // Simpan ke database
        $query = "INSERT INTO files (user_id, filename, file_path, file_size, file_type) 
                  VALUES (:user_id, :filename, :file_path, :file_size, :file_type)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":filename", $original_name);
        $stmt->bindParam(":file_path", $file_path);
        $stmt->bindParam(":file_size", $file['size']);
        $stmt->bindParam(":file_type", $file['type']);
        
        if ($stmt->execute()) {
            $_SESSION['upload_success'] = "File '{$original_name}' berhasil diupload!";
        } else {
            $_SESSION['upload_error'] = "Gagal menyimpan data file!";
            // Hapus file yang sudah diupload
            unlink($file_path);
        }
    } else {
        $_SESSION['upload_error'] = "Gagal mengupload file! Pastikan folder uploads memiliki permission write.";
    }
    
    header("Location: dashboard.php");
    exit();
} else {
    $_SESSION['upload_error'] = "Tidak ada file yang dipilih!";
    header("Location: dashboard.php");
    exit();
}

// Helper function untuk format size
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>