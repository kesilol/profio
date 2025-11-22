<?php
// Проверка авторизации и роли куратора
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'куратор') {
    header("Location: index.php?page=sign-in");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?page=reports");
    exit();
}

$student_id = intval($_GET['id']);
$curator_id = $_SESSION['user']['id_user'];

// Проверяем, что студент действительно привязан к этому куратору
$check_query = $link->prepare("SELECT cs.student_id FROM curator_students cs WHERE cs.curator_id = ? AND cs.student_id = ?");
$check_query->bind_param("ii", $curator_id, $student_id);
$check_query->execute();
$check_result = $check_query->get_result();

if ($check_result->num_rows === 0) {
    header("Location: index.php?page=reports");
    exit();
}

// Получаем основную информацию о студенте
$student_query = $link->prepare("
    SELECT u.id, u.name, u.email, u.education_level, u.created_at 
    FROM users u 
    WHERE u.id = ?
");
$student_query->bind_param("i", $student_id);
$student_query->execute();
$student = $student_query->get_result()->fetch_assoc();

if (!$student) {
    header("Location: index.php?page=reports");
    exit();
}

// Получаем результаты тестов
$tests_query = $link->prepare("
    SELECT tr.*, t.title, t.description 
    FROM test_results tr 
    JOIN tests t ON tr.test_id = t.id 
    WHERE tr.user_id = ? 
    ORDER BY tr.completed_at DESC
");
$tests_query->bind_param("i", $student_id);
$tests_query->execute();
$tests = $tests_query->get_result();

// Получаем рекомендации
$rec_query = $link->prepare("
    SELECT r.*, p.title as profession_title, p.category, p.salary_range, p.demand_level, p.description as profession_description,
           pd.image_url
    FROM recommendations r 
    JOIN professions p ON r.profession_id = p.id 
    LEFT JOIN profession_details pd ON p.id = pd.profession_id 
    WHERE r.user_id = ? 
    ORDER BY r.match_percentage DESC
");
$rec_query->bind_param("i", $student_id);
$rec_query->execute();
$recommendations = $rec_query->get_result();

// Получаем планы развития
$plans_query = $link->prepare("
    SELECT dp.*, 
           (SELECT COUNT(*) FROM plan_tasks WHERE plan_id = dp.id) as total_tasks,
           (SELECT COUNT(*) FROM plan_tasks WHERE plan_id = dp.id AND is_completed = 1) as completed_tasks
    FROM development_plans dp 
    WHERE dp.user_id = ? 
    ORDER BY dp.created_at DESC
");
$plans_query->bind_param("i", $student_id);
$plans_query->execute();
$plans = $plans_query->get_result();

// Получаем статистику
$stats_query = $link->prepare("
    SELECT 
        COUNT(DISTINCT tr.id) as tests_count,
        COUNT(DISTINCT r.id) as recommendations_count,
        COUNT(DISTINCT dp.id) as plans_count,
        MAX(tr.completed_at) as last_activity
    FROM users u
    LEFT JOIN test_results tr ON u.id = tr.user_id
    LEFT JOIN recommendations r ON u.id = r.user_id
    LEFT JOIN development_plans dp ON u.id = dp.user_id
    WHERE u.id = ?
");
$stats_query->bind_param("i", $student_id);
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    <div>
        <!-- Хлебные крошки -->
        <div class="flex items-center text-xs text-gray-500 gap-x-[11px] mb-[37px]">
            <div class="flex items-center gap-x-1">
                <img src="assets/images/icons/icon-home-2.svg" alt="Главная">
                <a class="capitalize" href="index.php">Главная</a>
            </div>
            <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
            <a class="capitalize" href="index.php?page=reports">Отчеты</a>
            <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
            <span class="capitalize text-color-brands"><?= $student['name'] ?></span>
        </div>

        <!-- Заголовок и кнопка назад -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100">
                    Профиль студента: <?= $student['name'] ?>
                </h2>
                <p class="text-gray-500"><?= $student['email'] ?></p>
            </div>
            <a href="index.php?page=reports" class="btn bg-gray-500 text-white px-4 py-2 flex items-center gap-2 hover:bg-gray-600 transition">
                <i class="bi bi-arrow-left"></i>
                Назад к списку
            </a>
        </div>

        <!-- Основная информация -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Карточка студента -->
            <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                    Основная информация
                </h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Имя:</span>
                        <span class="font-semibold"><?= $student['name'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Email:</span>
                        <span class="font-semibold"><?= $student['email'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Уровень образования:</span>
                        <span class="font-semibold"><?= ucfirst($student['education_level']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Зарегистрирован:</span>
                        <span class="font-semibold"><?= date('d.m.Y', strtotime($student['created_at'])) ?></span>
                    </div>
                </div>
            </div>

            <!-- Статистика -->
            <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                    Статистика
                </h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-3 rounded-lg bg-blue/10">
                        <div class="text-xl font-bold text-color-brands"><?= $stats['tests_count'] ?? 0 ?></div>
                        <div class="text-xs text-gray-500">Тестов</div>
                    </div>
                    <div class="text-center p-3 rounded-lg bg-green/10">
                        <div class="text-xl font-bold text-green"><?= $stats['recommendations_count'] ?? 0 ?></div>
                        <div class="text-xs text-gray-500">Рекомендаций</div>
                    </div>
                    <div class="text-center p-3 rounded-lg bg-purple/10">
                        <div class="text-xl font-bold text-purple"><?= $stats['plans_count'] ?? 0 ?></div>
                        <div class="text-xs text-gray-500">Планов</div>
                    </div>
                    <div class="text-center p-3 rounded-lg bg-orange/10">
                        <div class="text-xl font-bold text-orange">
                            <?= $stats['last_activity'] ? date('d.m.Y', strtotime($stats['last_activity'])) : '---' ?>
                        </div>
                        <div class="text-xs text-gray-500">Последняя активность</div>
                    </div>
                </div>
            </div>

            <!-- Быстрые действия -->
            <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                    Действия
                </h3>
                <div class="space-y-2">
                    <a href="index.php?page=reports"
                        class="w-full bg-color-brands text-white py-2 px-3 rounded-lg text-sm font-semibold hover:bg-opacity-90 transition flex items-center justify-center gap-2">
                        <i class="bi bi-arrow-left"></i>
                        Вернуться к списку
                    </a>
                </div>
            </div>
        </div>

        <!-- Результаты тестов -->
        <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 mb-6">
            <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                Результаты тестирования
            </h3>

            <?php if ($tests->num_rows > 0): ?>
                <div class="space-y-4">
                    <?php while ($test = $tests->fetch_assoc()): ?>
                        <div class="flex items-center justify-between p-4 border border-neutral dark:border-dark-neutral-border rounded-lg">
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-1100 dark:text-gray-dark-1100">
                                    <?= $test['title'] ?>
                                </h4>
                                <p class="text-sm text-gray-500 dark:text-gray-dark-500 mt-1">
                                    <?= $test['description'] ?>
                                </p>
                                <div class="flex items-center gap-4 mt-2 text-sm">
                                    <span class="text-gray-500">Баллы: <?= $test['total_score'] ?></span>
                                    <span class="text-gray-500">Тип: <?= $test['result_type'] ?></span>
                                    <span class="text-gray-500">
                                        <?= date('d.m.Y H:i', strtotime($test['completed_at'])) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-color-brands">
                                    <?= $test['total_score'] ?> баллов
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
        <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 mb-6">
            <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                Рекомендованные профессии
            </h3>

            <?php if ($recommendations->num_rows > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php while ($rec = $recommendations->fetch_assoc()): ?>
                        <div class="border border-neutral dark:border-dark-neutral-border rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-3 mb-3">
                                <?php if (!empty($rec['image_url'])): ?>
                                    <img src="<?= $rec['image_url'] ?>" alt="<?= $rec['profession_title'] ?>" class="w-12 h-12 rounded-lg object-cover">
                                <?php else: ?>
                                    <div class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-dark-100 grid place-items-center">
                                        <i class="bi bi-briefcase text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h4 class="font-semibold text-gray-1100 dark:text-gray-dark-1100">
                                        <?= $rec['profession_title'] ?>
                                    </h4>
                                    <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-dark-100 dark:text-gray-dark-600">
                                        <?= $rec['category'] ?>
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-2 text-sm mb-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Совпадение:</span>
                                    <span class="font-semibold text-color-brands"><?= $rec['match_percentage'] ?>%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Зарплата:</span>
                                    <span class="font-semibold"><?= $rec['salary_range'] ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Спрос:</span>
                                    <span class="font-semibold <?= $rec['demand_level'] === 'высокий' ? 'text-green-600' : ($rec['demand_level'] === 'средний' ? 'text-yellow-600' : 'text-red-600'); ?>">
                                        <?= ucfirst($rec['demand_level']) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-dark-200">
                                <a href="index.php?page=profession-detail&id=<?= $rec['profession_id'] ?>"
                                    class="text-xs text-color-brands font-semibold hover:underline flex items-center gap-1">
                                    Подробнее о профессии
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 dark:text-gray-dark-500 text-center py-4">
                    Рекомендации пока не сформированы
                </p>
            <?php endif; ?>
        </div>

        <!-- Планы развития -->
        <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
            <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                Планы развития
            </h3>

            <?php if ($plans->num_rows > 0): ?>
                <div class="space-y-4">
                    <?php while ($plan = $plans->fetch_assoc()): ?>
                        <div class="border border-neutral dark:border-dark-neutral-border rounded-lg p-4">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-1">
                                        <?= $plan['title'] ?>
                                    </h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-dark-500">
                                        <?= $plan['description'] ?>
                                    </p>
                                </div>
                                <span class="text-xs px-2 py-1 rounded-full <?= $plan['is_completed'] ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' ?>">
                                    <?= $plan['is_completed'] ? 'Завершен' : 'Активен' ?>
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-sm mb-2">
                                <span>Прогресс:</span>
                                <span><?= $plan['completed_tasks'] ?> / <?= $plan['total_tasks'] ?> задач</span>
                            </div>

                            <?php if ($plan['total_tasks'] > 0): ?>
                                <div class="w-full bg-gray-200 rounded-full h-2 mb-3">
                                    <?php $progress = ($plan['completed_tasks'] / $plan['total_tasks']) * 100; ?>
                                    <div class="bg-color-brands h-2 rounded-full" style="width: <?= $progress ?>%"></div>
                                </div>
                            <?php endif; ?>

                            <?php if ($plan['deadline']): ?>
                                <div class="text-xs text-gray-500">
                                    Срок: до <?= date('d.m.Y', strtotime($plan['deadline'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 dark:text-gray-dark-500 text-center py-4">
                    Планы развития еще не созданы
                </p>
            <?php endif; ?>
        </div>
    </div>
</main>