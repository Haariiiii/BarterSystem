<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['proposal_id'])) {
    exit(json_encode(['error' => 'Unauthorized']));
}

$proposal_id = $_POST['proposal_id'];
$typing = $_POST['typing'] ?? false;

// Store typing status in session or database
$_SESSION['typing_status'][$proposal_id] = [
    'user_id' => $_SESSION['user_id'],
    'typing' => $typing,
    'timestamp' => time()
];

echo json_encode(['success' => true]); 