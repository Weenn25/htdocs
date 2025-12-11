<?php
header('Content-Type: application/json');

session_name('STUDENT_SESSION');
session_start();

require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'student') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $query = "SELECT id, name, subject FROM teachers ORDER BY name ASC";
    
    if (!$result = $conn->query($query)) {
        http_response_code(500);
        echo json_encode(['error' => 'Query failed: ' . $conn->error]);
        exit;
    }
    
    $teachers = [];
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
    
    echo json_encode(['teachers' => $teachers, 'success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
}
?>