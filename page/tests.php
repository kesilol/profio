<?php
// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header("Location: index.php?page=sign-in");
    exit();
}

// Получаем список тестов из БД с JOIN к типам
$tests = $link->query("
    SELECT t.*, tt.name as type_name 
    FROM tests t 
    LEFT JOIN test_types tt ON t.test_type_id = tt.id
");

// Получаем информацию о методиках из БД
$methodologies = $link->query("
    SELECT tm.*, tt.name as type_name 
    FROM test_methodologies tm 
    LEFT JOIN test_types tt ON tm.test_type_id = tt.id
");
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    <div>
        <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100 mb-[13px]">
            Профориентационные тесты
        </h2>

        <div class="flex items-center text-xs text-gray-500 gap-x-[11px] mb-[37px]">
            <div class="flex items-center gap-x-1">
                <img src="assets/images/icons/icon-home-2.svg" alt="Главная">
                <a class="capitalize" href="index.php">Главная</a>
            </div>
            <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
            <span class="capitalize text-color-brands">Тестирование</span>
        </div>

        <!-- Список тестов -->
        <div class="mb-8">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <?php while ($test = $tests->fetch_assoc()): 
                    // Проверяем, есть ли вопросы у теста
                    $questions_count = $link->query("SELECT COUNT(*) as count FROM questions WHERE test_id = '{$test['id']}'")->fetch_assoc()['count'];
                    $is_available = $questions_count > 0;
                    
                    // Проверяем, прошел ли пользователь этот тест
                    $user_id = $_SESSION['user']['id_user'];
                    $passed = $link->query("SELECT id FROM test_results WHERE user_id = '$user_id' AND test_id = '{$test['id']}'");
                    $is_passed = $passed->num_rows > 0;
                ?>
                    <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 flex flex-col <?php echo !$is_available ? 'opacity-70' : ''; ?>">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-lg grid place-items-center <?php echo $is_available ? 'bg-blue/20' : 'bg-gray-200'; ?>">
                                <img src="assets/images/icons/icon-doc.svg" alt="Тест">
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-dark-100 dark:text-gray-dark-600">
                                    <?php echo $test['type_name']; ?>
                                </span>
                                <?php if (!$is_available): ?>
                                    <span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">
                                        В разработке
                                    </span>
                                <?php elseif ($is_passed): ?>
                                    <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-800">
                                        Пройден
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-800">
                                        Доступен
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-2">
                            <?php echo $test['title']; ?>
                        </h3>

                        <p class="text-gray-500 dark:text-gray-dark-500 text-sm mb-4 flex-grow">
                            <?php echo $test['description']; ?>
                        </p>

                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-dark-500 mb-4">
                            <span>Вопросов: <?php echo $questions_count; ?></span>
                            <span>
                                <?php
                                if (!$is_available) {
                                    echo '🔧 В разработке';
                                } elseif ($is_passed) {
                                    echo '✅ Пройден';
                                } else {
                                    echo '🟡 Доступен';
                                }
                                ?>
                            </span>
                        </div>

                        <?php if ($is_available): ?>
                            <a href="index.php?page=test&id=<?php echo $test['id']; ?>"
                                class="btn bg-color-brands text-white w-full text-center py-3 mt-auto hover:bg-color-brands/90 transition-colors">
                                <?php echo $is_passed ? 'Пройти снова' : 'Начать тест'; ?>
                            </a>
                        <?php else: ?>
                            <button class="btn bg-gray-300 text-gray-500 w-full text-center py-3 mt-auto cursor-not-allowed" disabled>
                                Скоро доступно
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Информация о тестах -->
        <div class="mt-8 rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
            <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                О профориентационных тестах
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php while ($method = $methodologies->fetch_assoc()): ?>
                    <div>
                        <h4 class="font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-2">
                            <?php echo $method['name']; ?>
                        </h4>
                        <p class="text-gray-500 dark:text-gray-dark-500 text-sm">
                            <?php echo $method['description']; ?>
                        </p>
                        <?php if ($method['key_points']): ?>
                            <p class="text-gray-400 dark:text-gray-dark-400 text-xs mt-2">
                                <strong>Ключевые типы:</strong> <?php echo $method['key_points']; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Статистика тестирования -->
        <?php
        $user_id = $_SESSION['user']['id_user'];
        $passed_tests = $link->query("SELECT COUNT(*) as count FROM test_results WHERE user_id = '$user_id'")->fetch_assoc()['count'];
        $total_tests = $link->query("SELECT COUNT(*) as count FROM tests WHERE id IN (SELECT DISTINCT test_id FROM questions)")->fetch_assoc()['count'];
        $progress = $total_tests > 0 ? round(($passed_tests / $total_tests) * 100) : 0;
        ?>
        
        <div class="mt-8 rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
            <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                Ваша статистика тестирования
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-color-brands"><?php echo $passed_tests; ?></div>
                    <div class="text-sm text-gray-500 dark:text-gray-dark-500">Пройдено тестов</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-1100 dark:text-gray-dark-1100"><?php echo $total_tests; ?></div>
                    <div class="text-sm text-gray-500 dark:text-gray-dark-500">Всего доступно</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600"><?php echo $progress; ?>%</div>
                    <div class="text-sm text-gray-500 dark:text-gray-dark-500">Прогресс</div>
                </div>
            </div>
            <?php if ($progress < 100): ?>
                <div class="mt-4">
                    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                        <div class="bg-color-brands h-2 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-dark-500 mt-2">
                        Пройдите все тесты для получения полной картины ваших профессиональных склонностей
                    </p>
                </div>
            <?php else: ?>
                <div class="mt-4 p-3 bg-green-50 rounded-lg border border-green-200 dark:bg-green-900/20 dark:border-green-800">
                    <p class="text-sm text-green-800 dark:text-green-300 text-center">
                        🎉 Поздравляем! Вы прошли все доступные тесты!
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>