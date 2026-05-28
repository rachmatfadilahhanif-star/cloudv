<?php
session_start();

// Cek jika file database.php ada
$db_path = __DIR__ . '/config/database.php';
if (file_exists($db_path)) {
    require_once $db_path;
} else {
    die("Error: File config/database.php tidak ditemukan.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
    
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // ⚡ PERBAIKAN: VALIDASI EMAIL SANGAT RINGAN ⚡
    // Hanya cek jika email tidak kosong, format bebas
    if (empty($username) || empty($email) || empty($password)) {
        $error = "Semua field harus diisi!";
    } elseif ($password !== $confirm_password) {
        $error = "Password tidak cocok!";
    } elseif (strlen($password) < 3) { // ⚡ Password minimal 3 karakter saja
        $error = "Password minimal 3 karakter!";
    } elseif (strlen($username) < 3) {
        $error = "Username minimal 3 karakter!";
    } else {
        // ⚡ PERBAIKAN: EMAIL TIDAK PERLU UNIK & FORMAT BEBAS ⚡
        // Cek hanya username saja yang harus unik
        $query = "SELECT id FROM users WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error = "Username sudah digunakan!";
        } else {
            // Hash password dan simpan user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":password", $hashed_password);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Registrasi berhasil! Silakan login.";
                header("Location: login.php");
                exit();
            } else {
                $error = "Terjadi kesalahan. Silakan coba lagi.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Cloud Storage</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>📝 Daftar Akun Baru</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">👤 Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo isset($username) ? $username : ''; ?>" 
                           required minlength="3" placeholder="Minimal 3 karakter">
                </div>
                
                <div class="form-group">
                    <label for="email">📧 Email:</label>
                    <input type="text" id="email" name="email" value="<?php echo isset($email) ? $email : ''; ?>" 
                           required placeholder="Email bebas, tidak perlu valid">
                </div>
                
                <div class="form-group">
                    <label for="password">🔒 Password:</label>
                    <input type="password" id="password" name="password" 
                           required minlength="3" placeholder="Minimal 3 karakter">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">✅ Konfirmasi Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           required minlength="3" placeholder="Ulangi password">
                </div>
                
                <button type="submit" class="btn">🚀 Daftar Sekarang</button>
            </form>
            
            <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
            
            <div class="register-info">
                <h4>💡 Informasi Pendaftaran:</h4>
                <ul>
                    <li>✅ Username minimal 3 karakter</li>
                    <li>✅ Password minimal 3 karakter</li>
                    <li>✅ Email bebas format (tidak perlu valid)</li>
                    <li>✅ Username harus unik</li>
                    <li>✅ Email boleh sama dengan user lain</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>