<?php
require_once '../config/optimized-config.php';
requireLogin();

$user = getCurrentUser();

// Get conversations
$conversations = fetchAll(
    "SELECT 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
        END as contact_id,
        u.first_name, u.last_name, u.profile_photo,
        MAX(m.created_at) as last_message_time,
        (SELECT message FROM messages m2 
         WHERE (m2.sender_id = ? AND m2.receiver_id = contact_id) 
            OR (m2.sender_id = contact_id AND m2.receiver_id = ?)
         ORDER BY m2.created_at DESC LIMIT 1) as last_message,
        (SELECT COUNT(*) FROM messages m3 
         WHERE m3.sender_id = contact_id AND m3.receiver_id = ? AND m3.is_read = FALSE) as unread_count
     FROM messages m
     JOIN users u ON (CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END) = u.id
     WHERE m.sender_id = ? OR m.receiver_id = ?
     GROUP BY contact_id, u.first_name, u.last_name, u.profile_photo
     ORDER BY last_message_time DESC",
    [$user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id']]
);

// Get selected conversation
$selectedContactId = intval($_GET['contact'] ?? 0);
$selectedContact = null;
$messages = [];

if ($selectedContactId > 0) {
    $selectedContact = fetchOne(
        "SELECT id, first_name, last_name, profile_photo, role FROM users WHERE id = ?",
        [$selectedContactId]
    );
    
    if ($selectedContact) {
        // Get messages for this conversation
        $messages = fetchAll(
            "SELECT * FROM messages 
             WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
             ORDER BY created_at ASC",
            [$user['id'], $selectedContactId, $selectedContactId, $user['id']]
        );
        
        // Mark messages as read
        executeQuery(
            "UPDATE messages SET is_read = TRUE 
             WHERE sender_id = ? AND receiver_id = ? AND is_read = FALSE",
            [$selectedContactId, $user['id']]
        );
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-layout">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <a href="<?php echo BASE_URL; ?>" class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <h1><?php echo APP_NAME; ?></h1>
                </a>
            </div>
            
            <div class="nav-menu">
                <div class="nav-item">
                    <a href="/dashboard/<?php echo $user['role']; ?>.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <?php if ($user['role'] === 'mentor'): ?>
                    <div class="nav-item">
                        <a href="/sessions/index.php" class="nav-link">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Sessions</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="/students/index.php" class="nav-link">
                            <i class="fas fa-user-graduate"></i>
                            <span>My Students</span>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="nav-item">
                        <a href="/mentors/browse.php" class="nav-link">
                            <i class="fas fa-search"></i>
                            <span>Find Mentors</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="/sessions/index.php" class="nav-link">
                            <i class="fas fa-calendar-alt"></i>
                            <span>My Sessions</span>
                        </a>
                    </div>
                <?php endif; ?>
                <div class="nav-item">
                    <a href="/messages/index.php" class="nav-link active">
                        <i class="fas fa-envelope"></i>
                        <span>Messages</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="/profile/edit.php" class="nav-link">
                        <i class="fas fa-user-edit"></i>
                        <span>Profile</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="/auth/logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button class="menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2>Messages</h2>
                </div>
                
                <div class="header-right">
                    <button class="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    
                    <div class="user-menu">
                        <img src="<?php echo $user['profile_photo'] ? '../uploads/' . $user['profile_photo'] : '../assets/images/default-profile.svg'; ?>" 
                             alt="Profile" class="user-avatar">
                    </div>
                </div>
            </header>

            <!-- Messages Content -->
            <div class="content messages-content">
                <div class="messages-container">
                    <!-- Conversations List -->
                    <div class="conversations-panel">
                        <div class="conversations-header">
                            <h3>Conversations</h3>
                            <button class="btn btn-primary btn-sm" onclick="composeMessage()">
                                <i class="fas fa-plus"></i>
                                New Message
                            </button>
                        </div>
                        
                        <div class="conversations-list">
                            <?php if (empty($conversations)): ?>
                                <div class="no-conversations">
                                    <i class="fas fa-comments" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                                    <p>No conversations yet</p>
                                    <button class="btn btn-primary" onclick="composeMessage()">Start a conversation</button>
                                </div>
                            <?php else: ?>
                                <?php foreach ($conversations as $conversation): ?>
                                    <div class="conversation-item <?php echo $selectedContactId == $conversation['contact_id'] ? 'active' : ''; ?>"
                                         onclick="selectConversation(<?php echo $conversation['contact_id']; ?>)">
                                        <img src="<?php echo $conversation['profile_photo'] ? '../uploads/' . $conversation['profile_photo'] : '../assets/images/default-profile.svg'; ?>" 
                                             alt="Contact" class="conversation-avatar">
                                        <div class="conversation-info">
                                            <div class="conversation-header">
                                                <h5><?php echo htmlspecialchars($conversation['first_name'] . ' ' . $conversation['last_name']); ?></h5>
                                                <small><?php echo formatTimeAgo($conversation['last_message_time']); ?></small>
                                            </div>
                                            <p class="last-message">
                                                <?php echo htmlspecialchars(substr($conversation['last_message'], 0, 50)); ?>
                                                <?php if (strlen($conversation['last_message']) > 50): ?>...<?php endif; ?>
                                            </p>
                                        </div>
                                        <?php if ($conversation['unread_count'] > 0): ?>
                                            <span class="unread-badge"><?php echo $conversation['unread_count']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Chat Panel -->
                    <div class="chat-panel">
                        <?php if ($selectedContact): ?>
                            <!-- Chat Header -->
                            <div class="chat-header">
                                <img src="<?php echo $selectedContact['profile_photo'] ? '../uploads/' . $selectedContact['profile_photo'] : '../assets/images/default-profile.svg'; ?>" 
                                     alt="Contact" class="chat-avatar">
                                <div class="chat-contact-info">
                                    <h4><?php echo htmlspecialchars($selectedContact['first_name'] . ' ' . $selectedContact['last_name']); ?></h4>
                                    <span class="contact-role"><?php echo ucfirst($selectedContact['role']); ?></span>
                                </div>
                                <div class="chat-actions">
                                    <button class="btn btn-ghost btn-sm">
                                        <i class="fas fa-video"></i>
                                    </button>
                                    <button class="btn btn-ghost btn-sm">
                                        <i class="fas fa-phone"></i>
                                    </button>
                                    <button class="btn btn-ghost btn-sm">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Messages Area -->
                            <div class="messages-area" id="messagesArea">
                                <?php foreach ($messages as $message): ?>
                                    <div class="message <?php echo $message['sender_id'] == $user['id'] ? 'sent' : 'received'; ?>">
                                        <div class="message-content">
                                            <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                            <small class="message-time"><?php echo date('g:i A', strtotime($message['created_at'])); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Message Input -->
                            <div class="message-input-area">
                                <form id="messageForm" onsubmit="sendMessage(event)">
                                    <div class="message-input-group">
                                        <button type="button" class="attachment-btn">
                                            <i class="fas fa-paperclip"></i>
                                        </button>
                                        <textarea id="messageInput" placeholder="Type your message..." rows="1"></textarea>
                                        <button type="submit" class="send-btn">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <!-- No Chat Selected -->
                            <div class="no-chat-selected">
                                <i class="fas fa-comments" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                                <h3>Select a conversation</h3>
                                <p>Choose a conversation from the list to start messaging.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
    .messages-content {
        padding: 0;
        height: calc(100vh - var(--header-height));
    }

    .messages-container {
        display: grid;
        grid-template-columns: 350px 1fr;
        height: 100%;
        background: var(--card-color);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-xl);
        overflow: hidden;
        margin: var(--spacing-lg);
    }

    .conversations-panel {
        border-right: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
    }

    .conversations-header {
        padding: var(--spacing-lg);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--surface-color);
    }

    .conversations-header h3 {
        margin: 0;
        color: var(--text-primary);
    }

    .conversations-list {
        flex: 1;
        overflow-y: auto;
    }

    .conversation-item {
        display: flex;
        align-items: center;
        gap: var(--spacing-md);
        padding: var(--spacing-md);
        cursor: pointer;
        transition: background-color var(--transition-fast);
        border-bottom: 1px solid var(--divider-color);
        position: relative;
    }

    .conversation-item:hover {
        background-color: var(--surface-color);
    }

    .conversation-item.active {
        background-color: var(--primary-color);
        color: white;
    }

    .conversation-item.active * {
        color: white !important;
    }

    .conversation-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--border-color);
    }

    .conversation-info {
        flex: 1;
        min-width: 0;
    }

    .conversation-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--spacing-xs);
    }

    .conversation-header h5 {
        margin: 0;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .conversation-header small {
        color: var(--text-muted);
        font-size: 0.75rem;
    }

    .last-message {
        margin: 0;
        font-size: 0.8rem;
        color: var(--text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .unread-badge {
        position: absolute;
        top: var(--spacing-sm);
        right: var(--spacing-sm);
        background: var(--error-color);
        color: white;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.125rem 0.375rem;
        border-radius: 50px;
        min-width: 1.25rem;
        text-align: center;
    }

    .no-conversations {
        padding: var(--spacing-2xl);
        text-align: center;
        color: var(--text-muted);
    }

    .chat-panel {
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .chat-header {
        padding: var(--spacing-lg);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: var(--spacing-md);
        background: var(--surface-color);
    }

    .chat-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--border-color);
    }

    .chat-contact-info {
        flex: 1;
    }

    .chat-contact-info h4 {
        margin: 0 0 var(--spacing-xs) 0;
        color: var(--text-primary);
    }

    .contact-role {
        color: var(--text-muted);
        font-size: 0.875rem;
        text-transform: capitalize;
    }

    .chat-actions {
        display: flex;
        gap: var(--spacing-sm);
    }

    .messages-area {
        flex: 1;
        padding: var(--spacing-lg);
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: var(--spacing-md);
    }

    .message {
        display: flex;
        max-width: 70%;
    }

    .message.sent {
        align-self: flex-end;
    }

    .message.received {
        align-self: flex-start;
    }

    .message-content {
        background: var(--surface-color);
        padding: var(--spacing-sm) var(--spacing-md);
        border-radius: var(--radius-lg);
        position: relative;
    }

    .message.sent .message-content {
        background: var(--primary-color);
        color: white;
    }

    .message-content p {
        margin: 0 0 var(--spacing-xs) 0;
        line-height: 1.4;
    }

    .message-time {
        font-size: 0.75rem;
        opacity: 0.7;
    }

    .message-input-area {
        padding: var(--spacing-lg);
        border-top: 1px solid var(--border-color);
        background: var(--surface-color);
    }

    .message-input-group {
        display: flex;
        align-items: flex-end;
        gap: var(--spacing-sm);
        background: var(--background-color);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: var(--spacing-sm);
    }

    .attachment-btn, .send-btn {
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        padding: var(--spacing-sm);
        border-radius: var(--radius-sm);
        transition: all var(--transition-fast);
    }

    .attachment-btn:hover, .send-btn:hover {
        background: var(--surface-color);
        color: var(--primary-color);
    }

    .send-btn {
        color: var(--primary-color);
    }

    #messageInput {
        flex: 1;
        border: none;
        background: none;
        resize: none;
        outline: none;
        font-family: inherit;
        font-size: 0.9rem;
        line-height: 1.4;
        max-height: 120px;
        min-height: 20px;
    }

    .no-chat-selected {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: var(--text-muted);
        text-align: center;
    }

    @media (max-width: 768px) {
        .messages-container {
            grid-template-columns: 1fr;
            margin: var(--spacing-md);
        }
        
        .conversations-panel {
            display: none;
        }
        
        .conversations-panel.show {
            display: flex;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 10;
            background: var(--card-color);
        }
    }
    </style>

    <script>
    let selectedContactId = <?php echo $selectedContactId; ?>;
    let currentUserId = <?php echo $user['id']; ?>;

    function selectConversation(contactId) {
        window.location.href = `/messages/index.php?contact=${contactId}`;
    }

    function composeMessage() {
        // This would open a modal or redirect to compose page
        window.location.href = '/messages/compose.php';
    }

    async function sendMessage(event) {
        event.preventDefault();
        
        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value.trim();
        
        if (!message || !selectedContactId) return;
        
        try {
            const response = await fetch('/api/messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'send',
                    receiver_id: selectedContactId,
                    message: message
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Add message to UI
                addMessageToUI(message, true);
                messageInput.value = '';
                messageInput.style.height = 'auto';
                
                // Scroll to bottom
                scrollToBottom();
            } else {
                window.app.showToast('Failed to send message', 'error');
            }
        } catch (error) {
            console.error('Error sending message:', error);
            window.app.showToast('Failed to send message', 'error');
        }
    }

    function addMessageToUI(message, isSent) {
        const messagesArea = document.getElementById('messagesArea');
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${isSent ? 'sent' : 'received'}`;
        
        const now = new Date();
        const timeString = now.toLocaleTimeString([], {hour: 'numeric', minute: '2-digit'});
        
        messageDiv.innerHTML = `
            <div class="message-content">
                <p>${message.replace(/\n/g, '<br>')}</p>
                <small class="message-time">${timeString}</small>
            </div>
        `;
        
        messagesArea.appendChild(messageDiv);
    }

    function scrollToBottom() {
        const messagesArea = document.getElementById('messagesArea');
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    // Auto-resize textarea
    document.getElementById('messageInput')?.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });

    // Enter to send (Shift+Enter for new line)
    document.getElementById('messageInput')?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('messageForm').dispatchEvent(new Event('submit'));
        }
    });

    // Scroll to bottom on load
    document.addEventListener('DOMContentLoaded', function() {
        scrollToBottom();
    });

    // Poll for new messages every 5 seconds
    if (selectedContactId > 0) {
        setInterval(async function() {
            try {
                const response = await fetch(`/api/messages.php?action=get&contact_id=${selectedContactId}&after=${getLastMessageTime()}`);
                const result = await response.json();
                
                if (result.success && result.messages.length > 0) {
                    result.messages.forEach(message => {
                        addMessageToUI(message.message, message.sender_id == currentUserId);
                    });
                    scrollToBottom();
                }
            } catch (error) {
                console.error('Error polling messages:', error);
            }
        }, 5000);
    }

    function getLastMessageTime() {
        const messages = document.querySelectorAll('.message');
        if (messages.length > 0) {
            // This would need to be implemented to track message timestamps
            return Date.now();
        }
        return 0;
    }
    </script>

    <script src="../assets/js/app.js"></script>
</body>
</html>
