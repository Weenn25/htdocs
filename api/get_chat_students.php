<?php

header('Content-Type: application/json');

$_SESSION_NAME = 'TEACHER_SESSION';
if (session_status() === PHP_SESSION_NONE) {
    session_name($_SESSION_NAME);
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'teacher') {
    http_response_code(401);
    echo json_encode(['success' => false, 'students' => []]);
    exit;
}

$students = [];
if ($result = $conn->query("SELECT id, name, grade_level, section FROM students WHERE is_archived != 1 ORDER BY name ASC")) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

echo json_encode(['success' => true, 'students' => $students]);