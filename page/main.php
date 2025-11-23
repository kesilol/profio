<?php
// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header("Location: index.php?page=sign-in");
    exit();
}

$user_id = $_SESSION['user']['id_user'];
$user_role = $_SESSION['user']['role'];

// ДАННЫЕ ДЛЯ КУРАТОРА 
if ($user_role === 'куратор') {
    // Получаем статистику по студентам куратора
    $students_stats = $link->query("
        SELECT 
            COUNT(DISTINCT cs.student_id) as total_students,
            COUNT(DISTINCT tr.id) as total_tests,
            COUNT(DISTINCT dp.id) as total_plans,
            COUNT(DISTINCT r.id) as total_recommendations,
            AVG(tr.total_score) as avg_score
        FROM curator_students cs
        LEFT JOIN users u ON cs.student_id = u.id
        LEFT JOIN test_results tr ON u.id = tr.user_id
        LEFT JOIN development_plans dp ON u.id = dp.user_id
        LEFT JOIN recommendations r ON u.id = r.user_id
        WHERE cs.curator_id = '$user_id'
    ")->fetch_assoc();

    // Получаем последних добавленных студентов
    $recent_students = $link->query("
        SELECT u.id, u.name, u.email, cs.assigned_at
        FROM curator_students cs
        JOIN users u ON cs.student_id = u.id
        WHERE cs.curator_id = '$user_id'
        ORDER BY cs.assigned_at DESC
        LIMIT 5
    ");

    // Получаем студентов с высокой активностью
    $active_students = $link->query("
        SELECT u.id, u.name, COUNT(tr.id) as tests_count, MAX(tr.completed_at) as last_activity
        FROM curator_students cs
        JOIN users u ON cs.student_id = u.id
        LEFT JOIN test_results tr ON u.id = tr.user_id
        WHERE cs.curator_id = '$user_id'
        GROUP BY u.id, u.name
        HAVING tests_count > 0
        ORDER BY tests_count DESC
        LIMIT 5
    ");

    // Получаем последние результаты тестов студентов
    $recent_test_results = $link->query("
        SELECT tr.*, t.title as test_title, u.name as student_name
        FROM test_results tr
        JOIN tests t ON tr.test_id = t.id
        JOIN users u ON tr.user_id = u.id
        JOIN curator_students cs ON u.id = cs.student_id
        WHERE cs.curator_id = '$user_id'
        ORDER BY tr.completed_at DESC
        LIMIT 5
    ");
}
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    <div>
        <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100 mb-[13px]">
            <?php echo $user_role === 'куратор' ? 'Панель куратора' : 'Добро пожаловать, ' . $_SESSION['user']['login'] . '!'; ?>
        </h2>

        <div class="flex items-center text-xs text-gray-500 gap-x-[11px] mb-[37px]">
            <div class="flex items-center gap-x-1">
                <img src="assets/images/icons/icon-home-2.svg" alt="Главная">
                <a class="capitalize" href="index.php">Главная</a>
            </div>
            <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
            <span class="capitalize text-color-brands">Обзор</span>
        </div>

        <?php if ($user_role === 'куратор'): ?>
            <!--  ПАНЕЛЬ КУРАТОРА  -->

            <!-- Статистика куратора -->
            <div class="grid grid-cols-1 gap-6 mb-[26px] lg:grid-cols-2 xl:grid-cols-4">
                <!-- Мои студенты -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg py-4 flex-1 px-[19px]">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-desc text-gray-500 dark:text-gray-dark-500">Мои студенты</p>
                        <div class="dropdown dropdown-end ml-auto translate-x-4 z-10">
                            <label class="cursor-pointer dropdown-label flex items-center justify-between py-2 px-4" tabindex="0">
                                <img class="cursor-pointer" src="assets/images/icons/icon-toggle.svg" alt="Настройки">
                            </label>
                        </div>
                    </div>
                    <div class="flex items-center justify-between mb-[2px]">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg grid place-items-center bg-green">
                                <img src="assets/images/icons/icon-people.svg" alt="Студенты">
                            </div>
                            <p class="text-btn-label font-bold text-gray-1100 dark:text-gray-dark-1100">
                                <?php echo $students_stats['total_students'] ?? 0; ?>
                            </p>
                        </div>
                        <div class="flex items-center gap-[7px]">
                            <img src="assets/images/icons/icon-export-green.svg" alt="Рост">
                            <span class="text-green text-subtitle font-medium">
                                +<?php echo $students_stats['total_students'] ?? 0; ?>
                            </span>
                        </div>
                    </div>
                    <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px]">Всего студентов</p>
                </div>

                <!-- Пройдено тестов -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg py-4 flex-1 px-[19px]">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-desc text-gray-500 dark:text-gray-dark-500">Пройдено тестов</p>
                        <div class="dropdown dropdown-end ml-auto translate-x-4 z-10">
                            <label class="cursor-pointer dropdown-label flex items-center justify-between py-2 px-4" tabindex="0">
                                <img class="cursor-pointer" src="assets/images/icons/icon-toggle.svg" alt="Настройки">
                            </label>
                        </div>
                    </div>
                    <div class="flex items-center justify-between mb-[2px]">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg grid place-items-center bg-blue">
                                <img src="assets/images/icons/icon-doc.svg" alt="Тесты">
                            </div>
                            <p class="text-btn-label font-bold text-gray-1100 dark:text-gray-dark-1100">
                                <?php echo $students_stats['total_tests'] ?? 0; ?>
                            </p>
                        </div>
                        <div class="flex items-center gap-[7px]">
                            <img src="assets/images/icons/icon-export-green.svg" alt="Рост">
                            <span class="text-green text-subtitle font-medium">
                                +<?php echo $students_stats['total_tests'] ?? 0; ?>
                            </span>
                        </div>
                    </div>
                    <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px]">Всего тестов</p>
                </div>

                <!-- Создано планов -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg py-4 flex-1 px-[19px]">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-desc text-gray-500 dark:text-gray-dark-500">Планы развития</p>
                        <div class="dropdown dropdown-end ml-auto translate-x-4 z-10">
                            <label class="cursor-pointer dropdown-label flex items-center justify-between py-2 px-4" tabindex="0">
                                <img class="cursor-pointer" src="assets/images/icons/icon-toggle.svg" alt="Настройки">
                            </label>
                        </div>
                    </div>
                    <div class="flex items-center justify-between mb-[2px]">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg grid place-items-center bg-violet">
                                <img src="assets/images/icons/icon-project.svg" alt="Планы">
                            </div>
                            <p class="text-btn-label font-bold text-gray-1100 dark:text-gray-dark-1100">
                                <?php echo $students_stats['total_plans'] ?? 0; ?>
                            </p>
                        </div>
                        <div class="flex items-center gap-[7px]">
                            <img src="assets/images/icons/icon-export-green.svg" alt="Рост">
                            <span class="text-green text-subtitle font-medium">
                                +<?php echo $students_stats['total_plans'] ?? 0; ?>
                            </span>
                        </div>
                    </div>
                    <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px]">Создано планов</p>
                </div>

                <!-- Средний балл -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg py-4 flex-1 px-[19px]">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-desc text-gray-500 dark:text-gray-dark-500">Средний балл</p>
                        <div class="dropdown dropdown-end ml-auto translate-x-4 z-10">
                            <label class="cursor-pointer dropdown-label flex items-center justify-between py-2 px-4" tabindex="0">
                                <img class="cursor-pointer" src="assets/images/icons/icon-toggle.svg" alt="Настройки">
                            </label>
                        </div>
                    </div>
                    <div class="flex items-center justify-between mb-[2px]">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg grid place-items-center bg-orange">
                                <img src="assets/images/icons/icon-star.svg" alt="Баллы">
                            </div>
                            <p class="text-btn-label font-bold text-gray-1100 dark:text-gray-dark-1100">
                                <?php echo round($students_stats['avg_score'] ?? 0, 1); ?>
                            </p>
                        </div>
                        <div class="flex items-center gap-[7px]">
                            <img src="assets/images/icons/icon-export-green.svg" alt="Рост">
                            <span class="text-green text-subtitle font-medium">+0.0</span>
                        </div>
                    </div>
                    <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px]">По всем тестам</p>
                </div>
            </div>

            <!-- Быстрый доступ -->
            <div class="grid grid-cols-1 items-center mb-6 gap-[18px] xl:grid-cols-2">
                <!-- Управление студентами -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100">Управление студентами</p>
                    </div>
                    <div class="w-full bg-neutral h-[1px] mb-[19px] dark:bg-dark-neutral-border"></div>
                    <p class="text-gray-500 dark:text-gray-dark-500 mb-6">
                        Добавляйте новых студентов, просматривайте их прогресс и управляйте списком подопечных
                    </p>
                    <div class="flex gap-4">
                        <a href="index.php?page=manage-students" class="btn bg-color-brands text-white px-6">Управление студентами</a>
                        <a href="index.php?page=reports" class="btn border border-neutral text-gray-500 dark:border-dark-neutral-border dark:text-gray-dark-500 px-6">Отчеты</a>
                    </div>
                </div>

                <!-- Последние студенты -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100">Последние студенты</p>
                    </div>
                    <div class="w-full bg-neutral h-[1px] mb-[19px] dark:bg-dark-neutral-border"></div>
                    <div class="space-y-4 mb-6">
                        <?php if ($recent_students->num_rows > 0): ?>
                            <?php while ($student = $recent_students->fetch_assoc()): ?>
                                <div class="flex items-center justify-between">
                                    <a href="index.php?page=student-detail&id=<?php echo $student['id']; ?>"
                                        class="text-normal text-gray-1100 dark:text-gray-dark-1100 hover:text-color-brands transition-colors">
                                        <?php echo $student['name']; ?>
                                    </a>
                                    <span class="text-gray-500 text-sm">
                                        <?php echo date('d.m.Y', strtotime($student['assigned_at'])); ?>
                                    </span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-gray-500 dark:text-gray-dark-500 text-center py-2">Нет студентов в списке</p>
                        <?php endif; ?>
                    </div>
                    <a href="index.php?page=reports" class="btn bg-color-brands text-white px-6">Все студенты</a>
                </div>
            </div>

            <!-- Активные студенты и последние результаты -->
            <div class="grid grid-cols-1 items-center mb-6 gap-[18px] xl:grid-cols-2">
                <!-- Самые активные студенты -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100">Самые активные студенты</p>
                    </div>
                    <div class="w-full bg-neutral h-[1px] mb-[19px] dark:bg-dark-neutral-border"></div>
                    <div class="space-y-4">
                        <?php if ($active_students->num_rows > 0): ?>
                            <?php while ($student = $active_students->fetch_assoc()): ?>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <a href="index.php?page=student-detail&id=<?php echo $student['id']; ?>"
                                            class="text-normal text-gray-1100 dark:text-gray-dark-1100 hover:text-color-brands transition-colors block">
                                            <?php echo $student['name']; ?>
                                        </a>
                                        <p class="text-sm text-gray-500">
                                            <?php echo $student['tests_count']; ?> тестов
                                        </p>
                                    </div>
                                    <span class="text-green text-sm">
                                        Активен
                                    </span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-gray-500 dark:text-gray-dark-500 text-center py-4">Нет активных студентов</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Последние результаты тестов -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100">Последние результаты тестов</p>
                    </div>
                    <div class="w-full bg-neutral h-[1px] mb-[19px] dark:bg-dark-neutral-border"></div>
                    <div class="space-y-4">
                        <?php if ($recent_test_results->num_rows > 0): ?>
                            <?php while ($test = $recent_test_results->fetch_assoc()): ?>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-normal text-gray-1100 dark:text-gray-dark-1100">
                                            <?php echo $test['student_name']; ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <?php echo $test['test_title']; ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-color-brands font-semibold">
                                            <?php echo $test['total_score']; ?> баллов
                                        </span>
                                        <p class="text-sm text-gray-500">
                                            <?php echo date('d.m.Y', strtotime($test['completed_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-gray-500 dark:text-gray-dark-500 text-center py-4">Нет результатов тестов</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- ★★★★ ПАНЕЛЬ СТУДЕНТА ★★★★ -->

            <!-- Получаем результаты теста для студентов -->
            <?php
            $recent_results = $link->query("
                SELECT tr.*, t.title as test_title 
                FROM test_results tr 
                LEFT JOIN tests t ON tr.test_id = t.id 
                WHERE tr.user_id = '$user_id' 
                ORDER BY tr.completed_at DESC 
                LIMIT 1
            ");

            // Проверяем есть ли результаты теста
            $has_test_results = $recent_results->num_rows > 0;
            if ($has_test_results) {
                $test_result = $recent_results->fetch_assoc();
            }

            // Получаем статистику для студента
            $tests_count = $link->query("SELECT COUNT(*) as count FROM test_results WHERE user_id = '$user_id'")->fetch_assoc()['count'];
            $recommendations_count = $link->query("SELECT COUNT(*) as count FROM recommendations WHERE user_id = '$user_id'")->fetch_assoc()['count'];
            $plans_count = $link->query("SELECT COUNT(*) as count FROM development_plans WHERE user_id = '$user_id'")->fetch_assoc()['count'];

            // Получаем последние рекомендации
            $latest_recommendations = $link->query("
                SELECT p.title, r.match_percentage, r.profession_id
                FROM recommendations r 
                JOIN professions p ON r.profession_id = p.id 
                WHERE r.user_id = '$user_id' 
                ORDER BY r.match_percentage DESC 
                LIMIT 3
            ");

            // Получаем последнюю активность
            $recent_activity = $link->query("
                (SELECT 'test' as type, t.title, tr.completed_at as date 
                 FROM test_results tr 
                 JOIN tests t ON tr.test_id = t.id 
                 WHERE tr.user_id = '$user_id' 
                 ORDER BY tr.completed_at DESC 
                 LIMIT 1)
                UNION
                (SELECT 'profession' as type, p.title, NOW() as date 
                 FROM recommendations r 
                 JOIN professions p ON r.profession_id = p.id 
                 WHERE r.user_id = '$user_id' 
                 ORDER BY r.match_percentage DESC 
                 LIMIT 1)
                UNION  
                (SELECT 'plan' as type, dp.title, dp.created_at as date 
                 FROM development_plans dp 
                 WHERE dp.user_id = '$user_id' 
                 ORDER BY dp.created_at DESC 
                 LIMIT 1)
                ORDER BY date DESC 
                LIMIT 3
            ");
            ?>

            <!-- Блок с результатами теста для новых пользователей -->
            <?php if ($has_test_results): ?>
                <div class="rounded-2xl border border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20 p-4 md:p-6 mb-6">
                    <div class="flex flex-col md:flex-row md:items-center mb-4 text-center md:text-left">
                        <div class="w-12 h-12 rounded-lg bg-green-100 dark:bg-green-800 grid place-items-center mr-0 md:mr-4 mb-3 md:mb-0 mx-auto md:mx-0">
                            <img src="assets/images/icons/icon-check-circle.svg" alt="Успех" class="w-6 h-6 text-green-600">
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Результаты вашего теста готовы!
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                <?php echo $test_result['test_title']; ?>
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4 mb-4">
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 md:p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Ваш тип личности</div>
                            <div class="text-lg md:text-xl font-bold text-color-brands capitalize">
                                <?php echo $test_result['result_type']; ?>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 md:p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Набранные баллы</div>
                            <div class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">
                                <?php echo $test_result['total_score']; ?> баллов
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row gap-3">
                        <a href="index.php?page=recommendations" class="btn bg-color-brands text-white px-4 py-3 text-sm md:text-base w-full md:w-auto text-center">
                            Посмотреть рекомендации
                        </a>
                        <a href="index.php?page=my-results" class="btn border border-color-brands text-color-brands px-4 py-3 text-sm md:text-base w-full md:w-auto text-center">
                            Все результаты
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Статистика пользователя -->
            <div class="grid grid-cols-1 gap-6 mb-[26px] lg:grid-cols-2 xl:grid-cols-4">
                <!-- Пройденные тесты -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg py-4 flex-1 px-[19px]">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-desc text-gray-500 dark:text-gray-dark-500">Пройдено тестов</p>
                        <div class="dropdown dropdown-end ml-auto translate-x-4 z-10">
                            <label class="cursor-pointer dropdown-label flex items-center justify-between py-2 px-4" tabindex="0">
                                <img class="cursor-pointer" src="assets/images/icons/icon-toggle.svg" alt="Настройки">
                            </label>
                        </div>
                    </div>
                    <div class="flex items-center justify-between mb-[2px]">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg grid place-items-center bg-green">
                                <img src="assets/images/icons/icon-doc.svg" alt="Тесты">
                            </div>
                            <p class="text-btn-label font-bold text-gray-1100 dark:text-gray-dark-1100"><?php echo $tests_count; ?></p>
                        </div>
                        <div class="flex items-center gap-[7px]">
                            <img src="assets/images/icons/icon-export-green.svg" alt="Рост">
                            <span class="text-green text-subtitle font-medium">+<?php echo $tests_count; ?></span>
                        </div>
                    </div>
                    <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px]">Всего пройдено</p>
                </div>

                <!-- Рекомендованные профессии -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg py-4 flex-1 px-[19px]">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-desc text-gray-500 dark:text-gray-dark-500">Рекомендации</p>
                        <div class="dropdown dropdown-end ml-auto translate-x-4 z-10">
                            <label class="cursor-pointer dropdown-label flex items-center justify-between py-2 px-4" tabindex="0">
                                <img class="cursor-pointer" src="assets/images/icons/icon-toggle.svg" alt="Настройки">
                            </label>
                        </div>
                    </div>
                    <div class="flex items-center justify-between mb-[2px]">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg grid place-items-center bg-blue">
                                <img src="assets/images/icons/icon-work.svg" alt="Профессии">
                            </div>
                            <p class="text-btn-label font-bold text-gray-1100 dark:text-gray-dark-1100"><?php echo $recommendations_count; ?></p>
                        </div>
                        <div class="flex items-center gap-[7px]">
                            <img src="assets/images/icons/icon-export-green.svg" alt="Рост">
                            <span class="text-green text-subtitle font-medium">+<?php echo $recommendations_count; ?></span>
                        </div>
                    </div>
                    <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px]">Всего рекомендаций</p>
                </div>

                <!-- Планы развития -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg py-4 flex-1 px-[19px]">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-desc text-gray-500 dark:text-gray-dark-500">Планы развития</p>
                        <div class="dropdown dropdown-end ml-auto translate-x-4 z-10">
                            <label class="cursor-pointer dropdown-label flex items-center justify-between py-2 px-4" tabindex="0">
                                <img class="cursor-pointer" src="assets/images/icons/icon-toggle.svg" alt="Настройки">
                            </label>
                        </div>
                    </div>
                    <div class="flex items-center justify-between mb-[2px]">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg grid place-items-center bg-violet">
                                <img src="assets/images/icons/icon-project.svg" alt="План">
                            </div>
                            <p class="text-btn-label font-bold text-gray-1100 dark:text-gray-dark-1100"><?php echo $plans_count; ?></p>
                        </div>
                        <div class="flex items-center gap-[7px]">
                            <img src="assets/images/icons/icon-export-green.svg" alt="Прогресс">
                            <span class="text-green text-subtitle font-medium">+<?php echo $plans_count; ?></span>
                        </div>
                    </div>
                    <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px]">Создано планов</p>
                </div>

                <!-- Активность -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg py-4 flex-1 px-[19px]">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-desc text-gray-500 dark:text-gray-dark-500">Активность</p>
                        <div class="dropdown dropdown-end ml-auto translate-x-4 z-10">
                            <label class="cursor-pointer dropdown-label flex items-center justify-between py-2 px-4" tabindex="0">
                                <img class="cursor-pointer" src="assets/images/icons/icon-toggle.svg" alt="Настройки">
                            </label>
                        </div>
                    </div>
                    <div class="flex items-center justify-between mb-[2px]">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg grid place-items-center bg-pink">
                                <img src="assets/images/icons/icon-analytics.svg" alt="Активность">
                            </div>
                            <p class="text-btn-label font-bold text-gray-1100 dark:text-gray-dark-1100"><?php echo ($tests_count + $recommendations_count + $plans_count); ?></p>
                        </div>
                        <div class="flex items-center gap-[7px]">
                            <img src="assets/images/icons/icon-export-green.svg" alt="Рост">
                            <span class="text-green text-subtitle font-medium">+<?php echo ($tests_count + $recommendations_count + $plans_count); ?></span>
                        </div>
                    </div>
                    <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px]">Всего действий</p>
                </div>
            </div>

            <!-- Быстрый доступ -->
            <div class="grid grid-cols-1 items-center mb-6 gap-[18px] xl:grid-cols-2">
                <!-- Карточка тестирования -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100">Профориентационное тестирование</p>
                    </div>
                    <div class="w-full bg-neutral h-[1px] mb-[19px] dark:bg-dark-neutral-border"></div>
                    <p class="text-gray-500 dark:text-gray-dark-500 mb-6">
                        Пройдите тесты и узнайте свои профессиональные склонности, сильные стороны и подходящие направления развития
                    </p>
                    <div class="flex gap-4">
                        <a href="index.php?page=tests" class="btn bg-color-brands text-white px-6">Начать тест</a>
                        <a href="index.php?page=my-results" class="btn border border-neutral text-gray-500 dark:border-dark-neutral-border dark:text-gray-dark-500 px-6">Мои результаты</a>
                    </div>
                </div>

                <!-- Карточка профессий -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100">
                            <?php echo $recommendations_count > 0 ? 'Рекомендованные профессии' : 'Популярные профессии'; ?>
                        </p>
                    </div>
                    <div class="w-full bg-neutral h-[1px] mb-[19px] dark:bg-dark-neutral-border"></div>
                    <div class="space-y-4 mb-6">
                        <?php if ($latest_recommendations->num_rows > 0): ?>
                            <?php while ($rec = $latest_recommendations->fetch_assoc()): ?>
                                <div class="flex items-center justify-between">
                                    <a href="index.php?page=profession-detail&id=<?php echo $rec['profession_id']; ?>"
                                        class="text-normal text-gray-1100 dark:text-gray-dark-1100 hover:text-color-brands transition-colors">
                                        <?php echo $rec['title']; ?>
                                    </a>
                                    <span class="text-green text-sm"><?php echo $rec['match_percentage']; ?>%</span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <!-- Показываем популярные профессии если нет рекомендаций -->
                            <?php
                            $popular_professions = $link->query("SELECT id, title FROM professions LIMIT 3");
                            while ($prof = $popular_professions->fetch_assoc()):
                            ?>
                                <div class="flex items-center justify-between">
                                    <a href="index.php?page=profession-detail&id=<?php echo $prof['id']; ?>"
                                        class="text-normal text-gray-1100 dark:text-gray-dark-1100 hover:text-color-brands transition-colors">
                                        <?php echo $prof['title']; ?>
                                    </a>
                                    <span class="text-gray-500 text-sm">Изучить</span>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                    <a href="index.php?page=professions" class="btn bg-color-brands text-white px-6">Все профессии</a>
                </div>
            </div>

            <!-- Последняя активность -->
            <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 mb-6">
                <div class="flex items-center justify-between mb-6">
                    <p class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100">Последняя активность</p>
                </div>
                <div class="space-y-4">
                    <?php if ($recent_activity->num_rows > 0): ?>
                        <?php while ($activity = $recent_activity->fetch_assoc()): ?>
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-lg grid place-items-center 
                                    <?php echo $activity['type'] == 'test' ? 'bg-green/20' : ($activity['type'] == 'profession' ? 'bg-blue/20' : 'bg-violet/20'); ?>">
                                    <img src="assets/images/icons/icon-<?php echo $activity['type'] == 'test' ? 'doc' : ($activity['type'] == 'profession' ? 'work' : 'project'); ?>.svg"
                                        alt="<?php echo $activity['type']; ?>">
                                </div>
                                <div>
                                    <p class="text-normal text-gray-1100 dark:text-gray-dark-1100">
                                        <?php
                                        if ($activity['type'] == 'test') {
                                            echo 'Пройден тест "' . $activity['title'] . '"';
                                        } elseif ($activity['type'] == 'profession') {
                                            echo 'Рекомендована профессия "' . $activity['title'] . '"';
                                        } else {
                                            echo 'Создан план "' . $activity['title'] . '"';
                                        }
                                        ?>
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-dark-500">
                                        <?php echo date('d.m.Y H:i', strtotime($activity['date'])); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-gray-500 dark:text-gray-dark-500 text-center py-4">Активность отсутствует</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>