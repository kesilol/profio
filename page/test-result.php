<?php
// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header("Location: index.php?page=sign-in");
    exit();
}

// Получаем ID результата
$result_id = $_GET['result_id'] ?? 0;

// Получаем ID теста MBTI
$mbti_test_result = $link->query("SELECT id FROM tests WHERE test_type_id = (SELECT id FROM test_types WHERE name = 'mbti') LIMIT 1");
$mbti_test_id = $mbti_test_result->num_rows > 0 ? $mbti_test_result->fetch_assoc()['id'] : 0;

// Получаем ID теста Голланда
$holland_test_result = $link->query("SELECT id FROM tests WHERE test_type_id = (SELECT id FROM test_types WHERE name = 'голланд') LIMIT 1");
$holland_test_id = $holland_test_result->num_rows > 0 ? $holland_test_result->fetch_assoc()['id'] : 0;

// Получаем информацию о результате
$result = $link->query("
    SELECT tr.*, t.title as test_title, t.test_type_id
    FROM test_results tr 
    JOIN tests t ON tr.test_id = t.id 
    WHERE tr.id = '$result_id' AND tr.user_id = '{$_SESSION['user']['id_user']}'
")->fetch_assoc();

if (!$result) {
    header("Location: index.php?page=tests");
    exit();
}

$is_mbti = ($result['test_id'] == $mbti_test_id);
$is_holland = ($result['test_id'] == $holland_test_id);

// Для MBTI получаем описание типа из таблицы mbti_types
$mbti_type_info = null;
if ($is_mbti && !empty($result['result_type'])) {
    $type_code = $result['result_type'];
    $mbti_type_query = $link->query("SELECT * FROM mbti_types WHERE type_code = '$type_code'");
    $mbti_type_info = $mbti_type_query->num_rows > 0 ? $mbti_type_query->fetch_assoc() : null;
}

// Для теста Голланда получаем описание типа из БД
$holland_type_info = null;
if ($is_holland && !empty($result['result_type'])) {
    // Находим тип по названию
    $type_name = $result['result_type'];
    $holland_query = $link->query("SELECT * FROM holland_types WHERE type_name = '$type_name'");
    $holland_type_info = $holland_query->num_rows > 0 ? $holland_query->fetch_assoc() : null;
    
    // Если не нашли по названию, ищем по коду
    if (!$holland_type_info) {
        $code_map = [
            'Реалистичный' => 'Р',
            'Интеллектуальный' => 'И',
            'Социальный' => 'С',
            'Конвенциональный' => 'К',
            'Предприимчивый' => 'П',
            'Артистичный' => 'А'
        ];
        $type_code = $code_map[$type_name] ?? '';
        if ($type_code) {
            $holland_query = $link->query("SELECT * FROM holland_types WHERE type_code = '$type_code'");
            $holland_type_info = $holland_query->num_rows > 0 ? $holland_query->fetch_assoc() : null;
        }
    }
}

// Получаем рекомендации для этого результата
$recommendations = $link->query("
    SELECT r.*, p.title as profession_title, p.description as profession_description
    FROM recommendations r 
    JOIN professions p ON r.profession_id = p.id 
    WHERE r.user_id = '{$_SESSION['user']['id_user']}' 
    AND r.result_type = '{$result['result_type']}'
    ORDER BY r.match_percentage DESC
    LIMIT 3
");

// Для MBTI парсим проценты по шкалам
$mbti_scores = [];
if ($is_mbti && !empty($result['total_score']) && strpos($result['total_score'], ':') !== false) {
    $parts = explode(',', $result['total_score']);
    foreach ($parts as $part) {
        list($key, $value) = explode(':', $part);
        $mbti_scores[$key] = (int)$value;
    }
}

// Описание шкал MBTI
$dimensions = [
    'IE' => ['name' => 'Экстраверсия / Интроверсия', 'left' => 'Интроверсия (I)', 'right' => 'Экстраверсия (E)'],
    'SN' => ['name' => 'Сенсорика / Интуиция', 'left' => 'Сенсорика (S)', 'right' => 'Интуиция (N)'],
    'TF' => ['name' => 'Мышление / Чувство', 'left' => 'Мышление (T)', 'right' => 'Чувство (F)'],
    'JP' => ['name' => 'Суждение / Восприятие', 'left' => 'Суждение (J)', 'right' => 'Восприятие (P)']
];

// Получаем буквы типа личности
$type_letters = [];
if ($is_mbti && !empty($result['result_type'])) {
    $type_letters = str_split($result['result_type']);
}
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    <div>
        <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100 mb-[13px]">
            Результаты теста
        </h2>
        
        <div class="flex items-center text-xs text-gray-500 gap-x-[11px] mb-[37px]">
            <div class="flex items-center gap-x-1">
                <img src="assets/images/icons/icon-home-2.svg" alt="Главная">
                <a class="capitalize" href="index.php">Главная</a>
            </div>
            <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
            <a class="capitalize" href="index.php?page=tests">Тестирование</a>
            <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
            <span class="capitalize text-color-brands">Результаты</span>
        </div>

        <!-- Основные результаты -->
        <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 mb-6">
            <div class="text-center mb-6">
                <div class="w-16 h-16 rounded-full bg-green/20 grid place-items-center mx-auto mb-4">
                    <img src="assets/images/icons/icon-check-circle.svg" alt="Успех" class="w-8 h-8">
                </div>
                <h3 class="text-xl font-bold text-gray-1100 dark:text-gray-dark-1100 mb-2">
                    Тест "<?php echo htmlspecialchars($result['test_title']); ?>" завершен!
                </h3>
                <p class="text-gray-500 dark:text-gray-dark-500">
                    Вы успешно прошли профориентационный тест
                </p>
            </div>

            <?php if ($is_mbti && $mbti_type_info): ?>
                <!-- MBTI результаты (как в вашем исходном файле) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-dark-50">
                        <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Тип личности</p>
                        <p class="text-3xl font-bold text-color-brands"><?php echo htmlspecialchars($result['result_type']); ?></p>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-1"><?php echo htmlspecialchars($mbti_type_info['type_name']); ?></p>
                    </div>
                    <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-dark-50">
                        <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Дата прохождения</p>
                        <p class="text-sm font-semibold text-gray-1100 dark:text-gray-dark-1100">
                            <?php echo date('d.m.Y H:i', strtotime($result['completed_at'])); ?>
                        </p>
                    </div>
                </div>

                <!-- Полное описание типа -->
                <?php if (!empty($mbti_type_info['full_description'])): ?>
                    <div class="mb-6 p-5 rounded-lg bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800">
                        <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($mbti_type_info['full_description'])); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Сильные и слабые стороны -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <?php if (!empty($mbti_type_info['strengths'])): ?>
                        <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                            <h4 class="font-semibold text-green-700 dark:text-green-400 mb-2">✓ Сильные стороны</h4>
                            <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($mbti_type_info['strengths'])); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($mbti_type_info['weaknesses'])): ?>
                        <div class="p-4 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                            <h4 class="font-semibold text-orange-700 dark:text-orange-400 mb-2">⚠ Слабые стороны</h4>
                            <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($mbti_type_info['weaknesses'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Шкалы MBTI -->
                <div class="mt-4">
                    <h4 class="font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-3">Распределение по шкалам:</h4>
                    <div class="space-y-4">
                        <?php
                        $index = 0;
                        foreach ($dimensions as $key => $dim):
                            $score = isset($mbti_scores[$key]) ? $mbti_scores[$key] : 50;
                            $current_letter = isset($type_letters[$index]) ? $type_letters[$index] : '';
                            $index++;
                            
                            $direction = '';
                            if ($score < 45) $direction = $dim['left'];
                            elseif ($score > 55) $direction = $dim['right'];
                            else $direction = 'Сбалансировано';
                        ?>
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="font-medium"><?= $dim['left'] ?></span>
                                    <span class="text-gray-500"><?= $dim['name'] ?></span>
                                    <span class="font-medium"><?= $dim['right'] ?></span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                    <div class="bg-color-brands h-2.5 rounded-full" style="width: <?= $score ?>%"></div>
                                </div>
                                <div class="flex justify-between text-xs mt-1">
                                    <span class="text-gray-500"><?= $score < 45 ? '← ' . $direction : '' ?></span>
                                    <span class="text-gray-500"><?= $score > 55 ? $direction . ' →' : '' ?></span>
                                </div>
                                <div class="text-center text-xs font-medium mt-1 text-color-brands">
                                    <?php if ($current_letter != 'X' && !empty($current_letter)): ?>
                                        Преобладает: <?= $current_letter == 'I' || $current_letter == 'S' || $current_letter == 'T' || $current_letter == 'J' ? $dim['left'] : $dim['right'] ?>
                                    <?php elseif ($score >= 45 && $score <= 55): ?>
                                        Сбалансировано
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Советы по карьере и развитию -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                    <?php if (!empty($mbti_type_info['career_advice'])): ?>
                        <div class="p-4 rounded-lg bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800">
                            <h4 class="font-semibold text-purple-700 dark:text-purple-400 mb-2">💼 Советы по карьере</h4>
                            <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($mbti_type_info['career_advice'])); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($mbti_type_info['development_tips'])): ?>
                        <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                            <h4 class="font-semibold text-blue-700 dark:text-blue-400 mb-2">📚 Советы по развитию</h4>
                            <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($mbti_type_info['development_tips'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Подходящая рабочая среда -->
                <?php if (!empty($mbti_type_info['suitable_work_environment'])): ?>
                    <div class="mt-6 p-4 rounded-lg bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800">
                        <h4 class="font-semibold text-teal-700 dark:text-teal-400 mb-2">🏢 Подходящая рабочая среда</h4>
                        <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($mbti_type_info['suitable_work_environment'])); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Стиль общения и отношения -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                    <?php if (!empty($mbti_type_info['communication_style'])): ?>
                        <div class="p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800">
                            <h4 class="font-semibold text-yellow-700 dark:text-yellow-400 mb-2">💬 Стиль общения</h4>
                            <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($mbti_type_info['communication_style'])); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($mbti_type_info['relationships'])): ?>
                        <div class="p-4 rounded-lg bg-pink-50 dark:bg-pink-900/20 border border-pink-200 dark:border-pink-800">
                            <h4 class="font-semibold text-pink-700 dark:text-pink-400 mb-2">❤️ Отношения</h4>
                            <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($mbti_type_info['relationships'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Зоны роста и частые заблуждения -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                    <?php if (!empty($mbti_type_info['growth_areas'])): ?>
                        <div class="p-4 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800">
                            <h4 class="font-semibold text-indigo-700 dark:text-indigo-400 mb-2">🌱 Зоны роста</h4>
                            <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($mbti_type_info['growth_areas'])); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($mbti_type_info['common_misconceptions'])): ?>
                        <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                            <h4 class="font-semibold text-red-700 dark:text-red-400 mb-2">⚠️ Частые заблуждения</h4>
                            <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($mbti_type_info['common_misconceptions'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Известные личности и интересные факты -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                    <?php if (!empty($mbti_type_info['famous_people'])): ?>
                        <div class="p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                            <h4 class="font-semibold text-amber-700 dark:text-amber-400 mb-2">🌟 Известные личности</h4>
                            <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($mbti_type_info['famous_people'])); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($mbti_type_info['fun_facts'])): ?>
                        <div class="p-4 rounded-lg bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-200 dark:border-cyan-800">
                            <h4 class="font-semibold text-cyan-700 dark:text-cyan-400 mb-2">✨ Интересные факты</h4>
                            <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($mbti_type_info['fun_facts'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($is_holland && $holland_type_info): ?>
                <!-- Результаты теста Голланда (данные из БД) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-dark-50">
                        <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Тип личности</p>
                        <p class="text-2xl font-bold text-color-brands"><?php echo htmlspecialchars($result['result_type']); ?></p>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-1"><?php echo htmlspecialchars($holland_type_info['type_name']); ?> (<?php echo $holland_type_info['type_code']; ?>)</p>
                    </div>
                    <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-dark-50">
                        <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Набранные баллы</p>
                        <p class="text-2xl font-bold text-gray-1100 dark:text-gray-dark-1100"><?php echo $result['total_score']; ?></p>
                    </div>
                    <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-dark-50">
                        <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Дата прохождения</p>
                        <p class="text-sm font-semibold text-gray-1100 dark:text-gray-dark-1100">
                            <?php echo date('d.m.Y H:i', strtotime($result['completed_at'])); ?>
                        </p>
                    </div>
                </div>
                
                <!-- Полное описание типа -->
                <?php if (!empty($holland_type_info['full_description'])): ?>
                    <div class="mb-6 p-5 rounded-lg bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 border border-purple-200 dark:border-purple-800">
                        <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($holland_type_info['full_description'])); ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="mb-6 p-5 rounded-lg bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 border border-purple-200 dark:border-purple-800">
                        <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($holland_type_info['description'])); ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <!-- Сильные и слабые стороны -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <?php if (!empty($holland_type_info['strengths'])): ?>
                        <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                            <h4 class="font-semibold text-green-700 dark:text-green-400 mb-2">✓ Сильные стороны</h4>
                            <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($holland_type_info['strengths'])); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($holland_type_info['weaknesses'])): ?>
                        <div class="p-4 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                            <h4 class="font-semibold text-orange-700 dark:text-orange-400 mb-2">⚠ Слабые стороны</h4>
                            <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($holland_type_info['weaknesses'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Рекомендуемые профессии -->
                <?php if (!empty($holland_type_info['suitable_professions'])): ?>
                    <div class="mb-6 p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                        <h4 class="font-semibold text-blue-700 dark:text-blue-400 mb-2">💼 Рекомендуемые профессии</h4>
                        <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($holland_type_info['suitable_professions'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Советы по карьере -->
                <?php if (!empty($holland_type_info['career_advice'])): ?>
                    <div class="mb-6 p-4 rounded-lg bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800">
                        <h4 class="font-semibold text-purple-700 dark:text-purple-400 mb-2">📈 Советы по карьере</h4>
                        <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($holland_type_info['career_advice'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Подходящая рабочая среда -->
                <?php if (!empty($holland_type_info['suitable_work_environment'])): ?>
                    <div class="mb-6 p-4 rounded-lg bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800">
                        <h4 class="font-semibold text-teal-700 dark:text-teal-400 mb-2">🏢 Подходящая рабочая среда</h4>
                        <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($holland_type_info['suitable_work_environment'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Стиль общения -->
                <?php if (!empty($holland_type_info['communication_style'])): ?>
                    <div class="mb-6 p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800">
                        <h4 class="font-semibold text-yellow-700 dark:text-yellow-400 mb-2">💬 Стиль общения</h4>
                        <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($holland_type_info['communication_style'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Известные личности -->
                <?php if (!empty($holland_type_info['famous_people'])): ?>
                    <div class="mb-6 p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                        <h4 class="font-semibold text-amber-700 dark:text-amber-400 mb-2">🌟 Известные личности с этим типом</h4>
                        <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($holland_type_info['famous_people'])); ?></p>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- Обычные тесты (Климов и другие) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-dark-50">
                        <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Набранные баллы</p>
                        <p class="text-2xl font-bold text-gray-1100 dark:text-gray-dark-1100"><?php echo $result['total_score']; ?></p>
                    </div>
                    <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-dark-50">
                        <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Тип результата</p>
                        <p class="text-xl font-bold text-color-brands"><?php echo ucfirst(htmlspecialchars($result['result_type'])); ?></p>
                    </div>
                    <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-dark-50">
                        <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Дата прохождения</p>
                        <p class="text-sm font-semibold text-gray-1100 dark:text-gray-dark-1100">
                            <?php echo date('d.m.Y H:i', strtotime($result['completed_at'])); ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Рекомендации -->
        <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
            <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                Рекомендованные профессии
            </h3>
            
            <?php if ($recommendations->num_rows > 0): ?>
                <div class="grid grid-cols-1 gap-4">
                    <?php while($rec = $recommendations->fetch_assoc()): ?>
                        <div class="flex items-center justify-between p-4 rounded-lg border border-neutral dark:border-dark-neutral-border hover:shadow-md transition">
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-1">
                                    <?php echo htmlspecialchars($rec['profession_title']); ?>
                                </h4>
                                <p class="text-sm text-gray-500 dark:text-gray-dark-500 line-clamp-2">
                                    <?php echo htmlspecialchars($rec['profession_description']); ?>
                                </p>
                            </div>
                            <div class="text-right ml-4">
                                <span class="inline-block px-3 py-1 rounded-full bg-green/20 text-green text-sm font-semibold whitespace-nowrap">
                                    <?php echo $rec['match_percentage']; ?>% совпадение
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 dark:text-gray-dark-500 text-center py-4">
                    Рекомендации будут доступны после анализа результатов
                </p>
            <?php endif; ?>

            <div class="mt-6 flex gap-4">
                <a href="index.php?page=tests" class="btn bg-color-brands text-white px-6">
                    Пройти другой тест
                </a>
                <a href="<?php echo $is_mbti ? 'index.php?page=mbti' : ($is_holland ? "index.php?page=test&id={$result['test_id']}" : "index.php?page=test&id={$result['test_id']}"); ?>" 
                   class="btn border border-neutral text-gray-500 dark:border-dark-neutral-border dark:text-gray-dark-500 px-6">
                    Пройти снова
                </a>
                <a href="index.php?page=main" class="btn border border-neutral text-gray-500 dark:border-dark-neutral-border dark:text-gray-dark-500 px-6">
                    На главную
                </a>
            </div>
        </div>
    </div>
</main>

<style>
.bg-green\/20 {
    background-color: rgba(34, 197, 94, 0.2);
}
.text-green {
    color: rgb(34, 197, 94);
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>