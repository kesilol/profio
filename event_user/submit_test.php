<?php
session_start();
require('../connect.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php?page=tests");
    exit();
}

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php?page=sign-in");
    exit();
}

$user_id = $_SESSION['user']['id_user'];
$test_id = $_POST['test_id'];

// Проверяем существование теста
$test = $link->query("SELECT * FROM tests WHERE id = '$test_id'")->fetch_assoc();
if (!$test) {
    $_SESSION['error'] = 'Тест не найден!';
    header("Location: ../index.php?page=tests");
    exit();
}

// Проверяем, есть ли вопросы у теста
$questions_count = $link->query("SELECT COUNT(*) as count FROM questions WHERE test_id = '$test_id'")->fetch_assoc()['count'];
if ($questions_count == 0) {
    $_SESSION['error'] = 'В этом тесте пока нет вопросов. Пожалуйста, выберите другой тест.';
    header("Location: ../index.php?page=test&id=$test_id");
    exit();
}

// Для методики Климова: считаем баллы по категориям
$categories = [
    'технический' => 0,
    'гуманитарный' => 0, 
    'документальный' => 0,
    'творческий' => 0,
    'научный' => 0
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
            // ДОБАВЛЯЕМ БАЛЛЫ К ОБЩЕЙ СУММЕ
            $total_score += $answer['score_value'];
            
            // Считаем по категориям
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
    header("Location: ../index.php?page=test&id=$test_id");
    exit();
}

// Определяем доминирующий тип
$result_type = 'гуманитарный';
$max_score = 0;

foreach ($categories as $type => $score) {
    if ($score > $max_score) {
        $max_score = $score;
        $result_type = $type;
    }
}

// Сохраняем результат теста
$insert_result = $link->query("INSERT INTO test_results (user_id, test_id, total_score, result_type) 
              VALUES ('$user_id', '$test_id', '$total_score', '$result_type')");

if (!$insert_result) {
    $_SESSION['error'] = 'Ошибка сохранения результата!';
    header("Location: ../index.php?page=test&id=$test_id");
    exit();
}

$result_id = $link->insert_id;

// Генерируем рекомендации на основе результата
$recommendations_map = [
    'технический' => [1, 7], // Программист, Инженер
    'гуманитарный' => [2, 4], // Психолог, Учитель
    'творческий' => [3, 5], // Дизайнер, Маркетолог
    'научный' => [6], // Ученый-исследователь
    'документальный' => [5] // Маркетолог
];

// Удаляем старые рекомендации для этого типа результата
$link->query("DELETE FROM recommendations WHERE user_id = '$user_id' AND result_type = '$result_type'");

// Создаем новые рекомендации
if (isset($recommendations_map[$result_type])) {
    foreach ($recommendations_map[$result_type] as $profession_id) {
        $match_percentage = 80 + ($categories[$result_type] * 3); // Зависит от количества баллов в категории
        $recommendation_text = "На основе теста {$test['title']} ($total_score баллов) от " . date('d.m.Y');
        
        $link->query("INSERT INTO recommendations (user_id, result_type, profession_id, match_percentage, recommendation_text) 
                      VALUES ('$user_id', '$result_type', '$profession_id', '$match_percentage', '$recommendation_text')");
    }
}

// Устанавливаем флаг успешного прохождения теста
$_SESSION['test_completed'] = true;
$_SESSION['test_result'] = [
    'test_id' => $test_id,
    'test_title' => $test['title'],
    'total_score' => $total_score,
    'result_type' => $result_type,
    'result_id' => $result_id
];

// Перенаправляем на страницу результатов
header("Location: ../index.php?page=test-result&result_id=$result_id");
exit();
?>