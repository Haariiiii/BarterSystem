<?php
require_once '../includes/config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$trade_id = $_GET['trade_id'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - BarterTrade</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/chat.css">
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <h2>Trade Discussion</h2>
        </div>
        <div class="chat-messages">
            <!-- Messages will be loaded here -->
        </div>
        <div class="chat-input">
            <input type="text" id="messageInput" placeholder="Type your message...">
            <button onclick="sendMessage()">Send</button>
        </div>
    </div>

    <script src="../assets/js/chat.js"></script>
    <script>
        const chat = new ChatSystem(<?php echo $_SESSION['user_id']; ?>, <?php echo $trade_id; ?>);

        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (message) {
                chat.sendMessage(message);
                input.value = '';
            }
        }

        document.getElementById('messageInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    </script>
</body>
</html> 