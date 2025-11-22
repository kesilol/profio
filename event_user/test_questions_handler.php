<?php
session_start();
require('../connect.php');

// Устанавливаем заголовок для JSON ответа
header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Проверка прав администратора
if ($_SESSION['user']['role'] !== 'администратор') {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

// Добавление вопроса
if (isset($_POST['add_question'])) {
    $test_id = intval($_POST['test_id']);
    $question_text = trim($_POST['question_text']);
    $question_type = 'одиночный';
    
    if (empty($question_text)) {
        echo json_encode(['success' => false, 'error' => 'Введите текст вопроса']);
        exit();
    }

    // Получаем максимальный порядок для этого теста
    $order_stmt = $link->prepare("SELECT MAX(question_order) as max_order FROM questions WHERE test_id = ?");
    $order_stmt->bind_param("i", $test_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result()->fetch_assoc();
    $question_order = ($order_result['max_order'] ?? 0) + 1;
    
    // Вставляем новый вопрос
    $stmt = $link->prepare("INSERT INTO questions (test_id, question_text, question_type, question_order) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $test_id, $question_text, $question_type, $question_order);
    
    if ($stmt->execute()) {
        $new_question_id = $stmt->insert_id;
        
        // Возвращаем полные данные нового вопроса
        $new_question_stmt = $link->prepare("SELECT * FROM questions WHERE id = ?");
        $new_question_stmt->bind_param("i", $new_question_id);
        $new_question_stmt->execute();
        $new_question = $new_question_stmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Вопрос успешно добавлен',
            'question' => $new_question
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Ошибка при добавлении вопроса: ' . $stmt->error]);
    }
    exit();
}

// Добавление ответа
if (isset($_POST['add_answer'])) {
    $question_id = intval($_POST['question_id']);
    $answer_text = trim($_POST['answer_text']);
    $score_value = intval($_POST['score_value']);
    
    if (empty($answer_text)) {
        echo json_encode(['success' => false, 'error' => 'Введите текст ответа']);
        exit();
    }

    // Получаем максимальный порядок для этого вопроса
    $order_stmt = $link->prepare("SELECT MAX(answer_order) as max_order FROM answers WHERE question_id = ?");
    $order_stmt->bind_param("i", $question_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result()->fetch_assoc();
    $answer_order = ($order_result['max_order'] ?? 0) + 1;
    
    $stmt = $link->prepare("INSERT INTO answers (question_id, answer_text, score_value, answer_order) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isii", $question_id, $answer_text, $score_value, $answer_order);
    
    if ($stmt->execute()) {
        $new_answer_id = $stmt->insert_id;
        
        // Возвращаем полные данные нового ответа
        $new_answer_stmt = $link->prepare("SELECT * FROM answers WHERE id = ?");
        $new_answer_stmt->bind_param("i", $new_answer_id);
        $new_answer_stmt->execute();
        $new_answer = $new_answer_stmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Ответ успешно добавлен',
            'answer' => $new_answer
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Ошибка при добавлении ответа: ' . $stmt->error]);
    }
    exit();
}

// Получение всех вопросов теста (для AJAX обновления)
if (isset($_GET['get_questions'])) {
    $test_id = intval($_GET['test_id']);
    
    $questions_stmt = $link->prepare("SELECT * FROM questions WHERE test_id = ? ORDER BY question_order ASC");
    $questions_stmt->bind_param("i", $test_id);
    $questions_stmt->execute();
    $questions_result = $questions_stmt->get_result();
    $questions = [];
    
    while($row = $questions_result->fetch_assoc()) {
        // Для каждого вопроса получаем ответы
        $answers_stmt = $link->prepare("SELECT * FROM answers WHERE question_id = ? ORDER BY answer_order ASC");
        $answers_stmt->bind_param("i", $row['id']);
        $answers_stmt->execute();
        $answers_result = $answers_stmt->get_result();
        $row['answers'] = [];
        while($answer = $answers_result->fetch_assoc()) {
            $row['answers'][] = $answer;
        }
        $questions[] = $row;
    }
    
    echo json_encode(['success' => true, 'questions' => $questions]);
    exit();
}

// Удаление вопроса
if (isset($_GET['delete_question'])) {
    $question_id = intval($_GET['delete_question']);
    $test_id = intval($_GET['test_id']);
    
    // Начинаем транзакцию
    $link->begin_transaction();
    
    try {
        // Удаляем ответы
        $delete_answers_stmt = $link->prepare("DELETE FROM answers WHERE question_id = ?");
        $delete_answers_stmt->bind_param("i", $question_id);
        $delete_answers_stmt->execute();
        
        // Удаляем вопрос
        $delete_question_stmt = $link->prepare("DELETE FROM questions WHERE id = ?");
        $delete_question_stmt->bind_param("i", $question_id);
        $delete_question_stmt->execute();
        
        $link->commit();
        $_SESSION['success'] = "Вопрос и все ответы успешно удалены";
    } catch (Exception $e) {
        $link->rollback();
        $_SESSION['error'] = "Ошибка при удалении вопроса: " . $e->getMessage();
    }
    
    header("Location: ../index.php?page=admin-tests");
    exit();
}

// Удаление ответа
if (isset($_GET['delete_answer'])) {
    $answer_id = intval($_GET['delete_answer']);
    $test_id = intval($_GET['test_id']);
    
    $stmt = $link->prepare("DELETE FROM answers WHERE id = ?");
    $stmt->bind_param("i", $answer_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Ответ успешно удален";
    } else {
        $_SESSION['error'] = "Ошибка при удалении ответа";
    }
    
    header("Location: ../index.php?page=admin-tests");
    exit();
}

echo json_encode(['success' => false, 'error' => 'Неизвестный запрос']);
?>