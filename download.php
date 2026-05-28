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
    
    if ($file && file_exists($file['file_path'])) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . $file['file_size']);
        readfile($file['file_path']);
        exit();
    } else {
        $_SESSION['upload_error'] = "File tidak ditemukan!";
        header("Location: dashboard.php");
        exit();
    }
}
?>