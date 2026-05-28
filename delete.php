<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $file_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    $query = "SELECT * FROM files WHERE id = :id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $file_id);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($file) {
        // Hapus file dari sistem
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }
        
        // Hapus dari database
        $query = "DELETE FROM files WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $file_id);
        
        if ($stmt->execute()) {
            $_SESSION['upload_success'] = "File berhasil dihapus!";
        } else {
            $_SESSION['upload_error'] = "Gagal menghapus file dari database!";
        }
    } else {
        $_SESSION['upload_error'] = "File tidak ditemukan!";
    }
    
    header("Location: dashboard.php");
    exit();
}
?>