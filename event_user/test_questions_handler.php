<?php
session_start();
require('../connect.php');
require_once(__DIR__ . '/admin_logs_handler.php');

header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if ($_SESSION['user']['role'] !== 'администратор') {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

$admin_id = $_SESSION['user']['id'] ?? $_SESSION['user']['id_user'] ?? null;

// РЕДАКТИРОВАНИЕ ВОПРОСА
if (isset($_POST['edit_question'])) {
    $question_id = intval($_POST['question_id']);
    $question_text = trim($_POST['question_text']);
    $question_type = $_POST['question_type'] ?? 'одиночный';
    
    if (empty($question_text)) {
        echo json_encode(['success' => false, 'error' => 'Введите текст вопроса']);
        exit();
    }
    
    // Получаем старый текст вопроса и информацию о тесте для лога
    $old_question = $link->query("SELECT q.*, t.title as test_title, t.test_type_id 
                                   FROM questions q 
                                   JOIN tests t ON q.test_id = t.id 
                                   WHERE q.id = $question_id")->fetch_assoc();
    
    // Проверяем, есть ли поле mbti_weight
    if (isset($_POST['mbti_weight'])) {
        $mbti_weight = floatval($_POST['mbti_weight']);
        $stmt = $link->prepare("UPDATE questions SET question_text = ?, question_type = ?, mbti_weight = ? WHERE id = ?");
        $stmt->bind_param("ssdi", $question_text, $question_type, $mbti_weight, $question_id);
        
        if ($stmt->execute()) {
            logAdminAction($admin_id, 'Редактирование вопроса', 'question', $question_id, 
                "Изменен вопрос в тесте '{$old_question['test_title']}': '{$old_question['question_text']}' → '{$question_text}' (вес: {$old_question['mbti_weight']} → {$mbti_weight})");
            echo json_encode(['success' => true, 'message' => 'Вопрос успешно обновлен']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Ошибка при обновлении вопроса: ' . $stmt->error]);
        }
    } else {
        $stmt = $link->prepare("UPDATE questions SET question_text = ?, question_type = ? WHERE id = ?");
        $stmt->bind_param("ssi", $question_text, $question_type, $question_id);
        
        if ($stmt->execute()) {
            logAdminAction($admin_id, 'Редактирование вопроса', 'question', $question_id, 
                "Изменен вопрос в тесте '{$old_question['test_title']}': '{$old_question['question_text']}' → '{$question_text}'");
            echo json_encode(['success' => true, 'message' => 'Вопрос успешно обновлен']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Ошибка при обновлении вопроса: ' . $stmt->error]);
        }
    }
    exit();
}

// РЕДАКТИРОВАНИЕ ОТВЕТА
if (isset($_POST['edit_answer'])) {
    $answer_id = intval($_POST['answer_id']);
    $answer_text = trim($_POST['answer_text']);
    $score_value = intval($_POST['score_value']);
    
    if (empty($answer_text)) {
        echo json_encode(['success' => false, 'error' => 'Введите текст ответа']);
        exit();
    }
    
    // Получаем старый текст ответа и информацию о тесте для лога
    $old_answer = $link->query("SELECT a.*, q.question_text, t.title as test_title 
                                 FROM answers a 
                                 JOIN questions q ON a.question_id = q.id 
                                 JOIN tests t ON q.test_id = t.id 
                                 WHERE a.id = $answer_id")->fetch_assoc();
    
    // Проверяем, есть ли поле mbti_dimension
    if (isset($_POST['mbti_dimension'])) {
        $mbti_dimension = trim($_POST['mbti_dimension']);
        $stmt = $link->prepare("UPDATE answers SET answer_text = ?, score_value = ?, mbti_dimension = ? WHERE id = ?");
        $stmt->bind_param("sisi", $answer_text, $score_value, $mbti_dimension, $answer_id);
        
        if ($stmt->execute()) {
            logAdminAction($admin_id, 'Редактирование ответа', 'answer', $answer_id, 
                "Изменен ответ в тесте '{$old_answer['test_title']}' (вопрос: '{$old_answer['question_text']}'): '{$old_answer['answer_text']}' → '{$answer_text}' (баллы: {$old_answer['score_value']} → {$score_value}, шкала: {$old_answer['mbti_dimension']} → {$mbti_dimension})");
            echo json_encode(['success' => true, 'message' => 'Ответ успешно обновлен']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Ошибка при обновлении ответа: ' . $stmt->error]);
        }
    } else {
        $stmt = $link->prepare("UPDATE answers SET answer_text = ?, score_value = ? WHERE id = ?");
        $stmt->bind_param("sii", $answer_text, $score_value, $answer_id);
        
        if ($stmt->execute()) {
            logAdminAction($admin_id, 'Редактирование ответа', 'answer', $answer_id, 
                "Изменен ответ в тесте '{$old_answer['test_title']}' (вопрос: '{$old_answer['question_text']}'): '{$old_answer['answer_text']}' → '{$answer_text}' (баллы: {$old_answer['score_value']} → {$score_value})");
            echo json_encode(['success' => true, 'message' => 'Ответ успешно обновлен']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Ошибка при обновлении ответа: ' . $stmt->error]);
        }
    }
    exit();
}

// Удаление вопроса
if (isset($_GET['delete_question'])) {
    $question_id = intval($_GET['delete_question']);
    $test_id = intval($_GET['test_id']);
    
    // Получаем информацию о вопросе для лога
    $question_info = $link->query("SELECT q.*, t.title as test_title 
                                    FROM questions q 
                                    JOIN tests t ON q.test_id = t.id 
                                    WHERE q.id = $question_id")->fetch_assoc();
    
    // Получаем количество ответов
    $answers_count = $link->query("SELECT COUNT(*) as count FROM answers WHERE question_id = $question_id")->fetch_assoc()['count'];
    
    $link->begin_transaction();
    
    try {
        $delete_answers_stmt = $link->prepare("DELETE FROM answers WHERE question_id = ?");
        $delete_answers_stmt->bind_param("i", $question_id);
        $delete_answers_stmt->execute();
        
        $delete_question_stmt = $link->prepare("DELETE FROM questions WHERE id = ?");
        $delete_question_stmt->bind_param("i", $question_id);
        $delete_question_stmt->execute();
        
        $link->commit();
        
        logAdminAction($admin_id, 'Удаление вопроса', 'question', $question_id, 
            "Удален вопрос из теста '{$question_info['test_title']}': '{$question_info['question_text']}' (было удалено {$answers_count} ответов)");
        
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
    
    // Получаем информацию об ответе для лога
    $answer_info = $link->query("SELECT a.answer_text, a.score_value, q.question_text, t.title as test_title 
                                  FROM answers a 
                                  JOIN questions q ON a.question_id = q.id 
                                  JOIN tests t ON q.test_id = t.id 
                                  WHERE a.id = $answer_id")->fetch_assoc();
    
    $stmt = $link->prepare("DELETE FROM answers WHERE id = ?");
    $stmt->bind_param("i", $answer_id);
    
    if ($stmt->execute()) {
        logAdminAction($admin_id, 'Удаление ответа', 'answer', $answer_id, 
            "Удален ответ из теста '{$answer_info['test_title']}' (вопрос: '{$answer_info['question_text']}'): '{$answer_info['answer_text']}' (баллы: {$answer_info['score_value']})");
        $_SESSION['success'] = "Ответ успешно удален";
    } else {
        $_SESSION['error'] = "Ошибка при удалении ответа";
    }
    
    header("Location: ../index.php?page=admin-tests");
    exit();
}

echo json_encode(['success' => false, 'error' => 'Неизвестный запрос']);
?>