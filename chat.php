<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['proposal_id'])) {
    header("Location: index.php");
    exit();
}

$proposal_id = $_GET['proposal_id'];

// Fetch proposal details and verify user is part of this trade
$stmt = $pdo->prepare("
    SELECT p.*, 
           u1.username as sender_name,
           u2.username as receiver_name,
           po.title as offered_title,
           pw.title as wanted_title
    FROM proposals p
    JOIN users u1 ON p.sender_id = u1.user_id
    JOIN users u2 ON p.receiver_id = u2.user_id
    JOIN products po ON p.product_offered_id = po.product_id
    JOIN products pw ON p.product_wanted_id = pw.product_id
    WHERE p.proposal_id = ? 
    AND (p.sender_id = ? OR p.receiver_id = ?)
    AND p.status = 'accepted'
");
$stmt->execute([$proposal_id, $_SESSION['user_id'], $_SESSION['user_id']]);
$proposal = $stmt->fetch();

if (!$proposal) {
    header("Location: my_proposals.php");
    exit();
}

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $stmt = $pdo->prepare("
            INSERT INTO chat_messages (proposal_id, sender_id, message)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$proposal_id, $_SESSION['user_id'], $message]);
    }
    // Return early if it's an AJAX request
    if (isset($_POST['ajax'])) {
        exit();
    }
}

// Fetch messages
$stmt = $pdo->prepare("
    SELECT m.*, u.username, u.profile_image
    FROM chat_messages m
    JOIN users u ON m.sender_id = u.user_id
    WHERE m.proposal_id = ?
    ORDER BY m.sent_at ASC
");
$stmt->execute([$proposal_id]);
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Chat - BarterTrade</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <h1>BarterTrade</h1>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="my_proposals.php">My Proposals</a></li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard">
        <div class="chat-container">
            <div class="chat-header">
                <div class="trade-avatar">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="trade-details">
                    <h2>Trade Chat</h2>
                    <p>
                        <?php echo htmlspecialchars($proposal['offered_title']); ?> 
                        <i class="fas fa-exchange-alt"></i> 
                        <?php echo htmlspecialchars($proposal['wanted_title']); ?>
                    </p>
                </div>
            </div>

            <div class="messages-container" id="messages">
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo $message['sender_id'] === $_SESSION['user_id'] ? 'sent' : 'received'; ?>"
                         data-message-id="<?php echo $message['message_id']; ?>">
                        <div class="message-avatar">
                            <?php if (!empty($message['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($message['profile_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($message['username']); ?>">
                            <?php else: ?>
                                <img src="assets/images/default-avatar.png" alt="Default avatar">
                            <?php endif; ?>
                        </div>
                        <div class="message-bubble">
                            <?php if ($message['sender_id'] !== $_SESSION['user_id']): ?>
                                <div class="message-header">
                                    <span class="username"><?php echo htmlspecialchars($message['username']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="message-content">
                                <?php echo htmlspecialchars($message['message']); ?>
                                <div class="message-status">
                                    <span class="time"><?php echo date('H:i', strtotime($message['sent_at'])); ?></span>
                                    <?php if ($message['sender_id'] === $_SESSION['user_id']): ?>
                                        <i class="fas fa-check-double"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="typing-indicator" style="display: none;">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>

            <form id="chat-form" class="chat-form" method="POST">
                <input type="text" name="message" id="message-input" 
                       placeholder="Type a message..." autocomplete="off" required>
                <button type="submit">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </main>

    <script>
        $(document).ready(function() {
            const messages = $('#messages');
            let typingTimer;
            let isTyping = false;

            function scrollToBottom() {
                messages.scrollTop(messages[0].scrollHeight);
            }
            scrollToBottom();

            // Handle form submission
            $('#chat-form').on('submit', function(e) {
                e.preventDefault();
                const messageInput = $('#message-input');
                const message = messageInput.val().trim();
                
                if (message) {
                    // Add message immediately to UI
                    const now = new Date();
                    const timeStr = now.getHours().toString().padStart(2, '0') + ':' + 
                                  now.getMinutes().toString().padStart(2, '0');
                    
                    const messageHtml = `
                        <div class="message sent">
                            <div class="message-content">
                                ${message}
                                <div class="message-status">
                                    <span class="time">${timeStr}</span>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    messages.append(messageHtml);
                    scrollToBottom();
                    messageInput.val('');

                    // Send to server
                    $.post('chat.php?proposal_id=<?php echo $proposal_id; ?>', {
                        message: message,
                        ajax: true
                    }).done(function() {
                        // Update status to delivered
                        $('.message.sent').last().find('.fa-check')
                            .removeClass('fa-check')
                            .addClass('fa-check-double');
                    });
                }
            });

            // Typing indicator
            $('#message-input').on('input', function() {
                if (!isTyping) {
                    isTyping = true;
                    $.post('typing.php', {
                        proposal_id: <?php echo $proposal_id; ?>,
                        typing: true
                    });
                }
                
                clearTimeout(typingTimer);
                typingTimer = setTimeout(function() {
                    isTyping = false;
                    $.post('typing.php', {
                        proposal_id: <?php echo $proposal_id; ?>,
                        typing: false
                    });
                }, 2000);
            });

            // Poll for new messages and typing status
            function updateChat() {
                $.get('get_messages.php', {
                    proposal_id: <?php echo $proposal_id; ?>,
                    last_message_id: $('.message').last().data('message-id') || 0
                }, function(data) {
                    if (data.messages && data.messages.length > 0) {
                        data.messages.forEach(function(message) {
                            const messageHtml = `
                                <div class="message ${message.sender_id == <?php echo $_SESSION['user_id']; ?> ? 'sent' : 'received'}" 
                                     data-message-id="${message.message_id}">
                                    <div class="message-content">
                                        ${message.sender_id != <?php echo $_SESSION['user_id']; ?> ? 
                                            `<div class="message-header">
                                                <span class="username">${message.username}</span>
                                            </div>` : ''}
                                        ${message.message}
                                        <div class="message-status">
                                            <span class="time">${message.sent_at}</span>
                                            ${message.sender_id == <?php echo $_SESSION['user_id']; ?> ? 
                                                '<i class="fas fa-check-double"></i>' : ''}
                                        </div>
                                    </div>
                                </div>
                            `;
                            messages.append(messageHtml);
                        });
                        scrollToBottom();
                    }
                    
                    // Update typing indicator
                    $('.typing-indicator').toggle(data.typing && data.typing_user_id != <?php echo $_SESSION['user_id']; ?>);
                });
            }

            setInterval(updateChat, 2000);
        });
    </script>
</body>
</html> 