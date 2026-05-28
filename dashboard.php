<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Ambil daftar file user
$query = "SELECT * FROM files WHERE user_id = :user_id ORDER BY uploaded_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total ukuran file
$query = "SELECT SUM(file_size) as total_size FROM files WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();
$total_size = $stmt->fetch(PDO::FETCH_ASSOC)['total_size'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Cloud Storage</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Cloud Storage</h1>
            <div class="user-info">
                <span>Halo, <?php echo $_SESSION['username']; ?></span>
                <a href="logout.php" class="btn logout">Logout</a>
            </div>
        </header>

        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Total File</h3>
                <p><?php echo count($files); ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Penyimpanan</h3>
                <p><?php echo formatFileSize($total_size); ?></p>
            </div>
        </div>

        <div class="upload-section">
            <h2>Upload File</h2>
            <form action="upload.php" method="POST" enctype="multipart/form-data" class="upload-form">
                <input type="file" name="file" required>
                <button type="submit" class="btn">Upload File</button>
            </form>
        </div>

        <div class="files-section">
            <h2>File Saya</h2>
            
            <?php if (isset($_SESSION['upload_success'])): ?>
                <div class="alert success"><?php echo $_SESSION['upload_success']; unset($_SESSION['upload_success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['upload_error'])): ?>
                <div class="alert error"><?php echo $_SESSION['upload_error']; unset($_SESSION['upload_error']); ?></div>
            <?php endif; ?>

            <?php if (empty($files)): ?>
                <p class="no-files">Belum ada file yang diupload.</p>
            <?php else: ?>
                <div class="files-grid">
                    <?php foreach ($files as $file): ?>
                        <div class="file-card">
                            <div class="file-icon">
                                <?php echo getFileIcon($file['file_type']); ?>
                            </div>
                            <div class="file-info">
                                <h4><?php echo htmlspecialchars($file['filename']); ?></h4>
                                <p>Ukuran: <?php echo formatFileSize($file['file_size']); ?></p>
                                <p>Upload: <?php echo date('d M Y H:i', strtotime($file['uploaded_at'])); ?></p>
                            </div>
                            <div class="file-actions">
                                <a href="download.php?id=<?php echo $file['id']; ?>" class="btn btn-download">Download</a>
                                <a href="delete.php?id=<?php echo $file['id']; ?>" class="btn btn-delete" onclick="return confirm('Yakin hapus file?')">Hapus</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php
    function formatFileSize($bytes) {
        if ($bytes == 0) return '0 Bytes';
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }

    function getFileIcon($file_type) {
        if (strpos($file_type, 'image') !== false) return '🖼️';
        if (strpos($file_type, 'pdf') !== false) return '📄';
        if (strpos($file_type, 'word') !== false) return '📝';
        if (strpos($file_type, 'excel') !== false) return '📊';
        if (strpos($file_type, 'zip') !== false) return '📦';
        return '📁';
    }
    ?>
</body>
</html>