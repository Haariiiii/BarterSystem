<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['proposal_id'])) {
    exit(json_encode(['error' => 'Unauthorized']));
}

$proposal_id = $_GET['proposal_id'];
$last_message_id = $_GET['last_message_id'] ?? 0;

// Verify user is part of this trade
$stmt = $pdo->prepare("
    SELECT 1 FROM proposals 
    WHERE proposal_id = ? 
    AND (sender_id = ? OR receiver_id = ?)
    AND status = 'accepted'
");
$stmt->execute([$proposal_id, $_SESSION['user_id'], $_SESSION['user_id']]);
if (!$stmt->fetch()) {
    exit(json_encode(['error' => 'Unauthorized']));
}

// Fetch new messages
$stmt = $pdo->prepare("
    SELECT m.*, u.username, u.profile_image
    FROM chat_messages m
    JOIN users u ON m.sender_id = u.user_id
    WHERE m.proposal_id = ?
    AND m.message_id > ?
    ORDER BY m.sent_at ASC
");
$stmt->execute([$proposal_id, $last_message_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check typing status
$typing = false;
$typing_user_id = null;
if (isset($_SESSION['typing_status'][$proposal_id])) {
    $status = $_SESSION['typing_status'][$proposal_id];
    if ($status['typing'] && (time() - $status['timestamp']) < 3) {
        $typing = true;
        $typing_user_id = $status['user_id'];
    }
}

echo json_encode([
    'messages' => $messages,
    'typing' => $typing,
    'typing_user_id' => $typing_user_id
]); 