<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if product_id is provided
if (!isset($_GET['product_id'])) {
    header("Location: index.php");
    exit();
}

$wanted_product_id = $_GET['product_id'];
$errors = [];
$success = false;

// Fetch the wanted product details
$stmt = $pdo->prepare("
    SELECT p.*, u.username 
    FROM products p 
    JOIN users u ON p.user_id = u.user_id 
    WHERE p.product_id = ? AND p.status = 'available'
");
$stmt->execute([$wanted_product_id]);
$wanted_product = $stmt->fetch();

if (!$wanted_product) {
    header("Location: index.php?error=product_not_found");
    exit();
}

// Fetch user's available products for trade
$stmt = $pdo->prepare("
    SELECT * FROM products 
    WHERE user_id = ? AND status = 'available' 
    AND product_id != ?
");
$stmt->execute([$_SESSION['user_id'], $wanted_product_id]);
$my_products = $stmt->fetchAll();

// Handle trade proposal submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['offered_product_id'])) {
    $offered_product_id = $_POST['offered_product_id'];
    
    // Validate the offered product
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE product_id = ? AND user_id = ? AND status = 'available'
    ");
    $stmt->execute([$offered_product_id, $_SESSION['user_id']]);
    $offered_product = $stmt->fetch();
    
    if (!$offered_product) {
        $errors[] = "Invalid product selection";
    } else {
        try {
            // Check if a proposal already exists
            $stmt = $pdo->prepare("
                SELECT * FROM proposals 
                WHERE product_offered_id = ? AND product_wanted_id = ? 
                AND status = 'pending'
            ");
            $stmt->execute([$offered_product_id, $wanted_product_id]);
            
            if ($stmt->fetch()) {
                $errors[] = "You already have a pending proposal for this trade";
            } else {
                // Create new proposal
                $stmt = $pdo->prepare("
                    INSERT INTO proposals (
                        product_offered_id, 
                        product_wanted_id, 
                        sender_id, 
                        receiver_id
                    ) VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $offered_product_id,
                    $wanted_product_id,
                    $_SESSION['user_id'],
                    $wanted_product['user_id']
                ]);
                
                $success = true;
            }
        } catch (PDOException $e) {
            $errors[] = "Failed to create proposal: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Propose Trade - BarterTrade</title>
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
                <li><a href="my_proposals.php">My Proposals</a></li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard">
        <?php if ($success): ?>
            <div class="success-message">
                Trade proposal sent successfully! <a href="my_proposals.php">View your proposals</a>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="trade-container">
            <h2>Propose Trade</h2>
            
            <div class="wanted-product">
                <h3>Product You Want</h3>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?php echo htmlspecialchars($wanted_product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($wanted_product['title']); ?>">
                    </div>
                    <div class="product-info">
                        <h4><?php echo htmlspecialchars($wanted_product['title']); ?></h4>
                        <p class="description"><?php echo htmlspecialchars($wanted_product['description']); ?></p>
                        <p class="value">Value: $<?php echo htmlspecialchars($wanted_product['expected_value']); ?></p>
                        <p class="owner">Owner: <?php echo htmlspecialchars($wanted_product['username']); ?></p>
                    </div>
                </div>
            </div>

            <?php if (empty($my_products)): ?>
                <div class="no-products">
                    <p>You don't have any available products to trade. <a href="add_product.php">Add a product</a></p>
                </div>
            <?php else: ?>
                <form method="POST" class="trade-form">
                    <h3>Select Your Product to Offer</h3>
                    <div class="products-grid">
                        <?php foreach ($my_products as $product): ?>
                            <div class="product-option">
                                <input type="radio" 
                                       name="offered_product_id" 
                                       id="product_<?php echo $product['product_id']; ?>" 
                                       value="<?php echo $product['product_id']; ?>" 
                                       required>
                                <label for="product_<?php echo $product['product_id']; ?>">
                                    <div class="product-card">
                                        <div class="product-image">
                                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['title']); ?>">
                                        </div>
                                        <div class="product-info">
                                            <h4><?php echo htmlspecialchars($product['title']); ?></h4>
                                            <p class="value">Value: $<?php echo htmlspecialchars($product['expected_value']); ?></p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="submit-proposal">Send Trade Proposal</button>
                </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html> 