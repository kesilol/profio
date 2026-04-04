<?php
session_start();
require_once '../config/database.php';

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php?page=sign-in");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php?page=tests");
    exit();
}

$test_id = $_POST['test_id'] ?? 0;
$user_id = $_SESSION['user']['id_user'];

// Проверяем, что это тест Голланда
$test_check = $link->query("SELECT id FROM tests WHERE id = '$test_id' AND test_type_id = (SELECT id FROM test_types WHERE name = 'голланд')");
if ($test_check->num_rows == 0) {
    die("Неверный тест");
}

// Маппинг типов Голланда с кодами для связи с БД
$holland_types_map = [
    'Р' => ['code' => 'Р', 'name' => 'Реалистичный'],
    'И' => ['code' => 'И', 'name' => 'Интеллектуальный'],
    'С' => ['code' => 'С', 'name' => 'Социальный'],
    'К' => ['code' => 'К', 'name' => 'Конвенциональный'],
    'П' => ['code' => 'П', 'name' => 'Предприимчивый'],
    'А' => ['code' => 'А', 'name' => 'Артистичный']
];

// Инициализируем счетчики
$scores = [
    'Р' => 0,
    'И' => 0,
    'С' => 0,
    'К' => 0,
    'П' => 0,
    'А' => 0
];

// Получаем все вопросы теста
$questions = $link->query("
    SELECT q.id, q.question_order, a.id as answer_id, a.answer_text, a.score_value, a.mbti_dimension
    FROM questions q
    JOIN answers a ON q.id = a.question_id
    WHERE q.test_id = '$test_id'
    ORDER BY q.question_order, a.answer_order
");

// Обрабатываем ответы
$answers_data = [];
$questions_count = 0;

while ($question = $questions->fetch_assoc()) {
    $question_id = $question['id'];
    $answer_key = "question_$question_id";
    
    if (isset($_POST[$answer_key])) {
        $selected_answer_id = $_POST[$answer_key];
        
        // Находим выбранный ответ
        $selected = $link->query("
            SELECT score_value, mbti_dimension 
            FROM answers 
            WHERE id = '$selected_answer_id'
        ")->fetch_assoc();
        
        if ($selected) {
            $type_code = $selected['mbti_dimension'];
            if (isset($scores[$type_code])) {
                $scores[$type_code]++;
            }
            $answers_data[] = [
                'question_id' => $question_id,
                'answer_id' => $selected_answer_id,
                'type' => $type_code
            ];
            $questions_count++;
        }
    }
}

// Проверяем, что ответили на все вопросы
$total_questions = $link->query("SELECT COUNT(*) as count FROM questions WHERE test_id = '$test_id'")->fetch_assoc()['count'];
if ($questions_count != $total_questions) {
    $_SESSION['error'] = "Пожалуйста, ответьте на все вопросы";
    header("Location: ../index.php?page=test&id=$test_id");
    exit();
}

// Определяем доминирующий тип
$max_score = 0;
$dominant_code = '';
$dominant_name = '';

foreach ($scores as $code => $score) {
    if ($score > $max_score) {
        $max_score = $score;
        $dominant_code = $code;
        $dominant_name = $holland_types_map[$code]['name'];
    }
}

// Сохраняем результат
$result_type = $dominant_name;
$total_score = $max_score;

$insert = $link->prepare("
    INSERT INTO test_results (user_id, test_id, total_score, result_type, completed_at) 
    VALUES (?, ?, ?, ?, NOW())
");
$insert->bind_param("iiis", $user_id, $test_id, $total_score, $result_type);
$insert->execute();
$result_id = $insert->insert_id;

// Генерируем рекомендации на основе типа Голланда из таблицы связей
$recommendations_query = $link->query("
    SELECT hp.profession_id, hp.relevance_score, p.title, p.description
    FROM holland_profession_relations hp
    JOIN professions p ON hp.profession_id = p.id
    WHERE hp.holland_type_code = '$dominant_code'
    ORDER BY hp.relevance_score DESC
    LIMIT 5
");

while ($rec = $recommendations_query->fetch_assoc()) {
    $recommendation_text = "Рекомендация на основе типа личности по методике Голланда: " . $dominant_name . " (" . $max_score . " баллов)";
    
    $insert_rec = $link->prepare("
        INSERT INTO recommendations (user_id, result_type, profession_id, match_percentage, recommendation_text) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $insert_rec->bind_param("isiis", $user_id, $result_type, $rec['profession_id'], $rec['relevance_score'], $recommendation_text);
    $insert_rec->execute();
}

// Перенаправляем на страницу результатов
header("Location: ../index.php?page=test-result&result_id=$result_id");
exit();
?>