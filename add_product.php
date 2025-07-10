<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Add this debugging section
$check = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
$check->execute([$_SESSION['user_id']]);
$user = $check->fetch();

if (!$user) {
    // User doesn't exist in database
    session_destroy();
    header("Location: login.php?error=invalid_session");
    exit();
}

var_dump($_SESSION);  // Temporarily add this to see session contents

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $expected_value = trim($_POST['expected_value'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $condition = trim($_POST['condition'] ?? '');

    // Validation
    if (empty($title)) {
        $errors[] = "Product title is required";
    }
    if (empty($description)) {
        $errors[] = "Product description is required";
    }
    if (empty($expected_value) || !is_numeric($expected_value)) {
        $errors[] = "Valid expected value is required";
    }
    if (empty($category)) {
        $errors[] = "Category is required";
    }
    if (empty($condition)) {
        $errors[] = "Condition is required";
    }

    // Handle image upload
    $image_url = '';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['product_image']['type'], $allowed_types)) {
            $errors[] = "Invalid file type. Only JPG, PNG and GIF are allowed.";
        } else {
            $upload_dir = 'uploads/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $file_extension;
            $destination = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $destination)) {
                $image_url = $destination;
            } else {
                $errors[] = "Failed to upload image. Error: " . error_get_last()['message'];
            }
        }
    } else {
        $errors[] = "Product image is required";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO products (
                    user_id, 
                    title, 
                    description, 
                    expected_value, 
                    category, 
                    condition_status, 
                    image_url,
                    status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'available')
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $title,
                $description,
                $expected_value,
                $category,
                $condition,
                $image_url
            ]);
            
            $success = true;
            header("Location: index.php?added=1");
            exit();
        } catch (PDOException $e) {
            error_log("Session user_id: " . $_SESSION['user_id']);
            error_log("SQL Error: " . $e->getMessage());
            error_log("SQL State: " . $e->getCode());
            $errors[] = "Failed to add product: " . $e->getMessage();
            
            // Check if user exists
            $check = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
            $check->execute([$_SESSION['user_id']]);
            $user = $check->fetch();
            if (!$user) {
                $errors[] = "User ID not found in database. Please try logging in again.";
            }
        }
    }
}

// Debug information (remove in production)
if (!empty($errors)) {
    error_log("User ID from session: " . $_SESSION['user_id']);
    error_log("Last SQL error: " . print_r($pdo->errorInfo(), true));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product - BarterTrade</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Add New Product</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="product-form">
            <div class="form-group">
                <label for="title">Product Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="expected_value">Expected Value ($)</label>
                <input type="number" id="expected_value" name="expected_value" min="0" step="0.01" value="<?php echo htmlspecialchars($expected_value ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="">Select Category</option>
                    <option value="electronics" <?php echo ($category ?? '') === 'electronics' ? 'selected' : ''; ?>>Electronics</option>
                    <option value="furniture" <?php echo ($category ?? '') === 'furniture' ? 'selected' : ''; ?>>Furniture</option>
                    <option value="clothing" <?php echo ($category ?? '') === 'clothing' ? 'selected' : ''; ?>>Clothing</option>
                    <option value="books" <?php echo ($category ?? '') === 'books' ? 'selected' : ''; ?>>Books</option>
                    <option value="sports" <?php echo ($category ?? '') === 'sports' ? 'selected' : ''; ?>>Sports Equipment</option>
                    <option value="other" <?php echo ($category ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="condition">Condition</label>
                <select id="condition" name="condition" required>
                    <option value="">Select Condition</option>
                    <option value="new" <?php echo ($condition ?? '') === 'new' ? 'selected' : ''; ?>>New</option>
                    <option value="like_new" <?php echo ($condition ?? '') === 'like_new' ? 'selected' : ''; ?>>Like New</option>
                    <option value="good" <?php echo ($condition ?? '') === 'good' ? 'selected' : ''; ?>>Good</option>
                    <option value="fair" <?php echo ($condition ?? '') === 'fair' ? 'selected' : ''; ?>>Fair</option>
                    <option value="poor" <?php echo ($condition ?? '') === 'poor' ? 'selected' : ''; ?>>Poor</option>
                </select>
            </div>

            <div class="form-group">
                <label for="product_image">Product Image</label>
                <input type="file" id="product_image" name="product_image" accept="image/*" required>
                <div id="image-preview"></div>
            </div>

            <button type="submit">Add Product</button>
        </form>
    </div>

    <script>
        // Image preview functionality
        document.getElementById('product_image').addEventListener('change', function(e) {
            const preview = document.getElementById('image-preview');
            const file = e.target.files[0];
            const reader = new FileReader();

            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" class="preview-image">`;
            }

            if (file) {
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html> 