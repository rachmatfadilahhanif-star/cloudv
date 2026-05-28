<?php
session_start();

// Cek jika file database.php ada
$db_path = __DIR__ . '/config/database.php';
if (file_exists($db_path)) {
    require_once $db_path;
} else {
    die("Error: File config/database.php tidak ditemukan.");
}

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi!";
    } else {
        $query = "SELECT id, username, password FROM users WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Username tidak ditemukan!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cloud Storage</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>🔐 Login Cloud Storage</h2>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">👤 Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo isset($username) ? $username : ''; ?>" 
                           required placeholder="Masukkan username Anda">
                </div>
                
                <div class="form-group">
                    <label for="password">🔒 Password:</label>
                    <input type="password" id="password" name="password" 
                           required placeholder="Masukkan password Anda">
                </div>
                
                <button type="submit" class="btn">🎯 Login</button>
            </form>
            
            <p>Belum punya akun? <a href="register.php">Daftar gratis di sini</a></p>
        </div>
    </div>
</body>
</html>