<?php
// This file enables chat notifications for all teacher pages
// Include this on every teacher page to show notification badges
?>

<style>
  /* Chat unread badge for teacher sidebar */
  .teacher-chat-badge {
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
    margin-left: 8px;
    animation: badge-pop 0.3s ease-out;
    flex-shrink: 0;
  }

  .teacher-chat-badge.hidden {
    display: none;
  }

  @keyframes badge-pop {
    0% { transform: scale(0.5); opacity: 0; }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); opacity: 1; }
  }
</style>

<script>
  // Update chat notification badge on all teacher pages
  function updateTeacherChatBadge() {
    // Find all elements with teacher-chat-badge class
    const badges = document.querySelectorAll('.teacher-chat-badge');
    
    if (badges.length === 0) return;
    
    const unread = JSON.parse(localStorage.getItem('unreadConversations_teacher') || '{}');
    const totalUnread = Object.values(unread).reduce((sum, count) => sum + count, 0);
    
    badges.forEach(badge => {
      if (totalUnread > 0) {
        badge.textContent = totalUnread;
        badge.classList.remove('hidden');
      } else {
        badge.classList.add('hidden');
      }
    });
  }

  // Play notification sound
  function playTeacherNotificationSound() {
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

  // Poll for new messages on all teacher pages
  async function pollForTeacherChatMessages() {
    try {
      // Use relative path that works from any teacher page
      const studentsResponse = await fetch('../api/get_chat_students.php');
      if (!studentsResponse.ok) {
        console.log('Failed to fetch students list');
        return;
      }
      
      const studentsData = await studentsResponse.json();
      const students = studentsData.students || [];
      
      for (let student of students) {
        try {
          const response = await fetch(`../api/get_chat_messages.php?user_type=teacher&student_id=${student.id}`);
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
              playTeacherNotificationSound();
              
              // Update badge immediately
              updateTeacherChatBadge();
            }
            
            // Update cache
            localStorage.setItem(`msgCount_teacher_${student.id}`, newCount);
          }
        } catch (error) {
          console.log('Error polling messages for student:', student.id, error);
        }
      }
    } catch (error) {
      console.log('Error in teacher chat polling:', error);
    }
  }

  // Update badge every 500ms for faster response
  setInterval(updateTeacherChatBadge, 500);
  
  // Poll for new messages every 2 seconds for faster notification
  setInterval(pollForTeacherChatMessages, 2000);
  
  // Initial update when page loads
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      updateTeacherChatBadge();
      pollForTeacherChatMessages();
    });
  } else {
    updateTeacherChatBadge();
    pollForTeacherChatMessages();
  }
</script>
