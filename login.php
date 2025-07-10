<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $errors[] = "Both username and password are required";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT user_id, username, password_hash FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                
                // Check if profile is complete
                $stmt = $pdo->prepare("SELECT profile_complete FROM users WHERE user_id = ?");
                $stmt->execute([$user['user_id']]);
                $profile = $stmt->fetch();
                
                if (!$profile['profile_complete']) {
                    header("Location: complete_profile.php");
                } else {
                    $redirect = $_SESSION['redirect_url'] ?? 'index.php';
                    unset($_SESSION['redirect_url']);
                    header("Location: " . $redirect);
                }
                exit();
            } else {
                $errors[] = "Invalid username or password";
            }
        } catch (PDOException $e) {
            $errors[] = "Login failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - BarterTrade</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Login</h2>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <div class="form-links">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</body>
</html> 