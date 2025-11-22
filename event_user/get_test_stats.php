<?php
session_start();
require('../connect.php');

header('Content-Type: application/json');

if (!isset($_GET['test_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID теста не указан']);
    exit();
}

$test_id = intval($_GET['test_id']);

// Получаем количество вопросов для теста
$stmt = $link->prepare("
    SELECT COUNT(q.id) as questions_count 
    FROM tests t 
    LEFT JOIN questions q ON t.id = q.test_id 
    WHERE t.id = ? 
    GROUP BY t.id
");
$stmt->bind_param("i", $test_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'questions_count' => $data['questions_count']
    ]);
} else {
    echo json_encode([
        'success' => true,
        'questions_count' => 0
    ]);
}
?>