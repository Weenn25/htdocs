<?php

$_SESSION_NAME = 'STUDENT_SESSION';
if (session_status() === PHP_SESSION_NONE) {
    session_name($_SESSION_NAME);
    session_start();
}

require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'student') {
    header('Location: student-login.php');
    exit;
}

$student_id = intval($_SESSION['user_id']);
$student_name = htmlspecialchars($_SESSION['user_name'] ?? 'Student');
$current_page = 'student_chat.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with Teachers - GGF Christian School</title>
    <link rel="stylesheet" href="css/student_v2.css">
    <style>
        /* Notification badge styles */
        .teacher-unread-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-weight: 700;
            font-size: 12px;
            margin-left: 8px;
            animation: badge-pop 0.3s ease-out;
        }

        .teacher-unread-badge.hidden {
            display: none;
        }

        @keyframes badge-pop {
            0% { transform: scale(0.5); opacity: 0; }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }

        .chat-container {
            display: flex;
            gap: 20px;
            height: calc(100vh - 200px);
            background: #f8fafc;
        }
        
        .teachers-list-panel {
            width: 280px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .teachers-list-header {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            color: #1e293b;
        }
        
        .teachers-list-body {
            flex: 1;
            overflow-y: auto;
            padding: 8px;
        }
        
        .teacher-item {
            padding: 12px;
            margin-bottom: 8px;
            background: #f8fafc;
            border-radius: 6px;
            cursor: pointer;
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }
        
        .teacher-item:hover {
            background: #f1f5f9;
            border-left-color: #3b82f6;
        }
        
        .teacher-item.active {
            background: #eff6ff;
            border-left-color: #3b82f6;
            font-weight: 600;
        }
        
        .teacher-name {
            font-weight: 600;
            font-size: 14px;
            color: #1e293b;
        }
        
        .teacher-subject {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
        
        .chat-panel {
            flex: 1;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .chat-header {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        
        .chat-header h3 {
            margin: 0;
            font-size: 16px;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .message {
            display: flex;
            margin-bottom: 12px;
            animation: fadeIn 0.3s ease-in;
            align-items: flex-end;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .message.own {
            justify-content: flex-end;
        }
        
        .message > div {
            display: flex;
            flex-direction: column;
        }
        
        .message.own > div {
            align-self: flex-end;
        }
        
        .message-bubble {
            max-width: 35%;
            min-width: fit-content;
            padding: 12px 16px;
            border-radius: 12px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .message.own .message-bubble {
            background: #3b82f6;
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .message.other .message-bubble {
            background: #e2e8f0;
            color: #1e293b;
            border-bottom-left-radius: 4px;
        }
        
        .message-time {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 2px;
            padding: 0 4px;
        }
        
        .message.own .message-time {
            align-self: flex-end;
        }
        
        .chat-input-area {
            padding: 16px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 8px;
        }
        
        .chat-input {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            resize: none;
            max-height: 100px;
        }
        
        .chat-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .chat-send-btn {
            padding: 10px 16px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
            white-space: nowrap;
        }
        
        .chat-send-btn:hover {
            background: #2563eb;
        }
        
        .empty-state {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #94a3b8;
            text-align: center;
            flex-direction: column;
            gap: 12px;
        }
        
        .empty-state-icon {
            font-size: 48px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .chat-container {
                flex-direction: column;
                gap: 12px;
            }
            
            .teachers-list-panel {
                width: 100%;
                max-height: 150px;
            }
            
            .message-bubble {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <img src="g2flogo.png" alt="Logo" style="height: 40px;">
            <div class="navbar-text">
                <div class="navbar-title">Glorious God's Family</div>
                <div class="navbar-subtitle">Christian School</div>
            </div>
        </div>
        <div class="navbar-actions">
            <div class="user-menu">
                <span><?php echo $student_name; ?></span>
                <button class="btn-icon">⋮</button>
            </div>
        </div>
    </nav>

    <div class="page-wrapper">
        <?php include __DIR__ . '/includes/student-sidebar.php'; ?>

        <main class="main">
            <header class="header">
                <h1>Chat with Teachers</h1>
                <p style="color: #666; margin-top: 4px; font-size: 14px;">Send messages to your teachers</p>
            </header>

            <div class="chat-container">
                <!-- Teachers List -->
                <div class="teachers-list-panel">
                    <div class="teachers-list-header">Teachers</div>
                    <div class="teachers-list-body" id="teachersList">
                        <div style="padding: 20px; text-align: center; color: #94a3b8; font-size: 14px;">
                            Loading teachers...
                        </div>
                    </div>
                </div>

                <!-- Chat Panel -->
                <div class="chat-panel">
                    <div id="chatArea" style="display: none; flex-direction: column; height: 100%;">
                        <div class="chat-header" style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 id="chatTeacherName" style="margin: 0;"></h3>
                            <button class="chat-delete-btn" style="background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 13px;">Delete</button>
                        </div>
                        <div class="chat-messages" id="messagesContainer"></div>
                        <div class="chat-input-area" style="display: flex; padding: 16px; border-top: 1px solid #e2e8f0; gap: 8px;">
                            <textarea class="chat-input" id="messageInput" placeholder="Type your message..." rows="2"></textarea>
                            <button class="chat-send-btn">Send</button>
                        </div>
                    </div>
                    
                    <div id="noChat" class="empty-state">
                        <div class="empty-state-icon">💬</div>
                        <div>Select a teacher to start chatting</div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        let teachers = [];
        let selectedTeacherId = null;
        let messages = {};
        let cachedMessagesCount = 0;
        let currentRequestId = null; // Track the current request

        // Notification management functions
        function getUnreadConversations() {
            const unread = JSON.parse(localStorage.getItem('unreadConversations_student') || '{}');
            return unread;
        }

        function setUnreadCount(teacherId, count) {
            const unread = getUnreadConversations();
            if (count > 0) {
                unread[teacherId] = count;
            } else {
                delete unread[teacherId];
            }
            localStorage.setItem('unreadConversations_student', JSON.stringify(unread));
            updateNotificationBadge();
        }

        function markConversationAsRead(teacherId) {
            setUnreadCount(teacherId, 0);
        }

        function updateNotificationBadge() {
            const unread = getUnreadConversations();
            
            // Update each teacher's badge
            document.querySelectorAll('.teacher-unread-badge').forEach(badge => {
                const teacherId = parseInt(badge.getAttribute('data-teacher-id'));
                const count = unread[teacherId] || 0;
                
                if (count > 0) {
                    badge.textContent = count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            });
        }

        function addUnreadMessage(teacherId) {
            const unread = getUnreadConversations();
            unread[teacherId] = (unread[teacherId] || 0) + 1;
            localStorage.setItem('unreadConversations_student', JSON.stringify(unread));
            playNotificationSound();
            updateNotificationBadge();
        }

        function playNotificationSound() {
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = 800; // Frequency in Hz
                oscillator.type = 'sine';
                
                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.5);
            } catch (error) {
                console.log('Could not play notification sound:', error);
            }
        }

        // Load teachers
        async function loadTeachers() {
            try {
                const response = await fetch('api/get_chat_teachers.php');
                const data = await response.json();
                teachers = data.teachers || [];
                renderTeachersList();
                // Update badges immediately after rendering
                updateNotificationBadge();
            } catch (error) {
                console.error('Error loading teachers:', error);
            }
        }

        function renderTeachersList() {
            const panel = document.getElementById('teachersList');
            if (teachers.length === 0) {
                panel.innerHTML = '<div style="padding: 20px; text-align: center; color: #94a3b8;">No teachers found</div>';
                return;
            }
            
            panel.innerHTML = teachers.map(t => `
                <div class="teacher-item" data-teacher-id="${t.id}" data-teacher-name="${t.name}">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div class="teacher-name">${t.name}</div>
                        <div class="teacher-unread-badge hidden" data-teacher-id="${t.id}">0</div>
                    </div>
                    <div class="teacher-subject">${t.subject || 'Teacher'}</div>
                </div>
            `).join('');
            
            // Attach click listeners AFTER rendering
            document.querySelectorAll('.teacher-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const teacherId = parseInt(this.getAttribute('data-teacher-id'));
                    const teacherName = this.getAttribute('data-teacher-name');
                    console.log('Teacher clicked:', teacherId, teacherName);
                    selectTeacher(teacherId, teacherName);
                });
            });
        }

        function selectTeacher(teacherId, teacherName) {
            console.log('Selecting teacher:', teacherId);
            selectedTeacherId = teacherId;
            cachedMessagesCount = 0;
            currentRequestId = Date.now(); // Create new request ID
            
            // Remove active class from all teachers
            document.querySelectorAll('.teacher-item').forEach(el => {
                el.classList.remove('active');
            });
            
            // Add active class to selected teacher
            const selectedItem = document.querySelector(`[data-teacher-id="${teacherId}"]`);
            if (selectedItem) {
                selectedItem.classList.add('active');
            }
            
            document.getElementById('chatTeacherName').textContent = teacherName;
            document.getElementById('chatArea').style.display = 'flex';
            document.getElementById('noChat').style.display = 'none';
            
            // Mark this conversation as read
            markConversationAsRead(teacherId);
            
            // Clear messages immediately
            document.getElementById('messagesContainer').innerHTML = '';
            
            loadMessages();
        }

        async function loadMessages() {
            if (!selectedTeacherId) return;
            
            const requestId = currentRequestId; // Capture the current request ID
            
            try {
                const response = await fetch(`api/get_chat_messages.php?user_type=student&teacher_id=${selectedTeacherId}`);
                
                if (!response.ok) {
                    console.error('Failed to load messages:', response.status);
                    return;
                }
                
                // Only process if this is still the current request
                if (requestId !== currentRequestId) {
                    console.log('Ignoring stale response for teacher:', selectedTeacherId);
                    return;
                }
                
                const data = await response.json();
                
                if (data.error) {
                    console.error('API error:', data.error);
                    return;
                }
                
                const newMessages = data.messages || [];
                
                // Only update if message count changed
                if (newMessages.length !== cachedMessagesCount) {
                    cachedMessagesCount = newMessages.length;
                    displayMessages(newMessages);
                    
                    // Mark all messages as read since conversation is open
                    markConversationAsRead(selectedTeacherId);
                    updateNotificationBadge();
                    
                    const container = document.getElementById('messagesContainer');
                    setTimeout(() => { container.scrollTop = container.scrollHeight; }, 100);
                }
            } catch (error) {
                console.error('Error loading messages:', error);
            }
        }

        function displayMessages(msgs) {
            const container = document.getElementById('messagesContainer');
            container.innerHTML = msgs.map(msg => `
                <div class="message ${msg.sender_type === 'student' ? 'own' : 'other'}">
                    <div>
                        <div class="message-bubble">${escapeHtml(msg.message)}</div>
                        <div class="message-time">${formatTime(msg.created_at)}</div>
                    </div>
                </div>
            `).join('');
        }

        async function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            console.log('Sending message:', { message, selectedTeacherId });
            
            if (!message || !selectedTeacherId) {
                console.warn('Invalid input - message:', message, 'teacher:', selectedTeacherId);
                return;
            }
            
            // Immediately add message to DOM without flickering
            const container = document.getElementById('messagesContainer');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message own';
            messageDiv.innerHTML = `
                <div>
                    <div class="message-bubble">${escapeHtml(message)}</div>
                    <div class="message-time">${formatTime(new Date().toISOString())}</div>
                </div>
            `;
            container.appendChild(messageDiv);
            container.scrollTop = container.scrollHeight;
            
            // Clear input immediately
            input.value = '';
            
            // Update cached count to prevent re-render
            cachedMessagesCount++;
            
            // Send to server in background (don't wait)
            try {
                const response = await fetch('api/send_chat_message.php?user_type=student', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        teacher_id: selectedTeacherId,
                        message: message
                    })
                });
                
                const data = await response.json();
                console.log('Send response:', data);
                
                if (data.error) {
                    console.error('Send error:', data.error);
                    alert('Failed to send message: ' + data.error);
                }
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Failed to send message');
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatTime(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        // Replace the inline onclick with event listener
        const sendBtn = document.querySelector('.chat-send-btn');
        if (sendBtn) {
            sendBtn.addEventListener('click', sendMessage);
        }
        
        // Add delete button listener
        const deleteBtn = document.querySelector('.chat-delete-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', deleteConversation);
        }
        
        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        function deleteConversation() {
            if (!selectedTeacherId) return;
            
            const confirmed = confirm('Are you sure you want to delete all messages in this conversation?');
            if (!confirmed) return;
            
            // Call API to delete messages from database
            fetch(`api/delete_chat_conversation.php?user_type=student&teacher_id=${selectedTeacherId}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear messages from UI
                    document.getElementById('messagesContainer').innerHTML = '';
                    document.getElementById('chatArea').style.display = 'none';
                    document.getElementById('noChat').style.display = 'flex';
                    cachedMessagesCount = 0;
                    selectedTeacherId = null;
                    
                    // Remove active class from all teachers
                    document.querySelectorAll('.teacher-item').forEach(el => {
                        el.classList.remove('active');
                    });
                    
                    alert(`Deleted ${data.deleted_messages} messages`);
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to delete messages');
            });
        }

        loadTeachers();
        
        // Background polling to check for new messages
        async function pollForNewMessages() {
            for (let teacher of teachers) {
                try {
                    const response = await fetch(`api/get_chat_messages.php?user_type=student&teacher_id=${teacher.id}`);
                    const data = await response.json();
                    
                    if (!data.error && data.messages) {
                        // Only count messages from the teacher (not from student)
                        const teacherMessages = data.messages.filter(msg => msg.sender_type === 'teacher');
                        const cachedCount = parseInt(localStorage.getItem(`msgCount_student_${teacher.id}`) || '0');
                        const newCount = teacherMessages.length;
                        
                        // If this is the currently open conversation, mark as read
                        if (teacher.id === selectedTeacherId) {
                            markConversationAsRead(teacher.id);
                        } else {
                            // For other conversations, update unread count if there are new messages from teacher
                            if (newCount > cachedCount) {
                                const newMessages = newCount - cachedCount;
                                addUnreadMessage(teacher.id);
                            }
                        }
                        
                        localStorage.setItem(`msgCount_student_${teacher.id}`, newCount);
                    }
                } catch (error) {
                    console.log('Error polling messages');
                }
            }
        }
        
        setInterval(() => {
            if (selectedTeacherId) {
                loadMessages();
            }
            // Always poll to update badges in real-time
            pollForNewMessages();
        }, 3000);
    </script>
</body>
</html>