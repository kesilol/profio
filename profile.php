<?php
session_start();
require('connect.php');

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header("Location: index.php?page=sign-in");
    exit();
}

$user_id = $_SESSION['user']['id'] ?? $_SESSION['user']['id_user'] ?? null;

// Получаем данные пользователя
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $link->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Получаем статистику пользователя
$tests_count = $link->query("SELECT COUNT(*) as count FROM test_results WHERE user_id = '$user_id'")->fetch_assoc()['count'];
$recommendations_count = $link->query("SELECT COUNT(*) as count FROM recommendations WHERE user_id = '$user_id'")->fetch_assoc()['count'];
$plans_count = $link->query("SELECT COUNT(*) as count FROM development_plans WHERE user_id = '$user_id'")->fetch_assoc()['count'];

// Получаем последние активности
$recent_activity = $link->query("
    (SELECT 'test' as type, t.title, tr.completed_at as date
     FROM test_results tr
     JOIN tests t ON tr.test_id = t.id
     WHERE tr.user_id = '$user_id'
     ORDER BY tr.completed_at DESC
     LIMIT 3)
    UNION
    (SELECT 'plan' as type, dp.title, dp.created_at as date
     FROM development_plans dp
     WHERE dp.user_id = '$user_id'
     ORDER BY dp.created_at DESC
     LIMIT 2)
    ORDER BY date DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html class="scroll-smooth overflow-x-hidden" lang="ru">
<head>
    <meta charset="utf-8">
    <title>Мой профиль - Profio</title>
    <meta name="description" content="Профиль пользователя системы Profio">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <link rel="icon" href="assets/images/icons/icon-favicon.svg" type="image/x-icon" sizes="16x16">
    <link rel="stylesheet" href="assets/styles/tailwind.min.css?v=5.0">
    <link rel="stylesheet" href="assets/styles/style.min.css?v=5.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Chivo:wght@400;700;900&family=Noto+Sans:wght@400;500;600;700;800&display=swap">
</head>
<body class="w-screen relative overflow-x-hidden min-h-screen bg-gray-100 scrollbar-hide dark:bg-[#000]">
    <?php require('page/header.php'); ?>
    
    <main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
        <div>
            <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100 mb-[13px]">
                Мой профиль
            </h2>
            
            <div class="flex items-center text-xs text-gray-500 gap-x-[11px] mb-[37px]">
                <div class="flex items-center gap-x-1">
                    <img src="assets/images/icons/icon-home-2.svg" alt="Главная">
                    <a class="capitalize" href="index.php">Главная</a>
                </div>
                <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
                <span class="capitalize text-color-brands">Мой профиль</span>
            </div>

            <!-- Основная информация -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Левая колонка - Информация профиля -->
                <div class="lg:col-span-2">
                    <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                        <h3 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                            Основная информация
                        </h3>
                        
                        <div class="space-y-4">
                            <div class="flex items-center gap-4">
                                <div class="w-20 h-20 rounded-full bg-color-brands grid place-items-center">
                                    <span class="text-white text-xl font-bold">
                                        <?php echo strtoupper(substr($user_data['name'], 0, 1)); ?>
                                    </span>
                                </div>
                                <div>
                                    <h4 class="text-xl font-bold text-gray-1100 dark:text-gray-dark-1100">
                                        <?php echo htmlspecialchars($user_data['name']); ?>
                                    </h4>
                                    <p class="text-gray-500 dark:text-gray-dark-500">
                                        <?php echo ucfirst($user_data['role']); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Email</label>
                                    <p class="text-gray-1100 dark:text-gray-dark-1100"><?php echo htmlspecialchars($user_data['email']); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Уровень образования</label>
                                    <p class="text-gray-1100 dark:text-gray-dark-1100"><?php echo ucfirst($user_data['education_level']); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Дата регистрации</label>
                                    <p class="text-gray-1100 dark:text-gray-dark-1100"><?php echo date('d.m.Y', strtotime($user_data['created_at'])); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Статус</label>
                                    <span class="px-2 py-1 rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 text-xs">
                                        Активен
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Правая колонка - Статистика -->
                <div>
                    <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                        <h3 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                            Статистика
                        </h3>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500 dark:text-gray-dark-500">Пройдено тестов:</span>
                                <span class="font-semibold"><?php echo $tests_count; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500 dark:text-gray-dark-500">Рекомендации:</span>
                                <span class="font-semibold"><?php echo $recommendations_count; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500 dark:text-gray-dark-500">Планы развития:</span>
                                <span class="font-semibold"><?php echo $plans_count; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Последняя активность -->
            <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                <h3 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                    Последняя активность
                </h3>
                
                <div class="space-y-3">
                    <?php if ($recent_activity->num_rows > 0): ?>
                        <?php while($activity = $recent_activity->fetch_assoc()): ?>
                            <div class="flex items-center gap-3 p-3 rounded-lg border border-neutral dark:border-dark-neutral-border">
                                <div class="w-8 h-8 rounded-lg grid place-items-center 
                                    <?php echo $activity['type'] == 'test' ? 'bg-green/20' : 'bg-violet/20'; ?>">
                                    <i class="bi <?php echo $activity['type'] == 'test' ? 'bi-pencil-square' : 'bi-kanban'; ?> 
                                        <?php echo $activity['type'] == 'test' ? 'text-green-600' : 'text-violet-600'; ?>"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-1100 dark:text-gray-dark-1100">
                                        <?php echo $activity['type'] == 'test' ? 'Пройден тест' : 'Создан план'; ?>: 
                                        <strong><?php echo htmlspecialchars($activity['title']); ?></strong>
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-dark-500">
                                        <?php echo date('d.m.Y H:i', strtotime($activity['date'])); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-gray-500 dark:text-gray-dark-500 text-center py-4">
                            Активность отсутствует
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <?php require('page/footer.php'); ?>
</body>
</html>