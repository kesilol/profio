<?php
// Функция для правильного склонения слов
function getCorrectWord($number, $form1, $form2, $form3) {
    $number = abs($number) % 100;
    if ($number > 10 && $number < 20) return $form3;
    $number = $number % 10;
    if ($number == 1) return $form1;
    if ($number >= 2 && $number <= 4) return $form2;
    return $form3;
}

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

            <!-- Статистика куратора (3 карточки) -->
            <div class="grid grid-cols-1 gap-6 mb-[26px] lg:grid-cols-3">
                <!-- Мои студенты -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg py-4 flex-1 px-[19px] hover:shadow-md transition-all group cursor-pointer" onclick="window.location.href='index.php?page=manage-students'">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-desc text-gray-500 dark:text-gray-dark-500">Мои обучающиеся</p>
                        <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                            <i class="bi bi-people text-green-600 dark:text-green-400 text-lg"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline justify-between mb-1">
                        <p class="text-3xl font-bold text-gray-1100 dark:text-gray-dark-1100">
                            <?php echo $students_stats['total_students'] ?? 0; ?>
                        </p>
                        <?php if (($students_stats['total_students'] ?? 0) > 0): ?>
                            <div class="flex items-center gap-1 text-green-600 text-sm">
                                <i class="bi bi-arrow-up-short"></i>
                                <span>+<?php echo $students_stats['total_students'] ?? 0; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                            <?php
                            $max_students = 50;
                            $progress = min(100, (($students_stats['total_students'] ?? 0) / $max_students) * 100);
                            ?>
                            <div class="bg-green-500 h-1.5 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px] mt-1">
                            Всего обучающихся
                        </p>
                    </div>
                    <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px] mt-1">
                        <i class="bi bi-chevron-right text-xs"></i> Управление
                    </p>
                </div>

                <!-- Пройдено тестов -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg py-4 flex-1 px-[19px] hover:shadow-md transition-all group cursor-pointer" onclick="window.location.href='index.php?page=reports'">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-desc text-gray-500 dark:text-gray-dark-500">Пройдено тестов</p>
                        <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                            <i class="bi bi-clipboard-data text-blue-600 dark:text-blue-400 text-lg"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline justify-between mb-1">
                        <p class="text-3xl font-bold text-gray-1100 dark:text-gray-dark-1100">
                            <?php echo $students_stats['total_tests'] ?? 0; ?>
                        </p>
                        <?php if (($students_stats['total_tests'] ?? 0) > 0): ?>
                            <div class="flex items-center gap-1 text-blue-600 text-sm">
                                <i class="bi bi-arrow-up-short"></i>
                                <span>+<?php echo $students_stats['total_tests'] ?? 0; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                            <?php
                            $max_tests = 100;
                            $progress = min(100, (($students_stats['total_tests'] ?? 0) / $max_tests) * 100);
                            ?>
                            <div class="bg-blue-500 h-1.5 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px] mt-1">
                            Всего тестов
                        </p>
                    </div>
                    <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px] mt-1">
                        <i class="bi bi-chevron-right text-xs"></i> Подробнее
                    </p>
                </div>

                <!-- Создано планов -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg py-4 flex-1 px-[19px] hover:shadow-md transition-all group cursor-pointer" onclick="window.location.href='index.php?page=reports'">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-desc text-gray-500 dark:text-gray-dark-500">Планы развития</p>
                        <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                            <i class="bi bi-kanban text-purple-600 dark:text-purple-400 text-lg"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline justify-between mb-1">
                        <p class="text-3xl font-bold text-gray-1100 dark:text-gray-dark-1100">
                            <?php echo $students_stats['total_plans'] ?? 0; ?>
                        </p>
                        <?php if (($students_stats['total_plans'] ?? 0) > 0): ?>
                            <div class="flex items-center gap-1 text-purple-600 text-sm">
                                <i class="bi bi-arrow-up-short"></i>
                                <span>+<?php echo $students_stats['total_plans'] ?? 0; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                            <?php
                            $max_plans = 50;
                            $progress = min(100, (($students_stats['total_plans'] ?? 0) / $max_plans) * 100);
                            ?>
                            <div class="bg-purple-500 h-1.5 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px] mt-1">
                            Создано планов
                        </p>
                    </div>
                    <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px] mt-1">
                        <i class="bi bi-chevron-right text-xs"></i> Подробнее
                    </p>
                </div>
            </div>

            <!-- Две колонки: слева список студентов, справа результаты тестов -->
            <div class="grid grid-cols-1 gap-6 mb-6 xl:grid-cols-2">
                <!-- Левая колонка: студенты -->
                <div class="space-y-6">
                    <!-- Последние добавленные студенты -->
                    <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100">Последние добавленные обучающиеся</p>
                            <a href="index.php?page=manage-students" class="text-color-brands text-sm hover:underline">Все обучающиеся →</a>
                        </div>
                        <div class="w-full bg-neutral h-[1px] mb-[19px] dark:bg-dark-neutral-border"></div>
                        <div class="space-y-4">
                            <?php if ($recent_students && $recent_students->num_rows > 0): ?>
                                <?php while ($student = $recent_students->fetch_assoc()): ?>
                                    <div class="flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-800/50 p-2 rounded-lg transition-colors">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                                <i class="bi bi-person text-blue-600 dark:text-blue-400"></i>
                                            </div>
                                            <div>
                                                <a href="index.php?page=student-detail&id=<?php echo $student['id']; ?>"
                                                    class="text-normal font-medium text-gray-1100 dark:text-gray-dark-1100 hover:text-color-brands transition-colors">
                                                    <?php echo $student['name']; ?>
                                                </a>
                                                <p class="text-xs text-gray-500"><?php echo $student['email']; ?></p>
                                            </div>
                                        </div>
                                        <span class="text-gray-500 text-sm">
                                            <?php echo date('d.m.Y', strtotime($student['assigned_at'])); ?>
                                        </span>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-gray-500 dark:text-gray-dark-500 text-center py-4">Нет обучающихся в списке</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Самые активные студенты -->
                    <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100">Самые активные обучающиеся</p>
                            <i class="bi bi-trophy text-xl text-yellow-500"></i>
                        </div>
                        <div class="w-full bg-neutral h-[1px] mb-[19px] dark:bg-dark-neutral-border"></div>
                        <div class="space-y-4">
                            <?php if ($active_students && $active_students->num_rows > 0): ?>
                                <?php 
                                $medal_colors = ['text-yellow-500', 'text-gray-400', 'text-amber-600'];
                                $i = 0;
                                while ($student = $active_students->fetch_assoc()): 
                                ?>
                                    <div class="flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-800/50 p-2 rounded-lg transition-colors">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo $i < 3 ? 'bg-yellow-100 dark:bg-yellow-900/30' : 'bg-gray-100 dark:bg-gray-800'; ?>">
                                                <i class="bi bi-trophy <?php echo $i < 3 ? $medal_colors[$i] : 'text-gray-400'; ?>"></i>
                                            </div>
                                            <div>
                                                <a href="index.php?page=student-detail&id=<?php echo $student['id']; ?>"
                                                    class="text-normal font-medium text-gray-1100 dark:text-gray-dark-1100 hover:text-color-brands transition-colors">
                                                    <?php echo $student['name']; ?>
                                                </a>
                                                <p class="text-xs text-gray-500"><?php echo $student['tests_count']; ?> пройденных тестов</p>
                                            </div>
                                        </div>
                                        <span class="text-green text-sm font-medium">
                                            Активен
                                        </span>
                                    </div>
                                <?php 
                                $i++;
                                endwhile; 
                                ?>
                            <?php else: ?>
                                <p class="text-gray-500 dark:text-gray-dark-500 text-center py-4">Нет активных обучающихся</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Правая колонка: последние результаты тестов -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100">Последние результаты тестов</p>
                        <a href="index.php?page=reports" class="text-color-brands text-sm hover:underline">Все результаты →</a>
                    </div>
                    <div class="w-full bg-neutral h-[1px] mb-[19px] dark:bg-dark-neutral-border"></div>
                    <div class="space-y-4">
                        <?php if ($recent_test_results && $recent_test_results->num_rows > 0): ?>
                            <?php while ($test = $recent_test_results->fetch_assoc()): ?>
                                <div class="flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-800/50 p-2 rounded-lg transition-colors">
                                    <div class="flex items-center gap-3 flex-1">
                                        <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                            <i class="bi bi-file-text text-blue-600 dark:text-blue-400"></i>
                                        </div>
                                        <div>
                                            <p class="text-normal font-medium text-gray-1100 dark:text-gray-dark-1100">
                                                <?php echo $test['student_name']; ?>
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                <?php echo $test['test_title']; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-color-brands font-semibold">
                                            <?php echo $test['total_score']; ?>
                                        </span>
                                        <span class="text-gray-500 text-xs">баллов</span>
                                        <p class="text-xs text-gray-500">
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
            
            <?php
            // Получаем общее количество доступных тестов
            $total_available_tests = $link->query("SELECT COUNT(*) as count FROM tests")->fetch_assoc()['count'];
            
            // Получаем общее количество профессий
            $total_professions = $link->query("SELECT COUNT(*) as count FROM professions")->fetch_assoc()['count'];
            
            // Получаем результаты теста для студентов
            $recent_results = $link->query("
                SELECT tr.*, t.title as test_title 
                FROM test_results tr 
                LEFT JOIN tests t ON tr.test_id = t.id 
                WHERE tr.user_id = '$user_id' 
                ORDER BY tr.completed_at DESC 
                LIMIT 1
            ");

            // Проверяем есть ли результаты теста
            $has_test_results = $recent_results && $recent_results->num_rows > 0;
            if ($has_test_results) {
                $test_result = $recent_results->fetch_assoc();
            }

            // Считаем количество УНИКАЛЬНЫХ тестов
            $tests_count = $link->query("SELECT COUNT(DISTINCT test_id) as count FROM test_results WHERE user_id = '$user_id'")->fetch_assoc()['count'];
            $recommendations_count = $link->query("SELECT COUNT(DISTINCT profession_id) as count FROM recommendations WHERE user_id = '$user_id'")->fetch_assoc()['count'];
            $plans_count = $link->query("SELECT COUNT(*) as count FROM development_plans WHERE user_id = '$user_id'")->fetch_assoc()['count'];

            // Дополнительно: получаем общее количество ПРОХОЖДЕНИЙ тестов
            $total_attempts = $link->query("SELECT COUNT(*) as count FROM test_results WHERE user_id = '$user_id'")->fetch_assoc()['count'];

            // Получаем последние уникальные рекомендации
            $latest_recommendations = $link->query("
                SELECT p.title, r.match_percentage, r.profession_id
                FROM recommendations r 
                JOIN professions p ON r.profession_id = p.id 
                WHERE r.user_id = '$user_id' 
                AND r.id = (
                    SELECT r2.id
                    FROM recommendations r2
                    WHERE r2.user_id = r.user_id 
                    AND r2.profession_id = r.profession_id
                    ORDER BY r2.match_percentage DESC, r2.id DESC
                    LIMIT 1
                )
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

            // Получаем информацию о последней активности и дне регистрации
            $user_created = $link->query("SELECT created_at FROM users WHERE id = '$user_id'")->fetch_assoc();
            $user_created_date = $user_created['created_at'] ?? date('Y-m-d');
            $days_on_platform = floor((time() - strtotime($user_created_date)) / (60 * 60 * 24));

            // Получаем прогресс активного плана
            $active_plan = $link->query("
                SELECT dp.id, 
                       COUNT(pt.id) as total_tasks,
                       SUM(pt.is_completed) as completed_tasks
                FROM development_plans dp
                LEFT JOIN plan_tasks pt ON dp.id = pt.plan_id
                WHERE dp.user_id = '$user_id' AND dp.is_completed = 0
                GROUP BY dp.id
                ORDER BY dp.created_at DESC
                LIMIT 1
            ");
            $active_plan_data = $active_plan && $active_plan->num_rows > 0 ? $active_plan->fetch_assoc() : null;

            // Получаем последнюю дату активности
            $last_activity = $link->query("
                SELECT MAX(completed_at) as last_date FROM test_results WHERE user_id = '$user_id'
                UNION
                SELECT MAX(created_at) as last_date FROM development_plans WHERE user_id = '$user_id'
                ORDER BY last_date DESC LIMIT 1
            ")->fetch_assoc();
            $last_activity_date = $last_activity['last_date'] ?? null;
            ?>

            <!-- Блок с результатами теста для новых пользователей -->
            <?php if ($has_test_results): ?>
                <div class="rounded-2xl border border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20 p-6 mb-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 rounded-lg bg-green-100 dark:bg-green-800 grid place-items-center mr-4 flex-shrink-0">
                            <img src="assets/images/icons/icon-check-circle.svg" alt="Успех" class="w-6 h-6">
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                Результаты вашего теста готовы!
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                <?php echo $test_result['test_title']; ?>
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Ваш тип личности</div>
                            <div class="text-xl font-bold text-color-brands capitalize">
                                <?php echo $test_result['result_type']; ?>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Набранные баллы</div>
                            <div class="text-xl font-bold text-gray-900 dark:text-white">
                                <?php echo $test_result['total_score']; ?> баллов
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="index.php?page=recommendations" class="btn bg-color-brands text-white px-6 py-3 text-center">
                            Посмотреть рекомендации
                        </a>
                        <a href="index.php?page=my-results" class="btn border border-color-brands text-color-brands px-6 py-3 text-center">
                            Все результаты
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Статистика пользователя -->
            <div class="grid grid-cols-1 gap-6 mb-[26px] lg:grid-cols-2 xl:grid-cols-4">
                <!-- Пройденные тесты -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg py-4 flex-1 px-[19px] hover:shadow-md transition-all group cursor-pointer" onclick="window.location.href='index.php?page=my-results'">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-desc text-gray-500 dark:text-gray-dark-500">Пройдено тестов</p>
                        <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                            <i class="bi bi-clipboard-data text-green-600 dark:text-green-400 text-lg"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline justify-between mb-1">
                        <p class="text-3xl font-bold text-gray-1100 dark:text-gray-dark-1100"><?php echo $tests_count; ?></p>
                        <?php if ($tests_count > 0): ?>
                            <div class="flex items-center gap-1 text-green-600 text-sm">
                                <i class="bi bi-arrow-up-short"></i>
                                <span>+<?php echo $tests_count; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                            <?php
                            $progress = $total_available_tests > 0 ? min(100, ($tests_count / $total_available_tests) * 100) : 0;
                            ?>
                            <div class="bg-green-500 h-1.5 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px] mt-1">
                            <?php echo $tests_count; ?> из <?php echo $total_available_tests; ?> <?php echo getCorrectWord($total_available_tests, 'тест', 'теста', 'тестов'); ?>
                        </p>
                    </div>
                    <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px] mt-1">
                        <i class="bi bi-chevron-right text-xs"></i> Подробнее
                    </p>
                </div>

                <!-- Рекомендации (уникальные профессии) -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg py-4 flex-1 px-[19px] hover:shadow-md transition-all group cursor-pointer" onclick="window.location.href='index.php?page=recommendations'">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-desc text-gray-500 dark:text-gray-dark-500">Рекомендации</p>
                        <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                            <i class="bi bi-star text-blue-600 dark:text-blue-400 text-lg"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline justify-between mb-1">
                        <p class="text-3xl font-bold text-gray-1100 dark:text-gray-dark-1100"><?php echo $recommendations_count; ?></p>
                        <?php if ($recommendations_count > 0): ?>
                            <div class="flex items-center gap-1 text-blue-600 text-sm">
                                <i class="bi bi-arrow-up-short"></i>
                                <span>+<?php echo $recommendations_count; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                            <?php
                            $progress = $total_professions > 0 ? min(100, ($recommendations_count / $total_professions) * 100) : 0;
                            ?>
                            <div class="bg-blue-500 h-1.5 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px] mt-1">
                            Уникальных профессий из <?php echo $total_professions; ?>
                        </p>
                    </div>
                    <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px] mt-1">
                        <i class="bi bi-chevron-right text-xs"></i> Смотреть все
                    </p>
                </div>

                <!-- Планы развития -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg py-4 flex-1 px-[19px] hover:shadow-md transition-all group cursor-pointer" onclick="window.location.href='index.php?page=plan'">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-desc text-gray-500 dark:text-gray-dark-500">Планы развития</p>
                        <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                            <i class="bi bi-kanban text-purple-600 dark:text-purple-400 text-lg"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline justify-between mb-1">
                        <p class="text-3xl font-bold text-gray-1100 dark:text-gray-dark-1100"><?php echo $plans_count; ?></p>
                        <?php if ($plans_count > 0): ?>
                            <div class="flex items-center gap-1 text-purple-600 text-sm">
                                <i class="bi bi-arrow-up-short"></i>
                                <span>+<?php echo $plans_count; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2">
                        <?php if ($active_plan_data && $active_plan_data['total_tasks'] > 0): ?>
                            <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                                <?php
                                $plan_progress = round(($active_plan_data['completed_tasks'] / $active_plan_data['total_tasks']) * 100);
                                ?>
                                <div class="bg-purple-500 h-1.5 rounded-full" style="width: <?php echo $plan_progress; ?>%"></div>
                            </div>
                            <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px] mt-1">
                                <?php echo $active_plan_data['completed_tasks']; ?> из <?php echo $active_plan_data['total_tasks']; ?> задач
                            </p>
                        <?php else: ?>
                            <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                                <div class="bg-gray-400 h-1.5 rounded-full" style="width: 0%"></div>
                            </div>
                            <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px] mt-1">
                                <?php echo $plans_count > 0 ? 'Нет активных планов' : 'Создайте первый план'; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px] mt-1">
                        <i class="bi bi-chevron-right text-xs"></i> Управлять
                    </p>
                </div>

                <!-- Активность -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg py-4 flex-1 px-[19px] hover:shadow-md transition-all group cursor-pointer" onclick="window.location.href='index.php?page=my-results'">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-desc text-gray-500 dark:text-gray-dark-500">Активность</p>
                        <div class="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                            <i class="bi bi-activity text-orange-600 dark:text-orange-400 text-lg"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline justify-between mb-1">
                        <p class="text-3xl font-bold text-gray-1100 dark:text-gray-dark-1100">
                            <?php
                            $total_actions = $tests_count + $recommendations_count + $plans_count;
                            echo $total_actions;
                            ?>
                        </p>
                        <div class="flex items-center gap-1 text-orange-600 text-sm">
                            <i class="bi bi-arrow-up-short"></i>
                            <span>+<?php echo $total_actions; ?></span>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Последняя активность:</span>
                            <span class="text-gray-600 dark:text-gray-400">
                                <?php echo $last_activity_date ? date('d.m.Y', strtotime($last_activity_date)) : 'Нет активности'; ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm mt-1">
                            <span class="text-gray-500">Дней на платформе:</span>
                            <span class="text-gray-600 dark:text-gray-400">
                                <?php echo $days_on_platform; ?> дней
                            </span>
                        </div>
                    </div>
                    <p class="text-right text-gray-400 dark:text-gray-dark-400 text-[11px] leading-[16px] mt-1">
                        <i class="bi bi-chevron-right text-xs"></i> История
                    </p>
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

                <!-- Карточка профессий с уникальными рекомендациями -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100">
                            <?php echo $recommendations_count > 0 ? 'Рекомендованные профессии' : 'Популярные профессии'; ?>
                        </p>
                    </div>
                    <div class="w-full bg-neutral h-[1px] mb-[19px] dark:bg-dark-neutral-border"></div>
                    <div class="space-y-4 mb-6">
                        <?php if ($latest_recommendations && $latest_recommendations->num_rows > 0): ?>
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
                            if ($popular_professions && $popular_professions->num_rows > 0):
                                while ($prof = $popular_professions->fetch_assoc()):
                            ?>
                                <div class="flex items-center justify-between">
                                    <a href="index.php?page=profession-detail&id=<?php echo $prof['id']; ?>"
                                        class="text-normal text-gray-1100 dark:text-gray-dark-1100 hover:text-color-brands transition-colors">
                                        <?php echo $prof['title']; ?>
                                    </a>
                                    <span class="text-gray-500 text-sm">Изучить</span>
                                </div>
                            <?php 
                                endwhile;
                            endif;
                            ?>
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
                    <?php if ($recent_activity && $recent_activity->num_rows > 0): ?>
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