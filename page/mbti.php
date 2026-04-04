<?php
session_start();
require('connect.php');

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header("Location: index.php?page=sign-in");
    exit();
}

// Получаем ID и данные теста MBTI из базы данных
$testQuery = $link->query("SELECT id FROM tests WHERE test_type_id = (SELECT id FROM test_types WHERE name = 'mbti') LIMIT 1");
if ($testQuery->num_rows == 0) {
    echo "<div class='text-center py-10'><p class='text-red-500'>Ошибка: Тест MBTI не найден в системе. Пожалуйста, сначала создайте его в админ-панели.</p></div>";
    exit();
}
$testData = $testQuery->fetch_assoc();
$mbti_test_id = $testData['id'];

// Получаем вопросы для теста из БД
$questionsQuery = $link->query("SELECT id, question_text, question_order, mbti_weight FROM questions WHERE test_id = $mbti_test_id ORDER BY question_order");
$questions = [];
while ($row = $questionsQuery->fetch_assoc()) {
    // Получаем ответы для этого вопроса
    $answersQuery = $link->query("SELECT id, answer_text, mbti_dimension FROM answers WHERE question_id = " . $row['id'] . " ORDER BY answer_order");
    $answers = [];
    while ($answer = $answersQuery->fetch_assoc()) {
        $answers[] = $answer;
    }
    $row['answers'] = $answers;
    $questions[] = $row;
}

// Если вопросов нет в БД
if (empty($questions)) {
    echo "<div class='text-center py-10'><p class='text-red-500'>Ошибка: Вопросы для теста MBTI не найдены в базе данных.</p></div>";
    exit();
}

