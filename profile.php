<?php
session_start();
require('connect.php');

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header("Location: index.php?page=sign-in");
    exit();
}

$user_id = $_SESSION['user']['id'] ?? $_SESSION['user']['id_user'] ?? null;
$user_role = $_SESSION['user']['role'] ?? 'обучающийся';
$user_name = $_SESSION['user']['login'] ?? $_SESSION['user']['name'] ?? '';
$user_email = $_SESSION['user']['email'] ?? '';

// Обработка AJAX запроса для отправки сообщения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_feedback'])) {
    header('Content-Type: application/json');
    
    $name = trim($_POST['name'] ?? $user_name);
    $email = trim($_POST['email'] ?? $user_email);
    $message = trim($_POST['message'] ?? '');
    
    $response = ['success' => false, 'message' => ''];
    
    if (empty($name) || empty($email) || empty($message)) {
        $response['message'] = 'Пожалуйста, заполните все поля';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Введите корректный email';
    } elseif (strlen($message) < 10) {
        $response['message'] = 'Сообщение должно содержать не менее 10 символов';
    } else {
        $insert_query = $link->prepare("
            INSERT INTO feedback (name, email, message, user_id, status, created_at) 
            VALUES (?, ?, ?, ?, 'new', NOW())
        ");
        $insert_query->bind_param("sssi", $name, $email, $message, $user_id);
        
        if ($insert_query->execute()) {
            $response['success'] = true;
            $response['message'] = 'Сообщение отправлено! Мы ответим вам в ближайшее время.';
        } else {
            $response['message'] = 'Ошибка при отправке сообщения. Попробуйте позже.';
        }
        $insert_query->close();
    }
    
    echo json_encode($response);
    exit();
}

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

