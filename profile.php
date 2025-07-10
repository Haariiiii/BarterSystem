<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success_message = '';
$errors = [];

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Validation
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check if email is already taken by another user
    if ($email !== $user['email']) {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $errors[] = "Email is already taken";
        }
    }

    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
            $errors[] = "Invalid file type. Only JPG, PNG and GIF are allowed.";
        } else {
            $upload_dir = 'uploads/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $file_extension;
            $destination = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $destination)) {
                // Delete old profile image if it exists and is not the default
                if ($user['profile_image'] !== 'default.jpg' && file_exists($user['profile_image'])) {
                    unlink($user['profile_image']);
                }
                $profile_image = $destination;
            } else {
                $errors[] = "Failed to upload image";
            }
        }
    }

    // Update profile if no errors
    if (empty($errors)) {
        try {
            $sql = "UPDATE users SET 
                    full_name = ?, 
                    email = ?, 
                    phone = ?, 
                    address = ?";
            $params = [$full_name, $email, $phone, $address];
            
            if (isset($profile_image)) {
                $sql .= ", profile_image = ?";
                $params[] = $profile_image;
            }
            
            $sql .= " WHERE user_id = ?";
            $params[] = $_SESSION['user_id'];
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $success_message = "Profile updated successfully!";
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            $errors[] = "Failed to update profile: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile - BarterTrade</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <h1>BarterTrade</h1>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="add_product.php">Add Product</a></li>
                <li><a href="profile.php" class="active">My Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container profile-container">
        <h2>My Profile</h2>
        
        <?php if ($success_message): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="profile-image-container">
            <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" 
                 alt="Profile Image" 
                 class="profile-image">
        </div>

        <form method="POST" enctype="multipart/form-data" class="profile-form">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" 
                       value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="profile_image">Update Profile Image</label>
                <input type="file" id="profile_image" name="profile_image" accept="image/*">
                <small class="form-text">Leave empty to keep current image</small>
            </div>

            <button type="submit">Update Profile</button>
        </form>
    </div>
</body>
</html> 