<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    // Create proposals table
    $sql = "CREATE TABLE IF NOT EXISTS proposals (
        proposal_id INT PRIMARY KEY AUTO_INCREMENT,
        product_offered_id INT,
        product_wanted_id INT,
        sender_id INT,
        receiver_id INT,
        status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_offered_id) REFERENCES products(product_id),
        FOREIGN KEY (product_wanted_id) REFERENCES products(product_id),
        FOREIGN KEY (sender_id) REFERENCES users(user_id),
        FOREIGN KEY (receiver_id) REFERENCES users(user_id)
    )";
    
    $pdo->exec($sql);
    echo "Proposals table created successfully!";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?> 