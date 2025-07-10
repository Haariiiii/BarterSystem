<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user's products
$stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$userProducts = $stmt->fetchAll();

// Fetch all available products except user's own
$stmt = $pdo->prepare("
    SELECT p.*, u.username 
    FROM products p 
    JOIN users u ON p.user_id = u.user_id 
    WHERE p.user_id != ? AND p.status = 'available' 
    ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$availableProducts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarterTrade - Home</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <h1>BarterTrade</h1>
            </div>
            <ul class="nav-links">
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="add_product.php" class="add-product-btn">Add Product</a></li>
                <li><a href="my_proposals.php">My Proposals</a></li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard">
        <?php if (isset($_GET['added'])): ?>
            <div class="success-message">
                Product added successfully!
            </div>
        <?php endif; ?>

        <div class="welcome-section">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        </div>

        <section class="my-products">
            <h3 class="section-title">My Products</h3>
            <div class="product-grid">
                <?php if (empty($userProducts)): ?>
                    <p class="no-products">You haven't added any products yet. 
                        <a href="add_product.php">Add your first product</a>
                    </p>
                <?php else: ?>
                    <?php foreach ($userProducts as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['title']); ?>">
                            </div>
                            <div class="product-info">
                                <h4><?php echo htmlspecialchars($product['title']); ?></h4>
                                <p class="description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                                <p class="value">Value: $<?php echo htmlspecialchars($product['expected_value']); ?></p>
                                <p class="category">Category: <?php echo htmlspecialchars($product['category']); ?></p>
                                <p class="condition">Condition: <?php echo htmlspecialchars($product['condition_status']); ?></p>
                                <p class="status <?php echo strtolower($product['status']); ?>">
                                    Status: <?php echo ucfirst(htmlspecialchars($product['status'])); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="available-products">
            <h3 class="section-title">Available for Trade</h3>
            <div class="product-grid">
                <?php if (empty($availableProducts)): ?>
                    <p class="no-products">No products available for trade at the moment.</p>
                <?php else: ?>
                    <?php foreach ($availableProducts as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php 
                                $image_path = htmlspecialchars($product['image_url']);
                                if (!empty($image_path) && file_exists($image_path)) {
                                    echo '<img src="' . $image_path . '" alt="' . htmlspecialchars($product['title']) . '">';
                                } else {
                                    echo '<img src="assets/images/default-product.jpg" alt="Default product image">';
                                }
                                ?>
                            </div>
                            <div class="product-info">
                                <h4><?php echo htmlspecialchars($product['title']); ?></h4>
                                <p class="description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                                <p class="value">Value: $<?php echo htmlspecialchars($product['expected_value']); ?></p>
                                <p class="category">Category: <?php echo htmlspecialchars($product['category']); ?></p>
                                <p class="condition">Condition: <?php echo htmlspecialchars($product['condition_status']); ?></p>
                                <p class="owner">Posted by: <?php echo htmlspecialchars($product['username']); ?></p>
                                <button class="trade-btn" onclick="location.href='trade.php?product_id=<?php echo $product['product_id']; ?>'">
                                    Propose Trade
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 BarterTrade. All rights reserved.</p>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html> 