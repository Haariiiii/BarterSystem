<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle proposal actions (accept/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['proposal_id'])) {
    $proposal_id = $_POST['proposal_id'];
    $action = $_POST['action'];
    
    if ($action === 'accept' || $action === 'reject') {
        try {
            $stmt = $pdo->prepare("UPDATE proposals SET status = ? WHERE proposal_id = ? AND receiver_id = ?");
            $stmt->execute([$action . 'ed', $proposal_id, $_SESSION['user_id']]);
            
            if ($action === 'accept') {
                // Update product statuses
                $stmt = $pdo->prepare("
                    UPDATE products p
                    JOIN proposals pr ON p.product_id IN (pr.product_offered_id, pr.product_wanted_id)
                    SET p.status = 'traded'
                    WHERE pr.proposal_id = ?
                ");
                $stmt->execute([$proposal_id]);
            }
            
            header("Location: my_proposals.php?status=success&action=$action");
            exit();
        } catch (PDOException $e) {
            $error = "Failed to $action proposal: " . $e->getMessage();
        }
    }
}

// Fetch received proposals
$stmt = $pdo->prepare("
    SELECT 
        p.proposal_id,
        p.status as proposal_status,
        p.created_at,
        u.username as sender_name,
        po.title as offered_title,
        po.image_url as offered_image,
        po.expected_value as offered_value,
        pw.title as wanted_title,
        pw.image_url as wanted_image,
        pw.expected_value as wanted_value
    FROM proposals p
    JOIN users u ON p.sender_id = u.user_id
    JOIN products po ON p.product_offered_id = po.product_id
    JOIN products pw ON p.product_wanted_id = pw.product_id
    WHERE p.receiver_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$proposals = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Proposals - BarterTrade</title>
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
                <li><a href="my_proposals.php" class="active">My Proposals</a></li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard">
        <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
            <div class="success-message">
                Proposal <?php echo htmlspecialchars($_GET['action']); ?> successfully!
            </div>
        <?php endif; ?>

        <h2>Received Trade Proposals</h2>

        <?php if (empty($proposals)): ?>
            <div class="no-proposals">
                <p>You haven't received any trade proposals yet.</p>
            </div>
        <?php else: ?>
            <div class="proposals-grid">
                <?php foreach ($proposals as $proposal): ?>
                    <div class="proposal-card">
                        <div class="proposal-header">
                            <span class="proposal-from">From: <?php echo htmlspecialchars($proposal['sender_name']); ?></span>
                            <span class="proposal-date"><?php echo date('M d, Y', strtotime($proposal['created_at'])); ?></span>
                        </div>
                        
                        <div class="trade-items">
                            <div class="offered-item">
                                <h4>Offered Item</h4>
                                <img src="<?php echo htmlspecialchars($proposal['offered_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($proposal['offered_title']); ?>">
                                <p class="item-title"><?php echo htmlspecialchars($proposal['offered_title']); ?></p>
                                <p class="item-value">Value: $<?php echo htmlspecialchars($proposal['offered_value']); ?></p>
                            </div>
                            
                            <div class="trade-arrow">↔</div>
                            
                            <div class="wanted-item">
                                <h4>Your Item</h4>
                                <img src="<?php echo htmlspecialchars($proposal['wanted_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($proposal['wanted_title']); ?>">
                                <p class="item-title"><?php echo htmlspecialchars($proposal['wanted_title']); ?></p>
                                <p class="item-value">Value: $<?php echo htmlspecialchars($proposal['wanted_value']); ?></p>
                            </div>
                        </div>

                        <div class="proposal-status <?php echo $proposal['proposal_status']; ?>">
                            Status: <?php echo ucfirst($proposal['proposal_status']); ?>
                        </div>

                        <?php if ($proposal['proposal_status'] === 'pending'): ?>
                            <div class="proposal-actions">
                                <form method="POST" class="action-form">
                                    <input type="hidden" name="proposal_id" value="<?php echo $proposal['proposal_id']; ?>">
                                    <button type="submit" name="action" value="accept" class="accept-btn">Accept</button>
                                    <button type="submit" name="action" value="reject" class="reject-btn">Reject</button>
                                </form>
                            </div>
                        <?php elseif ($proposal['proposal_status'] === 'accepted'): ?>
                            <div class="proposal-actions">
                                <a href="chat.php?proposal_id=<?php echo $proposal['proposal_id']; ?>" class="chat-btn">
                                    Open Chat
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html> 