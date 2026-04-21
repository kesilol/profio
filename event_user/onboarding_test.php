<?php
session_start();
require('../connect.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../landing.php");
    exit();
}

$test_id = $_POST['test_id'];

// Получаем информацию о тесте
$test = $link->query("SELECT * FROM tests WHERE id = '$test_id'")->fetch_assoc();
if (!$test) {
    header("Location: ../landing.php");
    exit();
}

// Для методики Климова: считаем баллы по категориям
$categories = [
    'технический' => 0,      // человек-техника (score_value = 2)
    'гуманитарный' => 0,     // человек-человек (score_value = 3) 
    'документальный' => 0,   // человек-знаковая система (score_value = 1)
    'творческий' => 0,       // человек-художественный образ (score_value = 4)
    'научный' => 0           // человек-природа (score_value = 5)
];

$total_score = 0;

// Получаем вопросы теста
$questions = $link->query("SELECT * FROM questions WHERE test_id = '$test_id' ORDER BY question_order");

// Проверяем, что на все вопросы ответили
$answered_questions = 0;
$total_questions = $questions->num_rows;

while ($question = $questions->fetch_assoc()) {
    $question_id = $question['id'];
    $answer_id = $_POST["question_{$question_id}"] ?? null;
    
    if ($answer_id) {
        $answered_questions++;
        // Получаем информацию о выбранном ответе
        $answer = $link->query("SELECT * FROM answers WHERE id = '$answer_id'")->fetch_assoc();
        if ($answer) {
            $total_score += $answer['score_value'];
            
            // Считаем по категориям Климова
            switch ($answer['score_value']) {
                case 1: $categories['документальный']++; break;
                case 2: $categories['технический']++; break;
                case 3: $categories['гуманитарный']++; break;
                case 4: $categories['творческий']++; break;
                case 5: $categories['научный']++; break;
            }
        }
    }
}

// Проверяем, что ответили на все вопросы
if ($answered_questions != $total_questions) {
    $_SESSION['error'] = 'Пожалуйста, ответьте на все вопросы теста.';
    header("Location: ../test-onboarding.php");
    exit();
}

// Определяем доминирующий тип по Климову
$result_type = 'гуманитарный';
$max_score = 0;

foreach ($categories as $type => $score) {
    if ($score > $max_score) {
        $max_score = $score;
        $result_type = $type;
    }
}

// ★★★ ОЧИЩАЕМ СТАРЫЕ ДАННЫЕ ТЕСТА ПЕРЕД СОХРАНЕНИЕМ НОВЫХ ★★★
unset($_SESSION['onboarding_results']);
unset($_SESSION['onboarding_completed']);
unset($_SESSION['onboarding_timestamp']);
unset($_SESSION['just_completed_test']);
unset($_SESSION['pending_registration']);

// ★★★ СОХРАНЯЕМ РЕЗУЛЬТАТЫ В СЕССИИ ДЛЯ ПОСЛЕДУЮЩЕГО СОХРАНЕНИЯ ПРИ РЕГИСТРАЦИИ ★★★
$_SESSION['onboarding_results'] = [
    'test_id' => $test_id,
    'test_title' => $test['title'],
    'total_score' => $total_score,
    'result_type' => $result_type,
    'categories' => $categories,
    'answers' => $_POST
];

$_SESSION['onboarding_completed'] = true;
$_SESSION['onboarding_timestamp'] = time(); // ★ ВРЕМЕННАЯ МЕТКА (Unix timestamp)
$_SESSION['just_completed_test'] = true;    // ★ ФЛАГ, ЧТО ТЕСТ БЫЛ ПРОЙДЕН ТОЛЬКО ЧТО
$_SESSION['pending_registration'] = true;   // ★ ФЛАГ ОЖИДАНИЯ РЕГИСТРАЦИИ

// Перенаправляем на регистрацию
header("Location: ../index.php?page=sign-up");
exit();
?>