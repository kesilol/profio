<?php
session_start();
require('connect.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'куратор') {
    die('Доступ запрещен');
}

if (!isset($_GET['id'])) {
    die('ID обучающегося не указан');
}

$student_id = intval($_GET['id']);
$curator_id = $_SESSION['user']['id_user'];

// Проверяем, что студент действительно привязан к этому куратору
$check_query = $link->prepare("SELECT cs.student_id FROM curator_students cs WHERE cs.curator_id = ? AND cs.student_id = ?");
$check_query->bind_param("ii", $curator_id, $student_id);
$check_query->execute();
$check_result = $check_query->get_result();

if ($check_result->num_rows === 0) {
    die('Обучающийся не найден в вашем списке');
}

// Получаем детальную информацию о студенте
$student_query = $link->prepare("
    SELECT u.name, u.email, u.education_level, u.created_at 
    FROM users u 
    WHERE u.id = ?
");
$student_query->bind_param("i", $student_id);
$student_query->execute();
$student = $student_query->get_result()->fetch_assoc();

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
    SELECT r.*, p.title as profession_title, p.category, p.salary_range, pd.image_url
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

?>
<div class="student-details">
    <!-- Основная информация -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-gray-50 dark:bg-gray-dark-50 p-4 rounded-lg">
            <h4 class="font-semibold mb-2">Основная информация</h4>
            <div class="space-y-2 text-sm">
                <div><strong>Имя:</strong> <?= $student['name'] ?></div>
                <div><strong>Email:</strong> <?= $student['email'] ?></div>
                <div><strong>Уровень образования:</strong> <?= ucfirst($student['education_level']) ?></div>
                <div><strong>Зарегистрирован:</strong> <?= date('d.m.Y', strtotime($student['created_at'])) ?></div>
            </div>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-dark-50 p-4 rounded-lg">
            <h4 class="font-semibold mb-2">Статистика</h4>
            <div class="space-y-2 text-sm">
                <div><strong>Пройдено тестов:</strong> <?= $tests->num_rows ?></div>
                <div><strong>Получено рекомендаций:</strong> <?= $recommendations->num_rows ?></div>
                <div><strong>Создано планов:</strong> <?= $plans->num_rows ?></div>
            </div>
        </div>
    </div>

    <!-- Результаты тестов -->
    <div class="mb-6">
        <h4 class="font-semibold mb-3">Результаты тестирования</h4>
        <?php if ($tests->num_rows > 0): ?>
            <div class="space-y-3">
                <?php while ($test = $tests->fetch_assoc()): ?>
                    <div class="border border-neutral dark:border-dark-neutral-border rounded-lg p-3">
                        <div class="flex justify-between items-center">
                            <div>
                                <h5 class="font-semibold"><?= $test['title'] ?></h5>
                                <p class="text-sm text-gray-500"><?= $test['description'] ?></p>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-color-brands"><?= $test['total_score'] ?> баллов</div>
                                <div class="text-sm text-gray-500"><?= $test['result_type'] ?></div>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500 mt-2">
                            Пройден: <?= date('d.m.Y H:i', strtotime($test['completed_at'])) ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">Тесты еще не пройдены</p>
        <?php endif; ?>
    </div>

    <!-- Рекомендации -->
    <div class="mb-6">
        <h4 class="font-semibold mb-3">Рекомендованные профессии</h4>
        <?php if ($recommendations->num_rows > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <?php while ($rec = $recommendations->fetch_assoc()): ?>
                    <div class="border border-neutral dark:border-dark-neutral-border rounded-lg p-3">
                        <div class="flex items-center gap-3 mb-2">
                            <?php if (!empty($rec['image_url'])): ?>
                                <img src="<?= $rec['image_url'] ?>" alt="<?= $rec['profession_title'] ?>" class="w-10 h-10 rounded-lg object-cover">
                            <?php endif; ?>
                            <div>
                                <h5 class="font-semibold"><?= $rec['profession_title'] ?></h5>
                                <span class="text-xs px-2 py-1 rounded-full bg-gray-100"><?= $rec['category'] ?></span>
                            </div>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span>Совпадение:</span>
                            <span class="font-semibold text-color-brands"><?= $rec['match_percentage'] ?>%</span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">Рекомендации пока не сформированы</p>
        <?php endif; ?>
    </div>

    <!-- Планы развития -->
    <div>
        <h4 class="font-semibold mb-3">Планы развития</h4>
        <?php if ($plans->num_rows > 0): ?>
            <div class="space-y-4">
                <?php while ($plan = $plans->fetch_assoc()): ?>
                    <div class="border border-neutral dark:border-dark-neutral-border rounded-lg p-4">
                        <div class="flex justify-between items-start mb-2">
                            <h5 class="font-semibold"><?= $plan['title'] ?></h5>
                            <span class="text-xs px-2 py-1 rounded-full <?= $plan['is_completed'] ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' ?>">
                                <?= $plan['is_completed'] ? 'Завершен' : 'Активен' ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mb-3"><?= $plan['description'] ?></p>
                        <div class="flex justify-between text-sm">
                            <span>Прогресс:</span>
                            <span><?= $plan['completed_tasks'] ?> / <?= $plan['total_tasks'] ?> задач</span>
                        </div>
                        <?php if ($plan['total_tasks'] > 0): ?>
                            <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                                <?php $progress = ($plan['completed_tasks'] / $plan['total_tasks']) * 100; ?>
                                <div class="bg-color-brands h-2 rounded-full" style="width: <?= $progress ?>%"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">Планы развития еще не созданы</p>
        <?php endif; ?>
    </div>
</div>
<?php
$link->close();
?>