$result = null;

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = [];
    $sums = ["IE" => 0, "SN" => 0, "TF" => 0, "JP" => 0];
    $weights = ["IE" => 0, "SN" => 0, "TF" => 0, "JP" => 0];

    // Перебираем все вопросы из БД
    foreach ($questions as $q) {
        $dbQuestionId = $q['id'];
        $weight = floatval($q['mbti_weight']);
        $dimension = ''; // Будет определена из выбранного ответа

        if (isset($_POST['q_' . $dbQuestionId])) {
            $selectedAnswerId = intval($_POST['q_' . $dbQuestionId]);
            
            // Находим выбранный ответ и его размерность (шкалу)
            foreach ($q['answers'] as $answer) {
                if ($answer['id'] == $selectedAnswerId) {
                    $dimension = $answer['mbti_dimension'];
                    break;
                }
            }
            
            // Определяем значение ответа: -1 для первого ответа (A), +1 для второго (B)
            $answerValue = isset($_POST['q_val_' . $dbQuestionId]) ? floatval($_POST['q_val_' . $dbQuestionId]) : 0;
            
            if (!empty($dimension)) {
                $sums[$dimension] += $answerValue * $weight;
                $weights[$dimension] += $weight;
            }
        }
    }

    // Рассчитываем итоговый код
    $result_code = "";
    $dims = [
        ["IE", "I", "E"],
        ["SN", "S", "N"],
        ["TF", "T", "F"],
        ["JP", "J", "P"]
    ];
    foreach ($dims as $dim) {
        $key = $dim[0];
        $char_a = $dim[1];
        $char_b = $dim[2];
        if ($weights[$key] > 0) {
            $score = $sums[$key] / $weights[$key];
            if (abs($score) < 0.25) {
                $result_code .= "X";
            } elseif ($score < 0) {
                $result_code .= $char_a;
            } else {
                $result_code .= $char_b;
            }
        } else {
            $result_code .= "X";
        }
    }

    $descriptions = [
        "I" => "Интроверсия — черпаете энергию из внутреннего мира, предпочитаете уединение.",
        "E" => "Экстраверсия — черпаете энергию из общения с людьми.",
        "S" => "Сенсорика — доверяете фактам и конкретному опыту.",
        "N" => "Интуиция — видите общие закономерности и возможности.",
        "T" => "Мышление — принимаете решения на основе логики.",
        "F" => "Чувство — ориентируетесь на ценности и гармонию.",
        "J" => "Суждение — любите порядок и планирование.",
        "P" => "Восприятие — гибки и склонны к импровизации.",
        "X" => "Пограничное состояние — черта выражена неярко."
    ];

    $result = [
        'code' => $result_code,
        'descriptions' => $descriptions
    ];
    
    // Сохранение результата в базу данных
    if (isset($_SESSION['user']['id_user'])) {
        $user_id = (int)$_SESSION['user']['id_user'];
        
        // Проверяем, есть ли уже результат этого теста
        $check_stmt = $link->prepare("SELECT id FROM test_results WHERE user_id = ? AND test_id = ?");
        $check_stmt->bind_param("ii", $user_id, $mbti_test_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Обновляем существующий результат
            $update_stmt = $link->prepare("UPDATE test_results SET result_type = ?, total_score = 0, completed_at = NOW() WHERE user_id = ? AND test_id = ?");
            $update_stmt->bind_param("sii", $result_code, $user_id, $mbti_test_id);
            $update_stmt->execute();
        } else {
            // Добавляем новый результат
            $insert_stmt = $link->prepare("INSERT INTO test_results (user_id, test_id, total_score, result_type, completed_at) VALUES (?, ?, 0, ?, NOW())");
            $insert_stmt->bind_param("iis", $user_id, $mbti_test_id, $result_code);
            $insert_stmt->execute();
        }
        
        // Сохраняем рекомендации по профессиям
        $prof_stmt = $link->prepare("
            SELECT p.id, p.title, mpr.relevance_score 
            FROM mbti_profession_relations mpr
            JOIN professions p ON mpr.profession_id = p.id
            WHERE mpr.mbti_type_code = ?
            ORDER BY mpr.relevance_score DESC
        ");
        $prof_stmt->bind_param("s", $result_code);
        $prof_stmt->execute();
        $prof_result = $prof_stmt->get_result();
        
        $recommendations = [];
        while ($prof = $prof_result->fetch_assoc()) {
            $recommendations[] = $prof;
            
            // Проверяем, есть ли уже рекомендация
            $check_rec_stmt = $link->prepare("SELECT id FROM recommendations WHERE user_id = ? AND profession_id = ? AND result_type = ?");
            $check_rec_stmt->bind_param("iis", $user_id, $prof['id'], $result_code);
            $check_rec_stmt->execute();
            $check_rec_result = $check_rec_stmt->get_result();
            
            if ($check_rec_result->num_rows == 0) {
                $rec_text = "Рекомендация на основе типа личности MBTI: {$result_code} - {$prof['title']}";
                $insert_rec_stmt = $link->prepare("INSERT INTO recommendations (user_id, result_type, profession_id, match_percentage, recommendation_text) VALUES (?, ?, ?, ?, ?)");
                $insert_rec_stmt->bind_param("isiis", $user_id, $result_code, $prof['id'], $prof['relevance_score'], $rec_text);
                $insert_rec_stmt->execute();
            }
        }
        
        $_SESSION['mbti_recommendations'] = $recommendations;
    }
}
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    <div>
        <?php if ($result): ?>
            <!-- ========== СТРАНИЦА РЕЗУЛЬТАТА ========== -->
            <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100 mb-[13px]">
                Ваш тип личности
            </h2>

            <div class="flex items-center text-xs text-gray-500 gap-x-[11px] mb-[37px]">
                <div class="flex items-center gap-x-1">
                    <img src="assets/images/icons/icon-home-2.svg" alt="Главная">
                    <a class="capitalize" href="index.php">Главная</a>
                </div>
                <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
                <a class="capitalize" href="index.php?page=tests">Тестирование</a>
                <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
                <span class="capitalize text-color-brands">Результат MBTI</span>
            </div>

            <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-8 mb-6">
                <div class="text-7xl font-bold text-color-brands mb-4 text-center">
                    <?= htmlspecialchars($result['code']) ?>
                </div>
                <p class="text-gray-500 dark:text-gray-dark-500 text-center mb-6">
                    Ваш тип личности по системе MBTI
                </p>
                <div class="text-left space-y-3 max-w-lg mx-auto">
                    <?php for ($i = 0; $i < 4; $i++):
                        $letter = $result['code'][$i];
                        if ($letter == 'X') continue;
                    ?>
                        <div class="flex gap-3">
                            <span class="font-bold text-color-brands text-xl"><?= $letter ?></span>
                            <span class="text-gray-700 dark:text-gray-300"><?= $result['descriptions'][$letter] ?></span>
                        </div>
                    <?php endfor; ?>
                    <?php if (strpos($result['code'], 'X') !== false): ?>
                        <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-semibold">X</span> означает, что соответствующая черта выражена неярко (пограничное значение).
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Рекомендуемые профессии -->
            <?php if (isset($_SESSION['mbti_recommendations']) && !empty($_SESSION['mbti_recommendations'])): ?>
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-8 mb-6">
                    <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200 mb-4">
                        <i class="bi bi-briefcase text-color-brands mr-2"></i>
                        Рекомендуемые профессии для типа <?= htmlspecialchars($result['code']) ?>
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($_SESSION['mbti_recommendations'] as $rec): ?>
                            <a href="index.php?page=profession&id=<?= $rec['id'] ?>" 
                               class="block p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:shadow-md transition hover:border-color-brands">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-semibold text-gray-800 dark:text-gray-200">
                                            <?= htmlspecialchars($rec['title']) ?>
                                        </h4>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-sm text-gray-500">Степень соответствия:</span>
                                            <span class="text-sm font-semibold text-color-brands"><?= $rec['relevance_score'] ?>%</span>
                                        </div>
                                    </div>
                                    <i class="bi bi-arrow-right-circle text-color-brands text-xl"></i>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="text-center">
                <a href="index.php?page=mbti" class="btn bg-color-brands text-white px-8 py-3 rounded-lg hover:bg-color-brands/90 transition-colors">
                    Пройти тест заново
                </a>
                <a href="index.php?page=tests" class="btn bg-gray-500 text-white px-8 py-3 rounded-lg hover:bg-gray-600 transition-colors ml-3">
                    К списку тестов
                </a>
            </div>

        <?php else: ?>
            <!-- ========== ФОРМА ТЕСТА (генерируется из БД) ========== -->
            <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100 mb-[13px]">
                Тест типа личности MBTI
            </h2>

            <div class="flex items-center text-xs text-gray-500 gap-x-[11px] mb-[37px]">
                <div class="flex items-center gap-x-1">
                    <img src="assets/images/icons/icon-home-2.svg" alt="Главная">
                    <a class="capitalize" href="index.php">Главная</a>
                </div>
                <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
                <a class="capitalize" href="index.php?page=tests">Тестирование</a>
                <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
                <span class="capitalize text-color-brands">MBTI</span>
            </div>

            <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 mb-6">
                <p class="text-gray-500 dark:text-gray-dark-500 mb-4">
                    Определение типа личности по методике Майерс-Бриггс (<?= count($questions) ?> вопросов)
                </p>
                <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-dark-500">
                    <span>Вопросов: <?= count($questions) ?></span>
                    <span>Тип: MBTI</span>
                    <span>Время: ~20-25 минут</span>
                </div>
            </div>

            <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-4 mb-6 text-sm text-gray-600 dark:text-gray-400">
                <p class="font-medium mb-2">Шкала ответов:</p>
                <div class="grid grid-cols-5 gap-2 text-center">
                    <div>1 — полностью A</div>
                    <div>2 — скорее A</div>
                    <div>3 — нейтрально</div>
                    <div>4 — скорее B</div>
                    <div>5 — полностью B</div>
                </div>
            </div>

            <form id="testForm" method="POST" action="">
                <div class="space-y-6 py-2">
                    <?php foreach ($questions as $q): 
                        $answers = $q['answers'];
                        // Первый ответ - A, второй - B
                        $optA = $answers[0]['answer_text'] ?? '';
                        $optB = $answers[1]['answer_text'] ?? '';
                    ?>
                    <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 question-block" data-question-id="<?= $q['id'] ?>">
                        <h3 class="text-normal font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                            Вопрос <?= $q['question_order'] ?>: <?= htmlspecialchars($q['question_text']) ?>
                        </h3>

                        <div class="mb-3 text-sm text-gray-600 dark:text-gray-400 flex justify-between">
                            <span><span class="font-medium">A:</span> <?= htmlspecialchars($optA) ?></span>
                            <span><span class="font-medium">B:</span> <?= htmlspecialchars($optB) ?></span>
                        </div>

                        <div class="space-y-3">
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-neutral dark:border-dark-neutral-border hover:bg-gray-50 dark:hover:bg-gray-dark-50 cursor-pointer transition-colors">
                                <input type="radio" name="q_<?= $q['id'] ?>" value="<?= $answers[0]['id'] ?>" data-value="-1" class="radio radio-primary" required>
                                <span class="text-gray-1100 dark:text-gray-dark-1100">1 — Полностью A</span>
                            </label>
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-neutral dark:border-dark-neutral-border hover:bg-gray-50 dark:hover:bg-gray-dark-50 cursor-pointer transition-colors">
                                <input type="radio" name="q_<?= $q['id'] ?>" value="<?= $answers[0]['id'] ?>" data-value="-0.5" class="radio radio-primary" required>
                                <span class="text-gray-1100 dark:text-gray-dark-1100">2 — Скорее A</span>
                            </label>
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-neutral dark:border-dark-neutral-border hover:bg-gray-50 dark:hover:bg-gray-dark-50 cursor-pointer transition-colors">
                                <input type="radio" name="q_<?= $q['id'] ?>" value="" data-value="0" class="radio radio-primary" required>
                                <span class="text-gray-1100 dark:text-gray-dark-1100">3 — Нейтрально</span>
                            </label>
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-neutral dark:border-dark-neutral-border hover:bg-gray-50 dark:hover:bg-gray-dark-50 cursor-pointer transition-colors">
                                <input type="radio" name="q_<?= $q['id'] ?>" value="<?= $answers[1]['id'] ?>" data-value="0.5" class="radio radio-primary" required>
                                <span class="text-gray-1100 dark:text-gray-dark-1100">4 — Скорее B</span>
                            </label>
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-neutral dark:border-dark-neutral-border hover:bg-gray-50 dark:hover:bg-gray-dark-50 cursor-pointer transition-colors">
                                <input type="radio" name="q_<?= $q['id'] ?>" value="<?= $answers[1]['id'] ?>" data-value="1" class="radio radio-primary" required>
                                <span class="text-gray-1100 dark:text-gray-dark-1100">5 — Полностью B</span>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-6 md:mt-12 text-center">
                    <button type="submit" class="btn bg-color-brands text-white px-4 md:px-8 py-3 text-base md:text-lg hover:bg-color-brands/90 transition-colors w-full md:w-auto">
                        Завершить тест и получить результаты
                    </button>
                </div>
            </form>

            <script>
                document.getElementById('testForm').addEventListener('submit', function(e) {
                    const questionBlocks = document.querySelectorAll('.question-block');
                    let allAnswered = true;

                    questionBlocks.forEach(block => {
                        const radios = block.querySelectorAll('input[type="radio"]');
                        const answered = Array.from(radios).some(radio => radio.checked);
                        if (!answered) {
                            allAnswered = false;
                            block.style.borderColor = 'red';
                        } else {
                            block.style.borderColor = '';
                        }
                    });

                    if (!allAnswered) {
                        e.preventDefault();
                        alert('Пожалуйста, ответьте на все вопросы перед завершением теста.');
                        return;
                    }
                    
                    // Добавляем скрытые поля со значениями ответов (-1, -0.5, 0, 0.5, 1)
                    questionBlocks.forEach(block => {
                        const radios = block.querySelectorAll('input[type="radio"]');
                        const questionId = block.dataset.questionId;
                        let selectedValue = 0;
                        radios.forEach(radio => {
                            if (radio.checked && radio.dataset.value !== undefined) {
                                selectedValue = radio.dataset.value;
                            }
                        });
                        // Обновляем скрытое поле или создаем, если его нет
                        let hiddenField = document.querySelector(`.hidden-value-${questionId}`);
                        if (!hiddenField) {
                            hiddenField = document.createElement('input');
                            hiddenField.type = 'hidden';
                            hiddenField.name = `q_val_${questionId}`;
                            hiddenField.className = `hidden-value-${questionId}`;
                            block.appendChild(hiddenField);
                        }
                        hiddenField.value = selectedValue;
                    });
                });
            </script>
        <?php endif; ?>
    </div>
</main>