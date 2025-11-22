<?php
// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header("Location: index.php?page=sign-in");
    exit();
}

require_once('event_user/reports_handler.php');

$user_id = $_SESSION['user']['id_user'];
$user_role = $_SESSION['user']['role'];

// Получаем данные в зависимости от роли
if ($user_role === 'студент') {
    $report_data = getStudentReportData($link, $user_id);
} elseif ($user_role === 'куратор') {
    $report_data = getCuratorReportData($link, $user_id);
} elseif ($user_role === 'администратор') {
    $report_data = getAdminReportData($link); 
}

// Проверяем на ошибки
if (isset($report_data['error'])) {
    echo "<div class='p-4 bg-red-100 text-red-700 rounded-lg'>Ошибка: {$report_data['error']}</div>";
    exit();
}
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    <div>
        <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100 mb-[13px]">
            <?php 
            if ($user_role === 'студент') {
                echo 'Мой отчет';
            } elseif ($user_role === 'куратор') {
                echo 'Сводка по студентам';
            } elseif ($user_role === 'администратор') {
                echo 'Статистика системы';
            }
            ?>
        </h2>

        <div class="flex items-center text-xs text-gray-500 gap-x-[11px] mb-[37px]">
            <div class="flex items-center gap-x-1">
                <img src="assets/images/icons/icon-home-2.svg" alt="Главная">
                <a class="capitalize" href="index.php">Главная</a>
            </div>
            <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
            <span class="capitalize text-color-brands">Отчеты</span>
        </div>

        <?php if ($user_role === 'студент'): ?>
            <!-- ОТЧЕТ ДЛЯ СТУДЕНТА -->
            <div class="space-y-6">
                <!-- Карточка с основной информацией -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100">
                            Общая информация
                        </h3>
                        <button onclick="generatePDF()" class="btn bg-color-brands text-white px-4 py-2 flex items-center gap-2">
                            <i class="bi bi-download"></i>
                            Скачать PDF отчет
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center p-4 rounded-lg bg-blue/10">
                            <div class="text-2xl font-bold text-color-brands mb-1">
                                <?php echo $report_data['user']['tests_count'] ?? 0; ?>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-dark-500">Пройдено тестов</div>
                        </div>
                        <div class="text-center p-4 rounded-lg bg-green/10">
                            <div class="text-2xl font-bold text-green mb-1">
                                <?php echo $report_data['user']['recommendations_count'] ?? 0; ?>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-dark-500">Рекомендаций</div>
                        </div>
                        <div class="text-center p-4 rounded-lg bg-purple/10">
                            <div class="text-2xl font-bold text-purple mb-1">
                                <?php echo $report_data['user']['plans_count'] ?? 0; ?>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-dark-500">Планов развития</div>
                        </div>
                    </div>
                </div>

                <!-- Результаты тестов -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                    <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                        Результаты тестирования
                    </h3>

                    <?php if ($report_data['test_results'] && $report_data['test_results']->num_rows > 0): ?>
                        <div class="space-y-4">
                            <?php while ($test = $report_data['test_results']->fetch_assoc()): ?>
                                <div class="flex items-center justify-between p-4 border border-neutral dark:border-dark-neutral-border rounded-lg">
                                    <div>
                                        <h4 class="font-semibold text-gray-1100 dark:text-gray-dark-1100">
                                            <?php echo $test['test_name']; ?>
                                        </h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-dark-500">
                                            <?php echo $test['test_description']; ?>
                                        </p>
                                        <div class="flex items-center gap-4 mt-2 text-sm">
                                            <span class="text-gray-500">Баллы: <?php echo $test['total_score']; ?></span>
                                            <span class="text-gray-500">Тип: <?php echo $test['result_type']; ?></span>
                                            <span class="text-gray-500">
                                                <?php echo date('d.m.Y', strtotime($test['completed_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-color-brands">
                                            <?php echo $test['total_score']; ?> баллов
                                        </div>
                                        <div class="text-sm text-gray-500">Результат</div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 dark:text-gray-dark-500 text-center py-4">
                            Тесты еще не пройдены
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Рекомендации -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                    <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                        Рекомендованные профессии
                    </h3>

                    <?php
                    // Используем правильную проверку количества рекомендаций
                    $recommendations_count = $report_data['recommendations'] ? $report_data['recommendations']->num_rows : 0;
                    ?>

                    <?php if ($recommendations_count > 0): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php
                            // Используем массив с изображениями если он есть, иначе оригинальный результат
                            if (isset($report_data['recommendations_with_images']) && !empty($report_data['recommendations_with_images'])) {
                                $recommendations_to_display = $report_data['recommendations_with_images'];
                            } else {
                                // Если массива с изображениями нет, используем оригинальный результат
                                $report_data['recommendations']->data_seek(0);
                                $recommendations_to_display = [];
                                while ($rec = $report_data['recommendations']->fetch_assoc()) {
                                    $recommendations_to_display[] = $rec;
                                }
                            }

                            foreach ($recommendations_to_display as $rec):
                                $institutions = getInstitutionsForProfessionReport($link, $rec['profession_id'], 2);
                                $companies = getCompaniesForProfessionReport($link, $rec['profession_id'], 2);
                            ?>
                                <div class="border border-neutral dark:border-dark-neutral-border rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center gap-3 mb-3">
                                        <?php if (!empty($rec['image_url'])): ?>
                                            <img src="<?php echo $rec['image_url']; ?>" alt="<?php echo $rec['profession_title']; ?>" class="w-12 h-12 rounded-lg object-cover">
                                        <?php else: ?>
                                            <div class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-dark-100 grid place-items-center">
                                                <i class="bi bi-briefcase text-gray-400"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h4 class="font-semibold text-gray-1100 dark:text-gray-dark-1100">
                                                <?php echo $rec['profession_title']; ?>
                                            </h4>
                                            <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-dark-100 dark:text-gray-dark-600">
                                                <?php echo $rec['category']; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="space-y-2 text-sm mb-3">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Совпадение:</span>
                                            <span class="font-semibold text-color-brands"><?php echo $rec['match_percentage']; ?>%</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Зарплата:</span>
                                            <span class="font-semibold"><?php echo $rec['salary_range']; ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Спрос:</span>
                                            <span class="font-semibold <?php echo $rec['demand_level'] === 'высокий' ? 'text-green-600' : ($rec['demand_level'] === 'средний' ? 'text-yellow-600' : 'text-red-600'); ?>">
                                                <?php echo ucfirst($rec['demand_level']); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Места обучения -->
                                    <?php if ($institutions && $institutions->num_rows > 0): ?>
                                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-dark-200">
                                            <p class="text-xs font-semibold text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-1">
                                                <i class="bi bi-mortarboard text-color-brands"></i>
                                                Где учиться:
                                            </p>
                                            <div class="space-y-1">
                                                <?php while ($institution = $institutions->fetch_assoc()): ?>
                                                    <div class="text-xs text-gray-600 dark:text-gray-dark-400 flex items-start gap-1">
                                                        <span class="text-color-brands mt-0.5">•</span>
                                                        <div>
                                                            <span class="font-medium"><?php echo $institution['name']; ?></span>
                                                            <?php if (!empty($institution['location'])): ?>
                                                                <span class="text-gray-400">(<?php echo $institution['location']; ?>)</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endwhile; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Места работы -->
                                    <?php if ($companies && $companies->num_rows > 0): ?>
                                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-dark-200">
                                            <p class="text-xs font-semibold text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-1">
                                                <i class="bi bi-briefcase text-color-brands"></i>
                                                Где работать:
                                            </p>
                                            <div class="space-y-1">
                                                <?php while ($company = $companies->fetch_assoc()): ?>
                                                    <div class="text-xs text-gray-600 dark:text-gray-dark-400 flex items-start gap-1">
                                                        <span class="text-color-brands mt-0.5">•</span>
                                                        <div>
                                                            <span class="font-medium"><?php echo $company['name']; ?></span>
                                                            <?php if (!empty($company['industry'])): ?>
                                                                <span class="text-gray-400">(<?php echo $company['industry']; ?>)</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endwhile; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Кнопка подробнее -->
                                    <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-dark-200">
                                        <a href="index.php?page=profession-detail&id=<?php echo $rec['profession_id']; ?>"
                                            class="text-xs text-color-brands font-semibold hover:underline flex items-center gap-1">
                                            Подробнее о профессии
                                            <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 dark:text-gray-dark-500 text-center py-4">
                            Рекомендации пока не сформированы
                        </p>
                    <?php endif; ?>
                </div>

                <!-- План развития -->
                <?php if ($report_data['development_plan']): ?>
                    <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                        <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                            Активный план развития
                        </h3>

                        <div class="mb-4">
                            <h4 class="font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-2">
                                <?php echo $report_data['development_plan']['title']; ?>
                            </h4>
                            <p class="text-gray-500 dark:text-gray-dark-500 text-sm mb-3">
                                <?php echo $report_data['development_plan']['description']; ?>
                            </p>

                            <div class="flex items-center gap-4 text-sm">
                                <span class="text-gray-500">
                                    Прогресс:
                                    <?php echo $report_data['development_plan']['completed_tasks'] ?? 0; ?> /
                                    <?php echo $report_data['development_plan']['total_tasks'] ?? 0; ?> задач
                                </span>
                                <?php if (!empty($report_data['development_plan']['deadline'])): ?>
                                    <span class="text-gray-500">
                                        Срок: <?php echo date('d.m.Y', strtotime($report_data['development_plan']['deadline'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Прогресс-бар -->
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-4 dark:bg-gray-700">
                            <?php
                            $total_tasks = $report_data['development_plan']['total_tasks'] ?? 0;
                            $completed_tasks = $report_data['development_plan']['completed_tasks'] ?? 0;
                            $progress = $total_tasks > 0 ? ($completed_tasks / $total_tasks) * 100 : 0;
                            ?>
                            <div class="bg-color-brands h-2 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                        </div>

                        <!-- Задачи -->
                        <?php if ($report_data['plan_tasks'] && $report_data['plan_tasks']->num_rows > 0): ?>
                            <div class="space-y-2">
                                <?php while ($task = $report_data['plan_tasks']->fetch_assoc()): ?>
                                    <div class="flex items-center gap-3 p-3 border border-neutral dark:border-dark-neutral-border rounded-lg">
                                        <div class="<?php echo $task['is_completed'] ? 'text-green' : 'text-gray-400'; ?>">
                                            <i class="bi <?php echo $task['is_completed'] ? 'bi-check-circle-fill' : 'bi-circle'; ?>"></i>
                                        </div>
                                        <span class="<?php echo $task['is_completed'] ? 'text-gray-500 line-through' : 'text-gray-1100 dark:text-gray-dark-1100'; ?>">
                                            <?php echo $task['task_text']; ?>
                                        </span>
                                        <?php if (!empty($task['deadline'])): ?>
                                            <span class="text-xs text-gray-500 ml-auto">
                                                до <?php echo date('d.m.Y', strtotime($task['deadline'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500 dark:text-gray-dark-500 text-center py-4">
                                Задачи плана не найдены
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>

        <?php elseif ($user_role === 'куратор'): ?>
            <!-- СВОДКА ДЛЯ КУРАТОРА -->
            <div class="space-y-6">

                <!-- Общая статистика -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 text-center">
                        <div class="text-2xl font-bold text-color-brands mb-1">
                            <?php echo $report_data['overall_stats']['total_students']; ?>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-dark-500">Студентов</div>
                    </div>
                    <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 text-center">
                        <div class="text-2xl font-bold text-green mb-1">
                            <?php echo $report_data['overall_stats']['total_tests']; ?>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-dark-500">Тестов пройдено</div>
                    </div>
                    <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 text-center">
                        <div class="text-2xl font-bold text-purple mb-1">
                            <?php echo $report_data['overall_stats']['total_plans']; ?>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-dark-500">Планов развития</div>
                    </div>
                    <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 text-center">
                        <div class="text-2xl font-bold text-orange mb-1">
                            <?php echo $report_data['overall_stats']['avg_test_score'] ?? '0'; ?>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-dark-500">Средний балл</div>
                    </div>
                </div>

                <!-- Список студентов в виде карточек -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100">
                            Мои студенты
                        </h3>
                        <!-- Кнопка для перехода к управлению студентами -->
                        <a href="index.php?page=manage-students" class="btn bg-color-brands text-white px-4 py-2 flex items-center gap-2">
                            <i class="bi bi-person-plus"></i>
                            Управление студентами
                        </a>
                    </div>

                    <!-- Поиск и фильтры -->
                    <div class="mb-6 flex flex-col md:flex-row gap-4">
                        <div class="flex-grow">
                            <input type="text" id="studentSearch" placeholder="Поиск по имени студента..."
                                class="w-full p-3 border border-neutral dark:border-dark-neutral-border rounded-lg bg-white dark:bg-dark-neutral-bg">
                        </div>
                        <div class="flex gap-4">
                            <select id="educationFilter" class="p-3 border border-neutral dark:border-dark-neutral-border rounded-lg bg-white dark:bg-dark-neutral-bg">
                                <option value="">Все уровни образования</option>
                                <option value="среднее">Среднее</option>
                                <option value="среднее-специальное">Среднее-специальное</option>
                                <option value="бакалавриат">Бакалавриат</option>
                                <option value="магистратура">Магистратура</option>
                                <option value="аспирантура">Аспирантура</option>
                            </select>
                            <select id="activityFilter" class="p-3 border border-neutral dark:border-dark-neutral-border rounded-lg bg-white dark:bg-dark-neutral-bg">
                                <option value="">Вся активность</option>
                                <option value="active">Активные (тесты за 30 дней)</option>
                                <option value="inactive">Неактивные</option>
                            </select>
                        </div>
                    </div>

                    <!-- Контейнер для карточек студентов -->
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6" id="studentsContainer">
                        <?php
                        $students_result = $report_data['students'];
                        if ($students_result && $students_result->num_rows > 0):
                            while ($student = $students_result->fetch_assoc()):
                                // Определяем статус активности
                                $is_active = false;
                                if ($student['last_test_date']) {
                                    $last_test = strtotime($student['last_test_date']);
                                    $thirty_days_ago = strtotime('-30 days');
                                    $is_active = $last_test >= $thirty_days_ago;
                                }
                        ?>
                                <div class="student-card border border-neutral dark:border-dark-neutral-border rounded-xl p-5 hover:shadow-lg transition-all duration-300 bg-white dark:bg-dark-neutral-bg flex flex-col min-h-[320px]"
                                    data-student-id="<?= $student['id'] ?>"
                                    data-education="<?= $student['education_level'] ?>"
                                    data-active="<?= $is_active ? 'active' : 'inactive' ?>"
                                    data-name="<?= htmlspecialchars(mb_strtolower($student['name'])) ?>">

                                    <!-- Заголовок карточки -->
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="flex-1">
                                            <h4 class="font-bold text-lg text-gray-1100 dark:text-gray-dark-1100 mb-1">
                                                <?= $student['name'] ?>
                                            </h4>
                                            <p class="text-sm text-gray-500 dark:text-gray-dark-500 truncate">
                                                <?= $student['email'] ?>
                                            </p>
                                        </div>
                                        <div class="flex flex-col items-end gap-2">
                                            <span class="text-xs px-2 py-1 rounded-full <?= $is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' ?>">
                                                <?= $is_active ? 'Активен' : 'Неактивен' ?>
                                            </span>
                                            <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-800">
                                                <?= ucfirst($student['education_level']) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Статистика студента -->
                                    <div class="space-y-3 mb-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600 dark:text-gray-dark-600">Пройдено тестов:</span>
                                            <span class="font-semibold <?= $student['tests_completed'] > 0 ? 'text-green-600' : 'text-gray-500' ?>">
                                                <?= $student['tests_completed'] ?>
                                                <?php if ($student['tests_completed'] > 0): ?>
                                                    <i class="bi bi-check-circle ml-1"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-circle ml-1"></i>
                                                <?php endif; ?>
                                            </span>
                                        </div>

                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600 dark:text-gray-dark-600">Рекомендаций:</span>
                                            <span class="font-semibold text-color-brands">
                                                <?= $student['recommendations_count'] ?>
                                            </span>
                                        </div>

                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600 dark:text-gray-dark-600">Последний тест:</span>
                                            <span class="text-sm text-gray-500">
                                                <?= $student['last_test_date'] ? date('d.m.Y', strtotime($student['last_test_date'])) : '---' ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Прогресс-бар (всегда показываем, но разный контент) -->
                                    <div class="mb-4">
                                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                                            <span>Прогресс</span>
                                            <span>
                                                <?php if ($student['tests_completed'] > 0): ?>
                                                    <?= $student['tests_completed'] ?> тестов
                                                <?php else: ?>
                                                    Нет тестов
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                            <?php if ($student['tests_completed'] > 0): ?>
                                                <?php
                                                $progress = min(100, ($student['tests_completed'] / 5) * 100);
                                                ?>
                                                <div class="bg-color-brands h-2 rounded-full" style="width: <?= $progress ?>%"></div>
                                            <?php else: ?>
                                                <div class="bg-gray-400 h-2 rounded-full" style="width: 100%"></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Гибкое пространство для выравнивания -->
                                    <div class="flex-grow"></div>

                                    <!-- Кнопка подробнее - ВСЕГДА ВНИЗУ -->
                                    <div class="pt-4 mt-auto">
                                        <a href="index.php?page=student-detail&id=<?= $student['id'] ?>"
                                            class="block w-full bg-color-brands text-white py-3 rounded-lg font-semibold hover:bg-opacity-90 transition flex items-center justify-center gap-2 text-center">
                                            <i class="bi bi-eye"></i>
                                            Подробнее
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-span-full text-center py-8">
                                <i class="bi bi-people text-4xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500 dark:text-gray-dark-500">Нет студентов в вашем списке</p>
                                <a href="index.php?page=manage-students" class="btn bg-color-brands text-white mt-3 inline-block">
                                    Добавить студентов
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Статистика по тестам -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                    <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                        Статистика по тестам
                    </h3>

                    <?php if ($report_data['test_stats'] && $report_data['test_stats']->num_rows > 0): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php while ($test = $report_data['test_stats']->fetch_assoc()): ?>
                                <div class="border border-neutral dark:border-dark-neutral-border rounded-lg p-4">
                                    <h4 class="font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-2">
                                        <?php echo $test['title']; ?>
                                    </h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Пройдено раз:</span>
                                            <span class="font-semibold"><?php echo $test['completions']; ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Студентов:</span>
                                            <span class="font-semibold"><?php echo $test['unique_students']; ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Средний балл:</span>
                                            <span class="font-semibold text-color-brands"><?php echo round($test['avg_score'], 1); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 dark:text-gray-dark-500 text-center py-4">
                            Нет данных по тестам
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Распределение по категориям профессий -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                    <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                        Распределение рекомендаций по категориям
                    </h3>

                    <?php if ($report_data['profession_stats'] && $report_data['profession_stats']->num_rows > 0): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php while ($cat = $report_data['profession_stats']->fetch_assoc()): ?>
                                <div class="border border-neutral dark:border-dark-neutral-border rounded-lg p-4 text-center">
                                    <div class="text-lg font-bold text-color-brands mb-1">
                                        <?php echo $cat['recommendations_count']; ?>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">
                                        <?php echo ucfirst($cat['category']); ?>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        Совпадение: <?php echo round($cat['avg_match'], 1); ?>%
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 dark:text-gray-dark-500 text-center py-4">
                            Нет данных по рекомендациям
                        </p>
                    <?php endif; ?>
                </div>

            </div>

            <script>
                // Поиск и фильтрация студентов
                document.getElementById('studentSearch').addEventListener('input', filterStudents);
                document.getElementById('educationFilter').addEventListener('change', filterStudents);
                document.getElementById('activityFilter').addEventListener('change', filterStudents);

                function filterStudents() {
                    const searchTerm = document.getElementById('studentSearch').value.toLowerCase();
                    const educationFilter = document.getElementById('educationFilter').value;
                    const activityFilter = document.getElementById('activityFilter').value;
                    const cards = document.querySelectorAll('.student-card');

                    cards.forEach(card => {
                        const name = card.dataset.name;
                        const education = card.dataset.education;
                        const active = card.dataset.active;

                        const matchesSearch = name.includes(searchTerm);
                        const matchesEducation = !educationFilter || education === educationFilter;
                        const matchesActivity = !activityFilter || active === activityFilter;

                        card.style.display = (matchesSearch && matchesEducation && matchesActivity) ? 'block' : 'none';
                    });
                }

                // Активируем поиск при загрузке страницы
                document.addEventListener('DOMContentLoaded', function() {
                    filterStudents();
                });
            </script>

        <?php elseif ($user_role === 'администратор'): ?>
            <!-- ПАНЕЛЬ АДМИНИСТРАТОРА -->
            <div class="space-y-6">
                
                <!-- Общая статистика системы -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                    <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 text-center">
                        <div class="text-2xl font-bold text-color-brands mb-1">
                            <?php echo $report_data['overall_stats']['total_students'] ?? 0; ?>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-dark-500">Студентов</div>
                    </div>
                    <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 text-center">
                        <div class="text-2xl font-bold text-green mb-1">
                            <?php echo $report_data['overall_stats']['total_curators'] ?? 0; ?>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-dark-500">Кураторов</div>
                    </div>
                    <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 text-center">
                        <div class="text-2xl font-bold text-purple mb-1">
                            <?php echo $report_data['overall_stats']['total_tests'] ?? 0; ?>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-dark-500">Тестов пройдено</div>
                    </div>
                    <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 text-center">
                        <div class="text-2xl font-bold text-orange mb-1">
                            <?php echo $report_data['overall_stats']['total_recommendations'] ?? 0; ?>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-dark-500">Рекомендаций</div>
                    </div>
                    <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 text-center">
                        <div class="text-2xl font-bold text-blue mb-1">
                            <?php echo $report_data['overall_stats']['active_plans'] ?? 0; ?>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-dark-500">Активных планов</div>
                    </div>
                </div>

                <!-- Статистика по тестам -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                    <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                        Статистика по тестам
                    </h3>

                    <?php if ($report_data['test_stats'] && $report_data['test_stats']->num_rows > 0): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php while ($test = $report_data['test_stats']->fetch_assoc()): ?>
                                <div class="border border-neutral dark:border-dark-neutral-border rounded-lg p-4">
                                    <h4 class="font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-2">
                                        <?php echo $test['title']; ?>
                                    </h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Пройдено раз:</span>
                                            <span class="font-semibold"><?php echo $test['completions']; ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Уникальных студентов:</span>
                                            <span class="font-semibold"><?php echo $test['unique_students']; ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Средний балл:</span>
                                            <span class="font-semibold text-color-brands">
                                                <?php echo $test['avg_score'] ? round($test['avg_score'], 1) : '0'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 dark:text-gray-dark-500 text-center py-4">
                            Нет данных по тестам
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    function generatePDF() {
        window.open('event_user/download_report.php', '_blank');
    }
</script>