<?php
header('Content-Type: application/json');

// Get user type from the client
$user_type = $_GET['user_type'] ?? null;

if (!$user_type || !in_array($user_type, ['student', 'teacher'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid user_type']);
    exit;
}

// Start the appropriate session
$session_name = $user_type === 'student' ? 'STUDENT_SESSION' : 'TEACHER_SESSION';
@session_name($session_name);
@session_start();

// Validate session
if (empty($_SESSION['user_id']) || $_SESSION['user_type'] !== $user_type) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = intval($_SESSION['user_id']);

require_once __DIR__ . '/../config/database.php';

$teacher_id = intval($_GET['teacher_id'] ?? 0);
$student_id = intval($_GET['student_id'] ?? 0);

// Validate based on user type
if ($user_type === 'student' && $teacher_id === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid teacher ID']);
    exit;
} elseif ($user_type === 'teacher' && $student_id === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid student ID']);
    exit;
}

try {
    // For students: delete messages with specific teacher
    if ($user_type === 'student') {
        $query = "DELETE FROM chat_messages 
                  WHERE student_id = ? AND teacher_id = ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("ii", $user_id, $teacher_id);
    } 
    // For teachers: delete messages with specific student
    elseif ($user_type === 'teacher') {
        $query = "DELETE FROM chat_messages 
                  WHERE student_id = ? AND teacher_id = ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("ii", $student_id, $user_id);
    }
    
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error' => 'Execute failed: ' . $stmt->error]);
        exit;
    }
    
    $deleted_rows = $stmt->affected_rows;
    $stmt->close();
    
    echo json_encode(['success' => true, 'deleted_messages' => $deleted_rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
}
?>
