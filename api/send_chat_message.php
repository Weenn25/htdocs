<?php

header('Content-Type: application/json');

// Get user type from the client (passed as a parameter)
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
$data = json_decode(file_get_contents('php://input'), true);

$teacher_id = intval($data['teacher_id'] ?? 0);
$student_id = intval($data['student_id'] ?? 0);
$message = trim($data['message'] ?? '');

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

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Empty message']);
    exit;
}

try {
    // For students: insert with student_id and teacher_id
    if ($user_type === 'student') {
        $query = "INSERT INTO chat_messages (student_id, teacher_id, message, sender_type, created_at) 
                  VALUES (?, ?, ?, 'student', NOW())";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("iis", $user_id, $teacher_id, $message);
    } 
    // For teachers: insert with student_id and teacher_id
    elseif ($user_type === 'teacher') {
        $query = "INSERT INTO chat_messages (student_id, teacher_id, message, sender_type, created_at) 
                  VALUES (?, ?, ?, 'teacher', NOW())";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("iis", $student_id, $user_id, $message);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid user type']);
        exit;
    }
    
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error' => 'Execute failed: ' . $stmt->error]);
        exit;
    }
    
    $message_id = $conn->insert_id;
    $stmt->close();
    
    echo json_encode(['success' => true, 'message_id' => $message_id]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
}
?>