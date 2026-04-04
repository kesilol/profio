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

// Проверяем существование теста и его тип
$test = $link->query("SELECT t.*, tt.name as type_name 
                      FROM tests t 
                      LEFT JOIN test_types tt ON t.test_type_id = tt.id 
                      WHERE t.id = '$test_id'")->fetch_assoc();
if (!$test) {
    $_SESSION['error'] = 'Тест не найден!';
    header("Location: ../index.php?page=tests");
    exit();
}

// Определяем тип теста
$is_holland = ($test['type_name'] == 'голланд') || 
              stripos($test['title'], 'Голланда') !== false;

$is_mbti = ($test['type_name'] == 'mbti') || 
           stripos($test['title'], 'MBTI') !== false;

// Если это MBTI - обрабатываем отдельно
if ($is_mbti) {
    header("Location: ../index.php?page=mbti-submit");
    exit();
}

// Если это тест Голланда - используем специальную логику
if ($is_holland) {
    // Получаем вопросы теста
    $questions = $link->query("SELECT * FROM questions WHERE test_id = '$test_id' ORDER BY question_order");
    
    // Маппинг типов Голланда
    $holland_scores = [
        'Р' => 0, // Реалистичный
        'И' => 0, // Интеллектуальный
        'С' => 0, // Социальный
        'К' => 0, // Конвенциональный
        'П' => 0, // Предприимчивый
        'А' => 0  // Артистичный
    ];
    
    $total_questions = $questions->num_rows;
    $answered_questions = 0;
    
    while ($question = $questions->fetch_assoc()) {
        $question_id = $question['id'];
        $answer_id = $_POST["question_{$question_id}"] ?? null;
        
        if ($answer_id) {
            $answered_questions++;
            $answer = $link->query("SELECT * FROM answers WHERE id = '$answer_id'")->fetch_assoc();
            if ($answer && isset($answer['mbti_dimension'])) {
                $type_code = $answer['mbti_dimension'];
                if (isset($holland_scores[$type_code])) {
                    $holland_scores[$type_code]++;
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
    $max_score = 0;
    $dominant_code = '';
    $type_names = [
        'Р' => 'Реалистичный',
        'И' => 'Интеллектуальный',
        'С' => 'Социальный',
        'К' => 'Конвенциональный',
        'П' => 'Предприимчивый',
        'А' => 'Артистичный'
    ];
    
    foreach ($holland_scores as $code => $score) {
        if ($score > $max_score) {
            $max_score = $score;
            $dominant_code = $code;
        }
    }
    
    $result_type = $type_names[$dominant_code];
    $total_score = $max_score;
    
    // Сохраняем результат
    $insert_result = $link->query("INSERT INTO test_results (user_id, test_id, total_score, result_type) 
                  VALUES ('$user_id', '$test_id', '$total_score', '$result_type')");
    
    if (!$insert_result) {
        $_SESSION['error'] = 'Ошибка сохранения результата!';
        header("Location: ../index.php?page=test&id=$test_id");
        exit();
    }
    
    $result_id = $link->insert_id;
    
    // Получаем рекомендации из таблицы holland_profession_relations
    $recommendations_query = $link->query("
        SELECT hp.profession_id, hp.relevance_score, p.title, p.description
        FROM holland_profession_relations hp
        JOIN professions p ON hp.profession_id = p.id
        WHERE hp.holland_type_code = '$dominant_code'
        ORDER BY hp.relevance_score DESC
        LIMIT 5
    ");
    
    // Удаляем старые рекомендации для этого типа
    $link->query("DELETE FROM recommendations WHERE user_id = '$user_id' AND result_type = '$result_type'");
    
    while ($rec = $recommendations_query->fetch_assoc()) {
        $match_percentage = $rec['relevance_score'];
        $recommendation_text = "Рекомендация на основе теста Голланда: " . $result_type . " (" . $max_score . " баллов) от " . date('d.m.Y');
        
        $link->query("INSERT INTO recommendations (user_id, result_type, profession_id, match_percentage, recommendation_text) 
                      VALUES ('$user_id', '$result_type', '{$rec['profession_id']}', '$match_percentage', '$recommendation_text')");
    }
    
    // Перенаправляем на страницу результатов
    header("Location: ../index.php?page=test-result&result_id=$result_id");
    exit();
}

// Для методики Климова
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
            $total_score += $answer['score_value'];
            
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
$second_score = 0;

$scores_array = array_values($categories);
rsort($scores_array);
$max_score = $scores_array[0];
$second_score = isset($scores_array[1]) ? $scores_array[1] : 0;

foreach ($categories as $type => $score) {
    if ($score == $max_score) {
        $result_type = $type;
        break;
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
    'технический' => [1, 7],
    'гуманитарный' => [2, 4],
    'творческий' => [3, 5],
    'научный' => [6],
    'документальный' => [5]
];

$link->query("DELETE FROM recommendations WHERE user_id = '$user_id' AND result_type = '$result_type'");

$max_possible = $total_questions;
$difference = $max_score - $second_score;
$base_percentage = 50 + round(($difference / $max_possible) * 50);
$base_percentage = max(50, min(98, $base_percentage));

if (isset($recommendations_map[$result_type])) {
    foreach ($recommendations_map[$result_type] as $profession_id) {
        $profession_coefficient = 1.0;
        
        if ($result_type == 'технический' && $profession_id == 1) {
            $profession_coefficient = 1.05;
        } elseif ($result_type == 'технический' && $profession_id == 7) {
            $profession_coefficient = 1.03;
        } elseif ($result_type == 'гуманитарный' && $profession_id == 2) {
            $profession_coefficient = 1.05;
        } elseif ($result_type == 'гуманитарный' && $profession_id == 4) {
            $profession_coefficient = 1.03;
        } elseif ($result_type == 'творческий' && $profession_id == 3) {
            $profession_coefficient = 1.05;
        } elseif ($result_type == 'творческий' && $profession_id == 5) {
            $profession_coefficient = 1.03;
        } elseif ($result_type == 'научный' && $profession_id == 6) {
            $profession_coefficient = 1.05;
        } elseif ($result_type == 'документальный' && $profession_id == 5) {
            $profession_coefficient = 1.03;
        }
        
        $match_percentage = min(100, round($base_percentage * $profession_coefficient));
        $recommendation_text = "На основе теста {$test['title']} ($total_score баллов) от " . date('d.m.Y');
        
        $link->query("INSERT INTO recommendations (user_id, result_type, profession_id, match_percentage, recommendation_text) 
                      VALUES ('$user_id', '$result_type', '$profession_id', '$match_percentage', '$recommendation_text')");
    }
}

$_SESSION['test_completed'] = true;
$_SESSION['test_result'] = [
    'test_id' => $test_id,
    'test_title' => $test['title'],
    'total_score' => $total_score,
    'result_type' => $result_type,
    'result_id' => $result_id
];

header("Location: ../index.php?page=test-result&result_id=$result_id");
exit();
?>