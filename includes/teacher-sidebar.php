<?php
// This file should be included in all teacher pages
// It displays the consistent sidebar navigation with chat notifications
?>
<style>
  /* Chat unread badge in teacher sidebar */
  .chat-unread-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #ef4444;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-weight: 700;
    font-size: 11px;
    margin-left: auto;
    animation: badge-pop 0.3s ease-out;
    flex-shrink: 0;
  }

  .chat-unread-badge.hidden {
    display: none;
  }

  @keyframes badge-pop {
    0% { transform: scale(0.5); opacity: 0; }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); opacity: 1; }
  }
</style>

<script>
  // Update chat notification badge in teacher sidebar
  function updateTeacherSidebarChatBadge() {
    const badge = document.getElementById('teacherChatBadge');
    if (!badge) return;
    
    const unread = JSON.parse(localStorage.getItem('unreadConversations_teacher') || '{}');
    const totalUnread = Object.values(unread).reduce((sum, count) => sum + count, 0);
    
    if (totalUnread > 0) {
      badge.textContent = totalUnread;
      badge.classList.remove('hidden');
    } else {
      badge.classList.add('hidden');
    }
  }

  // Play notification sound
  function playNotificationSound() {
    try {
      const audioContext = new (window.AudioContext || window.webkitAudioContext)();
      const oscillator = audioContext.createOscillator();
      const gainNode = audioContext.createGain();
      
      oscillator.connect(gainNode);
      gainNode.connect(audioContext.destination);
      
      oscillator.frequency.value = 800;
      oscillator.type = 'sine';
      
      gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
      gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
      
      oscillator.start(audioContext.currentTime);
      oscillator.stop(audioContext.currentTime + 0.5);
    } catch (error) {
      console.log('Could not play notification sound:', error);
    }
  }

  // Poll for new messages on other pages
  async function pollForTeacherMessagesInBackground() {
    try {
      // Fetch the list of students first
      const studentsResponse = await fetch('api/get_chat_students.php');
      if (!studentsResponse.ok) return;
      
      const studentsData = await studentsResponse.json();
      const students = studentsData.students || [];
      
      // Check messages for each student
      for (let student of students) {
        try {
          const response = await fetch(`api/get_chat_messages.php?user_type=teacher&student_id=${student.id}`);
          const data = await response.json();
          
          if (!data.error && data.messages) {
            // Only count messages from student (not from teacher)
            const studentMessages = data.messages.filter(msg => msg.sender_type === 'student');
            const cachedCount = parseInt(localStorage.getItem(`msgCount_teacher_${student.id}`) || '0');
            const newCount = studentMessages.length;
            
            // If there are new messages, update and play sound
            if (newCount > cachedCount) {
              const newMessages = newCount - cachedCount;
              
              // Update unread count
              const unread = JSON.parse(localStorage.getItem('unreadConversations_teacher') || '{}');
              unread[student.id] = (unread[student.id] || 0) + newMessages;
              localStorage.setItem('unreadConversations_teacher', JSON.stringify(unread));
              
              // Play notification sound
              playNotificationSound();
            }
            
            // Update cache
            localStorage.setItem(`msgCount_teacher_${student.id}`, newCount);
          }
        } catch (error) {
          console.log('Error polling messages for student:', student.id);
        }
      }
    } catch (error) {
      console.log('Error in background polling:', error);
    }
  }

  // Check sidebar badge every 1 second to stay in sync
  setInterval(updateTeacherSidebarChatBadge, 1000);
  
  // Poll for new messages every 3 seconds on other pages
  setInterval(pollForTeacherMessagesInBackground, 3000);
  
  // Initial update when page loads
  updateTeacherSidebarChatBadge();
</script>