// Получаем обращения пользователя
$user_feedback = $link->query("
    SELECT * FROM feedback 
    WHERE user_id = '$user_id' OR email = '{$user_data['email']}'
    ORDER BY created_at DESC
    LIMIT 5
");

// Для куратора - получаем его студентов
$curator_students_count = 0;
if ($user_role === 'куратор') {
    $students_count_query = $link->query("SELECT COUNT(*) as count FROM curator_students WHERE curator_id = '$user_id'");
    $curator_students_count = $students_count_query->fetch_assoc()['count'];
}

// Для администратора - получаем системную статистику
$system_stats = [];
if ($user_role === 'администратор') {
    $system_stats['total_users'] = $link->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
    $system_stats['total_tests'] = $link->query("SELECT COUNT(*) as count FROM test_results")->fetch_assoc()['count'];
    $system_stats['total_recommendations'] = $link->query("SELECT COUNT(*) as count FROM recommendations")->fetch_assoc()['count'];
}
?>
<!DOCTYPE html>
<html class="scroll-smooth overflow-x-hidden" lang="ru">
<head>
    <meta charset="utf-8">
    <title>Мой профиль - Profio</title>
    <meta name="description" content="Профиль пользователя системы Profio">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <link rel="icon" href="assets/images/icons/favicon.svg" type="image/svg+xml" sizes="16x16">
    <link rel="stylesheet" href="assets/styles/tailwind.min.css?v=5.0">
    <link rel="stylesheet" href="assets/styles/style.min.css?v=5.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Chivo:wght@400;700;900&family=Noto+Sans:wght@400;500;600;700;800&display=swap">
    
    <style>
        /* Стили для модального окна */
        .feedback-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .feedback-modal.active {
            display: flex;
        }
        
        .feedback-modal-content {
            background: white;
            border-radius: 1rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalFadeIn 0.3s ease;
        }
        
        .dark .feedback-modal-content {
            background: #1a1a1a;
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .toast-notification.hidden {
            display: none;
        }
    </style>
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
                                        <?php 
                                        $role_names = [
                                            'обучающийся' => 'Обучающийся',
                                            'куратор' => 'Куратор',
                                            'администратор' => 'Администратор'
                                        ];
                                        echo $role_names[$user_data['role']] ?? ucfirst($user_data['role']); 
                                        ?>
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
                                    <p class="text-gray-1100 dark:text-gray-dark-1100">
                                        <?php 
                                        $edu_map = [
                                            'нет образования' => 'Нет образования',
                                            'среднее' => 'Среднее',
                                            'среднее-общее' => 'Среднее общее',
                                            'среднее-специальное' => 'Среднее специальное',
                                            'бакалавриат' => 'Бакалавриат',
                                            'магистратура' => 'Магистратура',
                                            'аспирантура' => 'Аспирантура'
                                        ];
                                        echo $edu_map[$user_data['education_level']] ?? ucfirst($user_data['education_level']); 
                                        ?>
                                    </p>
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
                            <?php 
                            if ($user_role === 'обучающийся') echo 'Моя статистика';
                            elseif ($user_role === 'куратор') echo 'Статистика кураторства';
                            else echo 'Статистика системы';
                            ?>
                        </h3>
                        
                        <div class="space-y-4">
                            <?php if ($user_role === 'обучающийся'): ?>
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
                            <?php elseif ($user_role === 'куратор'): ?>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-dark-500">Мои обучающиеся:</span>
                                    <span class="font-semibold"><?php echo $curator_students_count; ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-dark-500">Всего тестов у обучающихся:</span>
                                    <span class="font-semibold"><?php echo $tests_count; ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-dark-500">Рекомендаций у обучающихся:</span>
                                    <span class="font-semibold"><?php echo $recommendations_count; ?></span>
                                </div>
                            <?php else: ?>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-dark-500">Всего пользователей:</span>
                                    <span class="font-semibold"><?php echo $system_stats['total_users']; ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-dark-500">Всего тестов пройдено:</span>
                                    <span class="font-semibold"><?php echo $system_stats['total_tests']; ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-dark-500">Всего рекомендаций:</span>
                                    <span class="font-semibold"><?php echo $system_stats['total_recommendations']; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Кнопка "Написать в поддержку" -->
                        <div class="mt-6 pt-4 border-t border-neutral dark:border-dark-neutral-border">
                            <button type="button" onclick="openFeedbackModal()" 
                                    class="w-full bg-color-brands text-white py-2 rounded-lg font-semibold hover:bg-opacity-90 transition flex items-center justify-center gap-2">
                                <i class="bi bi-chat-dots"></i>
                                Написать в поддержку
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Последняя активность (только для студентов) -->
            <?php if ($user_role === 'обучающийся'): ?>
            <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 mb-6">
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
            <?php endif; ?>
            
            <!-- МОИ ОБРАЩЕНИЯ -->
            <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100">
                        <i class="bi bi-chat-dots mr-2 text-color-brands"></i>
                        Мои обращения
                    </h3>
                    <button type="button" onclick="openFeedbackModal()" 
                            class="text-color-brands text-sm font-semibold hover:underline flex items-center gap-1">
                        <i class="bi bi-plus-circle"></i>
                        Новое обращение
                    </button>
                </div>
                
                <?php if ($user_feedback && $user_feedback->num_rows > 0): ?>
                    <div class="space-y-4">
                        <?php while($msg = $user_feedback->fetch_assoc()): ?>
                            <div class="border border-neutral dark:border-dark-neutral-border rounded-lg p-4">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-xs font-medium px-2 py-1 rounded-full 
                                                <?php 
                                                if ($msg['status'] === 'replied') echo 'bg-green-100 text-green-800';
                                                elseif ($msg['status'] === 'read') echo 'bg-gray-100 text-gray-800';
                                                else echo 'bg-yellow-100 text-yellow-800';
                                                ?>">
                                                <?php 
                                                $status_names = [
                                                    'new' => 'Новое',
                                                    'read' => 'Прочитано',
                                                    'replied' => 'Ответ получен',
                                                    'closed' => 'Закрыто'
                                                ];
                                                echo $status_names[$msg['status']] ?? $msg['status'];
                                                ?>
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500">
                                            <i class="bi bi-clock mr-1"></i>
                                            <?php echo date('d.m.Y H:i', strtotime($msg['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Сообщение пользователя -->
                                <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        <span class="font-semibold">Ваше сообщение:</span><br>
                                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                    </p>
                                </div>
                                
                                <!-- Ответ администратора -->
                                <?php if ($msg['admin_response']): ?>
                                    <div class="mt-3 p-3 bg-green-50 dark:bg-green-950/20 rounded-lg border-l-4 border-green-500">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="bi bi-reply-all-fill text-green-600"></i>
                                            <span class="font-semibold text-green-700 dark:text-green-400">Ответ администратора:</span>
                                        </div>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">
                                            <?php echo nl2br(htmlspecialchars($msg['admin_response'])); ?>
                                        </p>
                                        <?php if ($msg['responded_at']): ?>
                                            <p class="text-xs text-gray-400 mt-2">
                                                <?php echo date('d.m.Y H:i', strtotime($msg['responded_at'])); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-center">
                                        <p class="text-sm text-gray-500">
                                            <i class="bi bi-clock-history mr-1"></i>
                                            Ожидает ответа администратора
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                        
                        <?php 
                        // Проверяем, есть ли еще обращения (не показаны из-за LIMIT 5)
                        $total_count = $link->query("SELECT COUNT(*) as count FROM feedback WHERE user_id = '$user_id' OR email = '{$user_data['email']}'")->fetch_assoc()['count'];
                        if ($total_count > 5): 
                        ?>
                            <div class="text-center pt-2">
                                <a href="index.php?page=my-feedback" class="text-color-brands text-sm hover:underline">
                                    Показать все обращения (<?php echo $total_count; ?>)
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="bi bi-chat-dots text-4xl text-gray-400 mb-3"></i>
                        <p class="text-gray-500 dark:text-gray-dark-500">У вас пока нет обращений</p>
                        <button type="button" onclick="openFeedbackModal()" 
                                class="inline-block mt-3 text-color-brands font-semibold hover:underline">
                            Написать в поддержку
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Для куратора - ссылка на управление студентами -->
            <?php if ($user_role === 'куратор'): ?>
            <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 mt-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-2">
                            Управление обучающимися
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-dark-500">
                            Добавляйте и просматривайте своих обучающихся
                        </p>
                    </div>
                    <a href="index.php?page=manage-students" 
                       class="bg-color-brands text-white px-6 py-2 rounded-lg font-semibold hover:bg-opacity-90 transition flex items-center gap-2">
                        <i class="bi bi-people"></i>
                        Перейти к управлению
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Модальное окно для обратной связи -->
    <div id="feedbackModal" class="feedback-modal">
        <div class="feedback-modal-content">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold text-gray-1100 dark:text-gray-dark-1100">
                        <i class="bi bi-chat-dots text-color-brands mr-2"></i>
                        Написать в поддержку
                    </h3>
                    <button type="button" onclick="closeFeedbackModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="bi bi-x-lg text-xl"></i>
                    </button>
                </div>
                
                <form id="feedbackForm" class="space-y-4">
                    <div>
                        <label for="feedback_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Ваше имя <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="feedback_name" name="name" required
                               value="<?php echo htmlspecialchars($user_name); ?>"
                               class="w-full px-4 py-2 border border-neutral dark:border-dark-neutral-border rounded-lg bg-white dark:bg-dark-neutral-bg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-color-brands">
                    </div>
                    <div>
                        <label for="feedback_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" id="feedback_email" name="email" required
                               value="<?php echo htmlspecialchars($user_email); ?>"
                               class="w-full px-4 py-2 border border-neutral dark:border-dark-neutral-border rounded-lg bg-white dark:bg-dark-neutral-bg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-color-brands">
                    </div>
                    <div>
                        <label for="feedback_message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Сообщение <span class="text-red-500">*</span>
                        </label>
                        <textarea id="feedback_message" name="message" required rows="5"
                                  class="w-full px-4 py-2 border border-neutral dark:border-dark-neutral-border rounded-lg bg-white dark:bg-dark-neutral-bg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-color-brands"
                                  placeholder="Опишите ваш вопрос или предложение..."></textarea>
                        <p class="text-xs text-gray-400 mt-1">Минимум 10 символов</p>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="closeFeedbackModal()" 
                                class="px-4 py-2 border border-neutral rounded-lg text-gray-600 hover:bg-gray-100 transition">
                            Отмена
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-color-brands text-white rounded-lg font-semibold hover:bg-opacity-90 transition flex items-center gap-2">
                            <i class="bi bi-send"></i>
                            Отправить
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Toast уведомление -->
    <div id="toastNotification" class="toast-notification hidden">
        <div class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-2">
            <i class="bi bi-check-circle-fill"></i>
            <span id="toastMessage"></span>
        </div>
    </div>
    
    <?php require('page/footer.php'); ?>
    
    <script>
        // Ждем полной загрузки DOM
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM загружен');
            
            // Проверяем, что элементы существуют
            const modal = document.getElementById('feedbackModal');
            const toast = document.getElementById('toastNotification');
            const form = document.getElementById('feedbackForm');
            
            if (!modal) console.error('Модальное окно не найдено');
            if (!toast) console.error('Toast не найден');
            if (!form) console.error('Форма не найдена');
        });
        
        // Глобальные функции
        window.openFeedbackModal = function() {
            const modal = document.getElementById('feedbackModal');
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            } else {
                console.error('Модальное окно не найдено при открытии');
            }
        };
        
        window.closeFeedbackModal = function() {
            const modal = document.getElementById('feedbackModal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
                // Очищаем форму
                const form = document.getElementById('feedbackForm');
                if (form) form.reset();
                // Восстанавливаем значения из сессии
                const nameInput = document.getElementById('feedback_name');
                const emailInput = document.getElementById('feedback_email');
                if (nameInput) nameInput.value = '<?php echo htmlspecialchars($user_name); ?>';
                if (emailInput) emailInput.value = '<?php echo htmlspecialchars($user_email); ?>';
            }
        };
        
        window.showToast = function(message, isError = false) {
            const toast = document.getElementById('toastNotification');
            const toastMessage = document.getElementById('toastMessage');
            
            if (!toast || !toastMessage) {
                console.error('Toast элементы не найдены');
                alert(message);
                return;
            }
            
            toastMessage.textContent = message;
            
            const toastDiv = toast.querySelector('div');
            if (isError) {
                toastDiv.classList.remove('bg-green-500');
                toastDiv.classList.add('bg-red-500');
                const icon = toastDiv.querySelector('i');
                if (icon) {
                    icon.classList.remove('bi-check-circle-fill');
                    icon.classList.add('bi-exclamation-triangle-fill');
                }
            } else {
                toastDiv.classList.remove('bg-red-500');
                toastDiv.classList.add('bg-green-500');
                const icon = toastDiv.querySelector('i');
                if (icon) {
                    icon.classList.remove('bi-exclamation-triangle-fill');
                    icon.classList.add('bi-check-circle-fill');
                }
            }
            
            toast.classList.remove('hidden');
            
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 3000);
        };
        
        // Обработка отправки формы через AJAX
        const form = document.getElementById('feedbackForm');
        if (form) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('ajax_feedback', '1');
                
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showToast(result.message);
                        closeFeedbackModal();
                        // Перезагружаем страницу через 1.5 секунды, чтобы показать новое обращение
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showToast(result.message, true);
                    }
                } catch (error) {
                    console.error('Ошибка:', error);
                    showToast('Ошибка при отправке сообщения', true);
                }
            });
        }
        
        // Закрытие модального окна по клику вне его
        const modal = document.getElementById('feedbackModal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeFeedbackModal();
                }
            });
        }
        
        // Закрытие по Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('feedbackModal');
                if (modal && modal.classList.contains('active')) {
                    closeFeedbackModal();
                }
            }
        });
    </script>
</body>
</html>